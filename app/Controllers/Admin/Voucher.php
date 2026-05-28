<?php

namespace App\Controllers\Admin;

use App\Libraries\VoucherPdf;
use App\Models\ArchiveModel;
use App\Models\SchoolOptionModel;
use App\Models\VoucherModel;
use CodeIgniter\Controller;

class Voucher extends Controller
{
    protected VoucherModel $voucherModel;
    protected ArchiveModel $archiveModel;
    protected SchoolOptionModel $schoolOptionModel;

    public function __construct()
    {
        $this->voucherModel = new VoucherModel();
        $this->archiveModel = new ArchiveModel();
        $this->schoolOptionModel = new SchoolOptionModel();
    }

    protected function getSchoolDropdownData(): array
    {
        return [
            'juniorHighSchools' => $this->schoolOptionModel->getJuniorHighSchools(),
            'seniorHighSchools' => $this->schoolOptionModel->getSeniorHighSchools(),
        ];
    }

    protected function validateSchoolOptions(): bool
    {
        $jhs = trim((string) $this->request->getPost('junior_high_school'));
        $shs = trim((string) $this->request->getPost('preferred_senior_high_school'));

        if ($jhs !== '') {
            $this->schoolOptionModel->addSchool('JHS', $jhs);
        }

        if ($shs !== '') {
            $this->schoolOptionModel->addSchool('SHS', $shs);
        }

        return true;
    }

    protected function getStudentValidationRules(bool $includeVoucherStatus = false): array
    {
        $rules = [
            'voucher_date'                 => 'required|valid_date[Y-m-d]',
            'first_name'                   => 'required|max_length[100]',
            'middle_name'                  => 'permit_empty|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'suffix'                       => 'permit_empty|in_list[JR.,SR.,II,III,IV]',
            'rank_no'                      => 'permit_empty|is_natural_no_zero|less_than_equal_to[999999]',
            'gwa'                          => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'gender'                       => 'permit_empty|in_list[MALE,FEMALE]',
            'junior_high_school'           => 'required|max_length[200]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'contact_number'               => 'permit_empty|max_length[30]|regex_match[/^[0-9+().\\-\\s]+$/]',
            'remarks_status'               => 'permit_empty|in_list[PASSED,FOR REVIEW,FAILED]',
            'school_year'                  => 'required|max_length[20]|regex_match[/^\\d{4}(-\\d{4})?$/]',
            'eligibility_status'           => 'permit_empty|in_list[eligible,not_eligible]',
        ];

        if ($includeVoucherStatus) {
            $rules['voucher_status'] = 'permit_empty|in_list[not_generated,generated]';
        }

        return $rules;
    }

    protected function validateStudentInput(bool $includeVoucherStatus = false): bool
    {
        return $this->validate($this->getStudentValidationRules($includeVoucherStatus)) && $this->validateSchoolOptions();
    }

    protected function getStudentPayload(bool $includeVoucherStatus = false): array
    {
        $payload = [
            'voucher_date'                 => $this->request->getPost('voucher_date'),
            'first_name'                   => $this->cleanText($this->request->getPost('first_name')),
            'middle_name'                  => $this->cleanText($this->request->getPost('middle_name')),
            'last_name'                    => $this->cleanText($this->request->getPost('last_name')),
            'suffix'                       => strtoupper($this->cleanText($this->request->getPost('suffix'))),
            'rank_no'                      => $this->nullableInt($this->request->getPost('rank_no')),
            'gwa'                          => $this->nullableFloat($this->request->getPost('gwa')),
            'gender'                       => strtoupper($this->cleanText($this->request->getPost('gender'))),
            'junior_high_school'           => $this->cleanText($this->request->getPost('junior_high_school')),
            'preferred_senior_high_school' => $this->cleanText($this->request->getPost('preferred_senior_high_school')),
            'contact_number'               => $this->cleanText($this->request->getPost('contact_number')),
            'remarks_status'               => strtoupper($this->cleanText($this->request->getPost('remarks_status'))),
            'school_year'                  => $this->cleanText($this->request->getPost('school_year')),
            'eligibility_status'           => $this->request->getPost('eligibility_status') ?: 'eligible',
        ];

        if ($includeVoucherStatus) {
            $payload['voucher_status'] = $this->request->getPost('voucher_status') ?: 'not_generated';
        }

        return $payload;
    }

