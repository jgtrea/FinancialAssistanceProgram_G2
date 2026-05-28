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

        $totalUsers     = $userModel->countAll();
        $totalVouchers  = $db->table('students')->countAllResults();
        $generatedCount = $voucherModel->getTotalGeneratedVouchers();
        $pendingCount   = $db->table('students')->where('voucher_status', 'not_generated')->countAllResults();
        $totalArchived  = $db->table('student_archive')->countAll();

        $recentVouchers = $db->table('students')
            ->select("
                voucher_no,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                junior_high_school,
                voucher_status,
                generated_at
            ")
                        ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        return view('dashboard/index', [
            'title'          => 'Dashboard',
            'myVouchers'     => $totalVouchers,
            'generated'      => $generatedCount,
            'pending'        => $pendingCount,
            'archived'       => $totalArchived,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}
