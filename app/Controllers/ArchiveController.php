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
        $keyword = trim((string) $this->request->getGet('q'));

        // Prevent non-admin users from accessing user archive
        if ($type === 'user' && $role !== 'admin') {
            $type = 'voucher';
        }

        $data = [
            'title' => 'Archive',
            'type'  => $type,
            'keyword' => $keyword,
        ];

        if ($type === 'user') {
            $query = (new UserModel())->where('is_active', 0);
            if ($keyword !== '') {
                $query
                    ->groupStart()
                    ->like('username', $keyword)
                    ->orLike('email', $keyword)
                    ->orLike('role', $keyword)
                    ->groupEnd();
            }
            $data['users'] = $query
                ->orderBy('user_id', 'DESC')
                ->findAll();
        } elseif ($type === 'signatory') {
            $query = (new SignatoryModel())->where('is_active', 0);
            if ($keyword !== '') {
                $query
                    ->groupStart()
                    ->like('first_name', $keyword)
                    ->orLike('middle_name', $keyword)
                    ->orLike('last_name', $keyword)
                    ->orLike('suffix', $keyword)
                    ->orLike('position_title', $keyword)
                    ->groupEnd();
            }
            $data['signatories'] = $query
                ->orderBy('signatory_id', 'DESC')
                ->findAll();
        } else {
            $data['vouchers'] = (new ArchiveModel())->getArchivesForListing($keyword);
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
