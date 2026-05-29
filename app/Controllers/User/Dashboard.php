<?php

namespace App\Controllers\User;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();

        $myVouchers   = $db->table('students')->countAllResults();
        $generated    = $db->table('students')->where('generated_at IS NOT NULL', null, false)->countAllResults();
        $eligible     = $db->table('students')->where('eligibility_status', 'eligible')->countAllResults();
        $notEligible  = $db->table('students')->where('eligibility_status', 'not_eligible')->countAllResults();

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
            'myVouchers'     => $myVouchers,
            'generated'      => $generated,
            'eligible'       => $eligible,
            'notEligible'    => $notEligible,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}
