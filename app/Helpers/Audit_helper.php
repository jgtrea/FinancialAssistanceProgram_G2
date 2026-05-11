<?php

/**
 * Audit Log Helper
 * Usage: log_action($userId, 'ACTION_NAME', 'description', $voucherId);
 *
 * Load in controller: $this->load->helper('audit');
 * Or autoload in app/Config/Autoload.php helpers array.
 */

if (!function_exists('log_action')) {
    function log_action(?int $userId, string $action, string $description = '', ?int $voucherId = null): void
    {
        try {
            $db      = \Config\Database::connect();
            $request = \Config\Services::request();

            $db->table('audit_log')->insert([
                'user_id'     => $userId,
                'voucher_id'  => $voucherId,
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