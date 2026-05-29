<?php

namespace App\Commands;

use App\Libraries\JsonPdfQueue;
use App\Libraries\JsonPdfRunner;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark run:json-pdf-queue` — background worker that drains the
 * file-backed pdf queue (writable/pdf_queue/{queue,processing,finished}.json).
 *
 * One-shot mode (default): drain every claimable chunk in one pass, exit.
 *
 * Loop mode (`--loop` / `--loop=N`): stay running, drain every N seconds
 * (default 5). Heartbeat line every ~60s so the operator sees liveness.
 */
class ProcessJsonPdfQueue extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'run:json-pdf-queue';
    protected $description = 'Process pending jobs in the JSON file queue. Use --loop=5 to poll every 5 seconds.';

    public function run(array $params)
    {
        // Accept --loop=N, --loop N, -l N, or a positional first arg.
        // PowerShell sometimes mangles `--loop=5` so we fall back to params[0].
        $loopOpt      = CLI::getOption('loop');
        $loopShort    = CLI::getOption('l');
        $loopInterval = 0;

        $candidate = null;
        if ($loopOpt !== null && $loopOpt !== false && $loopOpt !== true) {
            $candidate = $loopOpt;
        } elseif ($loopShort !== null && $loopShort !== false && $loopShort !== true) {
            $candidate = $loopShort;
        } elseif (isset($params[0]) && is_numeric($params[0])) {
            $candidate = $params[0];
        } elseif ($loopOpt === true || $loopShort === true) {
            $candidate = 5; // bare --loop / -l → 5s default
        }

        if ($candidate !== null) {
            $loopInterval = max(1, (int) $candidate);
        }

        JsonPdfQueue::ensureFiles();
        CLI::write('Args parsed → loop=' . var_export($loopOpt, true)
            . ', l=' . var_export($loopShort, true)
            . ', params=' . json_encode($params)
            . ', interval=' . $loopInterval . 's');
        $this->printDiagnostics();

        if ($loopInterval > 0) {
            CLI::write("JSON worker running. Polling every {$loopInterval}s. Ctrl+C to stop.");
            $tick = 0;
            while (true) {
                $this->drain();
                $tick++;
                if ($tick % 12 === 0) {
                    CLI::write('[' . date('H:i:s') . '] heartbeat — still polling JSON queue');
                }
                sleep($loopInterval);
            }
        }

        return $this->drain() ? EXIT_SUCCESS : EXIT_ERROR;
    }

    protected function printDiagnostics(): void
    {
        try {
            $queue      = JsonPdfQueue::read(JsonPdfQueue::FILE_QUEUE);
            $processing = JsonPdfQueue::read(JsonPdfQueue::FILE_PROCESSING);
            $finished   = JsonPdfQueue::read(JsonPdfQueue::FILE_FINISHED);

            CLI::write('--- JSON PDF Worker Diagnostics ---');
            CLI::write('queue dir:        ' . JsonPdfQueue::dir());
            CLI::write('queue jobs:       ' . count($queue['jobs'] ?? []));
            CLI::write('processing jobs:  ' . count($processing['jobs'] ?? []));
            CLI::write('finished jobs:    ' . count($finished['jobs'] ?? []));
            CLI::write('next job_id:      ' . ($queue['next_job_id'] ?? '?'));
            CLI::write('-----------------------------------');
        } catch (\Throwable $e) {
            CLI::error('Diagnostics failed: ' . $e->getMessage());
        }
    }

    /**
     * Drain pass: claim + process chunks until queue is empty, then attempt
     * to finalize any parents whose chunks are all done.
     */
    protected function drain(): bool
    {
        $processed = 0;
        $failed    = 0;

        while (true) {
            $result = JsonPdfRunner::processOne();
            if ($result === null) {
                break; // No more claimable chunks
            }
            if ($result === true) {
                $processed++;
            } else {
                $failed++;
            }
        }

        // Attempt to finalize any parent that's still in queue.json but whose
        // chunks all moved to finished.json (no more pending chunks).
        $queue = JsonPdfQueue::read(JsonPdfQueue::FILE_QUEUE);
        foreach (($queue['jobs'] ?? []) as $job) {
            if (empty($job['parent_job_id']) && ($job['status'] ?? '') === 'pending') {
                JsonPdfRunner::tryFinalizeParent((int) $job['job_id']);
            }
        }

        if ($processed > 0 || $failed > 0) {
            CLI::write('[' . date('H:i:s') . "] Drained: {$processed} done, {$failed} failed.");
        }

        return $failed === 0;
    }
}
