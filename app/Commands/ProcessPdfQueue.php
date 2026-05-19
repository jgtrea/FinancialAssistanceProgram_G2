<?php

namespace App\Commands;

use App\Libraries\PdfJobRunner;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessPdfQueue extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'run:pdf-queue';
    protected $description = 'Process pending pdf_jobs rows. Use --loop=5 to stay running and poll every 5 seconds.';

    public function run(array $params)
    {
        // --loop or --loop=N is parsed by CLI as an option, not a positional param.
        $loopOpt      = CLI::getOption('loop');
        $loopInterval = 0;
        if ($loopOpt !== null && $loopOpt !== false) {
            $loopInterval = is_numeric($loopOpt) ? max(1, (int) $loopOpt) : 5;
        }

        $this->printConnectionDiagnostics();

        if ($loopInterval > 0) {
            CLI::write("Worker running. Polling every {$loopInterval}s. Ctrl+C to stop.");
            $tick = 0;
            while (true) {
                $this->drainQueue();
                $tick++;
                // Heartbeat every 12 cycles (~60s on a 5s interval) so the user can see it's alive
                if ($tick % 12 === 0) {
                    CLI::write('[' . date('H:i:s') . '] heartbeat — still polling, no pending jobs');
                }
                sleep($loopInterval);
            }
        }

        return $this->drainQueue() ? EXIT_SUCCESS : EXIT_ERROR;
    }

    protected function printConnectionDiagnostics(): void
    {
        try {
            $db    = \Config\Database::connect();
            $envFile = file_exists(ROOTPATH . '.env') ? ROOTPATH . '.env' : '(none)';
            $info  = method_exists($db, 'getDatabase') ? $db->getDatabase() : '?';
            $host  = property_exists($db, 'hostname') ? $db->hostname : '?';

            $total   = (int) $db->table('pdf_jobs')->countAllResults(false);
            $pending = (int) $db->table('pdf_jobs')->where('status', 'pending')->countAllResults();

            CLI::write('--- PDF Worker Diagnostics ---');
            CLI::write("env file:        {$envFile}");
            CLI::write("DB host:         {$host}");
            CLI::write("DB name:         {$info}");
            CLI::write("pdf_jobs total:  {$total}");
            CLI::write("pdf_jobs pending:{$pending}");
            CLI::write('-------------------------------');
        } catch (\Throwable $e) {
            CLI::error('Diagnostics failed: ' . $e->getMessage());
        }
    }

    protected function drainQueue(): bool
    {
        $db   = \Config\Database::connect();
        $jobs = $db->table('pdf_jobs')
            ->where('status', 'pending')
            ->orderBy('job_id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($jobs)) {
            return true;
        }

        CLI::write('[' . date('H:i:s') . '] Found ' . \count($jobs) . ' pending job(s).');

        $failCount = 0;
        foreach ($jobs as $job) {
            if (!$this->processJob((int) $job['job_id'])) {
                $failCount++;
            }
        }

        return $failCount === 0;
    }

    protected function processJob(int $jobId): bool
    {
        $ok = PdfJobRunner::claimAndProcess($jobId);
        if ($ok) {
            CLI::write("Job {$jobId} done.");
        } else {
            CLI::error("Job {$jobId} could not be processed (already claimed, missing, or failed).");
        }
        return $ok;
    }
}
