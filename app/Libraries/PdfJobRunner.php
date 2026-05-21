<?php

namespace App\Libraries;

use App\Models\VoucherModel;

class PdfJobRunner
{
    /**
     * Atomically claim a pending job's lease for rendering. Parents (jobs that
     * have child chunks) cannot be claimed here — they go through finalize.
     */
    public static function tryClaim(int $jobId): bool
    {
        $db = \Config\Database::connect();

        $hasChildren = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $jobId)
            ->countAllResults();

        if ($hasChildren > 0) {
            return false;
        }

        $db->table('pdf_jobs')
            ->where('job_id', $jobId)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

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

        if ($job->status !== 'processing') {
            return $job->status === 'done';
        }

        try {
            $ids      = json_decode($job->voucher_ids, true) ?: [];
            $students = (new VoucherModel())->getVouchersByIds($ids);

            if (empty($students)) {
                throw new \RuntimeException('No valid students found for this job.');
            }

            $pdfBytes = VoucherPdf::generate($students);

            $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'vouchers_job' . $jobId . '_' . date('Ymd_His') . '.pdf';
            file_put_contents($dir . $filename, $pdfBytes);

            $db->table('students')
                ->whereIn('student_id', $ids)
                ->update(['voucher_status' => 'generated']);

            $db->table('pdf_jobs')->where('job_id', $jobId)->update([
                'status'       => 'done',
                'file_path'    => $filename,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

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

            $current = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
            if ($current && $current->status === 'done') {
                return true;
            }

            $db->table('pdf_jobs')->where('job_id', $jobId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);

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

        $unfinished = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $parentJobId)
            ->where('status !=', 'done')
            ->countAllResults();

        if ($unfinished > 0) {
            return false;
        }

        $db->table('pdf_jobs')
            ->where('job_id', $parentJobId)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        if ($db->affectedRows() === 0) {
            $current = $db->table('pdf_jobs')->where('job_id', $parentJobId)->get()->getRow();
            return $current && $current->status === 'done';
        }

        try {
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
                $finalName  = 'vouchers_job' . $parentJobId . '_' . $stamp . '.pdf';
                $sourcePath = $dir . $chunks[0]['file_path'];
                if (!is_file($sourcePath) || !copy($sourcePath, $dir . $finalName)) {
                    throw new \RuntimeException('Failed to copy chunk PDF for parent ' . $parentJobId);
                }
            } else {
                $finalName = 'vouchers_job' . $parentJobId . '_' . $stamp . '.zip';
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
        if (!$parent || $parent->status === 'done' || $parent->status === 'failed') {
            return;
        }
        $db->table('pdf_jobs')->where('job_id', $parentJobId)->update([
            'status'        => 'failed',
            'error_message' => 'Chunk failed: ' . $reason,
            'completed_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
