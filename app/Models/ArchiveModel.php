<?php

namespace App\Models;

use CodeIgniter\Model;

class ArchiveModel extends Model
{
    protected $table         = 'student_archive';
    protected $primaryKey    = 'archive_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'student_id', 'voucher_no', 'voucher_date',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'rank_no', 'gwa', 'gender',
        'junior_high_school', 'preferred_senior_high_school',
        'contact_number', 'remarks_status', 'school_year',
        'eligibility_status', 'voucher_status',
        'archive_reason', 'archived_by', 'archived_at',
    ];

    public const LISTING_DEFAULT_LIMIT = 1000;

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
                CONCAT_WS(' ', NULLIF(a.first_name,''), NULLIF(a.middle_name,''), NULLIF(a.last_name,''), NULLIF(a.suffix,'')) AS full_name,
                u.username AS archived_by_name
            ")
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
                ->orLike('a.school_year', $keyword)
                ->orLike('a.archive_reason', $keyword)
                ->orLike('u.username', $keyword)
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

    // Applies any advanced-filter clauses to the listing builder. Returns true
    // if at least one filter was applied (so the caller can skip the row cap).
    protected function applyListingFilters($builder, array $filters): bool
    {
        $value = static function (array $f, string $key): string {
            return isset($f[$key]) ? trim((string) $f[$key]) : '';
        };

        $applied = false;

        if (($v = $value($filters, 'school_year')) !== '') {
            $builder->where('a.school_year', $v);
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
            $builder->where('a.junior_high_school', $v);
            $applied = true;
        }
        if (($v = $value($filters, 'preferred_hs')) !== '') {
            $builder->where('a.preferred_senior_high_school', $v);
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

    // Distinct school years that exist in the archive. Used to populate the
    // School Year dropdown on the listing page — the archive is gated on
    // school_year selection, so this list is what the user actually chooses
    // from to load data.
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

        return array_values(array_filter(array_map(
            static fn ($r) => trim((string) ($r['school_year'] ?? '')),
            $rows
        )));
    }

    // Distinct non-empty values for a given school column. Used to fold
    // archive-only school names (no longer in the school options table)
    // into the filter dropdown so they remain selectable.
    public function getDistinctSchools(string $column): array
    {
        if (!in_array($column, ['junior_high_school', 'preferred_senior_high_school'], true)) {
            return [];
        }

        $rows = $this->db->table('student_archive')
            ->select($column)
            ->distinct()
            ->where($column . ' IS NOT NULL')
            ->where($column . ' !=', '')
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map(
            static fn ($r) => trim((string) ($r[$column] ?? '')),
            $rows
        ), static fn ($v) => $v !== ''));
    }
}
