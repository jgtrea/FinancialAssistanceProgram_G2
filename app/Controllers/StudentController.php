<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Models\StudentArchiveModel;
use App\Models\SchoolOptionModel;
use App\Models\SignatoryModel;
use App\Models\GenerationHistoryModel;
use App\Models\VoucherModel;

class StudentController extends BaseController
{
    public function index()
    {
        $studentModel = new StudentModel();

        return view('students/index', [
            'title' => 'Students',
            'students' => $studentModel
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
            'student' => $student,
            'juniorHighSchools' => (new SchoolOptionModel())->getJuniorHighSchools(),
            'seniorHighSchools' => (new SchoolOptionModel())->getSeniorHighSchools(),
        ]);
    }

    public function getJson($id)
    {
        $student = (new VoucherModel())->getStudentById((int) $id);

        if (!$student) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Student not found.',
            ]);
        }

        $history = (new GenerationHistoryModel())->getRecentForStudent((int) $id, 0); // 0 = all
        $latest  = $history[0] ?? null;

        $student['last_generated_by'] = $latest['full_name'] ?? null;
        $student['last_generated_at'] = $latest['generated_at'] ?? ($student['generated_at'] ?? null);
        $student['generation_history'] = array_map(static function (array $row): array {
            return [
                'generated_by' => $row['full_name'] ?? null,
                'generated_at' => $row['generated_at'] ?? null,
                'source'       => $row['generation_source'] ?? null,
            ];
        }, $history);

        return $this->response->setJSON([
            'status'  => 'success',
            'student' => $student,
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
        $schoolOptions = new SchoolOptionModel();
        $studentId = $this->request->getPost('student_id');

        $validation = \Config\Services::validation();
        $validation->setRules($this->studentValidationRules());

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validationErrorMessage($errors, 'Please check the student details.'),
                'errors' => $errors,
                'csrf_token' => csrf_hash(),
            ]);
        }

        $remarksStatus = strtoupper($this->cleanText($this->request->getPost('remarks_status')));
        if ($remarksStatus === 'OTHERS' && $this->cleanText($this->request->getPost('other_remarks')) === '') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please enter the other remarks.',
                'errors' => ['other_remarks' => 'The Other Remarks field is required when Remarks is OTHERS.'],
                'csrf_token' => csrf_hash(),
            ]);
        }

        if (!$schoolOptions->juniorHighSchoolExists($this->request->getPost('junior_high_school'))) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please select a valid junior high school.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        if (!$schoolOptions->seniorHighSchoolExists($this->request->getPost('preferred_senior_high_school'))) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please select a valid senior high school.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $existingStudent = null;
        if ($studentId) {
            $existingStudent = $studentModel->find($studentId);
        }

        if ($studentId && !$existingStudent) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Student not found.',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $data = $this->studentPayload($existingStudent);

        if ($studentId) {
            $studentModel->update($studentId, $data);
            $this->writeAuditLog('VOUCHER_UPDATED', 'Updated student ' . $this->formatStudentName($data) . ' (ID #' . $studentId . ').', null, (int) $studentId);
            $message = 'Student updated successfully.';
        } else {
            $data['is_active'] = 1;
            $newStudentId = $studentModel->insert($data);
            $this->writeAuditLog('VOUCHER_CREATED', 'Created voucher for ' . $this->formatStudentName($data) . ' (ID #' . $newStudentId . ').', null, $newStudentId ? (int) $newStudentId : null);
            $message = 'Student added successfully.';
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message,
            'csrf_token' => csrf_hash(),
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

        $now = date('Y-m-d H:i:s');

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
            'other_remarks' => $student['other_remarks'] ?? null,
            'school_year' => $this->archiveSchoolYearLabel($now),
            // 'eligibility_status' => $student['eligibility_status'],
            'voucher_status' => $student['voucher_status'],
            'archive_reason' => 'Manually archived',
            'archived_by' => $userId,
            'archived_at' => $now,
        ]);

        $db = \Config\Database::connect();
        $db->table('audit_log')->where('student_id', $id)->update(['student_id' => null]);
        $studentModel->delete($id);

        $this->writeAuditLog('student_archived', 'Archived student ' . $this->formatStudentName($student) . ' (ID #' . $id . ').', null, null);

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
            $jhs  = $student['junior_high_school'] ?? '';
            $year = !empty($student['voucher_date'])
                ? date('Y', strtotime($student['voucher_date']))
                : date('Y');
            $voucherNo = generate_voucher_no($jhs, $year);
        }

        $generatedAt = date('Y-m-d H:i:s');

        $studentModel->update($id, [
            'voucher_no'     => $voucherNo,
            'voucher_status' => 'generated',
            'generated_at'   => $generatedAt,
        ]);

        $db = \Config\Database::connect();
        if ($db->fieldExists('generate_count', 'students')) {
            $db->query('UPDATE students SET generate_count = generate_count + 1 WHERE student_id = ?', [(int) $id]);
        }

        $student = $studentModel->find($id);
        (new GenerationHistoryModel())->recordMany(
            [$student],
            (int) (session()->get('user_id') ?? 1),
            null,
            'manual',
            $generatedAt
        );
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

    private function studentValidationRules(): array
    {
        return [
            'voucher_no'                   => 'permit_empty|max_length[50]',
            'control_no'                   => 'permit_empty|max_length[50]',
            'voucher_date'                 => 'permit_empty|valid_date[Y-m-d]',
            'first_name'                   => 'required|max_length[100]',
            'middle_name'                  => 'permit_empty|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'suffix'                       => 'permit_empty|in_list[JR.,SR.,II,III,IV]',
            'rank_no'                      => 'required|decimal|greater_than[0]|less_than_equal_to[999999]',
            'gwa'                          => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'gender'                       => 'permit_empty|in_list[MALE,FEMALE]',
            'junior_high_school'           => 'permit_empty|max_length[200]',
            'preferred_senior_high_school' => 'permit_empty|max_length[200]',
            'contact_number'               => 'permit_empty|max_length[30]|regex_match[/^[0-9+().\\-\\s]+$/]',
            'remarks_status'               => 'permit_empty|in_list[COMPLETE,INCOMPLETE,OTHERS]',
            'other_remarks'                 => 'permit_empty|max_length[255]',
            // 'eligibility_status'           => 'required|in_list[eligible,not_eligible]',
            'voucher_status'               => 'permit_empty|in_list[not_generated,generated]',
        ];
    }

    private function studentPayload(?array $existingStudent = null): array
    {
        $rankNo = trim((string) $this->request->getPost('rank_no'));
        $gwa = trim((string) $this->request->getPost('gwa'));
        $remarksStatus = strtoupper($this->cleanText($this->request->getPost('remarks_status')));
        $otherRemarks = $this->cleanText($this->request->getPost('other_remarks'));
        $existingVoucherStatus = $this->cleanText($existingStudent['voucher_status'] ?? '');

        return [
            'voucher_no'                   => $this->cleanText($this->request->getPost('voucher_no')) ?: null,
            'control_no'                   => $this->cleanText($this->request->getPost('control_no')) ?: null,
            'voucher_date'                 => $this->request->getPost('voucher_date') ?: null,
            'first_name'                   => $this->cleanText($this->request->getPost('first_name')),
            'middle_name'                  => $this->cleanText($this->request->getPost('middle_name')),
            'last_name'                    => $this->cleanText($this->request->getPost('last_name')),
            'suffix'                       => strtoupper($this->cleanText($this->request->getPost('suffix'))),
            'rank_no'                      => $rankNo === '' ? null : (float) $rankNo,
            'gwa'                          => $gwa === '' ? null : (float) $gwa,
            'gender'                       => strtoupper($this->cleanText($this->request->getPost('gender'))),
            'junior_high_school'           => (new SchoolOptionModel())->resolveSchoolId('JHS', $this->request->getPost('junior_high_school'), true),
            'preferred_senior_high_school' => (new SchoolOptionModel())->resolveSchoolId('SHS', $this->request->getPost('preferred_senior_high_school'), true),
            'contact_number'               => $this->cleanText($this->request->getPost('contact_number')),
            'remarks_status'               => $remarksStatus,
            'other_remarks'                 => $remarksStatus === 'OTHERS' ? $otherRemarks : null,
            // 'eligibility_status'           => $this->request->getPost('eligibility_status') ?: 'eligible',
            'voucher_status'               => $existingVoucherStatus !== '' ? $existingVoucherStatus : 'not_generated',
        ];
    }

    private function cleanText($value): string
    {
        return trim((string) $value);
    }

    private function archiveSchoolYearLabel(?string $archivedAt = null): string
    {
        $timestamp = strtotime($archivedAt ?: 'now') ?: time();
        $year      = (int) date('Y', $timestamp);
        $month     = (int) date('n', $timestamp);
        $startYear = $month >= 6 ? $year : $year - 1;

        return $startYear . '-' . ($startYear + 1);
    }

    private function validationErrorMessage(array $errors, string $fallback): string
    {
        if (empty($errors)) {
            return $fallback;
        }

        return 'Validation failed. Please review the field details below.';
    }
}
