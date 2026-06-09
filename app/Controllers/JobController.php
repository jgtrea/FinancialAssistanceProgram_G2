<?php

namespace App\Controllers;

use App\Libraries\JsonPdfQueue;

/**
 * JobController — generic status endpoint for any background queue job
 * (archive today; other types as they move to the worker). The browser polls
 * GET jobs/status/{id} after enqueuing; the response is the same shape the PDF
 * poller already understands (status / progress / download_url / error) plus a
 * `result` blob (e.g. archived count) and a human-readable `message`.
 */
class JobController extends BaseController
{
    public function status(int $jobId)
    {
        $snapshot = JsonPdfQueue::snapshot($jobId);

        if (! $snapshot) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $parent = $snapshot['parent'];
        $userId = (int) (session()->get('user_id') ?? 0);

        // Owner-or-admin guard, mirroring jsonPdfStatus().
        if (session()->get('role') !== 'admin' && (int) ($parent['created_by'] ?? 0) !== $userId) {
            return $this->response->setJSON(['status' => 'forbidden']);
        }

        $status = $parent['status'] ?? 'pending';

        // Refine a still-"pending" parent into queued vs processing for the UI.
        if ($status === 'pending') {
            if ($snapshot['processing'] > 0 || ($snapshot['done'] > 0 && $snapshot['done'] < $snapshot['total'])) {
                $status = 'processing';
            } else {
                $status = 'queued';
            }
        } elseif ($status === 'finalizing') {
            $status = 'processing';
        }

        $total   = max(1, (int) $snapshot['total']);
        $percent = (int) floor(($snapshot['done'] / $total) * 100);

        $result  = $parent['result'] ?? null;
        $message = null;
        if (($parent['status'] ?? '') === 'done' && isset($result['archived'])) {
            $message = $result['archived'] . ' student(s) archived successfully.';
        }

        return $this->response->setJSON([
            'status'           => $status,
            'progress_percent' => $percent,
            'result'           => $result,
            'message'          => $message,
            'download_url'     => null,
            'error'            => $parent['error_message'] ?? null,
            'progress'         => [
                'done'       => $snapshot['done'],
                'failed'     => $snapshot['failed'],
                'processing' => $snapshot['processing'],
                'queued'     => $snapshot['queued'],
                'total'      => $snapshot['total'],
            ],
        ]);
    }
}
