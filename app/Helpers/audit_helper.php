<?php

if (!function_exists('log_action')) {
    function log_action(?int $userId, string $action, string $description = '', ?int $studentId = null): void
    {
        try {
            $db      = \Config\Database::connect();
            $request = \Config\Services::request();

            $ipAddress = 'CLI';
            $userAgent = 'CLI';
            if (!is_cli() && !($request instanceof \CodeIgniter\HTTP\CLIRequest)) {
                $ipAddress = $request->getIPAddress();
                $ua = $request->getUserAgent();
                $userAgent = is_object($ua) && method_exists($ua, 'getAgentString')
                    ? $ua->getAgentString()
                    : (string) $ua;
            }

            $db->table('audit_log')->insert([
                'user_id'     => $userId,
                'student_id'  => $studentId,
                'action'      => $action,
                'description' => $description,
                'ip_address'  => $ipAddress,
                'user_agent'  => $userAgent,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[audit_helper] log_action failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('audit_student_summary')) {
    /**
     * Build a human-readable student label for audit descriptions, mirroring
     * the VOUCHER_UPDATED style: "NAME (ID #id)". Accepts either student row
     * arrays (first_name/last_name/.../student_id or full_name) or plain name
     * strings. For many students it lists the first $max names then "and N more".
     */
    function audit_student_summary(array $students, int $max = 3): string
    {
        $labels = [];
        foreach ($students as $s) {
            if (!is_array($s)) {
                $name = trim((string) $s);
                if ($name !== '') $labels[] = $name;
                continue;
            }

            $name = trim((string) ($s['full_name'] ?? ''));
            if ($name === '') {
                $name = trim(
                    ($s['first_name'] ?? '') . ' ' .
                    ($s['middle_name'] ?? '') . ' ' .
                    ($s['last_name'] ?? '') . ' ' .
                    ($s['suffix'] ?? '')
                );
            }
            if ($name === '') $name = 'Unnamed student';

            $id = $s['student_id'] ?? $s['id'] ?? null;
            $labels[] = ($id !== null && $id !== '') ? "{$name} (ID #{$id})" : $name;
        }

        $total = count($labels);
        if ($total === 0) return '';
        if ($total === 1) return $labels[0];

        $shown = array_slice($labels, 0, $max);
        $text  = implode(', ', $shown);
        $rest  = $total - count($shown);

        return $rest > 0 ? "{$text} and {$rest} more" : $text;
    }
}

if (!function_exists('audit_student_ids')) {
    /**
     * Format a flat list of student IDs for audit descriptions: "ID #1, ID #2".
     * Lists all ids (no truncation). Empty/zero ids are skipped.
     */
    function audit_student_ids(array $ids): string
    {
        $labels = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) $labels[] = 'ID #' . $id;
        }
        return implode(', ', $labels);
    }
}
