<?php

namespace App\Libraries;

use App\Models\VoucherModel;

/**
 * JsonPdfRunner — render one chunk job from the JSON-file queue.
 *
 * Mirrors PdfJobRunner (the DB-backed version) but uses JsonPdfQueue
 * for state. Workflow:
 *
 *   claimNextChunk()  ─ atomically move a pending chunk from queue.json
 *                       into processing.json, return its record.
 *   processClaimed()  ─ render PDF, move record from processing.json into
 *                       finished.json, then call tryFinalizeParent().
 *   tryFinalizeParent() ─ if all chunks of a parent are done, assemble
 *                       PDF / ZIP and move parent from queue.json into
 *                       finished.json.
 */
class JsonPdfRunner
{
    /**
     * Atomically claim the next pending chunk. Returns the claimed record
     * or null if nothing claimable. Parents are NOT claimable here — they
     * go through tryFinalizeParent().
     */
    public static function claimNextChunk(): ?array
    {
        return JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) {
            $jobs = $queue['jobs'] ?? [];

            // Chunks first (parent_job_id != null), sorted by job_id.
            $candidateIdx = null;
            foreach ($jobs as $i => $job) {
                if (!empty($job['parent_job_id']) && ($job['status'] ?? 'pending') === 'pending') {
                    $candidateIdx = $i;
                    break;
                }
            }
            if ($candidateIdx === null) {
                return null; // No claimable chunk
            }

            $claimed = $jobs[$candidateIdx];
            $claimed['status'] = 'processing';

            // Remove from queue.jobs
            array_splice($jobs, $candidateIdx, 1);
            $queue['jobs'] = array_values($jobs);

            // Append to processing.jobs
            $processing['jobs']   = $processing['jobs'] ?? [];
            $processing['jobs'][] = $claimed;

            return [[$queue, $processing, $finished], $claimed];
        });
    }

    /**
     * Render the given chunk record (already in processing.json), then move
     * it into finished.json. Triggers parent finalize attempt.
     */
    public static function processClaimed(array $job): bool
    {
        $jobId    = (int) $job['job_id'];
        $parentId = (int) ($job['parent_job_id'] ?? 0);

        try {
            $ids      = $job['voucher_ids'] ?? [];
            $students = (new VoucherModel())->getVouchersByIds($ids);

            if (empty($students)) {
                throw new \RuntimeException('No valid students found for this job.');
            }

            $pdfBytes = VoucherPdf::generate($students);

            $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'json_chunk_' . $jobId . '_' . date('Ymd_His') . '.pdf';
            file_put_contents($dir . $filename, $pdfBytes);

            // Flip the UI badge to "generated" for affected students + bump
            // generate_count so the listing can show running totals without
            // re-scanning pdf_jobs JSON.
            $db = \Config\Database::connect();
            $db->table('students')
                ->whereIn('student_id', $ids)
                ->update([
                    'voucher_status' => 'generated',
                    'generated_at'   => date('Y-m-d H:i:s'),
                ]);
            $db->query(
                'UPDATE students SET generate_count = generate_count + 1 WHERE student_id IN (' . implode(',', array_map('intval', $ids)) . ')'
            );

            JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($jobId, $filename) {
                $idx = self::findIndex($processing['jobs'] ?? [], $jobId);
                if ($idx === null) {
                    return null;
                }
                $rec = $processing['jobs'][$idx];
                $rec['status']       = 'done';
                $rec['file_path']    = $filename;
                $rec['completed_at'] = date('Y-m-d H:i:s');

                array_splice($processing['jobs'], $idx, 1);
                $processing['jobs']  = array_values($processing['jobs']);
                $finished['jobs']    = $finished['jobs'] ?? [];
                $finished['jobs'][]  = $rec;

                return [$queue, $processing, $finished];
            });

            if ($parentId > 0) {
                self::tryFinalizeParent($parentId);
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[JsonPdfRunner] Job {$jobId}: " . $e->getMessage());

            $msg = $e->getMessage();
            JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($jobId, $msg, $parentId) {
                $idx = self::findIndex($processing['jobs'] ?? [], $jobId);
                if ($idx !== null) {
                    $rec = $processing['jobs'][$idx];
                    $rec['status']        = 'failed';
                    $rec['error_message'] = $msg;
                    $rec['completed_at']  = date('Y-m-d H:i:s');

                    array_splice($processing['jobs'], $idx, 1);
                    $processing['jobs'] = array_values($processing['jobs']);
                    $finished['jobs']   = $finished['jobs'] ?? [];
                    $finished['jobs'][] = $rec;
                }

                // Mark parent failed if still pending in queue.
                if ($parentId > 0) {
                    $qIdx = self::findIndex($queue['jobs'] ?? [], $parentId);
                    if ($qIdx !== null) {
                        $parent = $queue['jobs'][$qIdx];
                        $parent['status']        = 'failed';
                        $parent['error_message'] = 'Chunk failed: ' . $msg;
                        $parent['completed_at']  = date('Y-m-d H:i:s');
                        array_splice($queue['jobs'], $qIdx, 1);
                        $queue['jobs']     = array_values($queue['jobs']);
                        $finished['jobs']  = $finished['jobs'] ?? [];
                        $finished['jobs'][] = $parent;
                    }
                }

                return [$queue, $processing, $finished];
            });

            return false;
        }
    }

    /**
     * Convenience: claim + process in one call. Returns true if a chunk was
     * processed successfully, false on failure, null if nothing to claim.
     */
    public static function processOne(): ?bool
    {
        $claimed = self::claimNextChunk();
        if ($claimed === null) {
            return null;
        }
        return self::processClaimed($claimed);
    }

    /**
     * If every chunk of $parentId is done, assemble the final file (PDF or
     * ZIP) and move the parent record from queue.json into finished.json.
     */
    public static function tryFinalizeParent(int $parentId): bool
    {
        // First — read-only check: are all chunks done?
        $finished = JsonPdfQueue::read(JsonPdfQueue::FILE_FINISHED);
        $chunks   = [];
        foreach (($finished['jobs'] ?? []) as $job) {
            if ((int) ($job['parent_job_id'] ?? 0) === $parentId && empty($job['chunk_index']) === false) {
                $chunks[] = $job;
            }
        }

        $queue      = JsonPdfQueue::read(JsonPdfQueue::FILE_QUEUE);
        $processing = JsonPdfQueue::read(JsonPdfQueue::FILE_PROCESSING);

        $parent = null;
        foreach (($queue['jobs'] ?? []) as $job) {
            if ((int) $job['job_id'] === $parentId && empty($job['parent_job_id'])) {
                $parent = $job;
                break;
            }
        }
        if (!$parent) {
            return false; // Parent already finalized or never existed.
        }

        $total = (int) ($parent['total_chunks'] ?? 0);
        if ($total <= 0 || count($chunks) < $total) {
            return false;
        }

        // Any chunk failed → parent fails too.
        $failedChunk = null;
        foreach ($chunks as $c) {
            if (($c['status'] ?? '') !== 'done') {
                $failedChunk = $c;
                break;
            }
        }

        try {
            $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $stamp = date('Ymd_His');

            if ($failedChunk) {
                throw new \RuntimeException('Chunk failed: ' . ($failedChunk['error_message'] ?? 'unknown'));
            }

            // Sort chunks by chunk_index for assembly.
            usort($chunks, static fn($a, $b) => ((int) $a['chunk_index']) <=> ((int) $b['chunk_index']));

            if (count($chunks) === 1) {
                $finalName  = 'vouchers_json_job' . $parentId . '_' . $stamp . '.pdf';
                $sourcePath = $dir . $chunks[0]['file_path'];
                if (!is_file($sourcePath) || !copy($sourcePath, $dir . $finalName)) {
                    throw new \RuntimeException('Failed to copy chunk PDF for parent ' . $parentId);
                }
            } else {
                $finalName = 'vouchers_json_job' . $parentId . '_' . $stamp . '.zip';
                $zipPath   = $dir . $finalName;
                $zip       = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new \RuntimeException('Failed to open ZIP for writing.');
                }
                foreach ($chunks as $chunk) {
                    $sourcePath = $dir . $chunk['file_path'];
                    if (!is_file($sourcePath)) {
                        $zip->close();
                        @unlink($zipPath);
                        throw new \RuntimeException('Missing chunk file: ' . $chunk['file_path']);
                    }
                    $entry = 'vouchers_chunk_' . str_pad((string) $chunk['chunk_index'], 3, '0', STR_PAD_LEFT) . '.pdf';
                    $zip->addFile($sourcePath, $entry);
                }
                $zip->close();
            }

            // Final file is built — chunk PDFs are no longer needed on disk.
            // Wipe them now (also wipe the finished-chunk records to keep the
            // JSON files lean over time).
            $chunkFileNames = [];
            foreach ($chunks as $chunk) {
                if (!empty($chunk['file_path'])) {
                    @unlink($dir . $chunk['file_path']);
                    $chunkFileNames[] = $chunk['file_path'];
                }
            }
            $chunkJobIds = array_map(static fn($c) => (int) $c['job_id'], $chunks);

            JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($parentId, $finalName, $chunkJobIds) {
                $idx = self::findIndex($queue['jobs'] ?? [], $parentId);
                if ($idx === null) {
                    return null;
                }
                $parent = $queue['jobs'][$idx];
                $parent['status']       = 'done';
                $parent['file_path']    = $finalName;
                $parent['completed_at'] = date('Y-m-d H:i:s');

                array_splice($queue['jobs'], $idx, 1);
                $queue['jobs']      = array_values($queue['jobs']);

                // Drop chunk records from finished.json now that their files
                // are gone. The parent record alone tracks the final output.
                $finished['jobs'] = array_values(array_filter(
                    $finished['jobs'] ?? [],
                    static fn($j) => !in_array((int) ($j['job_id'] ?? 0), $chunkJobIds, true)
                ));
                $finished['jobs'][] = $parent;

                return [$queue, $processing, $finished];
            });

            log_action(
                (int) ($parent['created_by'] ?? 0),
                'GENERATE_PDF',
                'Finalized JSON-queue PDF for parent job #' . $parentId . ' (' . count($chunks) . ' chunk(s))'
            );

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[JsonPdfRunner] Finalize parent {$parentId}: " . $e->getMessage());

            $msg = $e->getMessage();
            JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($parentId, $msg) {
                $idx = self::findIndex($queue['jobs'] ?? [], $parentId);
                if ($idx === null) {
                    return null;
                }
                $parent = $queue['jobs'][$idx];
                $parent['status']        = 'failed';
                $parent['error_message'] = $msg;
                $parent['completed_at']  = date('Y-m-d H:i:s');

                array_splice($queue['jobs'], $idx, 1);
                $queue['jobs']      = array_values($queue['jobs']);
                $finished['jobs']   = $finished['jobs'] ?? [];
                $finished['jobs'][] = $parent;

                return [$queue, $processing, $finished];
            });

            return false;
        }
    }

    protected static function findIndex(array $jobs, int $jobId): ?int
    {
        foreach ($jobs as $i => $job) {
            if ((int) $job['job_id'] === $jobId) {
                return $i;
            }
        }
        return null;
    }
}
