<?php

namespace App\Commands;

use App\Libraries\PdfJobRunner;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GeneratePdfJob extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'run:pdf';
    protected $description = 'Process a single PDF generation job by ID (chunk-aware).';

    public function run(array $params)
    {
        $jobId = (int) ($params[0] ?? 0);
        if (!$jobId) {
            CLI::error('Usage: php spark run:pdf <job_id>');
            return EXIT_ERROR;
        }

        if (PdfJobRunner::processPending($jobId)) {
            CLI::write("Job {$jobId} done.");
            return EXIT_SUCCESS;
        }

        CLI::error("Job {$jobId} could not be processed (already claimed, awaiting siblings, or failed).");
        return EXIT_ERROR;
    }
}
