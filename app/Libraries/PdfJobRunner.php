<?php

namespace App\Libraries;

use App\Models\VoucherModel;

class PdfJobRunner
{
    /**
     * Atomically claim a pending job's lease. Returns true if this caller
     * is now responsible for processing the job, false if someone else got
     * to it first (or the job is already done/failed/non-existent).
     */
    public static function tryClaim(int $jobId): bool
    {
        $db = \Config\Database::connect();
        $db->table('pdf_jobs')
            ->where('job_id', $jobId)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        return $db->affectedRows() > 0;
    }

    /**
     * Generate the PDF for a claimed job, write it to disk, mark the job
     * done (or failed). Idempotent: safe to call multiple times.
     */
    public static function process(int $jobId): bool
    {
        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();

        if (!$job) {
            return false;
        }

        if ($job->status !== 'processing') {
            // Either already done, or never got claimed. Caller should claim first.
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

            log_action(
                (int) $job->created_by,
                'GENERATE_PDF',
                'Generated PDF for ' . count($ids) . ' student(s) (job #' . $jobId . ')'
            );

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[PdfJobRunner] Job {$jobId}: " . $e->getMessage());

            $db->table('pdf_jobs')->where('job_id', $jobId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);

            return false;
        }
    }

    /**
     * Convenience: claim and process in one call. Returns true if this caller
     * successfully ran the job to completion (vs. someone else owning it).
     */
    public static function claimAndProcess(int $jobId): bool
    {
        if (!self::tryClaim($jobId)) {
            return false;
        }
        return self::process($jobId);
    }
}
