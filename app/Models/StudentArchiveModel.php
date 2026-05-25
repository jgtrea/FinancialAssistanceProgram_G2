<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentArchiveModel extends Model
{
    protected $table      = 'student_archive';
    protected $primaryKey = 'archive_id';

    protected $allowedFields = [
        'student_id',
        'voucher_no',
        'voucher_date',
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'rank_no',
        'gwa',
        'gender',
        'junior_high_school',
        'preferred_senior_high_school',
        'contact_number',
        'remarks_status',
        'school_year',
        'eligibility_status',
        'voucher_status',
        'archive_reason',
        'archived_by',
    ];
}