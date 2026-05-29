<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table         = 'students';
    protected $primaryKey    = 'student_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'voucher_no', 'voucher_date',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'rank_no', 'gwa', 'gender',
        'junior_high_school', 'preferred_senior_high_school',
        'contact_number', 'remarks_status', 'school_year',
        'eligibility_status', 'voucher_status', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $beforeInsert   = ['normalizeUppercase'];
    protected $beforeUpdate   = ['normalizeUppercase'];
    protected $afterFind      = ['normalizeUppercaseResult'];

    protected array $uppercaseFields = [
        'voucher_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'gender',
        'junior_high_school',
        'preferred_senior_high_school',
        'contact_number',
        'remarks_status',
        'school_year',
    ];

    protected function normalizeUppercase(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        foreach ($this->uppercaseFields as $field) {
            if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                $data['data'][$field] = $this->upper(trim($data['data'][$field]));
            }
        }

        return $data;
    }

    protected function normalizeUppercaseResult(array $data): array
    {
        if (empty($data['data'])) {
            return $data;
        }

        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                $row = $this->uppercaseRow($row);
            }
            unset($row);
        } elseif (is_array($data['data'])) {
            $data['data'] = $this->uppercaseRow($data['data']);
        }

        return $data;
    }

    protected function uppercaseRow(array $row): array
    {
        foreach ($this->uppercaseFields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->upper($row[$field]);
            }
        }

        if (isset($row['full_name']) && is_string($row['full_name'])) {
            $row['full_name'] = $this->upper($row['full_name']);
        }

        return $row;
    }

    protected function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    public const LISTING_DEFAULT_LIMIT = 1000;

    // Supported filter keys (also the GET param names used by the listing view).
    public const LISTING_FILTER_KEYS = [
        'school_year', 'gender', 'remarks', 'voucher_status',
        'date_from', 'date_to', 'junior_hs', 'preferred_hs',
        'gwa_min', 'gwa_max', 'eligibility',
    ];

    // When no keyword and no filter are given, return only the most recently
    // created N rows so the in-page DataTable stays fast. Advanced-search
    // (keyword) and advanced filters both hit the full table and ignore the
    // limit, replacing what's loaded.
    public function getVouchersForListing(
        string $keyword = '',
        int $limit = self::LISTING_DEFAULT_LIMIT,
        array $filters = []
    ): array {
        // Soft-archived rows (is_archived = 1) stay in the listing — they show
        // up with a disabled checkbox and an Unarchive-only action. Hard
        // archive (Archive All) deletes the row from this table entirely, so
        // those are gone by virtue of not existing here anymore.
        $builder = $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                preferred_senior_high_school, school_year,
                eligibility_status, voucher_status, is_active,
                gwa, rank_no, gender, junior_high_school,
                contact_number, remarks_status, created_at, generated_at
            ");

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $builder
                ->groupStart()
                ->like('voucher_no', $keyword)
                ->orLike('first_name', $keyword)
                ->orLike('middle_name', $keyword)
                ->orLike('last_name', $keyword)
                ->orLike('suffix', $keyword)
                ->orLike('junior_high_school', $keyword)
                ->orLike('preferred_senior_high_school', $keyword)
                ->orLike('school_year', $keyword)
                ->orLike('gender', $keyword)
                ->orLike('remarks_status', $keyword)
                ->orLike('voucher_status', $keyword)
                ->orLike('contact_number', $keyword)
                ->groupEnd();
        }

        $hasFilter = $this->applyListingFilters($builder, $filters);

        $builder
            ->orderBy('created_at', 'DESC')
            ->orderBy("CASE WHEN eligibility_status = 'eligible' THEN 0 ELSE 1 END", '', false)
            ->orderBy('is_active', 'DESC')
            ->orderBy('student_id', 'DESC');

        // Always cap the result set, even with keyword/filter applied. Without
        // this guard, a filter like eligibility=eligible can return tens of
        // thousands of rows on large databases and hang the browser. Users
        // refine further via the in-page search if they need a tighter slice.
        if ($limit > 0) {
            $builder->limit($limit);
        }

        $rows = $builder
            ->get()
            ->getResultArray();

        $rows = array_map(fn ($row) => $this->uppercaseRow($row), $rows);
        $counts = $this->getGenerateCounts(array_column($rows, 'student_id'));

        foreach ($rows as &$row) {
            $row['generate_count'] = $counts[(int) $row['student_id']] ?? 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * Server-side DataTables slice. Returns the page-sized rows plus the
     * filtered/total counts needed for DataTables' draw response.
     *
     * $params expects:
     *   start, length, search (string), order_col (int|null), order_dir (asc|desc),
     *   filters (array — same shape as getVouchersForListing)
     */
    public function getDatatableSlice(array $params): array
    {
        $start    = max(0, (int) ($params['start']    ?? 0));
        $length   = (int) ($params['length']   ?? 25);
        $search   = trim((string) ($params['search'] ?? ''));
        $orderCol = $params['order_col'] ?? null;
        $orderDir = (strtolower((string) ($params['order_dir'] ?? 'asc')) === 'desc') ? 'DESC' : 'ASC';
        $filters  = $params['filters'] ?? [];

        // Column indices match the <th> order in vouchers/index.php (including
        // the hidden name_sort column at index 3 that drives Name sorting).
        $columnMap = [
            1  => 'voucher_no',
            2  => 'last_name',          // visible Name column
            3  => 'last_name',          // hidden name_sort column (DataTables uses this to sort col 2)
            4  => 'junior_high_school',
            5  => 'preferred_senior_high_school',
            6  => 'school_year',
            7  => 'eligibility_status',
            8  => 'is_active',
            9  => 'remarks_status',
            10 => 'generate_count',
            11 => 'generated_at',
        ];

        // Total (unfiltered).
        $recordsTotal = (int) $this->db->table('students')->countAllResults();

        // Build the filtered query once. We need two passes: one for the count
        // (without LIMIT/ORDER) and one for the rows.
        $applyWhere = function ($builder) use ($search, $filters) {
            if ($search !== '') {
                $builder
                    ->groupStart()
                    ->like('voucher_no', $search)
                    ->orLike('first_name', $search)
                    ->orLike('middle_name', $search)
                    ->orLike('last_name', $search)
                    ->orLike('suffix', $search)
                    ->orLike('junior_high_school', $search)
                    ->orLike('preferred_senior_high_school', $search)
                    ->orLike('school_year', $search)
                    ->orLike('gender', $search)
                    ->orLike('remarks_status', $search)
                    ->orLike('voucher_status', $search)
                    ->orLike('contact_number', $search)
                    ->groupEnd();
            }
            $this->applyListingFilters($builder, $filters);
        };

        $countBuilder = $this->db->table('students');
        $applyWhere($countBuilder);
        $recordsFiltered = (int) $countBuilder->countAllResults();

        $builder = $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                preferred_senior_high_school, school_year,
                eligibility_status, voucher_status, is_active,
                gwa, rank_no, gender, junior_high_school,
                contact_number, remarks_status, created_at, generated_at,
                generate_count
            ");
        $applyWhere($builder);

        $orderColumn = $columnMap[(int) $orderCol] ?? null;
        if ($orderColumn !== null) {
            $builder->orderBy($orderColumn, $orderDir);
        } else {
            $builder->orderBy('last_name', 'ASC')->orderBy('first_name', 'ASC');
        }
        // Tiebreak for stable pagination.
        $builder->orderBy('student_id', 'ASC');

        if ($length > 0) {
            $builder->limit($length, $start);
        } else {
            // DataTables sends length=-1 for "All" — clamp so we don't return
            // 30k rows in a single page.
            $builder->limit(5000, $start);
        }

        $rows = array_map(fn ($r) => $this->uppercaseRow($r), $builder->get()->getResultArray());

        return [
            'rows'            => $rows,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
        ];
    }

    // Returns the distinct, non-empty values that exist in the students table
    // for each column used as a filter dropdown. Sourced from the full table
    // (not the capped listing slice) so the dropdowns always reflect every
    // value a user could actually filter against.
    public function getListingFilterOptions(): array
    {
        $distinct = function (string $column): array {
            $rows = $this->db->table('students')
                ->distinct()
                ->select($column)
                ->where($column . ' IS NOT NULL')
                ->where($column . ' !=', '')
                ->orderBy($column, 'ASC')
                ->get()
                ->getResultArray();
            return array_values(array_filter(array_map(
                static fn ($r) => trim((string) ($r[$column] ?? '')),
                $rows
            ), static fn ($v) => $v !== ''));
        };

        $fromSchoolTable = function (string $level): array {
            try {
                $rows = $this->db->table('school')
                    ->select('school_name')
                    ->where('school_level', $level)
                    ->where('is_active', 1)
                    ->get()
                    ->getResultArray();
            } catch (\Throwable $e) {
                return [];
            }
            return array_values(array_filter(array_map(
                static fn ($r) => trim((string) ($r['school_name'] ?? '')),
                $rows
            ), static fn ($v) => $v !== ''));
        };

        $mergeSchools = function (array $a, array $b): array {
            $seen = [];
            $out  = [];
            foreach (array_merge($a, $b) as $name) {
                $key = mb_strtoupper($name);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $out[] = $name;
            }
            sort($out, SORT_NATURAL | SORT_FLAG_CASE);
            return $out;
        };

        return [
            'junior_high_schools' => $mergeSchools($fromSchoolTable('JHS'), $distinct('junior_high_school')),
            'senior_high_schools' => $mergeSchools($fromSchoolTable('SHS'), $distinct('preferred_senior_high_school')),
            'school_years'        => $distinct('school_year'),
        ];
    }

    // Applies any advanced-filter clauses to the listing builder. Returns true
    // if at least one filter was applied (so the caller can skip the row cap).
    protected function applyListingFilters($builder, array $filters): bool
    {
        $value = static function (array $f, string $key): string {
            return isset($f[$key]) ? trim((string) $f[$key]) : '';
        };

        $applied = false;

        if (($v = $value($filters, 'school_year')) !== '') {
            $builder->where('school_year', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'gender')) !== '') {
            $builder->where('gender', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'remarks')) !== '') {
            $builder->where('remarks_status', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'voucher_status')) !== '') {
            $builder->where('voucher_status', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'date_from')) !== '') {
            $builder->where('voucher_date >=', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'date_to')) !== '') {
            $builder->where('voucher_date <=', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'junior_hs')) !== '') {
            $builder->where('junior_high_school', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'preferred_hs')) !== '') {
            $builder->where('preferred_senior_high_school', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'gwa_min')) !== '') {
            $builder->where('gwa >=', (float) $v);
            $applied = true;
        }
        if (($v = $value($filters, 'gwa_max')) !== '') {
            $builder->where('gwa <=', (float) $v);
            $applied = true;
        }
        if (($v = $value($filters, 'eligibility')) !== '') {
            $builder->where('eligibility_status', $v);
            $applied = true;
        }

        return $applied;
    }

    // Return every non-archived student_id matching the listing query —
    // uncapped, so "Archive All" can sweep the full DB, not just the loaded
    // listing slice. Shares its WHERE-clause logic with getVouchersForListing.
    public function getMatchingStudentIds(string $keyword = '', array $filters = []): array
    {
        // No is_archived filter: Archive All sweeps everything in the current
        // listing scope, including soft-archived rows (which would otherwise
        // be skipped and left behind in the students table).
        $builder = $this->db->table('students')
            ->select('student_id');

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $builder
                ->groupStart()
                ->like('voucher_no', $keyword)
                ->orLike('first_name', $keyword)
                ->orLike('middle_name', $keyword)
                ->orLike('last_name', $keyword)
                ->orLike('suffix', $keyword)
                ->orLike('junior_high_school', $keyword)
                ->orLike('preferred_senior_high_school', $keyword)
                ->orLike('school_year', $keyword)
                ->orLike('gender', $keyword)
                ->orLike('remarks_status', $keyword)
                ->orLike('voucher_status', $keyword)
                ->orLike('contact_number', $keyword)
                ->groupEnd();
        }

        $this->applyListingFilters($builder, $filters);

        return array_map(
            static fn ($r) => (int) $r['student_id'],
            $builder->get()->getResultArray()
        );
    }

    public function getStudentById(int $studentId): ?array
    {
        $row = $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                rank_no, gwa, gender,
                junior_high_school, preferred_senior_high_school,
                contact_number, remarks_status, school_year,
                eligibility_status, voucher_status,
                created_at, updated_at
            ")
            ->where('student_id', $studentId)
            ->get()->getRowArray() ?: null;

        return $row ? $this->uppercaseRow($row) : null;
    }

    public function getVouchersByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $rows = $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                rank_no, gwa, gender,
                junior_high_school, preferred_senior_high_school,
                contact_number, remarks_status, school_year,
                eligibility_status, voucher_status
            ")
            ->whereIn('student_id', $ids)
            ->orderBy('student_id', 'ASC')
            ->get()->getResultArray();

        return array_map(fn ($row) => $this->uppercaseRow($row), $rows);
    }

    public function getTotalGeneratedVouchers(): int
    {
        $jobs = $this->db->table('pdf_jobs')
            ->select('voucher_ids')
            ->where('status', 'done')
            ->get()
            ->getResultArray();

        $total = 0;
        foreach ($jobs as $job) {
            $ids = json_decode((string) ($job['voucher_ids'] ?? ''), true);
            if (is_array($ids)) {
                $total += count($ids);
            }
        }
        return $total;
    }

    public function getGenerateCounts(array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if (empty($studentIds)) {
            return [];
        }

        // generate_count column is maintained by JsonPdfRunner on each render
        // and backfilled from pdf_jobs by migration. O(1) per student now.
        $rows = $this->db->table('students')
            ->select('student_id, generate_count')
            ->whereIn('student_id', $studentIds)
            ->get()
            ->getResultArray();

        $counts = array_fill_keys($studentIds, 0);
        foreach ($rows as $r) {
            $counts[(int) $r['student_id']] = (int) ($r['generate_count'] ?? 0);
        }
        return $counts;
    }
}
