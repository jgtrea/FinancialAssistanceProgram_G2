<?php

namespace App\Controllers\Admin;

use App\Libraries\VoucherPdf;
use App\Models\ArchiveModel;
use App\Models\SchoolOptionModel;
use App\Models\VoucherModel;
use CodeIgniter\Controller;

/**
 * Admin-side voucher controller. Mirrored by App\Controllers\User\Voucher
 * (which extends this class) — the only differences are URL prefix and
 * authorization scope.
 *
 * Endpoint groups:
 *   - CRUD: index, generate (selection screen), create, store, view, edit, update
 *   - PDF queue: generatePdf (enqueue), checkPdfJob (poll + opportunistic worker),
 *                downloadPdf (stream result)
 *   - Bulk archive: archive
 *   - Internals: parseVoucherIds, queuePdfJob, prepareStudentsForGeneration, ...
 *
 * The PDF generation flow:
 *   1. User selects students → POSTs IDs to generatePdf().
 *   2. prepareStudentsForGeneration() mints missing voucher_no values.
 *   3. queuePdfJob() inserts one parent row + N CHUNK_SIZE chunk rows.
 *   4. Frontend polls checkPdfJob() — each poll claims & renders one chunk inline.
 *   5. When all chunks are done, tryFinalize() assembles a PDF (1 chunk) or ZIP (multi).
 *   6. downloadPdf() streams the final file to the browser.
 */
class Voucher extends Controller
{
    protected VoucherModel $voucherModel;
    protected ArchiveModel $archiveModel;
    protected SchoolOptionModel $schoolOptionModel;

    public function __construct()
    {
        // Models are `new`'d rather than injected — CI4 doesn't auto-inject.
        $this->voucherModel = new VoucherModel();
        $this->archiveModel = new ArchiveModel();
    }

    /**
     * Returns the oldest active user id, or 1, when no session user is set
     * (useful for local testing without login). All audit logs go through
     * getCurrentUserId() so they always have a non-null user id.
     */
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

    /**
     * Single source of truth for "who did this" used by every audit log call.
     */
    protected function getCurrentUserId(): int
    {
        return session()->get('user_id') ?? $this->getFallbackUserId();
    }

