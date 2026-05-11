<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

class Report extends Controller
{
    // ── Audit Logs ─────────────────────────────────────────────────────────────
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
                a.voucher_id,
                u.username,
                u.full_name,
                v.voucher_no
            ')
            ->join('users u',    'u.user_id = a.user_id',       'left')
            ->join('vouchers v', 'v.voucher_id = a.voucher_id', 'left')
            ->orderBy('a.created_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/logs', [
            'title' => 'Audit Logs',
            'logs'  => $logs,
        ]);
    }

    // ── Reports overview (future use) ─────────────────────────────────────────
    public function index()
    {
        $db = \Config\Database::connect();

        // Vouchers per month (last 6 months)
        $monthly = $db->query("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') AS month,
                COUNT(*) AS total,
                SUM(amount) AS total_amount
            FROM vouchers
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY created_at ASC
        ")->getResultArray();

        // Vouchers per school
        $bySchool = $db->query("
            SELECT
                senior_high_school AS school,
                COUNT(*) AS total,
                SUM(amount) AS total_amount
            FROM vouchers
            GROUP BY senior_high_school
            ORDER BY total DESC
            LIMIT 10
        ")->getResultArray();

        return view('admin/reports', [
            'title'    => 'Reports',
            'monthly'  => $monthly,
            'bySchool' => $bySchool,
        ]);
    }
}