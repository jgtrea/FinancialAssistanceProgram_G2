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

    // Chunk job_ids are parent_id * CHUNK_ID_OFFSET + chunk_index, so the
    // user-visible parent_id stays small (ticks once per generation) while
    // chunk IDs remain globally unique. Allows up to CHUNK_ID_OFFSET - 1
    // chunks per parent.
    public const CHUNK_ID_OFFSET = 100000;

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
     * Fill a job record with defaults, overlaying the given fields. Centralises
     * the record shape so every enqueue path writes the same keys (type/payload/
     * progress/result included), and any in-flight legacy record missing these
     * keys still reads back sanely.
     */
    protected static function newRecord(array $over): array
    {
        return array_merge([
            'job_id'        => 0,
            'parent_job_id' => null,
            'chunk_index'   => null,
            'total_chunks'  => 0,
            'type'          => 'pdf',
            'voucher_ids'   => [],
            'payload'       => [],
            'progress'      => null,
            'result'        => null,
            'created_by'    => 0,
            'created_at'    => date('Y-m-d H:i:s'),
            'status'        => 'pending',
            'file_path'     => null,
            'completed_at'  => null,
            'error_message' => null,
        ], $over);
    }

    /**
     * Enqueue one parent job + N chunk jobs. Returns the parent job_id.
     * Back-compat wrapper — generates PDF jobs ($type = 'pdf').
     */
    public static function enqueueJob(array $ids, int $userId, int $chunkSize): int
    {
        return self::enqueueChunked('pdf', $ids, $userId, $chunkSize);
    }

    /**
     * Enqueue a chunked job of the given $type: one parent record + N chunk
     * records split by $chunkSize. $payload is type-specific data (e.g. the
     * archive reason) and is copied onto BOTH the parent and every chunk so a
     * worker processing a chunk in isolation has everything it needs.
     * Returns the parent job_id.
     */
    public static function enqueueChunked(string $type, array $ids, int $userId, int $chunkSize, array $payload = []): int
    {
        $ids         = array_values($ids);
        $chunks      = array_chunk($ids, max(1, $chunkSize));
        $totalChunks = count($chunks);
        $now         = date('Y-m-d H:i:s');

        return self::mutate(self::FILE_QUEUE, function (array $queue) use ($type, $ids, $userId, $now, $chunks, $totalChunks, $payload) {
            // next_job_id ticks ONCE per generation (parent). Chunk records get
            // a derived ID parent_id * CHUNK_ID_OFFSET + chunk_index so they
            // stay unique without bloating the counter the user sees.
            $nextId = (int) ($queue['next_job_id'] ?? 1);
            if ($nextId < 1) $nextId = 1;

            $parentId       = $nextId++;
            $queue['jobs']  = $queue['jobs'] ?? [];
            // Parent intentionally does NOT carry the full id list — the chunks
            // own their ids, and nothing reads the parent's. Storing all N ids
            // here meant every chunk claim re-encoded a queue.json holding tens
            // of thousands of ints (huge cost on large batches).
            $queue['jobs'][] = self::newRecord([
                'job_id'        => $parentId,
                'parent_job_id' => null,
                'chunk_index'   => null,
                'total_chunks'  => $totalChunks,
                'type'          => $type,
                'voucher_ids'   => [],
                'payload'       => $payload,
                'created_by'    => $userId,
                'created_at'    => $now,
            ]);

            foreach ($chunks as $idx => $chunkIds) {
                $queue['jobs'][] = self::newRecord([
                    'job_id'        => $parentId * self::CHUNK_ID_OFFSET + ($idx + 1),
                    'parent_job_id' => $parentId,
                    'chunk_index'   => $idx + 1,
                    'total_chunks'  => $totalChunks,
                    'type'          => $type,
                    'voucher_ids'   => array_values($chunkIds),
                    'payload'       => $payload,
                    'created_by'    => $userId,
                    'created_at'    => $now,
                ]);
            }

            $queue['next_job_id'] = $nextId;
            return [$queue, $parentId];
        });
    }

    /**
     * Enqueue a SINGLE (non-chunked) job of the given $type — one parent record
     * with total_chunks = 0 and no chunk records. The parent IS the unit of work
     * (claimed and processed whole). Used by import/export where there's nothing
     * to parallelise across chunks. Returns the job_id.
     */
    public static function enqueueSingle(string $type, array $payload, int $userId): int
    {
        $now = date('Y-m-d H:i:s');

        return self::mutate(self::FILE_QUEUE, function (array $queue) use ($type, $payload, $userId, $now) {
            $nextId = (int) ($queue['next_job_id'] ?? 1);
            if ($nextId < 1) $nextId = 1;

            $jobId         = $nextId++;
            $queue['jobs'] = $queue['jobs'] ?? [];
            $queue['jobs'][] = self::newRecord([
                'job_id'       => $jobId,
                'parent_job_id'=> null,
                'chunk_index'  => null,
                'total_chunks' => 0,
                'type'         => $type,
                'voucher_ids'  => [],
                'payload'      => $payload,
                'created_by'   => $userId,
                'created_at'   => $now,
            ]);

            $queue['next_job_id'] = $nextId;
            return [$queue, $jobId];
        });
    }

    /**
     * Atomically claim the next pending SINGLE job (total_chunks = 0, no parent)
     * — moves it queue→processing and returns the record, or null if none. Picks
     * the lowest job_id (FIFO). Chunked-job parents are NOT claimable here; they
     * finalize via their runner once all chunks finish.
     */
    public static function claimNextSingle(): ?array
    {
        return self::mutateAll(function (array $queue, array $processing, array $finished) {
            $jobs       = $queue['jobs'] ?? [];
            $pickIdx    = null;
            $pickJobId  = null;
            foreach ($jobs as $i => $job) {
                if (! empty($job['parent_job_id'])) continue;          // chunk
                if ((int) ($job['total_chunks'] ?? 0) !== 0) continue; // chunked parent
                if (($job['status'] ?? 'pending') !== 'pending') continue;
                $jid = (int) $job['job_id'];
                if ($pickJobId === null || $jid < $pickJobId) {
                    $pickIdx   = $i;
                    $pickJobId = $jid;
                }
            }
            if ($pickIdx === null) {
                return null;
            }

            $claimed = $jobs[$pickIdx];
            $claimed['status'] = 'processing';
            array_splice($jobs, $pickIdx, 1);
            $queue['jobs'] = array_values($jobs);
            $processing['jobs']   = $processing['jobs'] ?? [];
            $processing['jobs'][] = $claimed;

            return [[$queue, $processing, $finished], $claimed];
        });
    }

    /**
     * Update a job's progress counter while it runs (it lives in processing.json
     * during execution). Lets single jobs (e.g. import) report coarse progress
     * that the status endpoint surfaces as a percentage.
     */
    public static function setProgress(int $jobId, int $done, int $total): void
    {
        self::mutate(self::FILE_PROCESSING, function (array $processing) use ($jobId, $done, $total) {
            $changed = false;
            foreach (($processing['jobs'] ?? []) as &$job) {
                if ((int) $job['job_id'] === $jobId) {
                    $job['progress'] = ['done' => $done, 'total' => $total];
                    $changed = true;
                    break;
                }
            }
            unset($job);
            return $changed ? $processing : null;
        });
    }

    /**
     * Move a single (non-chunked) job from processing.json into finished.json,
     * applying $apply to set its terminal fields (status/result/file_path/...).
     */
    public static function finishSingle(int $jobId, callable $apply): void
    {
        self::mutateAll(function (array $queue, array $processing, array $finished) use ($jobId, $apply) {
            $idx = null;
            foreach (($processing['jobs'] ?? []) as $i => $job) {
                if ((int) $job['job_id'] === $jobId) { $idx = $i; break; }
            }
            if ($idx === null) {
                return null;
            }
            $rec = $apply($processing['jobs'][$idx]);
            array_splice($processing['jobs'], $idx, 1);
            $processing['jobs'] = array_values($processing['jobs']);
            $finished['jobs']   = $finished['jobs'] ?? [];
            $finished['jobs'][] = $rec;
            return [$queue, $processing, $finished];
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
