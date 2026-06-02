<?php

namespace App\Libraries;

use App\Models\VoucherModel;
use App\Models\GenerationHistoryModel;

/**
 * PdfJobRunner — background orchestrator for voucher PDF generation.
 *
 * The `pdf_jobs` table is hierarchical:
 *   - PARENT rows have `parent_job_id = NULL` and `total_chunks > 0`.
 *   - CHILD (chunk) rows point at their parent and carry their own slice of
 *     student IDs in `voucher_ids` (JSON).
 *
 * Lifecycle:
 *   pending → processing → done | failed
 *
 * Workers (the polling endpoint or the `spark run:pdf-queue` command) call
 * `processPending()` which dispatches to either claim+process (for a chunk)
 * or tryFinalize (for a parent). Atomic `UPDATE … WHERE status='pending'`
 * guards prevent two workers from rendering the same row.
 */
class PdfJobRunner
{
    /**
     * Atomically claim a pending job's lease for rendering. Parents (jobs that
     * have child chunks) cannot be claimed here — they go through finalize.
     */
    public static function tryClaim(int $jobId): bool
    {
        $db = \Config\Database::connect();

        // Refuse to claim parents — they're assembled by tryFinalize(), not rendered.
        $hasChildren = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $jobId)
            ->countAllResults();

        if ($hasChildren > 0) {
            return false;
        }

