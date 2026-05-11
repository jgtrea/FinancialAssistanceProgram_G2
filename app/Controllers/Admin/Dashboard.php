<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;
use App\Models\VoucherModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $userModel    = new UserModel();
        $voucherModel = new VoucherModel();
        $db           = \Config\Database::connect();

        // Stats
        $totalUsers      = $userModel->countAll();
        $totalVouchers   = $voucherModel->countAll();
        $generatedCount  = $voucherModel->where('voucher_status', 'generated')->countAllResults();
        $pendingCount    = $voucherModel->where('voucher_status', 'not_generated')->countAllResults();
        $totalArchived   = $db->table('student_archive')->countAll();

        // Recent vouchers
        $recentVouchers = $db->table('vouchers v')
            ->select('v.voucher_no, v.recipient_name, v.amount, v.voucher_status, v.created_at, u.username')
            ->join('users u', 'u.user_id = v.created_by', 'left')
            ->orderBy('v.created_at', 'DESC')
            ->limit(6)
            ->get()->getResultArray();

        // Recent audit logs
        $recentLogs = $db->table('audit_log a')
            ->select('a.action, a.description, a.ip_address, a.created_at, u.username')
            ->join('users u', 'u.user_id = a.user_id', 'left')
            ->orderBy('a.created_at', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        return view('admin/dashboard', [
            'title'          => 'Dashboard',
            'totalUsers'     => $totalUsers,
            'totalVouchers'  => $totalVouchers,
            'generatedCount' => $generatedCount,
            'pendingCount'   => $pendingCount,
            'totalArchived'  => $totalArchived,
            'recentVouchers' => $recentVouchers,
            'recentLogs'     => $recentLogs,
        ]);
    }
}