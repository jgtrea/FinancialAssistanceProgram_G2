<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentArchiveModel extends Model
{
    protected $table      = 'student_archive';
    protected $primaryKey = 'archive_id';

    protected $allowedFields = [
        'voucher_id',
        'voucher_no',
        'recipient_name',
        'senior_high_school',
        'amount_in_words',
        'amount',
        'school_year',
        'voucher_status',
        'archive_reason',
        'archived_by',
    ];
}