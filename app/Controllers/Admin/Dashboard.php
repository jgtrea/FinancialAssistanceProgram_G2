<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $userModel = new UserModel();
        $db        = \Config\Database::connect();

        $totalUsers     = $userModel->countAll();
        $totalVouchers  = $db->table('students')->where('is_archived', 0)->countAllResults();
        $generatedCount = $db->table('students')->where('is_archived', 0)->where('voucher_status', 'generated')->countAllResults();
        $pendingCount   = $db->table('students')->where('is_archived', 0)->where('voucher_status', 'not_generated')->countAllResults();
        $totalArchived  = $db->table('student_archive')->countAll();

        $recentVouchers = $db->table('students')
            ->select("
                voucher_no,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                voucher_status,
                created_at
            ")
            ->where('is_archived', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(6)
            ->get()->getResultArray();

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
