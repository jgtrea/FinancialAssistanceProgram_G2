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
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => $students,
            'role'     => 'user',
        ]);
    }

    public function generate()
    {
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/generate', [
            'title'    => 'Voucher Generation',
            'vouchers' => $students,
            'role'     => 'user',
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
        ]);
    }

    public function store()
    {
        helper(['form']);

        $rules = [
            'voucher_date'                 => 'required|valid_date',
            'first_name'                   => 'required|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'suffix'                       => 'permit_empty|in_list[JR.,SR.,II,III,IV]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'remarks_status'               => 'permit_empty|in_list[PASSED,FOR REVIEW,FAILED]',
            'school_year'                  => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->create();
        }

        $studentId = (int) $this->voucherModel->insert([
            'voucher_no'                   => null,
            'voucher_date'                 => $this->request->getPost('voucher_date'),
            'first_name'                   => $this->request->getPost('first_name'),
            'middle_name'                  => $this->request->getPost('middle_name') ?: '',
            'last_name'                    => $this->request->getPost('last_name'),
            'suffix'                       => $this->request->getPost('suffix') ?: '',
            'rank_no'                      => $this->request->getPost('rank_no') ?: null,
            'gwa'                          => $this->request->getPost('gwa') ?: null,
            'gender'                       => $this->request->getPost('gender') ?: '',
            'junior_high_school'           => $this->request->getPost('junior_high_school') ?: '',
            'preferred_senior_high_school' => $this->request->getPost('preferred_senior_high_school'),
            'contact_number'               => $this->request->getPost('contact_number') ?: '',
            'remarks_status'               => $this->request->getPost('remarks_status') ?: '',
            'school_year'                  => $this->request->getPost('school_year'),
            'eligibility_status'           => $this->request->getPost('eligibility_status') ?: 'eligible',
            'voucher_status'               => 'not_generated',
            'is_archived'                  => 0,
        ]);

        $name = trim($this->request->getPost('first_name') . ' ' . $this->request->getPost('last_name'));
        log_action(session()->get('user_id'), 'CREATE_STUDENT',
            "Created student {$name}", $studentId);

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
        ]);
    }

    public function update(int $id)
    {
        helper(['form']);

        $rules = [
            'voucher_date'                 => 'required|valid_date',
            'first_name'                   => 'required|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'suffix'                       => 'permit_empty|in_list[JR.,SR.,II,III,IV]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'remarks_status'               => 'permit_empty|in_list[PASSED,FOR REVIEW,FAILED]',
            'school_year'                  => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->edit($id);
        }

        $this->voucherModel->update($id, [
            'voucher_date'                 => $this->request->getPost('voucher_date'),
            'first_name'                   => $this->request->getPost('first_name'),
            'middle_name'                  => $this->request->getPost('middle_name') ?: '',
            'last_name'                    => $this->request->getPost('last_name'),
            'suffix'                       => $this->request->getPost('suffix') ?: '',
            'rank_no'                      => $this->request->getPost('rank_no') ?: null,
            'gwa'                          => $this->request->getPost('gwa') ?: null,
            'gender'                       => $this->request->getPost('gender') ?: '',
            'junior_high_school'           => $this->request->getPost('junior_high_school') ?: '',
            'preferred_senior_high_school' => $this->request->getPost('preferred_senior_high_school'),
            'contact_number'               => $this->request->getPost('contact_number') ?: '',
            'remarks_status'               => $this->request->getPost('remarks_status') ?: '',
            'school_year'                  => $this->request->getPost('school_year'),
            'eligibility_status'           => $this->request->getPost('eligibility_status') ?: 'eligible',
        ]);

        $name = trim($this->request->getPost('first_name') . ' ' . $this->request->getPost('last_name'));
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

            // Soft-delete: flag the live row rather than removing it.
            $this->voucherModel->update($s['student_id'], ['is_archived' => 1]);
            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived successfully.",
        ]);
    }
}
