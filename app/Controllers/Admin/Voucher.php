<?php

namespace App\Controllers\Admin;

use App\Libraries\VoucherPdf;
use App\Models\ArchiveModel;
use App\Models\VoucherModel;
use CodeIgniter\Controller;

class Voucher extends Controller
{
    protected VoucherModel $voucherModel;
    protected ArchiveModel $archiveModel;

    public function __construct()
    {
        $this->voucherModel = new VoucherModel();
        $this->archiveModel = new ArchiveModel();
    }

    protected function getFallbackUserId(): int
    {
        $db   = \Config\Database::connect();
        $user = $db->table('users')
            ->select('user_id')
            ->where('is_active', 1)
            ->orderBy('user_id', 'ASC')
            ->limit(1)
            ->get()
            ->getRow();

        return $user->user_id ?? 1;
    }

    protected function getCurrentUserId(): int
    {
        return session()->get('user_id') ?? $this->getFallbackUserId();
    }

    // Accept voucher_ids as either a comma-joined string (preferred — bypasses
    // max_input_vars for large batches) or an array (legacy).
    protected function parseVoucherIds($raw): array
    {
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }
        $ids = array_filter(array_map('intval', $raw), static fn($id) => $id > 0);
        return array_values(array_unique($ids));
    }

    // ── List all students / vouchers ───────────────────────────────────────────
    public function index()
    {
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/index', [
            'title'    => 'Students',
            'vouchers' => $students,
            'role'     => session()->get('role') ?: 'admin',
        ]);
    }

    // ── Show create form ───────────────────────────────────────────────────────
    public function create()
    {
        helper(['form']);

        return view('vouchers/form', [
            'title'      => 'Add Student Voucher',
            'action'     => site_url('admin/vouchers/store'),
            'voucher'    => [],
            'validation' => \Config\Services::validation(),
        ]);
    }

    // ── Persist a new student/voucher ──────────────────────────────────────────
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

        $studentId = (int) $this->voucherModel->insert([
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
            'voucher_status'               => $this->request->getPost('voucher_status') ?: 'not_generated',
            'is_archived'                  => 0,
        ]);

        $name = trim($this->request->getPost('first_name') . ' ' . $this->request->getPost('last_name'));
        log_action($this->getCurrentUserId(), 'CREATE_STUDENT',
            "Created student {$name} (Voucher {$this->request->getPost('voucher_no')})", $studentId);

        return redirect()->to(site_url('admin/vouchers'))->with('message', 'Student voucher created successfully.');
    }

    // ── Show a student/voucher detail page ────────────────────────────────────
    public function view(int $id)
    {
        $student = $this->voucherModel->getStudentById($id);

        if (!$student) {
            return redirect()->to(site_url('admin/vouchers'))->with('error', 'Student not found.');
        }

        return view('vouchers/view', [
            'title'   => 'Voucher Details',
            'voucher' => $student,
            'role'    => session()->get('role') ?: 'admin',
        ]);
    }

    // ── Show edit form ─────────────────────────────────────────────────────────
    public function edit(int $id)
    {
        helper(['form']);

        $student = $this->voucherModel->getStudentById($id);
        if (!$student) {
            return redirect()->to(site_url('admin/vouchers'))->with('error', 'Student not found.');
        }

        return view('vouchers/form', [
            'title'      => 'Edit Student Voucher',
            'action'     => site_url('admin/vouchers/update/' . $id),
            'voucher'    => $student,
            'validation' => \Config\Services::validation(),
        ]);
    }

    // ── Persist student/voucher edits ─────────────────────────────────────────
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
            'voucher_status'               => $this->request->getPost('voucher_status') ?: 'not_generated',
        ]);

        $name = trim($this->request->getPost('first_name') . ' ' . $this->request->getPost('last_name'));
        log_action($this->getCurrentUserId(), 'UPDATE_STUDENT',
            "Updated student {$name} (Voucher {$this->request->getPost('voucher_no')})", $id);

        return redirect()->to(site_url('admin/vouchers'))->with('message', 'Student voucher updated successfully.');
    }

    // ── Queue PDF generation; the spark worker processes the job in the background ─
    public function generatePdf()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $students = $this->voucherModel->getVouchersByIds($ids);

        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Selected students not found.']);
        }

        $userId = $this->getCurrentUserId();
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';
        $jobId  = $this->queuePdfJob($ids, $userId);

        log_action($userId, 'QUEUE_PDF', 'Queued PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("{$prefix}/vouchers/pdf-status/{$jobId}"),
        ]);
    }

    // Insert a pending pdf_jobs row. The polling endpoint (checkPdfJob)
    // will atomically claim and process it on the next /pdf-status/<id> call.
    protected function queuePdfJob(array $ids, int $userId): int
    {
        $db = \Config\Database::connect();
        $db->table('pdf_jobs')->insert([
            'voucher_ids' => json_encode(array_values($ids)),
            'status'      => 'pending',
            'created_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    // ── Poll job status (AJAX GET) ─────────────────────────────────────────────
    // This endpoint doubles as the worker: if the job is still pending, the
    // first poll to land here claims the lease and runs the PDF generation
    // inline before responding. No daemon required.
    public function checkPdfJob(int $jobId)
    {
        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();

        if (!$job) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $userId = $this->getCurrentUserId();

        // Admins can view any job; non-admins only their own.
        if (session()->get('role') !== 'admin' && (int) $job->created_by !== $userId) {
            return $this->response->setJSON(['status' => 'forbidden']);
        }

        // If this job is still pending, try to grab the lease and run it.
        // Subsequent concurrent polls (or other tabs) will lose the race and
        // just see status='processing', so the work runs once.
        if ($job->status === 'pending' && \App\Libraries\PdfJobRunner::tryClaim($jobId)) {
            // Keep running even if the user navigates away or the request times out.
            @ignore_user_abort(true);
            @set_time_limit(0);

            \App\Libraries\PdfJobRunner::process($jobId);

            // Refresh the row to report the new status
            $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        }

        $prefix      = session()->get('role') === 'admin' ? 'admin' : 'user';
        $downloadUrl = $job->status === 'done'
            ? site_url("{$prefix}/vouchers/pdf-download/{$jobId}")
            : null;

        return $this->response->setJSON([
            'status'       => $job->status,
            'download_url' => $downloadUrl,
            'error'        => $job->error_message,
        ]);
    }

    // ── Stream the generated PDF to the browser ────────────────────────────────
    public function downloadPdf(int $jobId)
    {
        $db     = \Config\Database::connect();
        $job    = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        $userId = $this->getCurrentUserId();

        if (!$job || (session()->get('role') !== 'admin' && (int) $job->created_by !== $userId)) {
            return redirect()->back()->with('error', 'PDF not found or access denied.');
        }

        if ($job->status !== 'done') {
            return redirect()->back()->with('error', 'PDF is not ready yet.');
        }

        $filePath = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR . $job->file_path;

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'PDF file is missing from storage.');
        }

        log_action($userId, 'DOWNLOAD_PDF', "Downloaded PDF for job #{$jobId}");

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }

    // ── Archive selected students ─────────────────────────────────────────────
    public function archive()
    {
        $ids    = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        $reason = $this->request->getPost('archive_reason') ?? 'Archived by admin';

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $students = $this->voucherModel->getVouchersByIds($ids);
        $userId   = session()->get('user_id');
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

            log_action($userId, 'ARCHIVE_STUDENT',
                "Student {$s['full_name']} (Voucher {$s['voucher_no']}) archived",
                $s['student_id']);

            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived successfully.",
        ]);
    }

    // ── Save generated PDF bytes to disk and record the job ───────────────────
    protected function savePdfFile(array $ids, int $userId, string $pdfBytes): int
    {
        $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $db = \Config\Database::connect();
        $db->table('pdf_jobs')->insert([
            'voucher_ids' => json_encode(array_values($ids)),
            'status'      => 'pending',
            'created_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $jobId    = (int) $db->insertID();
        $filename = 'vouchers_job' . $jobId . '_' . date('Ymd_His') . '.pdf';

        file_put_contents($dir . $filename, $pdfBytes);

        $db->table('pdf_jobs')
            ->where('job_id', $jobId)
            ->update([
                'status'       => 'done',
                'file_path'    => $filename,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

        return $jobId;
    }
}