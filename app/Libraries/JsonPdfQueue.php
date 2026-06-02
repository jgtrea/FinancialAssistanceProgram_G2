<?php

namespace App\Libraries;

/**
 * JsonPdfQueue — file-backed job queue using three JSON files under
 * writable/pdf_queue/:
 *   - queue.json      pending jobs (chunks + parents)
 *   - processing.json jobs currently being rendered
 *   - finished.json   done/failed jobs
 *
 * All read/write goes through flock() to serialise access between the
 * spark worker, web requests (enqueue/status), and any concurrent polls.
 *
 * Job record shape:
 *   {
 *     "job_id": int,
 *     "parent_job_id": int|null,
 *     "chunk_index": int|null,
 *     "total_chunks": int,
 *     "voucher_ids": [int, ...],
 *     "created_by": int,
 *     "created_at": "Y-m-d H:i:s",
 *     "status": "pending|processing|done|failed",
 *     "file_path": string|null,
 *     "completed_at": "Y-m-d H:i:s"|null,
 *     "error_message": string|null
 *   }
 *
 * queue.json carries a top-level "next_job_id" counter so IDs stay unique
 * across the three files without DB help.
 */
class JsonPdfQueue
{
    public const FILE_QUEUE      = 'queue.json';
    public const FILE_PROCESSING = 'processing.json';
    public const FILE_FINISHED   = 'finished.json';

    /** Directory holding the three JSON files. */
    public static function dir(): string
    {
        return WRITEPATH . 'pdf_queue' . DIRECTORY_SEPARATOR;
    }

    public static function path(string $file): string
    {
        return self::dir() . $file;
    }

    /** Create the directory and seed empty JSON files if they don't exist. */
    public static function ensureFiles(): void
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $defaults = [
            self::FILE_QUEUE      => ['next_job_id' => 1, 'jobs' => []],
            self::FILE_PROCESSING => ['jobs' => []],
            self::FILE_FINISHED   => ['jobs' => []],
        ];

