<?php

namespace App\Controllers;

use App\Models\ArchiveModel;
use App\Models\SchoolOptionModel;

class ArchiveController extends BaseController
{
    // Archive only covers vouchers. Signatories are restored from the
    // /signatories page itself, and the legacy 'user' type was removed —
    // both legacy query strings collapse to the vouchers view.
    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $filters = $this->getListingFilters();

        $archiveModel = new ArchiveModel();

        // The archive table is gated on School Year — no data loads until the
        // user picks one. This keeps the initial view fast and forces an
        // explicit scope (the archive can grow indefinitely).
        $hasSchoolYear = trim((string) ($filters['school_year'] ?? '')) !== '';
        $vouchers      = $hasSchoolYear
            ? $archiveModel->getArchivesForListing(
                $keyword,
                ArchiveModel::LISTING_DEFAULT_LIMIT,
                $filters
            )
            : [];

        $schoolModel = new SchoolOptionModel();

        $data = [
            'title'       => 'Archive',
            'type'        => 'voucher',
            'keyword'     => $keyword,
            'filters'     => $filters,
            'vouchers'    => $vouchers,
            'schoolYears' => $archiveModel->getDistinctSchoolYears(),
            // School dropdowns — merged from the school options table, the
            // distinct values present in student_archive, AND the distinct
            // values still present in the live students table, so users can
            // filter by any school that has ever appeared.
            'juniorHighSchools' => $this->mergeSchoolOptions(
                $schoolModel->getJuniorHighSchools(),
                array_merge(
                    $archiveModel->getDistinctSchools('junior_high_school'),
                    $this->distinctFromStudents('junior_high_school')
                )
            ),
            'seniorHighSchools' => $this->mergeSchoolOptions(
                $schoolModel->getSeniorHighSchools(),
                array_merge(
                    $archiveModel->getDistinctSchools('preferred_senior_high_school'),
                    $this->distinctFromStudents('preferred_senior_high_school')
                )
            ),
        ];

        return view('archive/index', $data);
    }

    // Distinct non-empty school values from the live students table —
    // folded into archive filter dropdowns so a school that exists only in
    // active records (not in school table or archive) stays selectable.
    protected function distinctFromStudents(string $column): array
    {
        if (!in_array($column, ['junior_high_school', 'preferred_senior_high_school'], true)) {
            return [];
        }
        try {
            $alias = $column === 'junior_high_school' ? 'jhs' : 'shs';
            $rows = \Config\Database::connect()->table('students')
                ->distinct()
                ->select("COALESCE({$alias}.school_name, students.{$column}) AS school_name")
                ->join('school jhs', 'jhs.school_id = students.junior_high_school', 'left', false)
                ->join('school shs', 'shs.school_id = students.preferred_senior_high_school', 'left', false)
                ->where('students.' . $column . ' IS NOT NULL', null, false)
                ->where('students.' . $column . ' !=', '')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_values(array_filter(array_map(
            static fn ($r) => trim((string) ($r['school_name'] ?? '')),
            $rows
        ), static fn ($v) => $v !== ''));
    }

    // Merge school-table rows (['school_name' => ...]) with a flat list of
    // distinct strings into one deduped, naturally-sorted shape that the
    // archive view expects (array of ['school_name' => ...]).
    protected function mergeSchoolOptions(array $schoolRows, array $extraNames): array
    {
        $seen = [];
        $out  = [];

        $push = static function (string $name) use (&$seen, &$out) {
            $name = trim($name);
            if ($name === '') return;
            $key = mb_strtoupper($name);
            if (isset($seen[$key])) return;
            $seen[$key] = true;
            $out[] = ['school_name' => $name];
        };

        foreach ($schoolRows as $r) $push((string) ($r['school_name'] ?? ''));
        foreach ($extraNames as $n) $push((string) $n);

        usort($out, static fn ($a, $b) => strnatcasecmp($a['school_name'], $b['school_name']));
        return $out;
    }

    protected function getListingFilters(): array
    {
        $req = $this->request;
        $filters = [];
        foreach (ArchiveModel::LISTING_FILTER_KEYS as $key) {
            $filters[$key] = trim((string) $req->getGet($key));
        }
        return $filters;
    }
}
