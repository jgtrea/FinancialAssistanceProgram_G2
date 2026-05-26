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

        $data = [
            'title'       => 'Archive',
            'type'        => 'voucher',
            'keyword'     => $keyword,
            'filters'     => $filters,
            'vouchers'    => $vouchers,
            'schoolYears' => $archiveModel->getDistinctSchoolYears(),
            // School dropdowns for the filter modal — drawn from the full
            // school options table so they work against the entire archive,
            // not just the loaded slice.
            'juniorHighSchools' => (new SchoolOptionModel())->getJuniorHighSchools(),
            'seniorHighSchools' => (new SchoolOptionModel())->getSeniorHighSchools(),
        ];

        return view('archive/index', $data);
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
