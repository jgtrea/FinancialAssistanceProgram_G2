<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\VoucherModel;
use App\Libraries\VoucherPdf;

class GeneratePdfJob extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'run:pdf';
    protected $description = 'Process a pending PDF generation job';

    public function run(array $params)
    {
        $jobId = (int) ($params[0] ?? 0);
        if (!$jobId) {
            CLI::error('Usage: php spark run:pdf <job_id>');
            return EXIT_ERROR;
        }

        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();

        if (!$job) {
            CLI::error("Job {$jobId} not found.");
            return EXIT_ERROR;
        }

        if ($job->status !== 'pending') {
            return EXIT_SUCCESS;
        }

        $db->table('pdf_jobs')->where('job_id', $jobId)->update(['status' => 'processing']);

        try {
            $ids          = json_decode($job->voucher_ids, true);
            $voucherModel = new VoucherModel();
            $students     = $voucherModel->getVouchersByIds($ids);

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

            CLI::write("Job {$jobId} done: {$filename}");
            return EXIT_SUCCESS;

        } catch (\Throwable $e) {
            log_message('error', "[GeneratePdfJob] Job {$jobId}: " . $e->getMessage());

            $db->table('pdf_jobs')->where('job_id', $jobId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => date('Y-m-d H:i:s'),
            ]);

            CLI::error("Job {$jobId} failed: " . $e->getMessage());
            return EXIT_ERROR;
        }
    }
}
