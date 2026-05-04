<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table      = 'students';
    protected $primaryKey = 'student_id';

    protected $allowedFields = [
        'voucher_no',
        'voucher_date',
        'full_name',
        'rank_no',
        'gwa',
        'gender',
        'junior_high_school',
        'preferred_senior_high_school',
        'contact_number',
        'remarks_status',
        'school_year',
        'eligibility_status',
    ];
}