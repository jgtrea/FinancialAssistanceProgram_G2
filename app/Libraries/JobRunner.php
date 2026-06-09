<?php

namespace App\Libraries;

/**
 * JobRunner — type-aware dispatcher over the single JSON-file queue.
 *
 * The queue holds jobs of several types ('pdf', 'archive', ...). Claiming is
 * type-agnostic — JsonPdfRunner::claimNextChunk() pulls the next pending chunk
 * of ANY type — so this facade just claims one unit and routes it to the runner
 * that knows how to process that type. The `run:json-pdf-queue` worker drains
 * the queue purely through JobRunner; there is still exactly ONE worker.
 */
class JobRunner
{
    /**
     * Claim and process one chunk of any type. Returns true on success, false on
     * failure, null if there is nothing claimable.
     */
    public static function processOne(): ?bool
    {
        $claimed = JsonPdfRunner::claimNextChunk();
        if ($claimed === null) {
            return null;
        }

        $type = $claimed['type'] ?? 'pdf';

        switch ($type) {
            case 'archive':
                return ArchiveRunner::processClaimed($claimed);
            case 'pdf':
            default:
                return JsonPdfRunner::processClaimed($claimed);
        }
    }

    /**
     * After a drain pass, attempt to finalize any parent still pending in
     * queue.json whose chunks have all completed — dispatched to the right
     * runner by job type.
     */
    public static function finalizePendingParents(): void
    {
        $queue = JsonPdfQueue::read(JsonPdfQueue::FILE_QUEUE);
        foreach (($queue['jobs'] ?? []) as $job) {
            if (! empty($job['parent_job_id']) || ($job['status'] ?? '') !== 'pending') {
                continue;
            }
            $parentId = (int) $job['job_id'];
            $type     = $job['type'] ?? 'pdf';

            switch ($type) {
                case 'archive':
                    ArchiveRunner::tryFinalizeParent($parentId);
                    break;
                case 'pdf':
                default:
                    JsonPdfRunner::tryFinalizeParent($parentId);
                    break;
            }
        }
    }
}
