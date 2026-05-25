<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Models\StudentArchiveModel;
use App\Models\SignatoryModel;

class StudentController extends BaseController
{
    public function index()
    {
        $studentModel = new StudentModel();

        return view('students/index', [
            'title' => 'Students',
            'students' => $studentModel
                ->where('is_archived', 0)
                ->orderBy('student_id', 'DESC')
                ->findAll()
        ]);
    }

    public function form($id = null)
    {
        $studentModel = new StudentModel();

        $student = null;

        if ($id !== null) {
            $student = $studentModel->find($id);
        }

        return view('students/form', [
            'title' => $student ? 'Edit Student' : 'Add Student',
            'student' => $student
        ]);
    }

    public function save()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request.'
            ]);
        }

        $studentModel = new StudentModel();
        $studentId = $this->request->getPost('student_id');

        $data = [
            'voucher_no'                   => $this->request->getPost('voucher_no') ?: null,
            'voucher_date'                 => $this->request->getPost('voucher_date'),
            'first_name'                   => $this->request->getPost('first_name'),
            'middle_name'                  => $this->request->getPost('middle_name'),
            'last_name'                    => $this->request->getPost('last_name'),
            'suffix'                       => $this->request->getPost('suffix'),
            'rank_no'                      => $this->request->getPost('rank_no'),
            'gwa'                          => $this->request->getPost('gwa'),
            'gender'                       => $this->request->getPost('gender'),
            'junior_high_school'           => $this->request->getPost('junior_high_school'),
            'preferred_senior_high_school' => $this->request->getPost('preferred_senior_high_school'),
            'contact_number'               => $this->request->getPost('contact_number'),
            'remarks_status'               => $this->request->getPost('remarks_status'),
            'school_year'                  => $this->request->getPost('school_year'),
            'eligibility_status'           => $this->request->getPost('eligibility_status'),
            'voucher_status'               => $this->request->getPost('voucher_status') ?? 'not_generated',
        ];

        if ($studentId) {
            $studentModel->update($studentId, $data);
            $this->writeAuditLog('student_updated', 'Updated student ' . $this->formatStudentName($data) . ' (ID #' . $studentId . ').', null, (int) $studentId);
            $message = 'Student updated successfully.';
        } else {
            $newStudentId = $studentModel->insert($data);
            $this->writeAuditLog('student_created', 'Added student ' . $this->formatStudentName($data) . ' (ID #' . $newStudentId . ').', null, $newStudentId ? (int) $newStudentId : null);
            $message = 'Student added successfully.';
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message
        ]);
    }

    public function archive($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request.'
            ]);
        }

        $studentModel = new StudentModel();
        $archiveModel = new StudentArchiveModel();

        $student = $studentModel->find($id);

        if (!$student) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Student not found.'
            ]);
        }

        $userId = session()->get('user_id') ?? 1;

        $archiveModel->insert([
            'student_id' => $student['student_id'],
            'voucher_no' => $student['voucher_no'],
            'voucher_date' => $student['voucher_date'],
            'first_name' => $student['first_name'],
            'middle_name' => $student['middle_name'],
            'last_name' => $student['last_name'],
            'suffix' => $student['suffix'],
            'rank_no' => $student['rank_no'],
            'gwa' => $student['gwa'],
            'gender' => $student['gender'],
            'junior_high_school' => $student['junior_high_school'],
            'preferred_senior_high_school' => $student['preferred_senior_high_school'],
            'contact_number' => $student['contact_number'],
            'remarks_status' => $student['remarks_status'],
            'school_year' => $student['school_year'],
            'eligibility_status' => $student['eligibility_status'],
            'voucher_status' => $student['voucher_status'],
            'archive_reason' => 'Manually archived',
            'archived_by' => $userId,
        ]);

        $studentModel->update($id, [
            'is_archived' => 1
        ]);

        $this->writeAuditLog('student_archived', 'Archived student ' . $this->formatStudentName($student) . ' (ID #' . $id . ').', null, (int) $id);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Student archived successfully.'
        ]);
    }

    public function voucher($id)
    {
        $studentModel = new StudentModel();
        $signatoryModel = new SignatoryModel();

        $student = $studentModel->find($id);

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Student not found.');
        }

        return view('students/voucher', [
            'title' => 'Voucher Preview',
            'student' => $student,
            'signatories' => $signatoryModel
                ->where('is_active', 1)
                ->orderBy('signatory_id', 'ASC')
                ->findAll()
        ]);
    }

    public function markGenerated($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request.'
            ]);
        }

        $studentModel = new StudentModel();

        $student = $studentModel->find($id);
        $voucherNo = $student['voucher_no'] ?? null;

        if (empty($voucherNo)) {
            $voucherNo = generate_voucher_no();
        }

        $studentModel->update($id, [
            'voucher_no'     => $voucherNo,
            'voucher_status' => 'generated',
            'generated_at'   => date('Y-m-d H:i:s'),
        ]);

        $student = $studentModel->find($id);
        $this->writeAuditLog(
            'voucher_marked_generated',
            'Marked voucher ' . ($student['voucher_no'] ?? 'for student ID #' . $id) . ' as generated for ' . ($student ? $this->formatStudentName($student) : 'student ID #' . $id) . '.',
            null,
            (int) $id
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Voucher marked as generated.'
        ]);
    }

    private function formatStudentName(array $student): string
    {
        $name = trim(
            ($student['first_name'] ?? '') . ' ' .
            ($student['middle_name'] ?? '') . ' ' .
            ($student['last_name'] ?? '') . ' ' .
            ($student['suffix'] ?? '')
        );

        return $name !== '' ? $name : 'Unnamed student';
    }
}