    protected function cleanText($value): string
    {
        return trim((string) $value);
    }

    protected function nullableInt($value): ?int
    {
        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    protected function nullableFloat($value): ?float
    {
        $value = trim((string) $value);
        return $value === '' ? null : (float) $value;
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

    // Read advanced-filter values off the current GET request. Keys match the
    // server-side WHERE column mapping in VoucherModel::applyListingFilters
    // and the GET param names used by the listing view.
    protected function getListingFilters(): array
    {
        $req = $this->request;
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $req->getGet($key));
        }
        return $filters;
    }

    // ── List all students / vouchers ───────────────────────────────────────────
    public function index()
    {
        $keyword  = trim((string) $this->request->getGet('q'));
        $filters  = $this->getListingFilters();
        $students = $this->voucherModel->getVouchersForListing(
            $keyword,
            VoucherModel::LISTING_DEFAULT_LIMIT,
            $filters
        );

        return view('vouchers/index', [
            'title'         => 'Vouchers',
            'vouchers'      => $students,
            'role'          => session()->get('role') ?: 'admin',
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
            VoucherModel::LISTING_DEFAULT_LIMIT,
            $filters
        );

        return view('vouchers/generate', [
            'title'    => 'Voucher Generation',
            'vouchers' => $students,
            'role'     => session()->get('role') ?: 'admin',
            'keyword'  => $keyword,
            'filters'  => $filters,
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
            'voucher_no'     => null,
            'voucher_status' => 'not_generated',
            'is_active'      => 1,
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

        $data = $this->getStudentPayload();
        $this->voucherModel->update($id, $data);

        $name = trim($data['first_name'] . ' ' . $data['last_name']);
        log_action($this->getCurrentUserId(), 'UPDATE_STUDENT',
            "Updated student {$name}", $id);

        return redirect()->to(site_url('admin/students'))->with('message', 'Student voucher updated successfully.');
    }

    // ── Queue PDF generation; the spark worker processes the job in the background ─
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
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';
        $jobId  = $this->queuePdfJob($ids, $userId);

        log_action($userId, 'QUEUE_PDF', 'Queued PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("{$prefix}/vouchers/pdf-status/{$jobId}"),
            'vouchers'   => array_column($students, 'voucher_no', 'student_id'),
        ]);
    }

    public const CHUNK_SIZE = 501;

    // Insert a parent pdf_jobs row plus N pending chunk rows. Each chunk renders
    // independently; once all chunks complete, a finalize step assembles them
    // into either a single PDF (1 chunk) or a ZIP (multiple chunks).
    protected function queuePdfJob(array $ids, int $userId): int
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $idList      = array_values($ids);
        $chunks      = array_chunk($idList, self::CHUNK_SIZE);
        $totalChunks = count($chunks);

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

        $rows = [];
        foreach ($chunks as $idx => $chunkIds) {
            $rows[] = [
                'voucher_ids'   => json_encode(array_values($chunkIds)),
                'status'        => 'pending',
                'created_by'    => $userId,
                'created_at'    => $now,
                'parent_job_id' => $parentJobId,
                'chunk_index'   => $idx + 1,
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

        if (session()->get('role') !== 'admin' && (int) $job->created_by !== $userId) {
            return $this->response->setJSON(['status' => 'forbidden']);
        }

        $totalChunks = (int) ($job->total_chunks ?? 0);
        $childrenCount = (int) $db->table('pdf_jobs')
            ->where('parent_job_id', $jobId)
            ->countAllResults();
        $isParent = $childrenCount > 0;

        @ignore_user_abort(true);
        @set_time_limit(0);

        if ($isParent) {
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

            \App\Libraries\PdfJobRunner::tryFinalize($jobId);

            $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        } elseif ($job->status === 'pending' && \App\Libraries\PdfJobRunner::tryClaim($jobId)) {
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

        $isZip = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip';
        $contentType = $isZip ? 'application/zip' : 'application/pdf';

        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }

    // ── Archive selected students (hard — copies to student_archive, deletes from students) ─
    public function archive()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $reason   = $this->request->getPost('archive_reason') ?: 'Bulk archive (selected)';
        $archived = $this->archiveStudentsByIds($ids, $reason);

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived.",
        ]);
    }

