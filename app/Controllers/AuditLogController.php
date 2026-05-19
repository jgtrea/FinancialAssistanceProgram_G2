<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

class AuditLogController extends BaseController
{
    public function index()
    {
        $auditModel = new AuditLogModel();
        $action = trim((string) $this->request->getGet('action'));
        $keyword = trim((string) $this->request->getGet('q'));
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));

        $path = trim($this->request->getUri()->getPath(), '/');
        $isAdminRoute = str_contains('/' . $path, '/admin/audit-logs');

        $auditModel
            ->select('audit_log.*, users.full_name, users.username')
            ->join('users', 'users.user_id = audit_log.user_id', 'left');

        if (!$isAdminRoute) {
            $auditModel->where('audit_log.user_id', (int) session()->get('user_id'));
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
                ->orLike('users.full_name', $keyword)
                ->orLike('users.username', $keyword)
                ->groupEnd();
        }

        $actionOptionsQuery = (new AuditLogModel())
            ->select('action')
            ->distinct()
            ->orderBy('action', 'ASC');

        if (!$isAdminRoute) {
            $actionOptionsQuery->where('user_id', (int) session()->get('user_id'));
        }

        $actionOptions = $actionOptionsQuery->findAll();

        $view = $isAdminRoute ? 'admin/audit_logs/index' : 'audit_logs/index';
        $resetUrl = $isAdminRoute ? 'admin/audit-logs' : 'user/audit-logs';

        return view($view, [
            'title' => 'Audit Logs',
            'logs' => $auditModel
                ->orderBy('audit_log.created_at', 'DESC')
                ->orderBy('audit_log.audit_id', 'DESC')
                ->findAll(200),
            'actionOptions' => $actionOptions,
            'selectedAction' => $action,
            'keyword' => $keyword,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'resetUrl' => $resetUrl,
        ]);
    }
}
