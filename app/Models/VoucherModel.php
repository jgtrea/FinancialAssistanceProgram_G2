<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table         = 'students';
    protected $primaryKey    = 'student_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'voucher_no', 'voucher_date',
        'first_name', 'middle_name', 'last_name', 'suffix',
        'rank_no', 'gwa', 'gender',
        'junior_high_school', 'preferred_senior_high_school',
        'contact_number', 'remarks_status', 'school_year',
        'eligibility_status', 'voucher_status', 'is_archived',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getVouchersForListing(): array
    {
        return $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                preferred_senior_high_school, school_year,
                eligibility_status, voucher_status,
                gwa, rank_no, gender, junior_high_school,
                contact_number, remarks_status, created_at
            ")
            ->where('is_archived', 0)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
    }

    public function getStudentById(int $studentId): ?array
    {
        return $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                rank_no, gwa, gender,
                junior_high_school, preferred_senior_high_school,
                contact_number, remarks_status, school_year,
                eligibility_status, voucher_status, is_archived,
                created_at, updated_at
            ")
            ->where('student_id', $studentId)
            ->get()->getRowArray() ?: null;
    }

    public function getVouchersByIds(array $ids): array
    {
        if (empty($ids)) return [];

        return $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                rank_no, gwa, gender,
                junior_high_school, preferred_senior_high_school,
                contact_number, remarks_status, school_year,
                eligibility_status, voucher_status
            ")
            ->whereIn('student_id', $ids)
            ->where('is_archived', 0)
            ->orderBy('voucher_no', 'ASC')
            ->get()->getResultArray();
    }
}
