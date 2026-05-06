<?php

namespace App\Controllers;

use App\Models\StudentModel;

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
        $studentModel = new StudentModel();

        $studentId = $this->request->getPost('student_id');

        $data = [
            'voucher_no' => $this->request->getPost('voucher_no'),
            'voucher_date' => $this->request->getPost('voucher_date'),
            'full_name' => $this->request->getPost('full_name'),
            'rank_no' => $this->request->getPost('rank_no'),
            'gwa' => $this->request->getPost('gwa'),
            'gender' => $this->request->getPost('gender'),
            'junior_high_school' => $this->request->getPost('junior_high_school'),
            'preferred_senior_high_school' => $this->request->getPost('preferred_senior_high_school'),
            'contact_number' => $this->request->getPost('contact_number'),
            'remarks_status' => $this->request->getPost('remarks_status'),
            'school_year' => $this->request->getPost('school_year'),
            'eligibility_status' => $this->request->getPost('eligibility_status'),
        ];

        if ($studentId) {
            $studentModel->update($studentId, $data);
            $message = 'Student updated successfully.';
            $this->writeAuditLog(
                'student_updated',
                'Updated student: ' . ($data['full_name'] ?: 'Student #' . $studentId)
            );
        } else {
            $newStudentId = $studentModel->insert($data);
            $message = 'Student added successfully.';
            $this->writeAuditLog(
                'student_added',
                'Added student: ' . ($data['full_name'] ?: 'Student #' . $newStudentId)
            );
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message
            ]);
        }

        return redirect()->to('/students')->with('success', $message);
    }

    public function delete($id)
{
    if (!$this->request->isAJAX()) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Invalid request.'
        ]);
    }

    $studentModel = new \App\Models\StudentModel();
    $archiveModel = new \App\Models\StudentArchiveModel();

    $student = $studentModel->find($id);

    if (!$student) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Student not found.'
        ]);
    }

    // archive snapshot
    $archiveModel->insert([
        'voucher_id' => null,
        'voucher_no' => $student['voucher_no'],
        'recipient_name' => $student['full_name'],
        'senior_high_school' => $student['preferred_senior_high_school'],
        'amount_in_words' => 'TEN THOUSAND PESOS ONLY',
        'amount' => 10000,
        'school_year' => $student['school_year'],
        'voucher_status' => 'not_generated',
        'archive_reason' => 'Manually archived',
        'archived_by' => 1,
    ]);

    // soft archive only
    $studentModel->update($id, [
        'is_archived' => 1
    ]);

    $this->writeAuditLog(
        'student_archived',
        'Archived student: ' . ($student['full_name'] ?: 'Student #' . $id)
    );

    return $this->response->setJSON([
        'status' => 'success',
        'message' => 'Student archived successfully.'
    ]);
}
}
