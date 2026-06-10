<?php

namespace App\Controllers;

use App\Libraries\JsonPdfQueue;
use App\Models\VoucherModel;

/**
 * VoucherImport — student import/export. Both run on the background worker now:
 *   import_data    → save upload, enqueue an 'import' job (ImportRunner parses,
 *                    validates, and inserts; errors surface in the job result).
 *   vouchers/export→ enqueue an 'export' job (ExportRunner builds the xlsx/csv);
 *                    the browser polls, then downloads via jobs/download/{id}.
 *
 * The heavy work used to run inline and timed out large files. The controller
 * now returns instantly with a job to poll, matching the generate/archive flow.
 */
class VoucherImport extends BaseController
{
    public function index()
    {
        return view('FileConvertView');
    }

    public function import()
    {
        $file = $this->request->getFile('excel_file');

        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Please upload a valid file.']);
        }

        $ext = strtolower($file->getClientExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only .xlsx, .xls, or .csv files are allowed.']);
        }

        // The PHP temp upload is deleted at request end, so move it somewhere the
        // worker can read across requests.
        $clientName = $file->getClientName();
        $dir        = WRITEPATH . 'imports' . DIRECTORY_SEPARATOR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $stored = 'import_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        try {
            $file->move($dir, $stored);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to store upload: ' . $e->getMessage()]);
        }

        $userId = (int) session()->get('user_id');
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';

        $jobId = JsonPdfQueue::enqueueSingle('import', [
            'file_path'     => $dir . $stored,
            'original_name' => $clientName,
        ], $userId);

        log_action($userId, 'QUEUE_IMPORT', 'Queued import "' . $clientName . '" (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("{$prefix}/jobs/status/{$jobId}"),
        ]);
    }

    public function export()
    {
        $format = $this->request->getGet('format') ?? 'xlsx';
        if (! in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }

        $ids = $this->parseSelectedIds((string) $this->request->getGet('ids'));

        // No explicit selection — fall back to "everything matching the
        // current search/filter scope" (the toolbar Export button). An empty
        // scope (no q, no filters) leaves $ids empty, which ExportRunner
        // already treats as "export everything".
        if (empty($ids)) {
            $keyword = trim((string) $this->request->getGet('q'));
            $filters = [];
            foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
                $filters[$key] = trim((string) $this->request->getGet($key));
            }

            $hasScope = $keyword !== '';
            foreach ($filters as $value) {
                if ($value !== '') {
                    $hasScope = true;
                    break;
                }
            }

            if ($hasScope) {
                $ids = (new VoucherModel())->getMatchingStudentIds($keyword, $filters);
            }
        }

        $userId = (int) session()->get('user_id');
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';

        $jobId = JsonPdfQueue::enqueueSingle('export', [
            'ids'    => array_values($ids),
            'format' => $format,
        ], $userId);

        log_action($userId, 'QUEUE_EXPORT', 'Queued export (' . $format . ') (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("{$prefix}/jobs/status/{$jobId}"),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function parseSelectedIds(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $ids = array_filter(array_map('intval', explode(',', $raw)), static fn ($id) => $id > 0);
        return array_values(array_unique($ids));
    }
}
