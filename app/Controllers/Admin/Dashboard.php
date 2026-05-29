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
        $totalVouchers  = $db->table('students')->countAllResults();
        $generatedCount = $db->table('students')->where('generated_at IS NOT NULL', null, false)->countAllResults();
        $eligibleCount  = $db->table('students')->where('eligibility_status', 'eligible')->countAllResults();
        $notEligibleCount = $db->table('students')->where('eligibility_status', 'not_eligible')->countAllResults();

        $recentVouchers = $db->table('students')
            ->select("
                voucher_no,
                first_name, middle_name, last_name,
                TRIM(CONCAT_WS(' ', NULLIF(last_name,''), NULLIF(first_name,''), NULLIF(middle_name,''))) AS name_sort,
                junior_high_school,
                voucher_status,
                generated_at
            ")
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        return view('dashboard/index', [
            'title'          => 'Dashboard',
            'totalUsers'     => $totalUsers,
            'myVouchers'     => $totalVouchers,
            'generated'      => $generatedCount,
            'eligible'       => $eligibleCount,
            'notEligible'    => $notEligibleCount,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}