    // ── Bulk archive everything matching the current search + filter scope ────
    // Sweeps the full DB (not just the loaded 1000-row slice), including
    // not-eligible students that the per-row Archive checkbox can't select.
    public function archiveAll()
    {
        $keyword = trim((string) $this->request->getPost('q'));
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getPost($key));
        }
        $reason = $this->request->getPost('archive_reason') ?: 'Bulk archive (Archive All)';

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No students match the current search/filter — nothing to archive.',
            ]);
        }

        $archived = $this->archiveStudentsByIds($ids, $reason);

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived successfully.",
        ]);
    }

    // ── TEMP: Restore everything from student_archive back into students ──────
    // For testing the Archive All flow. Copies every row from student_archive
    // into students (preserving the original student_id), then truncates
    // student_archive. Safe to delete this method + its route + the matching
    // button in the view once Archive All is done being tested.
    public function restoreAllFromArchive()
    {
        $db = \Config\Database::connect();

        $rows = $db->table('student_archive')->get()->getResultArray();

        if (empty($rows)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'student_archive is empty — nothing to restore.',
            ]);
        }

        $now      = date('Y-m-d H:i:s');
        $restored = 0;
        $skipped  = 0;

        foreach ($rows as $r) {
            $studentId = (int) ($r['student_id'] ?? 0);
            if ($studentId <= 0) {
                $skipped++;
                continue;
            }

            // Skip if a fresh row was inserted with the same student_id
            // between the archive and the restore.
            $exists = $db->table('students')
                ->where('student_id', $studentId)
                ->countAllResults() > 0;
            if ($exists) {
                $skipped++;
                continue;
            }

            $db->table('students')->insert([
                'student_id'                   => $studentId,
                'voucher_no'                   => $r['voucher_no']                   ?? null,
                'voucher_date'                 => $r['voucher_date']                 ?? null,
                'first_name'                   => $r['first_name']                   ?? '',
                'middle_name'                  => $r['middle_name']                  ?? null,
                'last_name'                    => $r['last_name']                    ?? '',
                'suffix'                       => $r['suffix']                       ?? null,
                'rank_no'                      => $r['rank_no']                      ?? null,
                'gwa'                          => $r['gwa']                          ?? null,
                'gender'                       => $r['gender']                       ?? null,
                'junior_high_school'           => $r['junior_high_school']           ?? null,
                'preferred_senior_high_school' => $r['preferred_senior_high_school'] ?? null,
                'contact_number'               => $r['contact_number']               ?? null,
                'remarks_status'               => $r['remarks_status']               ?? null,
                'school_year'                  => $r['school_year']                  ?? null,
                'eligibility_status'           => $r['eligibility_status']           ?? 'eligible',
                'voucher_status'               => $r['voucher_status']               ?? 'not_generated',
                'is_active'                    => 1,
                'created_at'                   => $r['archived_at']                  ?? $now,
                'updated_at'                   => $now,
            ]);

            $db->table('student_archive')
                ->where('archive_id', $r['archive_id'])
                ->delete();

            $restored++;
        }

        log_action($this->getCurrentUserId(), 'RESTORE_ARCHIVE_TEST',
            "[TEST] Restored {$restored} student(s) from archive (skipped {$skipped})");

        return $this->response->setJSON([
            'success' => true,
            'message' => "Restored {$restored} student(s) from archive. Skipped {$skipped} (already exist in students).",
        ]);
    }

    // ── Count students matching the current search + filter scope (AJAX) ──────
    // Called by the "Archive All" confirmation modal so the user sees the
    // exact number before confirming the destructive action.
    public function countMatching()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getGet($key));
        }

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        return $this->response->setJSON([
            'success' => true,
            'count'   => count($ids),
        ]);
    }

    // Shared archive loop — copies each student row into student_archive then
    // deletes the source row. Used by both archive() (selected) and
    // archiveAll() (bulk by filter).
    protected function archiveStudentsByIds(array $ids, string $reason): int
    {
        $students = $this->voucherModel->getVouchersByIds($ids);
        $userId   = $this->getCurrentUserId();
        $now      = date('Y-m-d H:i:s');
        $archived = 0;

        $db = \Config\Database::connect();

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

            $archived++;
        }

        // The audit_log table has FKs (audit_log_ibfk_2 → students.student_id,
        // and likely one for voucher_id) that block DELETE on `students`.
        // Null them out for the affected IDs in one batch so the audit history
        // is preserved (descriptions stay) but the FK is released. Doing it
        // here, AFTER the archive inserts have succeeded, means we don't lose
        // the FK pointers if the archive step fails.
        if (!empty($ids)) {
            $db->table('audit_log')
                ->whereIn('student_id', $ids)
                ->update(['student_id' => null]);
            $db->table('audit_log')
                ->whereIn('voucher_id', $ids)
                ->update(['voucher_id' => null]);

            // Delete in one statement instead of N round-trips.
            $db->table('students')->whereIn('student_id', $ids)->delete();
        }

        foreach ($students as $s) {
            log_action($userId, 'ARCHIVE_STUDENT',
                "Student {$s['full_name']} (Voucher {$s['voucher_no']}) archived",
                null);
        }

        return $archived;
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

    // ── Bulk activate ─────────────────────────────────────────────────────────
    public function activateMultiple()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $userId = $this->getCurrentUserId();
        foreach ($ids as $id) {
            $this->voucherModel->update($id, ['is_active' => 1]);
            log_action($userId, 'ACTIVATE_STUDENT', "Activated student #{$id}");
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => count($ids) . ' student(s) activated.',
        ]);
    }

    // ── Bulk deactivate ───────────────────────────────────────────────────────
    public function deactivateMultiple()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));
        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $userId = $this->getCurrentUserId();
        foreach ($ids as $id) {
            $this->voucherModel->update($id, ['is_active' => 0]);
            log_action($userId, 'DEACTIVATE_STUDENT', "Deactivated student #{$id}");
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => count($ids) . ' student(s) deactivated.',
        ]);
    }

    // ── Per-row toggle active ─────────────────────────────────────────────────
    public function toggleActive(int $id)
    {
        $student = $this->voucherModel->find($id);
        if (!$student) {
            return $this->response->setJSON(['success' => false, 'message' => 'Student not found.']);
        }

        $newActive = empty($student['is_active']) ? 1 : 0;
        $this->voucherModel->update($id, ['is_active' => $newActive]);

        $userId = $this->getCurrentUserId();
        $action = $newActive ? 'ACTIVATE_STUDENT' : 'DEACTIVATE_STUDENT';
        log_action($userId, $action, ($newActive ? 'Activated' : 'Deactivated') . " student #{$id}");

        return $this->response->setJSON([
            'success'    => true,
            'is_active'  => $newActive,
            'message'    => 'Student ' . ($newActive ? 'activated' : 'deactivated') . '.',
            'csrf_token' => csrf_hash(),
        ]);
    }

    // ── Per-row toggle eligibility ────────────────────────────────────────────
    public function toggleEligibility(int $id)
    {
        $student = $this->voucherModel->find($id);
        if (!$student) {
            return $this->response->setJSON(['success' => false, 'message' => 'Student not found.']);
        }

        $current = $student['eligibility_status'] ?? '';
        $newEligibility = ($current === 'not_eligible') ? 'eligible' : 'not_eligible';
        $this->voucherModel->update($id, ['eligibility_status' => $newEligibility]);

        $userId = $this->getCurrentUserId();
        log_action($userId, 'UPDATE_ELIGIBILITY', "Set student #{$id} eligibility to {$newEligibility}");

        return $this->response->setJSON([
            'success'            => true,
            'eligibility_status' => $newEligibility,
            'message'            => 'Eligibility updated.',
            'csrf_token'         => csrf_hash(),
        ]);
    }

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

            $jhs  = $student['junior_high_school'] ?? '';
            $year = !empty($student['voucher_date'])
                ? date('Y', strtotime($student['voucher_date']))
                : date('Y');

            $this->voucherModel->update((int) $student['student_id'], [
                'voucher_no' => generate_voucher_no($jhs, $year),
            ]);
        }

        return $this->voucherModel->getVouchersByIds($ids);
    }
}
