<?php

namespace App\Controllers;

use App\Models\ArchiveModel;
use App\Models\SignatoryModel;

class ArchiveController extends BaseController
{
    public function index()
    {
        $type = $this->request->getGet('type') ?? 'voucher';

        // 'user' type no longer exists in archive — redirect to vouchers
        if ($type === 'user') {
            $type = 'voucher';
        }

        $data = [
            'title' => 'Archive',
            'type'  => $type,
        ];

        if ($type === 'signatory') {
            $data['signatories'] = (new SignatoryModel())
                ->where('is_active', 0)
                ->orderBy('signatory_id', 'DESC')
                ->findAll();
        } else {
            $data['vouchers'] = (new ArchiveModel())->getArchivesForListing();
        }

        return view('archive/index', $data);
    }

    public function restoreSignatory(int $id)
    {
        (new SignatoryModel())->update($id, ['is_active' => 1]);

        log_action(session()->get('user_id'), 'RESTORE_SIGNATORY', "Restored signatory #{$id}");

        return redirect()->to(site_url('archive?type=signatory'))->with('success', 'Signatory restored successfully.');
    }
}
