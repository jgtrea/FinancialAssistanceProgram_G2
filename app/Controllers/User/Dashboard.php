<?php

namespace App\Controllers\User;

use App\Models\VoucherModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $userId       = current_user_id();
        $voucherModel = new VoucherModel();
        $db           = \Config\Database::connect();

        $myVouchers = $voucherModel->where('created_by', $userId)->countAllResults();
        $generated  = $voucherModel->where('created_by', $userId)->where('voucher_status', 'generated')->countAllResults();
        $pending    = $voucherModel->where('created_by', $userId)->where('voucher_status', 'not_generated')->countAllResults();
        $archived   = $db->table('student_archive')->where('archived_by', $userId)->countAll();

        $recentVouchers = $db->table('vouchers')
            ->where('created_by', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(6)
            ->get()->getResultArray();

        return view('user/dashboard', [
            'title'          => 'My Dashboard',
            'myVouchers'     => $myVouchers,
            'generated'      => $generated,
            'pending'        => $pending,
            'archived'       => $archived,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}