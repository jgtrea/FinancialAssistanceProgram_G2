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

        // --throttle=N → pause N milliseconds between chunks so a big batch
        // doesn't monopolise MySQL/CPU and starve interactive LAN users.
        $throttleOpt = CLI::getOption('throttle');
        $throttleMs  = ($throttleOpt !== null && $throttleOpt !== false && $throttleOpt !== true)
            ? max(0, (int) $throttleOpt)
            : 0;

        JsonPdfQueue::ensureFiles();
        CLI::write('Args parsed → loop=' . var_export($loopOpt, true)
            . ', l=' . var_export($loopShort, true)
            . ', params=' . json_encode($params)
            . ', interval=' . $loopInterval . 's'
            . ', throttle=' . $throttleMs . 'ms');
        $this->printDiagnostics();

        if ($loopInterval > 0) {
            CLI::write("JSON worker running. Polling every {$loopInterval}s, {$throttleMs}ms between chunks. Ctrl+C to stop.");
            $tick = 0;
            while (true) {
                $this->drain($throttleMs);
                $tick++;
                if ($tick % 12 === 0) {
                    CLI::write('[' . date('H:i:s') . '] heartbeat — still polling JSON queue');
                }
                sleep($loopInterval);
            }
        }

        return $this->drain($throttleMs) ? EXIT_SUCCESS : EXIT_ERROR;
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
    protected function drain(int $throttleMs = 0): bool
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
            // Breathe between chunks so other LAN users' queries get a turn.
            if ($throttleMs > 0) {
                usleep($throttleMs * 1000);
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

        // Sweep stale finished records every drain so finished.json + the
        // writable/pdfs/ dir don't grow without bound.
        $swept = $this->sweepStaleFinished(self::FINISHED_TTL_SECONDS);

        if ($processed > 0 || $failed > 0 || $swept > 0) {
            CLI::write('[' . date('H:i:s') . "] Drained: {$processed} done, {$failed} failed, {$swept} swept.");
        }

        return $failed === 0;
    }

    /**
     * How long a finished record + its on-disk file linger before the worker
     * sweeps them out. Downloads no longer delete on first grab (so the toast's
     * manual Download link keeps working), so this TTL is the sole cleanup path.
     * 10 min comfortably outlives the toast's 5-min finished-linger window.
     */
    private const FINISHED_TTL_SECONDS = 600; // 10 minutes

    /**
     * Drop finished-job records older than $ttl seconds. Also unlinks the
     * referenced PDF/ZIP file if it still exists on disk. Returns the count
     * of records removed.
     */
    protected function sweepStaleFinished(int $ttl): int
    {
        $cutoff   = time() - max(60, $ttl);
        $pdfsDir  = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
        $removed  = 0;
        $unlinked = [];

        JsonPdfQueue::mutate(JsonPdfQueue::FILE_FINISHED, function (array $finished) use ($cutoff, $pdfsDir, &$removed, &$unlinked) {
            $kept = [];
            foreach (($finished['jobs'] ?? []) as $job) {
                $completedAt = $job['completed_at'] ?? null;
                $ts          = $completedAt ? strtotime($completedAt) : false;

                if ($ts !== false && $ts <= $cutoff) {
                    if (!empty($job['file_path'])) {
                        $path = $pdfsDir . $job['file_path'];
                        if (is_file($path) && @unlink($path)) {
                            $unlinked[] = $job['file_path'];
                        }
                    }
                    $removed++;
                    continue;
                }
                $kept[] = $job;
            }
            $finished['jobs'] = $kept;
            return $finished;
        });

        return $removed;
    }
}
