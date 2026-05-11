<?php

namespace App\Commands;

use App\Libraries\VoucherPdf;
use App\Models\VoucherModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GeneratePdfJob extends BaseCommand
{
    protected $group       = 'Vouchers';
    protected $name        = 'voucher:generate-pdf';
    protected $description = 'Process one pending PDF generation job from the queue';

    public function run(array $params): void
    {
        $db = \Config\Database::connect();

        $job = $db->table('pdf_jobs')
            ->where('status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->limit(1)
            ->get()
            ->getRow();

        if (!$job) {
            CLI::write('No pending PDF jobs.', 'yellow');
            return;
        }

        // Claim atomically — skip if another worker already grabbed it
        $db->table('pdf_jobs')
            ->where('job_id', $job->job_id)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        if ($db->affectedRows() === 0) {
            CLI::write('Job already claimed by another worker.', 'yellow');
            return;
        }

        CLI::write("Processing job #{$job->job_id}...", 'cyan');

        try {
            $ids      = json_decode($job->voucher_ids, true);
            $vouchers = (new VoucherModel())->getVouchersByIds($ids);

            if (empty($vouchers)) {
                throw new \RuntimeException('No vouchers found for job #' . $job->job_id);
            }

            $pdfBytes = VoucherPdf::generate($vouchers);

            $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'vouchers_job' . $job->job_id . '_' . date('Ymd_His') . '.pdf';
            file_put_contents($dir . $filename, $pdfBytes);

            $db->table('pdf_jobs')
                ->where('job_id', $job->job_id)
                ->update([
                    'status'       => 'done',
                    'file_path'    => $filename,
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);

            CLI::write("Job #{$job->job_id} done → {$filename}", 'green');
        } catch (\Throwable $e) {
            $db->table('pdf_jobs')
                ->where('job_id', $job->job_id)
                ->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at'  => date('Y-m-d H:i:s'),
                ]);

            CLI::error("Job #{$job->job_id} failed: " . $e->getMessage());
        }
    }
}
