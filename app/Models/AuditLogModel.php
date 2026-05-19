<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table      = 'audit_log';
    protected $primaryKey = 'audit_id';

    protected $allowedFields = [
        'user_id',
        'student_id',
        'voucher_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];
}
