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

        // Progress: chunked jobs report via chunk counts (snapshot); single jobs
        // (import/export) carry their own progress counter on the record.
        $ownProgress = $parent['progress'] ?? null;
        if (is_array($ownProgress) && (int) ($ownProgress['total'] ?? 0) > 0) {
            $done     = (int) ($ownProgress['done'] ?? 0);
            $total    = max(1, (int) $ownProgress['total']);
            $percent  = (int) floor(($done / $total) * 100);
            $progress = ['done' => $done, 'failed' => 0, 'processing' => 0, 'queued' => 0, 'total' => $total];
        } else {
            $total    = max(1, (int) $snapshot['total']);
            $percent  = (int) floor(($snapshot['done'] / $total) * 100);
            $progress = [
                'done'       => $snapshot['done'],
                'failed'     => $snapshot['failed'],
                'processing' => $snapshot['processing'],
                'queued'     => $snapshot['queued'],
                'total'      => $snapshot['total'],
            ];
        }
        // A done single job is 100% even if its counter wasn't flushed.
        if (($parent['status'] ?? '') === 'done') {
            $percent = 100;
        }

        $result  = $parent['result'] ?? null;
        $message = null;
        if (($parent['status'] ?? '') === 'done') {
            if (isset($result['archived'])) {
                $message = $result['archived'] . ' student(s) archived successfully.';
            } elseif (isset($result['imported'])) {
                $message = $result['imported'] . ' record(s) imported.';
                if (! empty($result['skipped'])) {
                    $message .= ' ' . $result['skipped'] . ' skipped (already exist or invalid).';
                }
            }
        }

        // Export jobs produce a downloadable file.
        $prefix      = session()->get('role') === 'admin' ? 'admin' : 'user';
        $downloadUrl = (($parent['status'] ?? '') === 'done' && ! empty($parent['file_path']))
            ? site_url("{$prefix}/jobs/download/{$jobId}")
            : null;

        return $this->response->setJSON([
            'status'           => $status,
            'progress_percent' => $percent,
            'result'           => $result,
            'message'          => $message,
            'download_url'     => $downloadUrl,
            'error'            => $parent['error_message'] ?? null,
            'progress'         => $progress,
        ]);
    }

    /**
     * Stream a finished job's output file (export xlsx/csv). Owner-or-admin only.
     */
    public function download(int $jobId)
    {
        $found = JsonPdfQueue::findJob($jobId);
        if (! $found || ! empty($found['job']['parent_job_id'])) {
            return redirect()->back()->with('error', 'File not found.');
        }

        $job    = $found['job'];
        $userId = (int) (session()->get('user_id') ?? 0);

        if (session()->get('role') !== 'admin' && (int) ($job['created_by'] ?? 0) !== $userId) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        if (($job['status'] ?? '') !== 'done' || empty($job['file_path'])) {
            return redirect()->back()->with('error', 'File is not ready yet.');
        }

        $filePath = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR . $job['file_path'];
        if (! is_file($filePath)) {
            return redirect()->back()->with('error', 'File is missing from storage.');
        }

        $ext         = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentType = $ext === 'csv'
            ? 'text/csv; charset=UTF-8'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody(file_get_contents($filePath));
    }
}
