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

    public function getArchivesForListing(string $keyword = ''): array
    {
        $builder = $this->db->table('student_archive a')
            ->select("
                a.*,
                CONCAT_WS(' ', NULLIF(a.first_name,''), NULLIF(a.middle_name,''), NULLIF(a.last_name,''), NULLIF(a.suffix,'')) AS full_name,
                u.username AS archived_by_name
            ")
            ->join('users u', 'u.user_id = a.archived_by', 'left');

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $builder
                ->groupStart()
                ->like('a.voucher_no', $keyword)
                ->orLike('a.first_name', $keyword)
                ->orLike('a.middle_name', $keyword)
                ->orLike('a.last_name', $keyword)
                ->orLike('a.suffix', $keyword)
                ->orLike('a.junior_high_school', $keyword)
                ->orLike('a.preferred_senior_high_school', $keyword)
                ->orLike('a.school_year', $keyword)
                ->orLike('a.archive_reason', $keyword)
                ->orLike('u.username', $keyword)
                ->groupEnd();
        }

        return $builder
            ->orderBy('a.archived_at', 'DESC')
            ->orderBy('a.archive_id', 'DESC')
            ->get()
            ->getResultArray();
    }
}
