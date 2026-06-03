<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

class AuditLogController extends BaseController
{
    public const LISTING_DEFAULT_LIMIT = 1000;

    public function index()
    {
        $auditModel = new AuditLogModel();

        $action   = trim((string) $this->request->getGet('action'));
        $keyword  = trim((string) $this->request->getGet('q'));
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo   = trim((string) $this->request->getGet('date_to'));

        $path         = trim($this->request->getUri()->getPath(), '/');
        $isAdminRoute = str_contains('/' . $path, '/admin/audit-logs');
        $sessionUser  = (int) session()->get('user_id');

        $hasFilter  = ($action !== '') || ($dateFrom !== '') || ($dateTo !== '');
        $hasKeyword = $keyword !== '';

        $auditModel
            ->select("audit_log.*, users.email, TRIM(CONCAT_WS(' ', NULLIF(users.first_name,''), NULLIF(users.middle_name,''), NULLIF(users.last_name,''))) AS full_name")
            ->join('users', 'users.user_id = audit_log.user_id', 'left');

        if (!$isAdminRoute) {
            $auditModel->where('audit_log.user_id', $sessionUser);
        }

        if ($action !== '') {
            $auditModel->where('audit_log.action', $action);
        }
        if ($dateFrom !== '') {
            $auditModel->where('audit_log.created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $auditModel->where('audit_log.created_at <=', $dateTo . ' 23:59:59');
        }
        if ($keyword !== '') {
            $auditModel
                ->groupStart()
                ->like('audit_log.description', $keyword)
                ->orLike('audit_log.action', $keyword)
                ->orLike('audit_log.ip_address', $keyword)
                ->orLike('audit_log.user_agent', $keyword)
                ->orLike('users.first_name', $keyword)
                ->orLike('users.last_name', $keyword)
                ->orLike('users.email', $keyword)
                ->groupEnd();
        }

        $auditModel
            ->orderBy('audit_log.created_at', 'DESC')
            ->orderBy('audit_log.audit_id', 'DESC');

        // Cap loaded rows to N most-recent when nothing is narrowing the result;
        // when a keyword or any filter is active, return the full filtered set.
        $logs = ($hasKeyword || $hasFilter)
            ? $auditModel->findAll()
            : $auditModel->findAll(self::LISTING_DEFAULT_LIMIT);

        return view($isAdminRoute ? 'admin/audit_logs/index' : 'audit_logs/index', [
            'title'          => 'Audit Logs',
            'logs'           => $logs,
            'actionOptions'  => $this->getActionOptions($isAdminRoute, $sessionUser),
            'selectedAction' => $action,
            'keyword'        => $keyword,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
            'resetUrl'       => $isAdminRoute ? 'admin/audit-logs' : 'user/audit-logs',
        ]);
    }

    private function getActionOptions(bool $isAdminRoute, int $sessionUser): array
    {
        $q = (new AuditLogModel())
            ->select('action')
            ->distinct()
            ->orderBy('action', 'ASC');
        if (!$isAdminRoute) {
            $q->where('user_id', $sessionUser);
        }
        return $q->findAll();
    }
}
