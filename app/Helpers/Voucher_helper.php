<?php

if (!function_exists('generate_voucher_no')) {
    function generate_voucher_no(string $jhs = '', string $year = ''): string
    {
        $db   = \Config\Database::connect();
        $year = $year ?: date('Y');

        // Build acronym: first letter of each word, uppercase
        $jhs = trim($jhs);
        if ($jhs !== '') {
            $words   = preg_split('/\s+/', $jhs);
            $acronym = strtoupper(implode('', array_map(fn($w) => $w[0] ?? '', array_filter($words))));
        } else {
            $acronym = 'VOU';
        }

        $prefix = "{$acronym}-{$year}-";

        // Cast the numeric suffix so 1000 > 999 (avoids string-sort issues)
        $row = $db->query(
            'SELECT MAX(CAST(SUBSTRING(voucher_no, ?) AS UNSIGNED)) AS max_seq
             FROM students
             WHERE voucher_no LIKE ?',
            [strlen($prefix) + 1, $prefix . '%']
        )->getRow();

        $seq = ($row && $row->max_seq) ? (int) $row->max_seq + 1 : 1;

        // Minimum 3 digits, grows naturally beyond 999
        return $prefix . ($seq < 1000 ? sprintf('%03d', $seq) : (string) $seq);
    }
}

if (!function_exists('voucher_status_label')) {
    function voucher_status_label(string $status): string
    {
        return match ($status) {
            'generated'     => 'Generated',
            'not_generated' => 'Pending',
            default         => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}

if (!function_exists('voucher_status_badge')) {
    function voucher_status_badge(string $status): string
    {
        $label = voucher_status_label($status);
        return "<span class=\"vs-status-badge vs-status-{$status}\">{$label}</span>";
    }
}
