<?php

namespace App\Models;

use CodeIgniter\Model;

class ArchiveModel extends Model
{
    protected $table         = 'student_archive';
    protected $primaryKey    = 'archive_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'student_id', 'control_no', 'voucher_no', 'voucher_date',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'rank_no', 'gwa', 'gender',
        'junior_high_school', 'preferred_senior_high_school',
        'contact_number', 'remarks_status', 'other_remarks', 'school_year',
        /* 'eligibility_status', */ 'voucher_status', 'evaluated_by',
        'archive_reason', 'archived_by', 'archived_at',
    ];

    public const LISTING_DEFAULT_LIMIT = 1000;

    // Sentinel SY value for archived rows that have no school_year. Lets users
    // still reach those rows via the (otherwise required) SY filter.
    public const NO_SCHOOL_YEAR = '(No School Year)';

    // Supported filter keys (also the GET param names used by the archive view).
    // Mirrors VoucherModel::LISTING_FILTER_KEYS so the two pages behave the
    // same way; date_from/date_to here filter on voucher_date, not archived_at.
    public const LISTING_FILTER_KEYS = [
        'school_year', 'gender', 'remarks', 'voucher_status',
        'date_from', 'date_to', 'junior_hs', 'preferred_hs',
        'gwa_min', 'gwa_max',
    ];

    // When no keyword and no filter are given, return only the most recently
    // archived N rows so the in-page DataTable stays fast. Advanced-search
    // (keyword) and advanced filters both hit the full table and ignore the
    // limit, replacing what's loaded.
    public function getArchivesForListing(
        string $keyword = '',
        int $limit = self::LISTING_DEFAULT_LIMIT,
        array $filters = []
    ): array {
        $builder = $this->db->table('student_archive a')
            ->select("
                a.*,
                COALESCE(jhs.school_name, a.junior_high_school) AS junior_high_school,
                COALESCE(shs.school_name, a.preferred_senior_high_school) AS preferred_senior_high_school,
                CONCAT_WS(' ', NULLIF(a.first_name,''), NULLIF(a.middle_name,''), NULLIF(a.last_name,''), NULLIF(a.suffix,'')) AS full_name,
                TRIM(CONCAT_WS(' ', NULLIF(a.last_name,''), NULLIF(a.first_name,''), NULLIF(a.middle_name,''))) AS name_sort,
                TRIM(CONCAT_WS(' ', NULLIF(u.first_name,''), NULLIF(u.middle_name,''), NULLIF(u.last_name,''))) AS archived_by_name
            ")
            ->join('school jhs', 'jhs.school_id = a.junior_high_school', 'left', false)
            ->join('school shs', 'shs.school_id = a.preferred_senior_high_school', 'left', false)
            ->join('users u', 'u.user_id = a.archived_by', 'left');

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $builder
                ->groupStart()
                ->like('a.voucher_no', $keyword)
                ->orLike('a.first_name', $keyword)
                ->orLike('a.middle_name', $keyword)
                ->orLike('a.last_name', $keyword)
                ->orLike('a.suffix', $keyword)
                ->orLike('a.junior_high_school', $keyword)
                ->orLike('a.preferred_senior_high_school', $keyword)
                ->orLike('jhs.school_name', $keyword)
                ->orLike('jhs.acronym', $keyword)
                ->orLike('shs.school_name', $keyword)
                ->orLike('shs.acronym', $keyword)
                ->orLike('a.school_year', $keyword)
                ->orLike('a.archive_reason', $keyword)
                ->orLike('u.first_name', $keyword)
                ->orLike('u.last_name', $keyword)
                ->groupEnd();
        }

        $hasFilter = $this->applyListingFilters($builder, $filters);

        $builder
            ->orderBy('a.archived_at', 'DESC')
            ->orderBy('a.archive_id', 'DESC');

        if ($keyword === '' && !$hasFilter && $limit > 0) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Server-side DataTables slice for the archive listing. Mirrors
     * VoucherModel::getDatatableSlice but trimmed (no checkboxes/actions).
     * The archive is gated on SY: without a school_year filter this returns an
     * empty set, so the page never dumps the whole table into the DOM.
     *
     * $params: start, length, keyword (outer ?q), search (inner DT box),
     *          order_col (int|null), order_dir (asc|desc),
     *          filters (array — same shape as getArchivesForListing).
     */
    public function getDatatableSlice(array $params): array
    {
        $start    = max(0, (int) ($params['start']  ?? 0));
        $length   = (int) ($params['length'] ?? 25);
        $keyword  = trim((string) ($params['keyword'] ?? ''));
        $search   = trim((string) ($params['search']  ?? ''));
        $orderCol = $params['order_col'] ?? null;
        $orderDir = (strtolower((string) ($params['order_dir'] ?? 'asc')) === 'desc') ? 'DESC' : 'ASC';
        $filters  = $params['filters'] ?? [];

        $schoolYear = trim((string) ($filters['school_year'] ?? ''));
        if ($schoolYear === '') {
            // No SY chosen — load nothing (matches the page's gated empty state).
            return ['rows' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0];
        }

        // Column indices match the <th> order in archive/index.php (the hidden
        // name_sort column at index 2 drives Name sorting). Columns without an
        // entry (Printed / Last Generated) are not server-sortable.
        $columnMap = [
            0 => 'a.voucher_no',
            1 => 'a.last_name',
            2 => 'a.last_name',
            3 => 'jhs.school_name',
            4 => 'shs.school_name',
            5 => 'a.school_year',
            6 => 'a.remarks_status',
            9 => 'a.archived_at',
        ];

        $base = fn () => $this->db->table('student_archive a')
            ->join('school jhs', 'jhs.school_id = a.junior_high_school', 'left', false)
            ->join('school shs', 'shs.school_id = a.preferred_senior_high_school', 'left', false);

        $likeGroup = static function ($builder, string $needle): void {
            $builder->groupStart()
                ->like('a.voucher_no', $needle)
                ->orLike('a.first_name', $needle)
                ->orLike('a.middle_name', $needle)
                ->orLike('a.last_name', $needle)
                ->orLike('a.suffix', $needle)
                ->orLike('jhs.school_name', $needle)
                ->orLike('jhs.acronym', $needle)
                ->orLike('shs.school_name', $needle)
                ->orLike('shs.acronym', $needle)
                ->orLike('a.school_year', $needle)
                ->orLike('a.remarks_status', $needle)
                ->orLike('a.other_remarks', $needle)
                ->groupEnd();
        };

        $applyWhere = function ($builder) use ($keyword, $search, $filters, $likeGroup): void {
            if ($keyword !== '') $likeGroup($builder, $keyword);
            if ($search  !== '') $likeGroup($builder, $search);
            $this->applyListingFilters($builder, $filters);
        };

        // recordsTotal = everything in the chosen SY (the baseline scope). The
        // "(No School Year)" bucket matches rows with a NULL/blank school_year.
        $totalBuilder = $base();
        $this->whereSchoolYear($totalBuilder, $schoolYear);
        $recordsTotal = (int) $totalBuilder->countAllResults();

        $countBuilder = $base();
        $applyWhere($countBuilder);
        $recordsFiltered = (int) $countBuilder->countAllResults();

        $builder = $base()->select("
            a.*,
            COALESCE(jhs.school_name, a.junior_high_school) AS junior_high_school,
            COALESCE(shs.school_name, a.preferred_senior_high_school) AS preferred_senior_high_school
        ");
        $applyWhere($builder);

        $orderColumn = $columnMap[(int) $orderCol] ?? null;
        if ($orderColumn !== null) {
            $builder->orderBy($orderColumn, $orderDir);
        } else {
            $builder->orderBy('a.archived_at', 'DESC');
        }
        $builder->orderBy('a.archive_id', 'DESC'); // stable tiebreak

        if ($length > 0) {
            $builder->limit($length, $start);
        }

        return [
            'rows'            => $builder->get()->getResultArray(),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
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
            $this->whereSchoolYear($builder, $v);
            $applied = true;
        }
        if (($v = $value($filters, 'gender')) !== '') {
            $builder->where('a.gender', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'remarks')) !== '') {
            $builder->where('a.remarks_status', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'voucher_status')) !== '') {
            $builder->where('a.voucher_status', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'date_from')) !== '') {
            $builder->where('a.voucher_date >=', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'date_to')) !== '') {
            $builder->where('a.voucher_date <=', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'junior_hs')) !== '') {
            $builder->groupStart()
                ->where('a.junior_high_school', $v)
                ->orWhere('jhs.school_name', $v)
                ->groupEnd();
            $applied = true;
        }
        if (($v = $value($filters, 'preferred_hs')) !== '') {
            $builder->groupStart()
                ->where('a.preferred_senior_high_school', $v)
                ->orWhere('shs.school_name', $v)
                ->groupEnd();
            $applied = true;
        }
        if (($v = $value($filters, 'gwa_min')) !== '') {
            $builder->where('a.gwa >=', (float) $v);
            $applied = true;
        }
        if (($v = $value($filters, 'gwa_max')) !== '') {
            $builder->where('a.gwa <=', (float) $v);
            $applied = true;
        }

        return $applied;
    }

    // Apply the SY filter to a builder, translating the NO_SCHOOL_YEAR sentinel
    // into a NULL/blank match so the "(No School Year)" bucket works everywhere.
    protected function whereSchoolYear($builder, string $schoolYear): void
    {
        if ($schoolYear === self::NO_SCHOOL_YEAR) {
            $builder->groupStart()
                ->where('a.school_year IS NULL', null, false)
                ->orWhere('a.school_year', '')
                ->groupEnd();
        } else {
            $builder->where('a.school_year', $schoolYear);
        }
    }

    // Distinct school years that exist in the archive. Used to populate the
    // SY dropdown on the listing page — the archive is gated on school_year
    // selection, so this list is what the user actually chooses. If any archived
    // rows have no school_year, a "(No School Year)" bucket is appended so those
    // rows stay reachable through the (required) SY filter.
    public function getDistinctSchoolYears(): array
    {
        $rows = $this->db->table('student_archive')
            ->select('school_year')
            ->distinct()
            ->where('school_year IS NOT NULL')
            ->where("school_year !=", '')
            ->orderBy('school_year', 'DESC')
            ->get()
            ->getResultArray();

        $years = array_values(array_filter(array_map(
            static fn ($r) => trim((string) ($r['school_year'] ?? '')),
            $rows
        )));

        $blank = (int) $this->db->table('student_archive')
            ->groupStart()
                ->where('school_year IS NULL', null, false)
                ->orWhere('school_year', '')
            ->groupEnd()
            ->countAllResults();
        if ($blank > 0) {
            $years[] = self::NO_SCHOOL_YEAR;
        }

        return $years;
    }
}
