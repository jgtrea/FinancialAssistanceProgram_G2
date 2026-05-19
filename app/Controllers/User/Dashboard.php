<?php

namespace App\Controllers\User;

use App\Models\VoucherModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $db           = \Config\Database::connect();
        $voucherModel = new VoucherModel();

        $myVouchers = $db->table('students')->where('is_archived', 0)->countAllResults();
        $generated  = $voucherModel->getTotalGeneratedVouchers();
        $pending    = $db->table('students')->where('is_archived', 0)->where('voucher_status', 'not_generated')->countAllResults();
        $archived   = $db->table('student_archive')->countAll();

        $recentVouchers = $db->table('students')
            ->select("
                voucher_no,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                preferred_senior_high_school,
                voucher_status,
                created_at
            ")
            ->where('is_archived', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        return view('dashboard/index', [
            'title'          => 'Dashboard',
            'myVouchers'     => $myVouchers,
            'generated'      => $generated,
            'pending'        => $pending,
            'archived'       => $archived,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}
