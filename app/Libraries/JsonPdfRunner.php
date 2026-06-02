<?php

namespace App\Libraries;

use App\Models\VoucherModel;
use App\Models\GenerationHistoryModel;

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

            // Assign voucher numbers to any chunk students that don't have one
            // yet. Moved out of the web request (where it ran per-student for the
            // whole batch and timed out) into the worker, scoped to this chunk.
            $students = self::assignVoucherNumbers($students);

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
            $generatedAt = date('Y-m-d H:i:s');
            $db->table('students')
                ->whereIn('student_id', $ids)
                ->update([
                    'voucher_status' => 'generated',
                    'generated_at'   => $generatedAt,
                ]);
            $db->query(
                'UPDATE students SET generate_count = generate_count + 1 WHERE student_id IN (' . implode(',', array_map('intval', $ids)) . ')'
            );
            (new GenerationHistoryModel())->recordMany(
                $students,
                isset($job['created_by']) ? (int) $job['created_by'] : null,
                $parentId > 0 ? $parentId : $jobId,
                'json_queue',
                $generatedAt
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
     * Assign a voucher_no to any students in this chunk that don't have one,
     * persist it, and reflect it in the returned in-memory rows so the renderer
     * prints the new number.
     *
     * generate_voucher_no() derives the next sequence with a live MAX()+1 query,
     * so concurrent workers could otherwise hand out the same number. We serialise
     * assignment across all workers with a MySQL named lock (GET_LOCK). Each chunk
     * holds it only for the few students it actually needs to number.
     */
    protected static function assignVoucherNumbers(array $students): array
    {
        helper('voucher');

        $missing = array_filter($students, static fn($s) => empty($s['voucher_no']));
        if (empty($missing)) {
            return $students;
        }

        $db = \Config\Database::connect();

        // Block (up to 30s) until no other worker is assigning. Skip numbering
        // this pass if the lock can't be acquired rather than risk duplicates.
        $got = $db->query("SELECT GET_LOCK('fap_voucher_no', 30) AS ok")->getRow();
        if (!$got || (int) $got->ok !== 1) {
            throw new \RuntimeException('Could not acquire voucher_no lock; will retry on next pass.');
        }

        $assigned = [];
        try {
            foreach ($missing as $s) {
                $sid  = (int) $s['student_id'];
                $jhs  = $s['junior_high_school'] ?? '';
                $year = !empty($s['voucher_date'])
                    ? date('Y', strtotime($s['voucher_date']))
                    : date('Y');

                $vno = generate_voucher_no($jhs, $year);
                $db->table('students')->where('student_id', $sid)->update(['voucher_no' => $vno]);
                $assigned[$sid] = $vno;
            }
        } finally {
            $db->query("SELECT RELEASE_LOCK('fap_voucher_no')");
        }

        foreach ($students as &$s) {
            $sid = (int) $s['student_id'];
            if (isset($assigned[$sid])) {
                $s['voucher_no'] = $assigned[$sid];
            }
        }
        unset($s);

        return $students;
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

        // Atomically claim the parent so exactly one worker finalizes it. Without
        // this, concurrent workers both pass the all-chunks-done check above and
        // race: one unlinks the chunk PDFs (cleanup below) while the other is
        // still inside ZipArchive::close() reading them -> "Can't open file".
        // Flip pending -> finalizing under the queue's exclusive lock; whoever
        // wins proceeds, everyone else backs off.
        $claimed = JsonPdfQueue::mutate(JsonPdfQueue::FILE_QUEUE, function (array $queue) use ($parentId) {
            $idx = self::findIndex($queue['jobs'] ?? [], $parentId);
            if ($idx === null) {
                return null; // parent gone — already finalized by another worker
            }
            $job = $queue['jobs'][$idx];
            if (!empty($job['parent_job_id']) || ($job['status'] ?? '') !== 'pending') {
                return null; // not a parent, or already claimed/finalizing
            }
            $queue['jobs'][$idx]['status'] = 'finalizing';
            return [$queue, true];
        });
        if ($claimed !== true) {
            return false; // another worker owns the finalize
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

                // Build the ZIP in batches. ZipArchive::addFile() defers opening
                // each source file until close(), so adding all chunks at once
                // makes close() open every chunk PDF simultaneously — which blows
                // past the OS open-file-handle limit on large batches (e.g. 100
                // chunks) and fails with "Can't open file". Adding/closing in
                // bounded batches keeps the number of concurrently-open handles
                // capped while still appending to the same archive.
                $batchSize = 50;

                // Start from a clean file; first open() below uses CREATE (append),
                // so wipe any stale archive at this path before the first batch.
                if (is_file($zipPath)) {
                    @unlink($zipPath);
                }

                foreach (array_chunk($chunks, $batchSize) as $batch) {
                    $zip = new \ZipArchive();
                    if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                        throw new \RuntimeException('Failed to open ZIP for writing.');
                    }
                    foreach ($batch as $chunk) {
                        $sourcePath = $dir . $chunk['file_path'];
                        if (!is_file($sourcePath)) {
                            $zip->close();
                            @unlink($zipPath);
                            throw new \RuntimeException('Missing chunk file: ' . $chunk['file_path']);
                        }
                        $entry = 'vouchers_chunk_' . str_pad((string) $chunk['chunk_index'], 3, '0', STR_PAD_LEFT) . '.pdf';
                        $zip->addFile($sourcePath, $entry);
                    }
                    if ($zip->close() !== true) {
                        $status = $zip->getStatusString();
                        @unlink($zipPath);
                        throw new \RuntimeException('ZIP close failed: ' . $status);
                    }
                }
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
