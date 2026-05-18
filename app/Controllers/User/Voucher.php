<?php

namespace App\Controllers\User;

use App\Libraries\VoucherPdf;
use App\Controllers\Admin\Voucher as AdminVoucher;

class Voucher extends AdminVoucher
{
    public function index()
    {
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/index', [
            'title'    => 'Students',
            'vouchers' => $students,
            'role'     => 'user',
        ]);
    }

    public function create()
    {
        helper(['form']);

        return view('vouchers/form', [
            'title'      => 'Add Student',
            'action'     => site_url('user/students/store'),
            'voucher'    => [],
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function store()
    {
        helper(['form']);

        $rules = [
            'voucher_no'                   => 'required|max_length[50]',
            'voucher_date'                 => 'required|valid_date',
            'first_name'                   => 'required|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'school_year'                  => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->create();
        }

        $this->voucherModel->insert([
            'voucher_no'                   => $this->request->getPost('voucher_no'),
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
            'title'      => 'Edit Student',
            'action'     => site_url('user/students/update/' . $id),
            'voucher'    => $student,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function update(int $id)
    {
        helper(['form']);

        $rules = [
            'voucher_no'                   => 'required|max_length[50]',
            'voucher_date'                 => 'required|valid_date',
            'first_name'                   => 'required|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'school_year'                  => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->edit($id);
        }

        $this->voucherModel->update($id, [
            'voucher_no'                   => $this->request->getPost('voucher_no'),
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

        return redirect()->to(site_url('user/students'))->with('message', 'Student updated successfully.');
    }

    public function generatePdf()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id');

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
        $students = $this->voucherModel->getVouchersByIds($ids);

        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid students found.']);
        }

        try {
            $pdfBytes   = VoucherPdf::generate($students);
            $studentIds = array_column($students, 'student_id');
            $jobId      = $this->savePdfFile($studentIds, $userId, $pdfBytes);

            \Config\Database::connect()
                ->table('students')
                ->whereIn('student_id', $studentIds)
                ->update(['voucher_status' => 'generated']);

            return $this->response->setJSON([
                'success'      => true,
                'download_url' => site_url('user/students/pdf-download/' . $jobId),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[generatePdf user] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
        }
    }

    public function archive()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id');
        $reason = $this->request->getPost('archive_reason') ?? 'Archived by user';

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
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
