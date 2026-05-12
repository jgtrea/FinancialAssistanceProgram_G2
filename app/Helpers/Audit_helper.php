<?php

if (!function_exists('log_action')) {
    function log_action(?int $userId, string $action, string $description = '', ?int $studentId = null): void
    {
        try {
            $db      = \Config\Database::connect();
            $request = \Config\Services::request();

            $db->table('audit_log')->insert([
                'user_id'     => $userId,
                'student_id'  => $studentId,
                'action'      => $action,
                'description' => $description,
                'ip_address'  => $request->getIPAddress(),
                'user_agent'  => $request->getUserAgent()->getAgentString(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', '[audit_helper] log_action failed: ' . $e->getMessage());
        }
    }
}
