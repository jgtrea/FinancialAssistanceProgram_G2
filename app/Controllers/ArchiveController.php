<?php

namespace App\Controllers;

use App\Models\StudentArchiveModel;

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
            $data['users'] = [];
        } else {
            $data['vouchers'] = [];
        }

        return view('archive/index', $data);
    }
}