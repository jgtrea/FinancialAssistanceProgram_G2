<?php

if (!function_exists('generate_voucher_no')) {
    function generate_voucher_no(): string
    {
        $db   = \Config\Database::connect();
        $year = date('Y');

        $last = $db->table('students')
                   ->like('voucher_no', "VOC-{$year}-", 'after')
                   ->selectMax('voucher_no', 'last_no')
                   ->get()->getRow();

        $seq = 1;
        if ($last && $last->last_no) {
            $parts = explode('-', $last->last_no);
            $seq   = (int) end($parts) + 1;
        }

        return sprintf('VOC-%s-%06d', $year, $seq);
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
