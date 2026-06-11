<?php

if (!function_exists('generate_voucher_no')) {
    function generate_voucher_no(string $jhs = '', string $year = ''): string
    {
        $db   = \Config\Database::connect();
        $year = $year ?: date('Y');

        // The voucher number prefix comes straight from the school's stored
        // acronym — no auto-derived placeholder. Generation is gated upstream
        // (Voucher::incompleteSchoolsResponse) so the school always has one by
        // the time we get here; the exception below is a last-resort guard.
        $jhs = trim($jhs);
        $acronym = '';
        if ($jhs !== '') {
            $schoolBuilder = $db->table('school')->select('acronym, school_name');
            if (ctype_digit($jhs)) {
                $schoolBuilder->where('school_id', (int) $jhs);
            } else {
                $schoolBuilder->where('school_name', strtoupper($jhs));
            }
            $schoolRow = $schoolBuilder->limit(1)->get()->getRow();
            $acronym   = $schoolRow ? strtoupper(trim((string) ($schoolRow->acronym ?? ''))) : '';
        }

        if ($acronym === '') {
            throw new \RuntimeException('Cannot build a voucher number: the school has no acronym set.');
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
        $label = esc(voucher_status_label($status));
        $class = preg_replace('/[^a-z0-9_-]+/', '-', strtolower($status)) ?: 'custom';
        return "<span class=\"vs-status-badge vs-status-{$class}\">{$label}</span>";
    }
}
