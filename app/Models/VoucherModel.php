<?php

namespace App\Models;

use CodeIgniter\Model;

class VoucherModel extends Model
{
    protected $table         = 'vouchers';
    protected $primaryKey    = 'voucher_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'voucher_no', 'voucher_date', 'recipient_name',
        'senior_high_school', 'amount_in_words', 'amount',
        'created_by', 'signatory_1_id', 'signatory_2_id', 'signatory_3_id',
        'school_year', 'voucher_status', 'student_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get vouchers with joined user and student info.
     * Admins see all; users see only their own.
     */
    public function getVouchersForListing(?int $userId = null): array
    {
        $builder = $this->db->table('vouchers v')
            ->select('
                v.voucher_id,
                v.voucher_no,
                v.voucher_date,
                v.recipient_name,
                v.senior_high_school,
                v.amount,
                v.amount_in_words,
                v.school_year,
                v.voucher_status,
                v.created_at,
                u.user_id,
                u.username,
                u.full_name AS created_by_name,
                s.student_id,
                s.full_name AS student_name,
                s.rank_no,
                s.gwa,
                s.gender,
                s.junior_high_school,
                s.preferred_senior_high_school,
                s.contact_number,
                s.eligibility_status
            ')
            ->join('users u', 'u.user_id = v.created_by', 'left')
            ->join('students s', 's.student_id = v.student_id', 'left');

        if ($userId !== null) {
            $builder->where('v.created_by', $userId);
        }

        $builder->orderBy('v.created_at', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get a single voucher with all signatories for PDF generation.
     */
    public function getVoucherWithSignatories(int $voucherId): ?array
    {
        $row = $this->db->table('vouchers v')
            ->select('
                v.*,
                u.username, u.full_name AS created_by_name,
                s.full_name AS student_name, s.rank_no, s.gwa,
                s.gender, s.junior_high_school,
                s.preferred_senior_high_school, s.contact_number,
                sg1.full_name AS sig1_name, sg1.position_title AS sig1_title, sg1.signature_image AS sig1_img,
                sg2.full_name AS sig2_name, sg2.position_title AS sig2_title, sg2.signature_image AS sig2_img,
                sg3.full_name AS sig3_name, sg3.position_title AS sig3_title, sg3.signature_image AS sig3_img
            ')
            ->join('users u',       'u.user_id = v.created_by',         'left')
            ->join('students s',    's.student_id = v.student_id',       'left')
            ->join('signatories sg1','sg1.signatory_id = v.signatory_1_id','left')
            ->join('signatories sg2','sg2.signatory_id = v.signatory_2_id','left')
            ->join('signatories sg3','sg3.signatory_id = v.signatory_3_id','left')
            ->where('v.voucher_id', $voucherId)
            ->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Get multiple vouchers by IDs for batch PDF.
     */
    public function getVouchersByIds(array $ids): array
    {
        if (empty($ids)) return [];

        return $this->db->table('vouchers v')
            ->select('
                v.*,
                u.username, u.full_name AS created_by_name,
                s.full_name AS student_name, s.rank_no, s.gwa,
                s.gender, s.junior_high_school,
                s.preferred_senior_high_school, s.contact_number,
                sg1.full_name AS sig1_name, sg1.position_title AS sig1_title, sg1.signature_image AS sig1_img,
                sg2.full_name AS sig2_name, sg2.position_title AS sig2_title, sg2.signature_image AS sig2_img,
                sg3.full_name AS sig3_name, sg3.position_title AS sig3_title, sg3.signature_image AS sig3_img
            ')
            ->join('users u',        'u.user_id = v.created_by',          'left')
            ->join('students s',     's.student_id = v.student_id',        'left')
            ->join('signatories sg1','sg1.signatory_id = v.signatory_1_id','left')
            ->join('signatories sg2','sg2.signatory_id = v.signatory_2_id','left')
            ->join('signatories sg3','sg3.signatory_id = v.signatory_3_id','left')
            ->whereIn('v.voucher_id', $ids)
            ->orderBy('v.voucher_no', 'ASC')
            ->get()->getResultArray();
    }
}