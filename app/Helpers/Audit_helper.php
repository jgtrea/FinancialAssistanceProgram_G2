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
