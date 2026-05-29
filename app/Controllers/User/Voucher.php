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
        $keyword  = trim((string) $this->request->getGet('q'));
        $filters  = $this->getListingFilters();
        $students = $this->voucherModel->getVouchersForListing(
            $keyword,
            \App\Models\VoucherModel::LISTING_DEFAULT_LIMIT,
            $filters
        );

        return view('vouchers/index', [
            'title'         => 'Vouchers',
            'vouchers'      => $students,
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
     * User-side bulk archive. Same snapshot-then-soft-delete pattern as
     * Admin\Voucher::archive(), minus the per-row audit log entry. Default
     * reason is "Archived by user" instead of "Archived by admin".
     */
    public function archive()
    {
        $ids    = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        $userId = session()->get('user_id');
        $reason = $this->request->getPost('archive_reason') ?? 'Archived by user';

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $students = $this->voucherModel->getVouchersByIds($ids);
        $now      = date('Y-m-d H:i:s');
        $archived = 0;

        foreach ($students as $s) {
            // Snapshot into archived_students so the archive survives later
            // edits/deletes of the live row.
            $this->archiveModel->insert([
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
            ]);

            $this->voucherModel->delete((int) $s['student_id']);
            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived successfully.",
        ]);
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