    // Accept voucher_ids as either a comma-joined string (preferred — bypasses
    // max_input_vars for large batches) or an array (legacy).
    protected function parseVoucherIds($raw): array
    {
        // String → explode on commas (the large-batch path).
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }
        // Cast each to int (intval('abc')=0) and drop zero/negative values,
        // then dedupe + reindex so callers get a clean 0-based list.
        $ids = array_filter(array_map('intval', $raw), static fn($id) => $id > 0);
        return array_values(array_unique($ids));
    }

    // ── List all students / vouchers ───────────────────────────────────────────
    public function index()
    {
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => $students,
            'role'     => session()->get('role') ?: 'admin',
        ] + $this->getSchoolDropdownData());
    }

    /**
     * Selection screen for the bulk-generate flow — same data as index(),
     * different view template.
     */
    public function generate()
    {
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/generate', [
            'title'    => 'Voucher Generation',
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
        ] + $this->getSchoolDropdownData());
    }

    // ── Persist a new student/voucher ──────────────────────────────────────────
    public function store()
    {
        helper(['form']);

        if (!$this->validateStudentInput()) {
            return $this->create();
        }

        $data = $this->getStudentPayload() + [
            // voucher_no is deliberately null — assigned later by
            // generate_voucher_no() the first time a PDF is generated.
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
        ];

        $studentId = (int) $this->voucherModel->insert($data);

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        log_action($this->getCurrentUserId(), 'CREATE_STUDENT',
            "Created student {$name}", $studentId);

        return redirect()->to(site_url('admin/students'))->with('message', 'Student voucher created successfully.');
    }

    // ── Show a student/voucher detail page ────────────────────────────────────
    public function view(int $id)
    {
        $student = $this->voucherModel->getStudentById($id);

        if (!$student) {
            return redirect()->to(site_url('admin/students'))->with('error', 'Student not found.');
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
            return redirect()->to(site_url('admin/students'))->with('error', 'Student not found.');
        }

        return view('vouchers/form', [
            'title'      => 'Update Voucher',
            'action'     => site_url('admin/vouchers/update/' . $id),
            'voucher'    => $student,
            'validation' => \Config\Services::validation(),
        ] + $this->getSchoolDropdownData());
    }

    // ── Persist student/voucher edits ─────────────────────────────────────────
    public function update(int $id)
    {
        helper(['form']);

        if (!$this->validateStudentInput()) {
            return $this->edit($id);
        }

        // Note: voucher_no, voucher_status and is_archived are intentionally
        // omitted — they're system-managed, not user-editable here.
        $data = $this->getStudentPayload();
        $this->voucherModel->update($id, $data);

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        log_action($this->getCurrentUserId(), 'UPDATE_STUDENT',
            "Updated student {$name}", $id);

        return redirect()->to(site_url('admin/students'))->with('message', 'Student voucher updated successfully.');
    }

    // ── Queue PDF generation; the spark worker processes the job in the background ─
    /**
     * Enqueue a PDF generation job for the selected students. Returns JSON
     * with a job id and the URL the frontend should poll for status.
     * Does NOT render anything itself — rendering happens in
     * PdfJobRunner::process(), driven by the poll or the spark worker.
     */
    public function generatePdf()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        // Assign voucher numbers up front (fast DB op). The slow PDF render
        // is then queued for background processing.
        $students = $this->prepareStudentsForGeneration($ids);

        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Selected students not found.']);
        }

        $userId = $this->getCurrentUserId();
        // The same controller is mirrored under /user/ — match the prefix so
        // the status/download URLs route correctly for the caller's role.
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';
        $jobId  = $this->queuePdfJob($ids, $userId);

        log_action($userId, 'QUEUE_PDF', 'Queued PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("{$prefix}/vouchers/pdf-status/{$jobId}"),
            // student_id → voucher_no map so the UI can show newly assigned
            // numbers without an extra round-trip.
            'vouchers'   => array_column($students, 'voucher_no', 'student_id'),
        ]);
    }

    // Max vouchers per chunk. Tuned for the memory/time profile of
    // VoucherPdf::generate (≈167 A4 pages per chunk, 3 vouchers per page).
    public const CHUNK_SIZE = 501;

    // Insert a parent pdf_jobs row plus N pending chunk rows. Each chunk renders
    // independently; once all chunks complete, a finalize step assembles them
    // into either a single PDF (1 chunk) or a ZIP (multiple chunks).
    protected function queuePdfJob(array $ids, int $userId): int
    {
        $db  = \Config\Database::connect();
        // Single timestamp shared by parent + children so they sort together.
        $now = date('Y-m-d H:i:s');

        $idList      = array_values($ids);
        $chunks      = array_chunk($idList, self::CHUNK_SIZE);
        $totalChunks = count($chunks);

        // Parent row: parent_job_id/chunk_index are NULL — that's what marks
        // it as a parent. `voucher_ids` keeps the full set for audit/debug.
        $db->table('pdf_jobs')->insert([
            'voucher_ids'   => json_encode($idList),
            'status'        => 'pending',
            'created_by'    => $userId,
            'created_at'    => $now,
            'parent_job_id' => null,
            'chunk_index'   => null,
            'total_chunks'  => $totalChunks,
        ]);
        $parentJobId = (int) $db->insertID();

        // Build child rows in memory, then a single insertBatch — much cheaper
        // than N individual inserts.
        $rows = [];
        foreach ($chunks as $idx => $chunkIds) {
            $rows[] = [
                'voucher_ids'   => json_encode(array_values($chunkIds)),
                'status'        => 'pending',
                'created_by'    => $userId,
                'created_at'    => $now,
                'parent_job_id' => $parentJobId,
                'chunk_index'   => $idx + 1, // 1-based for human-friendly ordering
                'total_chunks'  => $totalChunks,
            ];
        }
        if (!empty($rows)) {
            $db->table('pdf_jobs')->insertBatch($rows);
        }

        return $parentJobId;
    }

    // ── Poll job status (AJAX GET) ─────────────────────────────────────────────
    // Polls the parent job. If chunks are still pending, this request claims
    // and renders one of them inline so a single watching browser still makes
    // progress; multiple polls / a queue worker render chunks in parallel.
    // After all chunks complete, the parent is finalized (PDF or ZIP).
    public function checkPdfJob(int $jobId)
    {
        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();

        if (!$job) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $userId = $this->getCurrentUserId();

        // Non-admins can only see jobs they created themselves.
        if (session()->get('role') !== 'admin' && (int) $job->created_by !== $userId) {
            return $this->response->setJSON(['status' => 'forbidden']);
        }

        $totalChunks = (int) ($job->total_chunks ?? 0);
        // Presence of children → this row is a parent.
        $childrenCount = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $jobId)
            ->countAllResults();
        $isParent = $childrenCount > 0;

        // A chunk render can exceed PHP's default 30s; the user may also close
        // the tab mid-render. Keep going either way to avoid leaving a chunk
        // half-claimed in 'processing'.
        @ignore_user_abort(true);
        @set_time_limit(0);

        if ($isParent) {
            // Pick the next pending chunk (lowest chunk_index) and render it
            // inline so a single polling browser still makes forward progress
            // without a background worker.
            $pendingChild = $db->table('pdf_jobs')
                ->where('parent_job_id', $jobId)
                ->where('status', 'pending')
                ->orderBy('chunk_index', 'ASC')
                ->limit(1)
                ->get()
                ->getRow();

            if ($pendingChild && \App\Libraries\PdfJobRunner::tryClaim((int) $pendingChild->job_id)) {
                \App\Libraries\PdfJobRunner::process((int) $pendingChild->job_id);
            }

            // Always try finalize — succeeds only when every child is `done`.
            \App\Libraries\PdfJobRunner::tryFinalize($jobId);

            // Reload parent so the response below reflects the latest status.
            $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        } elseif ($job->status === 'pending' && \App\Libraries\PdfJobRunner::tryClaim($jobId)) {
            // Legacy pre-chunking standalone job: render inline.
            \App\Libraries\PdfJobRunner::process($jobId);
            $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        }

        $doneCount = $isParent
            ? (int) $db->table('pdf_jobs')
                ->where('parent_job_id', $jobId)
                ->where('status', 'done')
                ->countAllResults()
            : ($job->status === 'done' ? 1 : 0);

        $prefix      = session()->get('role') === 'admin' ? 'admin' : 'user';
        $downloadUrl = $job->status === 'done'
            ? site_url("{$prefix}/vouchers/pdf-download/{$jobId}")
            : null;

        return $this->response->setJSON([
            'status'       => $job->status,
            'download_url' => $downloadUrl,
            'error'        => $job->error_message,
            'progress'     => [
                'done'  => $doneCount,
                // total_chunks if recorded, else live child count, else 1 (standalone).
                'total' => $totalChunks > 0 ? $totalChunks : ($isParent ? $childrenCount : 1),
            ],
        ]);
    }

    // ── Stream the generated PDF to the browser ────────────────────────────────
    public function downloadPdf(int $jobId)
    {
        $db     = \Config\Database::connect();
        $job    = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        $userId = $this->getCurrentUserId();

        // Combined missing-or-forbidden check — same response either way so
        // callers can't probe which job IDs exist.
        if (!$job || (session()->get('role') !== 'admin' && (int) $job->created_by !== $userId)) {
            return redirect()->back()->with('error', 'PDF not found or access denied.');
        }

        if ($job->status !== 'done') {
            return redirect()->back()->with('error', 'PDF is not ready yet.');
        }

        $filePath = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR . $job->file_path;

        // Can happen if writable/pdfs/ was cleaned out post-completion.
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'PDF file is missing from storage.');
        }

        log_action($userId, 'DOWNLOAD_PDF', "Downloaded PDF for job #{$jobId}");

        // Single-chunk jobs produce a .pdf; multi-chunk jobs a .zip.
        $isZip = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip';
        $contentType = $isZip ? 'application/zip' : 'application/pdf';

        // file_get_contents loads the whole file into memory — fine given
        // CHUNK_SIZE caps the per-chunk file size.
        return $this->response
            ->setHeader('Content-Type', $contentType)
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

        // Not wrapped in a transaction: a mid-loop failure leaves some
        // students archived and others not. Acceptable for an admin tool.
        foreach ($students as $s) {
            // Snapshot the row into archived_students so the archive survives
            // later edits/deletes of the live row.
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
    // Legacy synchronous-generation helper. Not used by the chunked queue
    // flow above, but kept for any direct callers.
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

    /**
     * Mint voucher numbers for any selected students that don't already have
     * one. Existing numbers are preserved — regenerating a PDF must never
     * change a student's voucher code.
     */
    protected function prepareStudentsForGeneration(array $ids): array
    {
        $students = $this->voucherModel->getVouchersByIds($ids);
        if (empty($students)) {
            return [];
        }

        foreach ($students as $student) {
            if (!empty($student['voucher_no'])) {
                continue;
            }

            $this->voucherModel->update((int) $student['student_id'], [
                'voucher_no' => generate_voucher_no(),
            ]);
        }

        // Re-fetch so the returned array reflects the newly assigned numbers.
        return $this->voucherModel->getVouchersByIds($ids);
    }
}
