<?php

namespace App\Models;

use CodeIgniter\Model;

class ArchiveModel extends Model
{
    protected $table         = 'student_archive';
    protected $primaryKey    = 'archive_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'student_id', 'voucher_no', 'voucher_date',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'rank_no', 'gwa', 'gender',
        'junior_high_school', 'preferred_senior_high_school',
        'contact_number', 'remarks_status', 'school_year',
        'eligibility_status', 'voucher_status',
        'archive_reason', 'archived_by', 'archived_at',
    ];

    public function getArchivesForListing(): array
    {
        return $this->db->table('student_archive a')
            ->select("
                a.*,
                CONCAT_WS(' ', NULLIF(a.first_name,''), NULLIF(a.middle_name,''), NULLIF(a.last_name,''), NULLIF(a.suffix,'')) AS full_name,
                u.username AS archived_by_name
            ")
            ->join('users u', 'u.user_id = a.archived_by', 'left')
            ->orderBy('a.archived_at', 'DESC')
            ->get()->getResultArray();
    }
}
