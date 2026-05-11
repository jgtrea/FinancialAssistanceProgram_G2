<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

class Report extends Controller
{
    public function logs()
    {
        $db = \Config\Database::connect();

        $logs = $db->table('audit_log a')
            ->select('
                a.audit_id,
                a.action,
                a.description,
                a.ip_address,
                a.user_agent,
                a.created_at,
                a.student_id,
                u.username,
                u.full_name,
                s.voucher_no
            ')
            ->join('users u',    'u.user_id = a.user_id',         'left')
            ->join('students s', 's.student_id = a.student_id',  'left')
            ->orderBy('a.created_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/logs', [
            'title' => 'Audit Logs',
            'logs'  => $logs,
        ]);
    }
}
