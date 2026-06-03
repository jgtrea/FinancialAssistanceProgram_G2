<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Models\StudentArchiveModel;
use App\Models\SchoolOptionModel;
use App\Models\SignatoryModel;
use App\Models\GenerationHistoryModel;

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
        $student = (new StudentModel())->find($id);

        if (!$student) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Student not found.',
            ]);
        }

        $history = (new GenerationHistoryModel())->getRecentForStudent((int) $id);
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
            ]);
        }

        if (!$schoolOptions->juniorHighSchoolExists($this->request->getPost('junior_high_school'))) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please select a valid junior high school.',
            ]);
        }

        if (!$schoolOptions->seniorHighSchoolExists($this->request->getPost('preferred_senior_high_school'))) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please select a valid senior high school.',
            ]);
        }

        if ($studentId && !$studentModel->find($studentId)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Student not found.',
            ]);
        }

        $data = $this->studentPayload();

        if ($studentId) {
            $studentModel->update($studentId, $data);
            $this->writeAuditLog('VOUCHER_UPDATED', 'Updated student ' . $this->formatStudentName($data) . ' (ID #' . $studentId . ').', null, (int) $studentId);
            $message = 'Student updated successfully.';
        } else {
            $newStudentId = $studentModel->insert($data);
            $this->writeAuditLog('VOUCHER_CREATED', 'Created voucher for ' . $this->formatStudentName($data) . ' (ID #' . $newStudentId . ').', null, $newStudentId ? (int) $newStudentId : null);
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
            'voucher_date'                 => 'required|valid_date[Y-m-d]',
            'first_name'                   => 'required|max_length[100]',
            'middle_name'                  => 'permit_empty|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'suffix'                       => 'permit_empty|in_list[JR.,SR.,II,III,IV]',
            'rank_no'                      => 'permit_empty|is_natural_no_zero|less_than_equal_to[999999]',
            'gwa'                          => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'gender'                       => 'permit_empty|in_list[MALE,FEMALE]',
            'junior_high_school'           => 'permit_empty|max_length[200]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'contact_number'               => 'permit_empty|max_length[30]|regex_match[/^[0-9+().\\-\\s]+$/]',
            'remarks_status'               => 'permit_empty|in_list[PASSED,FOR REVIEW,FAILED]',
            'school_year'                  => 'required|max_length[20]|regex_match[/^\\d{4}(-\\d{4})?$/]',
            'eligibility_status'           => 'required|in_list[eligible,not_eligible]',
            'voucher_status'               => 'permit_empty|in_list[not_generated,generated]',
        ];
    }

    private function studentPayload(): array
    {
        $rankNo = trim((string) $this->request->getPost('rank_no'));
        $gwa = trim((string) $this->request->getPost('gwa'));

        return [
            'voucher_no'                   => $this->cleanText($this->request->getPost('voucher_no')) ?: null,
            'voucher_date'                 => $this->request->getPost('voucher_date'),
            'first_name'                   => $this->cleanText($this->request->getPost('first_name')),
            'middle_name'                  => $this->cleanText($this->request->getPost('middle_name')),
            'last_name'                    => $this->cleanText($this->request->getPost('last_name')),
            'suffix'                       => strtoupper($this->cleanText($this->request->getPost('suffix'))),
            'rank_no'                      => $rankNo === '' ? null : (int) $rankNo,
            'gwa'                          => $gwa === '' ? null : (float) $gwa,
            'gender'                       => strtoupper($this->cleanText($this->request->getPost('gender'))),
            'junior_high_school'           => $this->cleanText($this->request->getPost('junior_high_school')),
            'preferred_senior_high_school' => $this->cleanText($this->request->getPost('preferred_senior_high_school')),
            'contact_number'               => $this->cleanText($this->request->getPost('contact_number')),
            'remarks_status'               => strtoupper($this->cleanText($this->request->getPost('remarks_status'))),
            'school_year'                  => $this->cleanText($this->request->getPost('school_year')),
            'eligibility_status'           => $this->request->getPost('eligibility_status') ?: 'eligible',
            'voucher_status'               => $this->request->getPost('voucher_status') ?: 'not_generated',
        ];
    }

    private function cleanText($value): string
    {
        return trim((string) $value);
    }

    private function validationErrorMessage(array $errors, string $fallback): string
    {
        if (empty($errors)) {
            return $fallback;
        }

        return 'Validation failed. Please review the field details below.';
    }
}