        // The `status='pending'` predicate is the race guard: if two callers
        // race, only one UPDATE actually changes a row.
        $db->table('pdf_jobs')
            ->where('job_id', $jobId)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        // True iff this caller won the race.
        return $db->affectedRows() > 0;
    }

    /**
     * Render a claimed job (a chunk or a standalone legacy job). When a chunk
     * completes, the parent's finalize step is attempted.
     */
    public static function process(int $jobId): bool
    {
        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();

        if (!$job) {
            return false;
        }

        // Idempotency: only `processing` rows do work here. Already-done jobs
        // report success so retries are safe.
        if ($job->status !== 'processing') {
            return $job->status === 'done';
        }

        try {
            // Chunk's JSON id slice → full student rows.
            $ids      = json_decode($job->voucher_ids, true) ?: [];
            $students = (new VoucherModel())->getVouchersByIds($ids);

            if (empty($students)) {
                throw new \RuntimeException('No valid students found for this job.');
            }

            // The slow step — mPDF rendering. Returns binary PDF as a string.
            $pdfBytes = VoucherPdf::generate($students);

            $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'vouchers_job' . $jobId . '_' . date('Ymd_His') . '.pdf';
            file_put_contents($dir . $filename, $pdfBytes);

            // Flip the UI badge from "Pending" → "Generated" for every student
            // in this chunk, stamping the moment of generation.
            $generatedAt = date('Y-m-d H:i:s');
            $db->table('students')
                ->whereIn('student_id', $ids)
                ->update([
                    'voucher_status' => 'generated',
                    'generated_at'   => $generatedAt,
                ]);
            if ($db->fieldExists('generate_count', 'students')) {
                $db->query(
                    'UPDATE students SET generate_count = generate_count + 1 WHERE student_id IN (' . implode(',', array_map('intval', $ids)) . ')'
                );
            }
            (new GenerationHistoryModel())->recordMany(
                $students,
                isset($job->created_by) ? (int) $job->created_by : null,
                !empty($job->parent_job_id) ? (int) $job->parent_job_id : $jobId,
                'db_queue',
                $generatedAt
            );

            $db->table('pdf_jobs')->where('job_id', $jobId)->update([
                'status'       => 'done',
                'file_path'    => $filename,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            // Chunk → try to wrap up the parent (succeeds only if this was the
            // last outstanding chunk). Standalone job → audit log directly.
            if (!empty($job->parent_job_id)) {
                self::tryFinalize((int) $job->parent_job_id);
            } else {
                log_action(
                    (int) $job->created_by,
                    'GENERATE_PDF',
                    'Generated PDF for ' . count($ids) . ' student(s) (job #' . $jobId . ')'
                );
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[PdfJobRunner] Job {$jobId}: " . $e->getMessage());

            // Defensive re-check: if another worker already finished this row
            // between our throw and now, respect their success.
            $current = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
            if ($current && $current->status === 'done') {
                return true;
            }

            $db->table('pdf_jobs')->where('job_id', $jobId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);

            // Cascade the failure up so the parent doesn't sit pending forever.
            if (!empty($job->parent_job_id)) {
                self::markParentFailed((int) $job->parent_job_id, $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Convenience: claim and process in one call.
     */
    public static function claimAndProcess(int $jobId): bool
    {
        if (!self::tryClaim($jobId)) {
            return false;
        }
        return self::process($jobId);
    }

    /**
     * Dispatcher used by pollers and the queue worker. Parents → finalize check.
     * Chunks / standalone → claim+process.
     */
    public static function processPending(int $jobId): bool
    {
        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        if (!$job) {
            return false;
        }
        if ($job->status !== 'pending') {
            return $job->status === 'done';
        }

        // Presence of child rows is what distinguishes a parent from a chunk.
        $hasChildren = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $jobId)
            ->countAllResults();

        if ($hasChildren > 0) {
            return self::tryFinalize($jobId);
        }

        return self::claimAndProcess($jobId);
    }

    /**
     * If all children of a parent are done, race-safely claim the parent's
     * finalize step and assemble the final output (PDF or ZIP).
     */
    public static function tryFinalize(int $parentJobId): bool
    {
        $db = \Config\Database::connect();

        // Any child not yet done → bail. We'll be called again when the next
        // chunk finishes (or by the queue worker's next drain pass).
        $unfinished = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $parentJobId)
            ->where('status !=', 'done')
            ->countAllResults();

        if ($unfinished > 0) {
            return false;
        }

        // Same atomic-claim trick as tryClaim(), but on the parent row.
        $db->table('pdf_jobs')
            ->where('job_id', $parentJobId)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        if ($db->affectedRows() === 0) {
            // Lost the race: another worker is finalizing. If they already
            // finished, report success; otherwise let them complete.
            $current = $db->table('pdf_jobs')->where('job_id', $parentJobId)->get()->getRow();
            return $current && $current->status === 'done';
        }

        try {
            // Assembly order MUST match chunk_index so user-visible pages stay sequential.
            $chunks = $db->table('pdf_jobs')
                ->where('parent_job_id', $parentJobId)
                ->orderBy('chunk_index', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($chunks)) {
                throw new \RuntimeException('No chunks found for parent job ' . $parentJobId);
            }

            $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $stamp = date('Ymd_His');

            if (count($chunks) === 1) {
                // Single chunk → just copy (not move) so the chunk row still
                // points at a valid file for audit/debug.
                $finalName  = 'vouchers_job' . $parentJobId . '_' . $stamp . '.pdf';
                $sourcePath = $dir . $chunks[0]['file_path'];
                if (!is_file($sourcePath) || !copy($sourcePath, $dir . $finalName)) {
                    throw new \RuntimeException('Failed to copy chunk PDF for parent ' . $parentJobId);
                }
            } else {
                // Multiple chunks → bundle as ZIP.
                $finalName = 'vouchers_job' . $parentJobId . '_' . $stamp . '.zip';
                $zipPath   = $dir . $finalName;
                $zip       = new \ZipArchive();

                // CREATE | OVERWRITE: create new, replace any stale file with the same name.
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new \RuntimeException('Failed to open ZIP for writing.');
                }

                foreach ($chunks as $chunk) {
                    $sourcePath = $dir . $chunk['file_path'];
                    if (!is_file($sourcePath)) {
                        // Clean up the partial archive before throwing — no half-written files left behind.
                        $zip->close();
                        @unlink($zipPath);
                        throw new \RuntimeException('Missing chunk file: ' . $chunk['file_path']);
                    }
                    // Zero-padded entry name → archive contents sort correctly when extracted.
                    $entry = 'vouchers_chunk_' . str_pad((string) $chunk['chunk_index'], 3, '0', STR_PAD_LEFT) . '.pdf';
                    $zip->addFile($sourcePath, $entry);
                }
                // close() is what actually flushes the archive to disk.
                $zip->close();
            }

            $parent = $db->table('pdf_jobs')->where('job_id', $parentJobId)->get()->getRow();
            $db->table('pdf_jobs')->where('job_id', $parentJobId)->update([
                'status'       => 'done',
                'file_path'    => $finalName,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            log_action(
                (int) ($parent->created_by ?? 0),
                'GENERATE_PDF',
                'Finalized PDF for parent job #' . $parentJobId . ' (' . count($chunks) . ' chunk(s))'
            );

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[PdfJobRunner] Finalize parent {$parentJobId}: " . $e->getMessage());

            $current = $db->table('pdf_jobs')->where('job_id', $parentJobId)->get()->getRow();
            if ($current && $current->status === 'done') {
                return true;
            }

            $db->table('pdf_jobs')->where('job_id', $parentJobId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);

            return false;
        }
    }

    /**
     * Mark the parent as failed when one of its chunks failed. Idempotent.
     */
    protected static function markParentFailed(int $parentJobId, string $reason): void
    {
        $db      = \Config\Database::connect();
        $parent  = $db->table('pdf_jobs')->where('job_id', $parentJobId)->get()->getRow();
        // Don't clobber a parent that's already settled (done or failed).
        if (!$parent || $parent->status === 'done' || $parent->status === 'failed') {
            return;
        }
        $db->table('pdf_jobs')->where('job_id', $parentJobId)->update([
            'status'        => 'failed',
            // Prefix makes it obvious whether the failure was a chunk or finalize step.
            'error_message' => 'Chunk failed: ' . $reason,
            'completed_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
