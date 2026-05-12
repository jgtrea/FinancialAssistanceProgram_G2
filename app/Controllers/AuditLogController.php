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

        if ($action !== '') {
            $auditModel->where('action', $action);
        }

        if ($keyword !== '') {
            $auditModel
                ->groupStart()
                ->like('description', $keyword)
                ->orLike('action', $keyword)
                ->orLike('ip_address', $keyword)
                ->orLike('user_agent', $keyword)
                ->groupEnd();
        }

        $actionOptions = (new AuditLogModel())
            ->select('action')
            ->distinct()
            ->orderBy('action', 'ASC')
            ->findAll();

        return view('admin/audit_logs/index', [  // REVIEW: FOR ADMIN 
            'title' => 'Audit Logs',
            'logs' => $auditModel
                ->orderBy('created_at', 'DESC')
                ->orderBy('audit_id', 'DESC')
                ->findAll(),
            'actionOptions' => $actionOptions,
            'selectedAction' => $action,
            'keyword' => $keyword,
        ]);
    }
}