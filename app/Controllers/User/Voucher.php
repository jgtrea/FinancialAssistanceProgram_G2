<?php

namespace App\Controllers\User;

use App\Libraries\VoucherPdf;
use App\Controllers\Admin\Voucher as AdminVoucher;

/**
 * User-side voucher controller. Extends Admin\Voucher and reuses its queue +
 * polling + finalize plumbing (queuePdfJob, parseVoucherIds,
 * prepareStudentsForGeneration, checkPdfJob, downloadPdf, ...). The overrides
 * here only differ in:
 *   - URL prefix (`user/` instead of `admin/`)
 *   - Hard-coded role 'user' in view payloads
 *   - Skipping the audit-log entry on create/update (user-side flows log
 *     simpler messages)
 *
 * The actual PDF generation pipeline is identical — see the parent class
 * docblock for the full flow.
 */
class Voucher extends AdminVoucher
{
    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = $this->getListingFilters();

        // Server-side DataTables — see Admin\Voucher::index for the same note.
        return view('vouchers/index', [
            'title'         => 'Vouchers',
            'vouchers'      => [],
            'role'          => 'user',
            'keyword'       => $keyword,
            'filters'       => $filters,
            'filterOptions' => $this->voucherModel->getListingFilterOptions(),
        ] + $this->getSchoolDropdownData());
    }

    public function generate()
    {
        $keyword  = trim((string) $this->request->getGet('q'));
        $filters  = $this->getListingFilters();
        $students = $this->voucherModel->getVouchersForListing(
            $keyword,
            \App\Models\VoucherModel::LISTING_DEFAULT_LIMIT,
            $filters
        );

        return view('vouchers/generate', [
            'title'    => 'Voucher Generation',
            'vouchers' => $students,
            'role'     => 'user',
            'keyword'  => $keyword,
            'filters'  => $filters,
        ]);
    }

    public function create()
    {
        helper(['form']);

        return view('vouchers/form', [
            'title'      => 'Add Student',
            'action'     => site_url('user/vouchers/store'),
            'voucher'    => [],
            'validation' => \Config\Services::validation(),
        ] + $this->getSchoolDropdownData());
    }

    public function store()
    {
        helper(['form']);

        if (!$this->validateStudentInput()) {
            return $this->create();
        }

        $data = $this->getStudentPayload() + [
            'voucher_no'                   => null,
            'voucher_status'               => 'not_generated',
            'is_active'                    => 1,
        ];

        $studentId = (int) $this->voucherModel->insert($data);

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        log_action(session()->get('user_id'), 'CREATE_VOUCHER',
            "Created voucher for {$name}", $studentId);

        return redirect()->to(site_url('user/students'))->with('message', 'Student added successfully.');
    }

    public function edit(int $id)
    {
        helper(['form']);

        $student = $this->voucherModel->getStudentById($id);
        if (!$student) {
            return redirect()->to(site_url('user/students'))->with('error', 'Student not found.');
        }

        return view('vouchers/form', [
            'title'      => 'Update Voucher',
            'action'     => site_url('user/vouchers/update/' . $id),
            'voucher'    => $student,
            'validation' => \Config\Services::validation(),
        ] + $this->getSchoolDropdownData());
    }

    public function update(int $id)
    {
        helper(['form']);

        if (!$this->validateStudentInput()) {
            return $this->edit($id);
        }

        $data = $this->getStudentPayload();
        $this->voucherModel->update($id, $data);

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        log_action(session()->get('user_id'), 'UPDATE_STUDENT',
            "Updated student {$name}", $id);

        return redirect()->to(site_url('user/students'))->with('message', 'Student updated successfully.');
    }

    /**
     * User-side enqueue. Same flow as Admin\Voucher::generatePdf() — see that
     * parent docblock — but always routes the status URL through the `user/`
     * prefix regardless of session role.
     */
    public function generatePdf()
    {
        $ids    = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        $userId = session()->get('user_id');

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        // Assign voucher numbers up front (fast DB op); the slow PDF render
        // is queued for background processing.
        $students = $this->prepareStudentsForGeneration($ids);

        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid students found.']);
        }

        $jobId = $this->queuePdfJob($ids, $userId);
        log_action($userId, 'QUEUE_PDF', 'Queued PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("user/vouchers/pdf-status/{$jobId}"),
            // student_id → voucher_no map so the UI can render new numbers
            // without an extra round-trip.
            'vouchers'   => array_column($students, 'voucher_no', 'student_id'),
        ]);
    }

    /**
     * User-side JSON-queue enqueue. Forces the status URL through the user/
     * prefix regardless of session role.
     */
    public function generateJsonPdf()
    {
        $ids    = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        $userId = session()->get('user_id');

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $students = $this->prepareStudentsForGeneration($ids);
        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid students found.']);
        }

        $jobId = \App\Libraries\JsonPdfQueue::enqueueJob($ids, (int) $userId, AdminVoucher::CHUNK_SIZE);
        log_action($userId, 'QUEUE_PDF_JSON', 'Queued JSON-PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("user/vouchers/json-pdf-status/{$jobId}"),
            'vouchers'   => array_column($students, 'voucher_no', 'student_id'),
        ]);
    }

    /**
     * User-side bulk archive. Same snapshot-then-soft-delete pattern as
     * Admin\Voucher::archive(), minus the per-row audit log entry. Default
     * reason is "Archived by user" instead of "Archived by admin".
     */
    public function archive()
    {
        $ids    = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        $reason = $this->request->getPost('archive_reason') ?: 'Archived by user';

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        // Queue the archive for the background worker (ArchiveRunner) instead of
        // looping inline — same path as Admin. enqueueArchiveJob() picks the
        // 'user/' status prefix from the session role.
        return $this->enqueueArchiveJob($ids, $reason);
    }

    // ── Preview archive scope: count + distinct school years ─────────────────
    public function archivePreview()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = [];
        foreach (\App\Models\VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getGet($key));
        }

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        if (empty($ids)) {
            return $this->response->setJSON([
                'success'     => true,
                'count'       => 0,
                'schoolYears' => [],
            ]);
        }

        $rows = \Config\Database::connect()
            ->table('students')
            ->select('school_year')
            ->distinct()
            ->whereIn('student_id', $ids)
            ->where('school_year !=', '')
            ->orderBy('school_year', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'success'     => true,
            'count'       => count($ids),
            'schoolYears' => array_column($rows, 'school_year'),
        ]);
    }
}
