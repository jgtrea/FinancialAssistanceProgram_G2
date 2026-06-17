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

        // Default school_year to the current SY so the table loads on first visit.
        if (trim((string) ($filters['school_year'] ?? '')) === '') {
            $year      = (int) date('Y');
            $month     = (int) date('n');
            $startYear = $month >= 6 ? $year : $year - 1;
            $filters['school_year'] = $startYear . '-' . ($startYear + 1);
        }

        $hasSchoolYear = trim((string) ($filters['school_year'] ?? '')) !== '';

        return view('archive/index', [
            'title'         => 'Archive',
            'type'          => 'voucher',
            'keyword'       => $keyword,
            'filters'       => $filters,
            'hasSchoolYear' => $hasSchoolYear,
        ]);
    }

    /**
     * Lazy-loaded filter dropdown data (SY list + school lists), fetched by the
     * archive page the first time the user opens the Filters modal. School lists
     * come straight from the school table (the authoritative source — small and
     * fast), not from scanning student_archive. SY is a cheap indexed DISTINCT.
     */
    public function filterOptions()
    {
        $archiveModel = new ArchiveModel();
        $schoolModel  = new SchoolOptionModel();

        return $this->response->setJSON([
            'schoolYears'       => $archiveModel->getDistinctSchoolYears(),
            'juniorHighSchools' => $schoolModel->getJuniorHighSchools(),
            'seniorHighSchools' => $schoolModel->getSeniorHighSchools(),
        ]);
    }

    /**
     * Server-side DataTables endpoint for the archive listing. Returns
     * DataTables-shaped JSON: { draw, recordsTotal, recordsFiltered, data }.
     * Gated on SY inside the model — no SY filter ⇒ empty result.
     */
    public function datatable()
    {
        $req     = $this->request;
        $draw    = (int) $req->getGet('draw');
        $start   = (int) $req->getGet('start');
        $length  = (int) ($req->getGet('length') ?? 25);
        // Outer ?q widens within the SY scope; inner DataTables box narrows.
        $keyword = (string) ($req->getGet('q') ?? '');
        $search  = (string) ($req->getGet('search')['value'] ?? '');
        $orderC  = $req->getGet('order')[0]['column'] ?? null;
        $orderD  = $req->getGet('order')[0]['dir']    ?? 'asc';

        $filters = [];
        foreach (ArchiveModel::LISTING_FILTER_KEYS as $k) {
            $filters[$k] = trim((string) $req->getGet($k));
        }

        $slice = (new ArchiveModel())->getDatatableSlice([
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
            $data[] = $this->renderArchiveRowForDatatable($v);
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $slice['recordsTotal'],
            'recordsFiltered' => $slice['recordsFiltered'],
            'data'            => $data,
        ]);
    }

    // Mirror of the per-row HTML the archive view used to emit in PHP, now keyed
    // by DataTables `data` column names so the markup lives in one place.
    protected function renderArchiveRowForDatatable(array $v): array
    {
        $lastName  = trim((string) ($v['last_name']   ?? ''));
        $firstName = trim((string) ($v['first_name']  ?? ''));
        $middle    = trim((string) ($v['middle_name'] ?? ''));
        $firstMid  = implode(' ', array_filter([$firstName, $middle]));
        $name      = $lastName !== '' ? $lastName . ($firstMid !== '' ? ', ' . $firstMid : '') : $firstMid;
        $nameSort  = trim($lastName . ' ' . $firstName . ' ' . $middle);

        $archivedAt = !empty($v['archived_at']) ? date('M d, Y h:i A', strtotime($v['archived_at'])) : '-';
        $lastGen    = !empty($v['generated_at']) ? date('M d, Y', strtotime($v['generated_at'])) : '-';

        return [
            'DT_RowAttr' => [
                'data-archived-date' => !empty($v['archived_at']) ? date('Y-m-d', strtotime($v['archived_at'])) : '',
            ],
            'voucher_no'     => esc($v['voucher_no'] ?: '-'),
            'name'           => esc($name),
            'name_sort'      => esc($nameSort),
            'rank_no'        => isset($v['rank_no']) && $v['rank_no'] !== null && $v['rank_no'] !== ''
                                    ? esc(rtrim(rtrim(number_format((float) $v['rank_no'], 2, '.', ''), '0'), '.'))
                                    : '-',
            'jhs'            => esc((string) ($v['junior_high_school'] ?: '-')),
            'shs'            => esc((string) ($v['preferred_senior_high_school'] ?: '-')),
            'sy'             => esc((string) ($v['school_year'] ?? '')),
            'remarks'        => esc($v['remarks_status'] ?: '-'),
            'voucher_status' => ($v['voucher_status'] ?? '') === 'generated'
                                    ? '<span class="badge bg-success">Generated</span>'
                                    : '<span class="badge bg-warning text-dark">Not Generated</span>',
            'printed'        => esc((string) ($v['generate_count'] ?? 0)),
            'last_generated' => esc($lastGen),
            'archived_at'    => esc($archivedAt),
        ];
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