        foreach ($defaults as $file => $payload) {
            $path = $dir . $file;
            if (!file_exists($path)) {
                file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT));
            }
        }
    }

    /**
     * Read a JSON file with a shared lock. Returns decoded array.
     */
    public static function read(string $file): array
    {
        self::ensureFiles();
        $path = self::path($file);

        $fp = fopen($path, 'rb');
        if (!$fp) {
            return [];
        }
        try {
            flock($fp, LOCK_SH);
            $raw = stream_get_contents($fp);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }

        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    /**
     * Atomically mutate a JSON file under an exclusive lock. The callback
     * receives the decoded array and must return the new array to persist.
     */
    public static function mutate(string $file, callable $mutator)
    {
        self::ensureFiles();
        $path = self::path($file);

        $fp = fopen($path, 'c+b');
        if (!$fp) {
            throw new \RuntimeException("Cannot open {$path}");
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \RuntimeException("Cannot lock {$path}");
            }

            $raw  = stream_get_contents($fp);
            $data = json_decode($raw ?: '[]', true);
            if (!is_array($data)) {
                $data = [];
            }

            $result = $mutator($data);
            if ($result === null) {
                // Mutator opted out — leave file untouched.
                return null;
            }
            [$newData, $payload] = is_array($result) && array_key_exists(0, $result) && array_key_exists(1, $result)
                ? $result
                : [$result, null];

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($newData, JSON_PRETTY_PRINT));
            fflush($fp);

            return $payload;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Atomically mutate all three files together. Locks queue → processing →
     * finished in that order to avoid cross-deadlock with other callers.
     * Callback receives [$queue, $processing, $finished] and must return
     * either the new tuple or [tuple, return_payload].
     */
    public static function mutateAll(callable $mutator)
    {
        self::ensureFiles();

        $files = [self::FILE_QUEUE, self::FILE_PROCESSING, self::FILE_FINISHED];
        $fps   = [];
        $data  = [];

        try {
            foreach ($files as $file) {
                $path = self::path($file);
                $fp   = fopen($path, 'c+b');
                if (!$fp || !flock($fp, LOCK_EX)) {
                    throw new \RuntimeException("Cannot lock {$path}");
                }
                $fps[$file] = $fp;
                $raw        = stream_get_contents($fp);
                $decoded    = json_decode($raw ?: '[]', true);
                $data[$file] = is_array($decoded) ? $decoded : [];
            }

            $result = $mutator($data[self::FILE_QUEUE], $data[self::FILE_PROCESSING], $data[self::FILE_FINISHED]);
            if ($result === null) {
                return null;
            }

            // Result can be [$q, $p, $f] OR [[$q, $p, $f], $payload]
            $payload = null;
            if (is_array($result) && count($result) === 2 && is_array($result[0]) && count($result[0]) === 3) {
                [$tuple, $payload] = $result;
                [$newQ, $newP, $newF] = $tuple;
            } else {
                [$newQ, $newP, $newF] = $result;
            }

            $writes = [
                self::FILE_QUEUE      => $newQ,
                self::FILE_PROCESSING => $newP,
                self::FILE_FINISHED   => $newF,
            ];
            foreach ($writes as $file => $newData) {
                $fp = $fps[$file];
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode($newData, JSON_PRETTY_PRINT));
                fflush($fp);
            }

            return $payload;
        } finally {
            foreach ($fps as $fp) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }

    /**
     * Enqueue one parent job + N chunk jobs. Returns the parent job_id.
     * $ids = full list of student_ids. Chunks split by $chunkSize.
     */
    public static function enqueueJob(array $ids, int $userId, int $chunkSize): int
    {
        $ids         = array_values($ids);
        $chunks      = array_chunk($ids, $chunkSize);
        $totalChunks = count($chunks);
        $now         = date('Y-m-d H:i:s');

        return self::mutateAll(function (array $queue, array $processing, array $finished) use ($ids, $userId, $now, $chunks, $totalChunks) {
            $maxJobId = 0;
            foreach ([$queue, $processing, $finished] as $bucket) {
                foreach (($bucket['jobs'] ?? []) as $job) {
                    $maxJobId = max($maxJobId, (int) ($job['job_id'] ?? 0));
                }
            }

            $nextId = max((int) ($queue['next_job_id'] ?? 1), $maxJobId + 1, 1);

            $parentId       = $nextId++;
            $queue['jobs']  = $queue['jobs'] ?? [];
            $queue['jobs'][] = [
                'job_id'        => $parentId,
                'parent_job_id' => null,
                'chunk_index'   => null,
                'total_chunks'  => $totalChunks,
                'voucher_ids'   => $ids,
                'created_by'    => $userId,
                'created_at'    => $now,
                'status'        => 'pending',
                'file_path'     => null,
                'completed_at'  => null,
                'error_message' => null,
            ];

            foreach ($chunks as $idx => $chunkIds) {
                $queue['jobs'][] = [
                    'job_id'        => $nextId++,
                    'parent_job_id' => $parentId,
                    'chunk_index'   => $idx + 1,
                    'total_chunks'  => $totalChunks,
                    'voucher_ids'   => array_values($chunkIds),
                    'created_by'    => $userId,
                    'created_at'    => $now,
                    'status'        => 'pending',
                    'file_path'     => null,
                    'completed_at'  => null,
                    'error_message' => null,
                ];
            }

            $queue['next_job_id'] = $nextId;
            return [[$queue, $processing, $finished], $parentId];
        });
    }

    /**
     * Look up a job by id across all three files. Returns ['file' => ..., 'job' => ...]
     * or null if not found.
     */
    public static function findJob(int $jobId): ?array
    {
        foreach ([self::FILE_QUEUE, self::FILE_PROCESSING, self::FILE_FINISHED] as $file) {
            $data = self::read($file);
            foreach (($data['jobs'] ?? []) as $job) {
                if ((int) $job['job_id'] === $jobId) {
                    return ['file' => $file, 'job' => $job];
                }
            }
        }
        return null;
    }

    /**
     * Aggregate status snapshot of a parent job and its chunks.
     * Returns the parent job (wherever it lives) plus chunk counts.
     */
    public static function snapshot(int $parentJobId): ?array
    {
        $queue      = self::read(self::FILE_QUEUE);
        $processing = self::read(self::FILE_PROCESSING);
        $finished   = self::read(self::FILE_FINISHED);

        $parent = null;
        foreach ([$queue, $processing, $finished] as $bucket) {
            foreach (($bucket['jobs'] ?? []) as $job) {
                if ((int) $job['job_id'] === $parentJobId && empty($job['parent_job_id'])) {
                    $parent = $job;
                    break 2;
                }
            }
        }
        if (!$parent) {
            return null;
        }

        $total      = (int) ($parent['total_chunks'] ?? 0);
        $done       = 0;
        $failed     = 0;
        $processingCount = 0;
        $queued     = 0;

        $countChunks = static function (array $bucket, int $parentId, string $forceStatus = '') use (&$done, &$failed, &$processingCount, &$queued) {
            foreach (($bucket['jobs'] ?? []) as $job) {
                if ((int) ($job['parent_job_id'] ?? 0) !== $parentId) {
                    continue;
                }
                $st = $forceStatus !== '' ? $forceStatus : ($job['status'] ?? 'pending');
                if ($st === 'done')        $done++;
                elseif ($st === 'failed')  $failed++;
                elseif ($st === 'processing') $processingCount++;
                else                       $queued++;
            }
        };

        $countChunks($queue,      $parentJobId);
        $countChunks($processing, $parentJobId, 'processing');
        $countChunks($finished,   $parentJobId);

        return [
            'parent'     => $parent,
            'total'      => $total,
            'done'       => $done,
            'failed'     => $failed,
            'processing' => $processingCount,
            'queued'     => $queued,
        ];
    }
}
