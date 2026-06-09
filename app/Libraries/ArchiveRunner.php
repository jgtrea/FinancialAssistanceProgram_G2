<?php

namespace App\Libraries;

use App\Models\ArchiveModel;
use App\Models\VoucherModel;

/**
 * ArchiveRunner — process one ARCHIVE chunk job from the JSON-file queue.
 *
 * Sibling of JsonPdfRunner: same claim/finished plumbing (JsonPdfQueue), but
 * instead of rendering a PDF it archives the chunk's students — copies each row
 * into student_archive, releases the audit_log FKs, then deletes the source
 * rows. The reason lives in the job payload (copied onto every chunk by
 * JsonPdfQueue::enqueueChunked).
 *
 * Workflow per chunk (claimed by JobRunner/JsonPdfRunner::claimNextChunk):
 *   processClaimed()    archive the ids, move record queue→finished as 'done'
 *                       with result.archived = count, then tryFinalizeParent().
 *   tryFinalizeParent() once every chunk is done, sum the archived counts, write
 *                       ONE bulk audit row, and move the parent into finished.
 */
class ArchiveRunner
{
    /**
     * Archive the chunk's students, then mark the chunk done (or failed) and
     * attempt to finalize the parent. Returns true on success, false on failure.
     */
    public static function processClaimed(array $job): bool
    {
        $jobId    = (int) $job['job_id'];
        $parentId = (int) ($job['parent_job_id'] ?? 0);
        $ids      = array_map('intval', $job['voucher_ids'] ?? []);
        $reason   = (string) ($job['payload']['reason'] ?? 'Bulk archive');
        $userId   = isset($job['created_by']) ? (int) $job['created_by'] : null;

        // The worker may have slept long enough for MySQL to drop the connection.
        try { \Config\Database::connect()->reconnect(); } catch (\Throwable $_) {}

        try {
            $archived = self::archiveIds($ids, $reason, $userId);

            JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($jobId, $archived) {
                $idx = self::findIndex($processing['jobs'] ?? [], $jobId);
                if ($idx === null) {
                    return null;
                }
                $rec = $processing['jobs'][$idx];
                $rec['status']       = 'done';
                $rec['result']       = ['archived' => $archived];
                $rec['completed_at'] = date('Y-m-d H:i:s');
                // Ids already archived — drop them so finished.json doesn't carry
                // tens of thousands of ints that every later mutateAll re-encodes.
                $rec['voucher_ids']  = [];

                array_splice($processing['jobs'], $idx, 1);
                $processing['jobs'] = array_values($processing['jobs']);
                $finished['jobs']   = $finished['jobs'] ?? [];
                $finished['jobs'][] = $rec;

                return [$queue, $processing, $finished];
            });

            if ($parentId > 0) {
                self::tryFinalizeParent($parentId);
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[ArchiveRunner] Job {$jobId}: " . $e->getMessage());

            $msg = $e->getMessage();
            JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($jobId, $msg, $parentId) {
                $idx = self::findIndex($processing['jobs'] ?? [], $jobId);
                if ($idx !== null) {
                    $rec = $processing['jobs'][$idx];
                    $rec['status']        = 'failed';
                    $rec['error_message'] = $msg;
                    $rec['completed_at']  = date('Y-m-d H:i:s');

                    array_splice($processing['jobs'], $idx, 1);
                    $processing['jobs'] = array_values($processing['jobs']);
                    $finished['jobs']   = $finished['jobs'] ?? [];
                    $finished['jobs'][] = $rec;
                }

                // Fail the parent too if it's still pending in the queue.
                if ($parentId > 0) {
                    $qIdx = self::findIndex($queue['jobs'] ?? [], $parentId);
                    if ($qIdx !== null) {
                        $parent = $queue['jobs'][$qIdx];
                        $parent['status']        = 'failed';
                        $parent['error_message'] = 'Chunk failed: ' . $msg;
                        $parent['completed_at']  = date('Y-m-d H:i:s');
                        array_splice($queue['jobs'], $qIdx, 1);
                        $queue['jobs']      = array_values($queue['jobs']);
                        $finished['jobs']   = $finished['jobs'] ?? [];
                        $finished['jobs'][] = $parent;
                    }
                }

                return [$queue, $processing, $finished];
            });

            return false;
        }
    }

    /**
     * Archive a single chunk's worth of student ids: copy each into
     * student_archive, null the audit_log FKs, then delete from students.
     * Mirrors Admin\Voucher::archiveStudentsByIds() scoped to the chunk, but
     * WITHOUT the per-student/bulk audit log — that's written once at finalize.
     * Returns the number of rows archived.
     */
    protected static function archiveIds(array $ids, string $reason, ?int $userId): int
    {
        if (empty($ids)) {
            return 0;
        }

        $students     = (new VoucherModel())->getVouchersByIds($ids);
        $archiveModel = new ArchiveModel();
        $db           = \Config\Database::connect();
        $now          = date('Y-m-d H:i:s');

        if (empty($students)) {
            // No live rows matched (e.g. already archived) — nothing to copy, but
            // still release any audit FKs / delete below is a no-op on these ids.
            $db->table('audit_log')->whereIn('student_id', $ids)->update(['student_id' => null]);
            if ($db->fieldExists('voucher_id', 'audit_log')) {
                $db->table('audit_log')->whereIn('voucher_id', $ids)->update(['voucher_id' => null]);
            }
            $db->table('students')->whereIn('student_id', $ids)->delete();
            return 0;
        }

        // Build all rows for this chunk and insert them in ONE multi-row INSERT
        // (insertBatch) instead of 501 individual round-trips — the per-row loop
        // was the dominant DB load on large archives (99k single INSERTs).
        $rows = [];
        foreach ($students as $s) {
            $rows[] = [
                'student_id'                   => $s['student_id'],
                'voucher_no'                   => $s['voucher_no'],
                'voucher_date'                 => $s['voucher_date'],
                'first_name'                   => $s['first_name'],
                'middle_name'                  => $s['middle_name'],
                'last_name'                    => $s['last_name'],
                'suffix'                       => $s['suffix'],
                'rank_no'                      => $s['rank_no'],
                'gwa'                          => $s['gwa'],
                'gender'                       => $s['gender'],
                'junior_high_school'           => $s['junior_high_school'],
                'preferred_senior_high_school' => $s['preferred_senior_high_school'],
                'contact_number'               => $s['contact_number'],
                'remarks_status'               => $s['remarks_status'],
                'school_year'                  => $s['school_year'],
                'eligibility_status'           => $s['eligibility_status'],
                'voucher_status'               => $s['voucher_status'],
                'archive_reason'               => $reason,
                'archived_by'                  => $userId,
                'archived_at'                  => $now,
            ];
        }

        $archiveModel->insertBatch($rows);
        $archived = count($rows);

        // audit_log has FKs to students.student_id (and possibly voucher_id) that
        // block DELETE. Null them in one batch so history is preserved but the FK
        // is released — done only AFTER the archive inserts succeed.
        $db->table('audit_log')->whereIn('student_id', $ids)->update(['student_id' => null]);
        if ($db->fieldExists('voucher_id', 'audit_log')) {
            $db->table('audit_log')->whereIn('voucher_id', $ids)->update(['voucher_id' => null]);
        }

        $db->table('students')->whereIn('student_id', $ids)->delete();

        return $archived;
    }

    /**
     * If every chunk of $parentId is done, sum the archived counts, write one
     * bulk audit row, and move the parent from queue.json into finished.json.
     * If any chunk failed, mark the parent failed instead.
     */
    public static function tryFinalizeParent(int $parentId): bool
    {
        $finished = JsonPdfQueue::read(JsonPdfQueue::FILE_FINISHED);
        $chunks   = [];
        foreach (($finished['jobs'] ?? []) as $job) {
            if ((int) ($job['parent_job_id'] ?? 0) === $parentId && ! empty($job['chunk_index'])) {
                $chunks[] = $job;
            }
        }

        $queue  = JsonPdfQueue::read(JsonPdfQueue::FILE_QUEUE);
        $parent = null;
        foreach (($queue['jobs'] ?? []) as $job) {
            if ((int) $job['job_id'] === $parentId && empty($job['parent_job_id'])) {
                $parent = $job;
                break;
            }
        }
        if (! $parent) {
            return false; // already finalized or never existed
        }

        $total = (int) ($parent['total_chunks'] ?? 0);
        if ($total <= 0 || count($chunks) < $total) {
            return false; // not all chunks done yet
        }

        // Atomically claim the parent so exactly one worker finalizes it.
        $claimed = JsonPdfQueue::mutate(JsonPdfQueue::FILE_QUEUE, function (array $queue) use ($parentId) {
            $idx = self::findIndex($queue['jobs'] ?? [], $parentId);
            if ($idx === null) {
                return null;
            }
            $job = $queue['jobs'][$idx];
            if (! empty($job['parent_job_id']) || ($job['status'] ?? '') !== 'pending') {
                return null;
            }
            $queue['jobs'][$idx]['status'] = 'finalizing';
            return [$queue, true];
        });
        if ($claimed !== true) {
            return false;
        }

        // Did any chunk fail?
        $failedChunk = null;
        $archived    = 0;
        foreach ($chunks as $c) {
            if (($c['status'] ?? '') !== 'done') {
                $failedChunk = $c;
            }
            $archived += (int) ($c['result']['archived'] ?? 0);
        }

        $chunkJobIds = array_map(static fn ($c) => (int) $c['job_id'], $chunks);
        $userId      = isset($parent['created_by']) ? (int) $parent['created_by'] : 0;

        if ($failedChunk) {
            $msg = 'Chunk failed: ' . ($failedChunk['error_message'] ?? 'unknown');
            self::moveParentToFinished($parentId, $chunkJobIds, static function (array $parent) use ($msg, $archived) {
                $parent['status']        = 'failed';
                $parent['error_message'] = $msg;
                $parent['result']        = ['archived' => $archived];
                $parent['completed_at']  = date('Y-m-d H:i:s');
                return $parent;
            });
            return false;
        }

        // One bulk audit row for the whole archive job.
        log_action($userId, 'ARCHIVE_STUDENT', "Bulk archived {$archived} student(s) (queued job #{$parentId})", null);

        self::moveParentToFinished($parentId, $chunkJobIds, static function (array $parent) use ($archived) {
            $parent['status']       = 'done';
            $parent['result']       = ['archived' => $archived];
            $parent['completed_at'] = date('Y-m-d H:i:s');
            return $parent;
        });

        return true;
    }

    /**
     * Move the parent record out of queue.json into finished.json (after running
     * $apply to set its terminal fields) and drop the finished chunk records so
     * the JSON files stay lean. The parent record alone tracks the final result.
     */
    protected static function moveParentToFinished(int $parentId, array $chunkJobIds, callable $apply): void
    {
        JsonPdfQueue::mutateAll(function (array $queue, array $processing, array $finished) use ($parentId, $chunkJobIds, $apply) {
            $idx = self::findIndex($queue['jobs'] ?? [], $parentId);
            if ($idx === null) {
                return null;
            }
            $parent = $apply($queue['jobs'][$idx]);

            array_splice($queue['jobs'], $idx, 1);
            $queue['jobs'] = array_values($queue['jobs']);

            $finished['jobs'] = array_values(array_filter(
                $finished['jobs'] ?? [],
                static fn ($j) => ! in_array((int) ($j['job_id'] ?? 0), $chunkJobIds, true)
            ));
            $finished['jobs'][] = $parent;

            return [$queue, $processing, $finished];
        });
    }

    protected static function findIndex(array $jobs, int $jobId): ?int
    {
        foreach ($jobs as $i => $job) {
            if ((int) $job['job_id'] === $jobId) {
                return $i;
            }
        }
        return null;
    }
}
