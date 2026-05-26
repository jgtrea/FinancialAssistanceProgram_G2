<?php

namespace App\Commands;

use App\Libraries\PdfJobRunner;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark run:pdf <job_id>` — process a single job by ID.
 *
 * Chunk-aware: if the ID points at a parent, the runner dispatches to
 * tryFinalize(); if it points at a chunk or a standalone legacy job, the
 * runner claims + renders it. Useful for retrying a specific stuck job
 * without restarting the whole queue worker.
 */
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

        // processPending() picks the right path for the row type — see
        // PdfJobRunner::processPending().
        if (PdfJobRunner::processPending($jobId)) {
            CLI::write("Job {$jobId} done.");
            return EXIT_SUCCESS;
        }

        CLI::error("Job {$jobId} could not be processed (already claimed, awaiting siblings, or failed).");
        return EXIT_ERROR;
    }
}
