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
        return true;
    }

    protected function getStudentValidationRules(bool $includeVoucherStatus = false): array
    {
        $rules = [
            'control_no'                   => 'permit_empty|max_length[50]',
            'evaluated_by'                 => 'permit_empty|max_length[150]',
            'voucher_date'                 => 'required|valid_date[Y-m-d]',
            'first_name'                   => 'required|max_length[100]',
            'middle_name'                  => 'permit_empty|max_length[100]',
            'last_name'                    => 'required|max_length[100]',
            'suffix'                       => 'permit_empty|in_list[JR.,SR.,II,III,IV]',
            'rank_no'                      => 'permit_empty|decimal|greater_than[0]|less_than_equal_to[999999]',
            'gwa'                          => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'gender'                       => 'permit_empty|in_list[MALE,FEMALE]',
            'junior_high_school'           => 'required|max_length[200]',
            'preferred_senior_high_school' => 'required|max_length[200]',
            'contact_number'               => 'permit_empty|max_length[30]|regex_match[/^[0-9+().\\-\\s]+$/]',
            'remarks_status'               => 'permit_empty|in_list[COMPLETE,INCOMPLETE,OTHERS]',
            'other_remarks'                 => 'permit_empty|max_length[255]',
            // 'eligibility_status'           => 'required|in_list[eligible,not_eligible]',
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
            'control_no'                   => $this->cleanText($this->request->getPost('control_no')) ?: null,
            'evaluated_by'                 => $this->cleanText($this->request->getPost('evaluated_by')) ?: null,
            'voucher_date'                 => $this->request->getPost('voucher_date'),
            'first_name'                   => $this->cleanText($this->request->getPost('first_name')),
            'middle_name'                  => $this->cleanText($this->request->getPost('middle_name')),
            'last_name'                    => $this->cleanText($this->request->getPost('last_name')),
            'suffix'                       => strtoupper($this->cleanText($this->request->getPost('suffix'))),
            'rank_no'                      => $this->nullableFloat($this->request->getPost('rank_no')),
            'gwa'                          => $this->nullableFloat($this->request->getPost('gwa')),
            'gender'                       => strtoupper($this->cleanText($this->request->getPost('gender'))),
            'junior_high_school'           => $this->schoolOptionModel->resolveSchoolId('JHS', $this->request->getPost('junior_high_school'), false),
            'preferred_senior_high_school' => $this->schoolOptionModel->resolveSchoolId('SHS', $this->request->getPost('preferred_senior_high_school'), false),
            'contact_number'               => $this->cleanText($this->request->getPost('contact_number')),
            'remarks_status'               => strtoupper($this->cleanText($this->request->getPost('remarks_status'))),
            'other_remarks'                 => strtoupper($this->cleanText($this->request->getPost('remarks_status'))) === 'OTHERS'
                ? $this->cleanText($this->request->getPost('other_remarks'))
                : null,
            // 'eligibility_status'           => $this->request->getPost('eligibility_status') ?: 'eligible',
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

    protected function archiveSchoolYearLabel(?string $archivedAt = null): string
    {
        $timestamp = strtotime($archivedAt ?: 'now') ?: time();
        $year      = (int) date('Y', $timestamp);
        $month     = (int) date('n', $timestamp);
        $startYear = $month >= 6 ? $year : $year - 1;

        return $startYear . '-' . ($startYear + 1);
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
        return $this->renderListing('Vouchers', true, 'vouchers');
    }

    public function students()
    {
        return $this->renderListing('Students', false, 'students');
    }

    protected function renderListing(string $title, bool $allowGenerate, string $listingPath)
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = $this->getListingFilters();

        // The listing now uses server-side DataTables (see vouchers/index.php's
        // data-datatable-url + the studentsDatatable() endpoint). The initial
        // table renders empty and rows arrive via AJAX, so we skip the upfront
        // 1000-row query that the old client-side mode relied on.
        return view('vouchers/index', [
            'title'         => $title,
            'vouchers'      => [],
            'role'          => session()->get('role') ?: 'admin',
            'allowGenerate' => $allowGenerate,
            'listingPath'   => $listingPath,
            'keyword'       => $keyword,
            'filters'       => $filters,
            'filterOptions' => $this->voucherModel->getListingFilterOptions(),
        ] + $this->getSchoolDropdownData());
    }

    /**
     * Returns every student_id matching the current search + advanced filters.
     * Used by the "Select all N matching across all pages" link in the
     * server-side DataTables UI so the user can act on every match, not just
     * the visible page slice.
     */
    public function studentsMatchingIds()
    {
        $req     = $this->request;
        // Outer keyword (?q=) widens scope. Inner DT search (`search[value]`)
        // narrows within scope. Either acts as a LIKE for matching IDs.
        $keyword = (string) ($req->getGet('q') ?? '');
        $search  = (string) ($req->getGet('search')['value'] ?? '');
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $k) {
            $filters[$k] = trim((string) $req->getGet($k));
        }

        // Reuse the model's listing logic so the same scope cap (1000 most-
        // recent) and narrow/widen semantics apply here. We pass length=0 then
        // override below with a much higher cap, since "matching IDs" can
        // legitimately need every row.
        $hasFilter = false;
        foreach ($filters as $v) {
            if (trim((string) $v) !== '') { $hasFilter = true; break; }
        }
        $noScope = ($keyword === '' && !$hasFilter);

        $combinedKeyword = trim($keyword . ' ' . $search);
        $ids = $this->voucherModel->getMatchingStudentIds(trim($keyword), $filters);
        if ($search !== '') {
            // Narrow further by the inner search using the same model helper
            // (it already does a wide multi-column LIKE).
            $innerIds = $this->voucherModel->getMatchingStudentIds($search, $filters);
            $ids = array_values(array_intersect($ids, $innerIds));
        }

        // Cap to the 1000-row scope when nothing widens, so "Select all
        // matching" mirrors the count DataTables shows.
        if ($noScope) {
            $db = \Config\Database::connect();
            $scopeRows = $db->table('students')
                ->select('student_id')
                ->orderBy('created_at', 'DESC')
                ->orderBy('student_id', 'DESC')
                ->limit(VoucherModel::LISTING_DEFAULT_LIMIT)
                ->get()
                ->getResultArray();
            $scopeIds = array_map(static fn($r) => (int) $r['student_id'], $scopeRows);
            $ids = array_values(array_intersect($ids, $scopeIds));
        }


        // Exclude inactive rows — their checkboxes are disabled in the UI.
        if (!empty($ids)) {
            $db  = \Config\Database::connect();
            $ids = $db->table('students')
                ->select('student_id')
                ->whereIn('student_id', $ids)
                // ->where('eligibility_status', 'eligible')
                ->where('is_active', 1)
                ->get()
                ->getResultArray();
            $ids = array_map(static fn($r) => (int) $r['student_id'], $ids);
        }

        return $this->response->setJSON([
            'count' => count($ids),
            'ids'   => array_values($ids),
        ]);
    }

    /**
     * Server-side DataTables endpoint for the students listing. Returns
     * DataTables-shaped JSON: { draw, recordsTotal, recordsFiltered, data }.
     * Each row in `data` is an array of pre-rendered HTML cell strings, in the
     * same order as the <th>s in vouchers/index.php.
     */
    public function studentsDatatable()
    {
        helper(['asset_icon']);

        $req     = $this->request;
        $draw    = (int) $req->getGet('draw');
        $start   = (int) $req->getGet('start');
        $length  = (int) ($req->getGet('length') ?? 25);
        // Two distinct search inputs:
        //   - $keyword (`q`) = outer advanced-search box. WIDENS the scope to
        //     the full DB and applies a LIKE filter across student fields.
        //   - $search (`search[value]`) = DataTables' built-in box, used by
        //     the inner per-card input. NARROWS within whatever scope the
        //     outer keyword + advanced filters produced (does NOT widen).
        $keyword = (string) ($req->getGet('q') ?? '');
        $search  = (string) ($req->getGet('search')['value'] ?? '');
        $orderC  = $req->getGet('order')[0]['column'] ?? null;
        $orderD  = $req->getGet('order')[0]['dir']    ?? 'asc';

        // Advanced filters arrive as flat top-level GET params (same names the
        // listing page uses — see VoucherModel::LISTING_FILTER_KEYS).
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $k) {
            $filters[$k] = trim((string) $req->getGet($k));
        }

        $slice = $this->voucherModel->getDatatableSlice([
            'start'     => $start,
            'length'    => $length,
            'keyword'   => $keyword,
            'search'    => $search,
            'order_col' => $orderC,
            'order_dir' => $orderD,
            'filters'   => $filters,
        ]);

        $data = [];
        foreach ($slice['rows'] as $v) {
            $data[] = $this->renderStudentRowForDatatable($v);
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $slice['recordsTotal'],
            'recordsFiltered' => $slice['recordsFiltered'],
            'data'            => $data,
        ]);
    }

    /**
     * Mirror of the per-row HTML emitted by vouchers/index.php's PHP foreach,
     * but built as an associative array of cell strings keyed by DataTables
     * `data` column names. Keeps row markup in one place (here) instead of
     * duplicated in JS column renderers.
     */
    protected function renderStudentRowForDatatable(array $v): array
    {
        $studentId   = (int) ($v['student_id'] ?? 0);
        // $notEligible = ($v['eligibility_status'] ?? '') === 'not_eligible';
        $isActive    = !isset($v['is_active']) || !empty($v['is_active']);
        // $elig        = (string) ($v['eligibility_status'] ?? '');

        $disabledReason = !$isActive ? 'Deactivated — cannot be selected' : '';
        $checkbox = '<input type="checkbox" class="vs-check vs-row-check" value="'
            . esc($studentId, 'attr') . '"'
            . ($disabledReason !== '' ? ' disabled title="' . esc($disabledReason, 'attr') . '"' : '')
            . '>';

        $voucherNo  = '<span class="js-voucher-no">' . esc($v['voucher_no'] ?: '-') . '</span>';
        $lastName   = trim((string) ($v['last_name']   ?? ''));
        $firstName  = trim((string) ($v['first_name']  ?? ''));
        $middleName = trim((string) ($v['middle_name'] ?? ''));
        $firstMid   = implode(' ', array_filter([$firstName, $middleName]));
        $name       = esc($lastName !== '' ? $lastName . ($firstMid !== '' ? ', ' . $firstMid : '') : $firstMid);
        $nameSort   = esc(trim($lastName . ' ' . $firstName . ' ' . $middleName));
        $rank       = ($v['rank_no'] ?? null) !== null && (string) $v['rank_no'] !== '' ? esc((string) $v['rank_no']) : '-';
        $jhsName    = (string) ($v['junior_high_school'] ?: '-');
        $shsName    = (string) ($v['preferred_senior_high_school'] ?? '-');
        $jhsAcr     = trim((string) ($v['jhs_acronym'] ?? ''));
        $shsAcr     = trim((string) ($v['shs_acronym'] ?? ''));
        $jhs        = '<span title="' . esc($jhsName, 'attr') . '">' . esc($jhsAcr !== '' ? $jhsAcr : $jhsName) . '</span>';
        $shs        = '<span title="' . esc($shsName, 'attr') . '">' . esc($shsAcr !== '' ? $shsAcr : $shsName) . '</span>';
        $remarks    = '<span class="js-remarks-cell">' . esc($v['remarks_status'] ?: '-') . '</span>';

        /*
        if ($elig === 'eligible' || $elig === 'not_eligible') {
            $color    = $elig === 'eligible' ? '#16a34a' : '#9ca3af';
            $label    = $elig === 'eligible' ? 'Eligible' : 'Not eligible';
            $icon     = asset_icon($elig === 'eligible' ? 'circle_check' : 'circle_x', ['width' => '18', 'height' => '18']);
            $eligCell = '<span class="js-elig-icon" style="color:' . $color . ';display:inline-flex" title="' . esc($label, 'attr') . '" aria-label="' . esc($label, 'attr') . '">' . $icon . '</span>';
        } else {
            $eligCell = '<span aria-label="Unknown">—</span>';
        }
        */

        $statusColor = $isActive ? '#16a34a' : '#9ca3af';
        $statusLabel = $isActive ? 'Active' : 'Inactive';
        $statusIcon  = asset_icon($isActive ? 'circle_check' : 'circle_x', ['width' => '18', 'height' => '18']);
        $statusCell  = '<span class="js-status-icon" style="color:' . $statusColor . ';display:inline-flex" title="' . $statusLabel . '" aria-label="' . $statusLabel . '">' . $statusIcon . '</span>';

        $genCount  = '<span class="js-generate-count">' . esc((string) ($v['generate_count'] ?? 0)) . '</span>';
        $lastGen   = !empty($v['generated_at']) ? date('M d, Y', strtotime($v['generated_at'])) : '-';
        $lastGenCell = '<span class="js-last-generated">' . esc($lastGen) . '</span>';

        // $eligAttr   = esc($elig, 'attr');
        // $toggleText = $elig === 'not_eligible' ? 'Mark Eligible' : 'Mark Not Eligible';
        $actCell = '<div class="js-actions-cell"><div class="dropdown">'
            . '<button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config=\'{"strategy":"fixed"}\' aria-expanded="false">Actions</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><button class="dropdown-item js-voucher-action" type="button" data-mode="view" data-id="' . $studentId . '">View</button></li>'
            . '<li><button class="dropdown-item js-voucher-action" type="button" data-mode="edit" data-id="' . $studentId . '">Edit</button></li>'
            // . '<li><button class="dropdown-item js-toggle-eligibility" type="button" data-id="' . $studentId . '" data-eligibility="' . $eligAttr . '">' . $toggleText . '</button></li>'
            . '<li><hr class="dropdown-divider"></li>'
            . '<li><button class="dropdown-item ' . ($isActive ? 'text-danger' : '') . ' js-toggle-active" type="button" data-id="' . $studentId . '" data-active="' . ($isActive ? '1' : '0') . '">' . ($isActive ? 'Deactivate' : 'Activate') . '</button></li>'
            . '</ul></div></div>';

        return [
            'DT_RowId'    => 'row-' . $studentId,
            'DT_RowClass' => $isActive ? '' : 'vs-row-archived',
            'DT_RowAttr' => [
                'data-gender'         => (string) ($v['gender'] ?? ''),
                'data-remarks'        => (string) ($v['remarks_status'] ?? ''),
                'data-voucher-date'   => (string) ($v['voucher_date'] ?? ''),
                'data-voucher-status' => (string) ($v['voucher_status'] ?? ''),
                // 'data-eligibility'    => $elig,
                'data-active'         => $isActive ? '1' : '0',
                'data-gwa'            => (string) ($v['gwa'] ?? ''),
                'data-search-extra'   => implode(' ', array_filter([
                    $v['jhs_acronym'] ?? '',
                    $v['shs_acronym'] ?? '',
                ])),
            ],
            'checkbox'      => $checkbox,
            'voucher_no'    => $voucherNo,
            'name'          => $name,
            'name_sort'     => $nameSort,
            'rank'          => $rank,
            'jhs'           => $jhs,
            'shs'           => $shs,
            // 'eligibility'   => $eligCell,
            'remarks'       => $remarks,
            'generate_count'=> $genCount,
            'last_generated'=> $lastGenCell,
            'status'        => $statusCell,
            'actions'       => $actCell,
        ];
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
        log_action($this->getCurrentUserId(), 'CREATE_VOUCHER',
            "Created voucher for {$name}", $studentId);

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
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody(file_get_contents($filePath));
    }

    // ── JSON-FILE QUEUE FLOW ──────────────────────────────────────────────────
    // Parallel pipeline to the DB-backed pdf_jobs flow. Jobs are queued into
    // writable/pdf_queue/queue.json, drained by `spark run:json-pdf-queue`,
    // and surface in finished.json when done. See App\Libraries\JsonPdfQueue
    // and App\Libraries\JsonPdfRunner for the storage + render logic.

    // Block generation when any selected student has no Preferred Senior High
    // School — a voucher can't be issued without it. Returns a JSON error
    // response listing the offenders, or null when all are OK. Shared by the
    // admin + user generate flows.
    protected function missingPreferredResponse(array $ids)
    {
        $missing = $this->voucherModel->getMissingPreferredSchool($ids);
        if (empty($missing)) {
            return null;
        }
        $names = array_slice(array_map(static fn ($r) => $r['full_name'], $missing), 0, 5);
        $more  = \count($missing) > 5 ? ' and ' . (\count($missing) - 5) . ' more' : '';

        return $this->response->setJSON([
            'success' => false,
            'message' => \count($missing) . ' selected student(s) have no Preferred Senior High School and cannot be generated: '
                . implode(', ', $names) . $more . '. Set their preferred school first.',
        ]);
    }

    public function generateJsonPdf()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        if ($resp = $this->missingPreferredResponse($ids)) {
            return $resp;
        }

        $userId = $this->getCurrentUserId();
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';

        // Enqueue raw IDs only. voucher_no assignment + rendering happen in the
        // background worker (JsonPdfRunner::processClaimed), per chunk — so this
        // request returns instantly even for tens of thousands of students.
        // Previously prepareStudentsForGeneration() ran ~2 queries per student
        // here (a MAX/LIKE scan + an UPDATE) and timed out large batches.
        $jobId = \App\Libraries\JsonPdfQueue::enqueueJob($ids, $userId, self::CHUNK_SIZE);

        log_action($userId, 'QUEUE_PDF_JSON', 'Queued JSON-PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'status_url' => site_url("{$prefix}/vouchers/json-pdf-status/{$jobId}"),
        ]);
    }

    public function jsonPdfStatus(int $jobId)
    {
        $snapshot = \App\Libraries\JsonPdfQueue::snapshot($jobId);

        if (!$snapshot) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $userId = $this->getCurrentUserId();
        $parent = $snapshot['parent'];

        if (session()->get('role') !== 'admin' && (int) ($parent['created_by'] ?? 0) !== $userId) {
            return $this->response->setJSON(['status' => 'forbidden']);
        }

        $status = $parent['status'] ?? 'pending';

        // Refine "pending" into queued vs processing for UI clarity.
        if ($status === 'pending') {
            if ($snapshot['processing'] > 0) {
                $status = 'processing';
            } elseif ($snapshot['done'] > 0 && $snapshot['done'] < $snapshot['total']) {
                $status = 'processing';
            } else {
                $status = 'queued';
            }
        }

        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';
        $downloadUrl = ($parent['status'] ?? '') === 'done'
            ? site_url("{$prefix}/vouchers/json-pdf-download/{$jobId}")
            : null;

        return $this->response->setJSON([
            'status'       => $status,
            'download_url' => $downloadUrl,
            'error'        => $parent['error_message'] ?? null,
            'progress'     => [
                'done'       => $snapshot['done'],
                'failed'     => $snapshot['failed'],
                'processing' => $snapshot['processing'],
                'queued'     => $snapshot['queued'],
                'total'      => $snapshot['total'],
            ],
        ]);
    }

    public function jsonPdfDownload(int $jobId)
    {
        $found = \App\Libraries\JsonPdfQueue::findJob($jobId);

        // Only parent records are downloadable; chunks have parent_job_id != null.
        if (!$found || !empty($found['job']['parent_job_id'])) {
            return redirect()->back()->with('error', 'PDF not found.');
        }

        $job    = $found['job'];
        $userId = $this->getCurrentUserId();

        if (session()->get('role') !== 'admin' && (int) ($job['created_by'] ?? 0) !== $userId) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        if (($job['status'] ?? '') !== 'done' || empty($job['file_path'])) {
            return redirect()->back()->with('error', 'PDF is not ready yet.');
        }

        $filePath = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR . $job['file_path'];

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'PDF file is missing from storage.');
        }

        log_action($userId, 'DOWNLOAD_PDF_JSON', "Downloaded JSON-PDF for job #{$jobId}");

        $isZip       = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip';
        $contentType = $isZip ? 'application/zip' : 'application/pdf';
        $body        = file_get_contents($filePath);
        $basename    = basename($filePath);

        // Keep the file + record after download so the manual Download link in
        // the toast keeps working (e.g. when the automatic download is blocked
        // or interrupted). The worker's sweepStaleFinished() unlinks both ~10
        // min after completion, so writable/ still stays clean on its own.
        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $basename . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($body);
    }

    // ── Enqueue an archive job for the background worker ──────────────────────
    // Archiving used to run inline in the request — copy-to-archive + delete for
    // every matching student — which timed out the session on large batches.
    // Now it's queued into the JSON file queue ('archive' type) and drained in
    // chunks by `spark run:json-pdf-queue` (ArchiveRunner). The request returns
    // instantly with a job to poll. Used by archive() / archiveAll() /
    // archiveByFilter() below; User\Voucher reuses this too (prefix follows the
    // session role).
    protected function enqueueArchiveJob(array $ids, string $reason)
    {
        $userId = $this->getCurrentUserId();
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';

        $jobId = \App\Libraries\JsonPdfQueue::enqueueChunked('archive', $ids, $userId, self::CHUNK_SIZE, ['reason' => $reason]);

        log_action($userId, 'QUEUE_ARCHIVE', 'Queued archive for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'count'      => \count($ids),
            'status_url' => site_url("{$prefix}/jobs/status/{$jobId}"),
        ]);
    }

    // ── Archive selected students (hard — copies to student_archive, deletes from students) ─
    public function archive()
    {
        $ids = $this->parseVoucherIds($this->request->getPost('voucher_ids'));

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $reason = $this->request->getPost('archive_reason') ?: 'Bulk archive (selected)';

        return $this->enqueueArchiveJob($ids, $reason);
    }

    // ── Bulk archive everything matching the current search + filter scope ────
    // Sweeps the full DB (not just the loaded 1000-row slice), including
    // inactive students that the per-row Archive checkbox can't select.
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

        return $this->enqueueArchiveJob($ids, $reason);
    }

    // ── Bulk-generate vouchers for everything matching the current search +
    // filter scope. Mirrors archiveAll()'s scope resolution, but instead of
    // blocking the whole batch (like missingPreferredResponse() does for
    // per-selection generation), it silently skips students that can't be
    // generated: inactive, or missing a preferred senior high
    // school.
    public function generateAll()
    {
        $keyword = trim((string) $this->request->getPost('q'));
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getPost($key));
        }

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        if (!empty($ids)) {
            $db  = \Config\Database::connect();
            $ids = $db->table('students')
                ->select('student_id')
                ->whereIn('student_id', $ids)
                // ->where('eligibility_status', 'eligible')
                ->where('is_active', 1)
                ->where('preferred_senior_high_school IS NOT NULL', null, false)
                ->where('preferred_senior_high_school !=', 0)
                ->get()
                ->getResultArray();
            $ids = array_map(static fn($r) => (int) $r['student_id'], $ids);
        }

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No students match the current search/filter — nothing to generate.',
            ]);
        }

        $userId = $this->getCurrentUserId();
        $prefix = session()->get('role') === 'admin' ? 'admin' : 'user';

        $jobId = \App\Libraries\JsonPdfQueue::enqueueJob($ids, $userId, self::CHUNK_SIZE);
        log_action($userId, 'QUEUE_PDF_JSON', 'Queued JSON-PDF for ' . \count($ids) . ' student(s) (job #' . $jobId . ')');

        return $this->response->setJSON([
            'success'    => true,
            'queued'     => true,
            'job_id'     => $jobId,
            'count'      => \count($ids),
            'status_url' => site_url("{$prefix}/vouchers/json-pdf-status/{$jobId}"),
        ]);
    }

    // ── Archive all students matching the archive-page filter (GET params) ───
    public function archiveByFilter()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getGet($key));
        }
        $reason = trim((string) ($this->request->getPost('archive_reason') ?: '')) ?: 'Archive current data';

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No students match the current filters — nothing to archive.',
            ]);
        }

        return $this->enqueueArchiveJob($ids, $reason);
    }

    // ── Bulk activate everything matching the current search + filter scope ───
    public function activateAll()
    {
        return $this->bulkSetActiveAll(1);
    }

    // ── Bulk deactivate everything matching the current search + filter scope ─
    public function deactivateAll()
    {
        return $this->bulkSetActiveAll(0);
    }

    protected function bulkSetActiveAll(int $state)
    {
        $keyword = trim((string) $this->request->getPost('q'));
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getPost($key));
        }

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No students match the current search/filter.',
            ]);
        }

        $userId = $this->getCurrentUserId();
        $label  = $state === 1 ? 'activated' : 'deactivated';
        $action = $state === 1 ? 'ACTIVATE_STUDENT' : 'DEACTIVATE_STUDENT';

        foreach ($ids as $id) {
            $this->voucherModel->update($id, ['is_active' => $state]);
        }
        $count = count($ids);
        log_action($userId, $action, "Bulk {$label} {$count} student(s) (" . ucfirst($label) . " All)");

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$count} student(s) {$label}.",
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
                'control_no'                   => $r['control_no']                   ?? null,
                'voucher_no'                   => $r['voucher_no']                   ?? null,
                'voucher_date'                 => $r['voucher_date']                 ?? null,
                'first_name'                   => $r['first_name']                   ?? '',
                'middle_name'                  => $r['middle_name']                  ?? null,
                'last_name'                    => $r['last_name']                    ?? '',
                'suffix'                       => $r['suffix']                       ?? null,
                'rank_no'                      => $r['rank_no']                      ?? null,
                'gwa'                          => $r['gwa']                          ?? null,
                'gender'                       => $r['gender']                       ?? null,
                'junior_high_school'           => $this->schoolOptionModel->resolveSchoolId('JHS', $r['junior_high_school'] ?? null, true),
                'preferred_senior_high_school' => $this->schoolOptionModel->resolveSchoolId('SHS', $r['preferred_senior_high_school'] ?? null, false),
                'contact_number'               => $r['contact_number']               ?? null,
                'remarks_status'               => $r['remarks_status']               ?? null,
                'other_remarks'                 => $r['other_remarks']                 ?? null,
                'evaluated_by'                 => $r['evaluated_by']                 ?? null,
                'school_year'                  => null,
                // 'eligibility_status'           => $r['eligibility_status']           ?? 'eligible',
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
    // archiveAll() (bulk by filter). $bulkLog=true collapses N per-student
    // audit entries into a single summary row, matching the unarchive flow.
    protected function archiveStudentsByIds(array $ids, string $reason, bool $bulkLog = false): int
    {
        $students = $this->voucherModel->getVouchersByIds($ids);
        $userId   = $this->getCurrentUserId();
        $now      = date('Y-m-d H:i:s');
        $schoolYear = $this->archiveSchoolYearLabel($now);
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
                'other_remarks'                 => $s['other_remarks'] ?? null,
                'school_year'                  => $schoolYear,
                // 'eligibility_status'           => $s['eligibility_status'],
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
            if ($db->fieldExists('voucher_id', 'audit_log')) {
                $db->table('audit_log')
                    ->whereIn('voucher_id', $ids)
                    ->update(['voucher_id' => null]);
            }

            // Delete in one statement instead of N round-trips.
            $db->table('students')->whereIn('student_id', $ids)->delete();
        }

        if ($bulkLog) {
            log_action($userId, 'ARCHIVE_STUDENT',
                "Bulk archived {$archived} student(s) (Archive All)",
                null);
        } else {
            foreach ($students as $s) {
                log_action($userId, 'ARCHIVE_STUDENT',
                    "Student {$s['full_name']} (Voucher {$s['voucher_no']}) archived",
                    null);
            }
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

    /*
    // ── Per-row toggle eligibility ────────────────────────────────────────────
    public function toggleEligibility(int $id)
    {
        $student = $this->voucherModel->find($id);
        if (!$student) {
            return $this->response->setJSON(['success' => false, 'message' => 'Student not found.']);
        }

        $current = $student['eligibility_status'] ?? '';
        $newEligibility = ($current === 'not_eligible') ? 'eligible' : 'not_eligible';

        $updateData = ['eligibility_status' => $newEligibility];
        $updateData['remarks_status'] = $newEligibility === 'not_eligible' ? 'INCOMPLETE' : 'COMPLETE';
        $this->voucherModel->update($id, $updateData);

        $userId = $this->getCurrentUserId();
        log_action($userId, 'UPDATE_ELIGIBILITY', "Set student #{$id} eligibility to {$newEligibility}");

        return $this->response->setJSON([
            'success'            => true,
            'eligibility_status' => $newEligibility,
            'remarks_status'     => $updateData['remarks_status'] ?? null,
            'message'            => 'Eligibility updated.',
            'csrf_token'         => csrf_hash(),
        ]);
    }
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

    // ── Preview archive scope: count + auto archive SY ───────────────────────
    public function archivePreview()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = [];
        foreach (VoucherModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $this->request->getGet($key));
        }

        $ids = $this->voucherModel->getMatchingStudentIds($keyword, $filters);

        if (empty($ids)) {
            return $this->response->setJSON([
                'success'     => true,
                'count'       => 0,
                'schoolYears' => [],
            ]);
        }

        return $this->response->setJSON([
            'success'     => true,
            'count'       => count($ids),
            'schoolYears' => [$this->archiveSchoolYearLabel()],
        ]);
    }
}
