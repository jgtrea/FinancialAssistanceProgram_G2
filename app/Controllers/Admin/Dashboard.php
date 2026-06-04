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
            ->join('school jhs', 'jhs.school_id = students.junior_high_school', 'left', false)
            ->select("
                students.voucher_no,
                students.first_name, students.middle_name, students.last_name,
                TRIM(CONCAT_WS(' ', NULLIF(students.last_name,''), NULLIF(students.first_name,''), NULLIF(students.middle_name,''))) AS name_sort,
                COALESCE(jhs.school_name, students.junior_high_school) AS junior_high_school,
                students.voucher_status,
                students.generated_at
            ")
            ->orderBy('students.created_at', 'DESC')
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
