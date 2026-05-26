<?php

namespace App\Controllers;

use App\Models\ArchiveModel;

class ArchiveController extends BaseController
{
    // Archive only covers vouchers. Signatories are restored from the
    // /signatories page itself, and the legacy 'user' type was removed —
    // both legacy query strings collapse to the vouchers view.
    public function index()
    {
        $keyword = trim((string) $this->request->getGet('q'));

        $data = [
            'title'    => 'Archive',
            'type'     => 'voucher',
            'keyword'  => $keyword,
            'vouchers' => (new ArchiveModel())->getArchivesForListing($keyword),
        ];

        return view('archive/index', $data);
    }
}
