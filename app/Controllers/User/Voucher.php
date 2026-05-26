<?php

namespace App\Controllers\User;

use App\Libraries\VoucherPdf;
use App\Controllers\Admin\Voucher as AdminVoucher;

class Voucher extends AdminVoucher
{
    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $students = $this->voucherModel->getVouchersForListing($keyword);

        return view('vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => $students,
            'role'     => 'user',
            'keyword'  => $keyword,
        ] + $this->getSchoolDropdownData());
    }

    public function generate()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $students = $this->voucherModel->getVouchersForListing($keyword);

        return view('vouchers/generate', [
            'title'    => 'Voucher Generation',
            'vouchers' => $students,
            'role'     => 'user',
            'keyword'  => $keyword,
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
            'is_archived'                  => 0,
        ];

        $studentId = (int) $this->voucherModel->insert($data);

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
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

    public function generatePdf()
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

        $jobId = $this->queuePdfJob($ids, $userId);
        log_action($userId, 'QUEUE_PDF', 'Queued PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("user/vouchers/pdf-status/{$jobId}"),
            'vouchers'   => array_column($students, 'voucher_no', 'student_id'),
        ]);
    }

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

            $this->voucherModel->update($s['student_id'], ['is_archived' => 1]);
            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived successfully.",
        ]);
    }
}
