<?php

namespace App\Controllers;

use App\Models\ArchiveModel;
use App\Models\SignatoryModel;
use App\Models\UserModel;

class ArchiveController extends BaseController
{
    public function index()
    {
        $role = session('role') ?: 'user';
        $type = $this->request->getGet('type') ?? ($role === 'admin' ? 'user' : 'voucher');

        // Prevent non-admin users from accessing user archive
        if ($type === 'user' && $role !== 'admin') {
            $type = 'voucher';
        }

        $data = [
            'title' => 'Archive',
            'type'  => $type,
        ];

        if ($type === 'user') {
            $data['users'] = (new UserModel())
                ->where('is_active', 0)
                ->orderBy('user_id', 'DESC')
                ->findAll();
        } elseif ($type === 'signatory') {
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
