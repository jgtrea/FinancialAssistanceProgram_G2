<?php

namespace App\Models;

use CodeIgniter\Model;

class ArchiveModel extends Model
{
    protected $table         = 'student_archive';
    protected $primaryKey    = 'archive_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'voucher_id', 'voucher_no', 'recipient_name',
        'senior_high_school', 'amount_in_words', 'amount',
        'school_year', 'voucher_status', 'archive_reason',
        'archived_by', 'archived_at',
    ];

    public function getArchivesForListing(?int $userId = null): array
    {
        $builder = $this->db->table('student_archive a')
            ->select('a.*, u.username, u.full_name AS archived_by_name')
            ->join('users u', 'u.user_id = a.archived_by', 'left')
            ->orderBy('a.archived_at', 'DESC');

        if ($userId !== null) {
            $builder->where('a.archived_by', $userId);
        }

        return $builder->get()->getResultArray();
    }
}