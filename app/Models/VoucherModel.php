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
    protected $beforeInsert   = ['normalizeUppercase'];
    protected $beforeUpdate   = ['normalizeUppercase'];
    protected $afterFind      = ['normalizeUppercaseResult'];

    protected array $uppercaseFields = [
        'voucher_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'gender',
        'junior_high_school',
        'preferred_senior_high_school',
        'contact_number',
        'remarks_status',
        'school_year',
    ];

    protected function normalizeUppercase(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        foreach ($this->uppercaseFields as $field) {
            if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                $data['data'][$field] = $this->upper(trim($data['data'][$field]));
            }
        }

        return $data;
    }

    protected function normalizeUppercaseResult(array $data): array
    {
        if (empty($data['data'])) {
            return $data;
        }

        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                $row = $this->uppercaseRow($row);
            }
            unset($row);
        } elseif (is_array($data['data'])) {
            $data['data'] = $this->uppercaseRow($data['data']);
        }

        return $data;
    }

    protected function uppercaseRow(array $row): array
    {
        foreach ($this->uppercaseFields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->upper($row[$field]);
            }
        }

        if (isset($row['full_name']) && is_string($row['full_name'])) {
            $row['full_name'] = $this->upper($row['full_name']);
        }

        return $row;
    }

    protected function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    public function getVouchersForListing(): array
    {
        $rows = $this->db->table('students')
            ->select("
                student_id, voucher_no, voucher_date,
                first_name, middle_name, last_name, suffix,
                CONCAT_WS(' ', NULLIF(first_name,''), NULLIF(middle_name,''), NULLIF(last_name,''), NULLIF(suffix,'')) AS full_name,
                preferred_senior_high_school, school_year,
                eligibility_status, voucher_status,
                gwa, rank_no, gender, junior_high_school,
                contact_number, remarks_status, created_at, generated_at
            ")
            ->where('is_archived', 0)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        $rows = array_map(fn ($row) => $this->uppercaseRow($row), $rows);
        $counts = $this->getGenerateCounts(array_column($rows, 'student_id'));

        foreach ($rows as &$row) {
            $row['generate_count'] = $counts[(int) $row['student_id']] ?? 0;
        }
        unset($row);

        return $rows;
    }

    public function getStudentById(int $studentId): ?array
    {
        $row = $this->db->table('students')
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

        return $row ? $this->uppercaseRow($row) : null;
    }

    public function getVouchersByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $rows = $this->db->table('students')
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
            ->orderBy('student_id', 'ASC')
            ->get()->getResultArray();

        return array_map(fn ($row) => $this->uppercaseRow($row), $rows);
    }

    public function getTotalGeneratedVouchers(): int
    {
        $jobs = $this->db->table('pdf_jobs')
            ->select('voucher_ids')
            ->where('status', 'done')
            ->get()
            ->getResultArray();

        $total = 0;
        foreach ($jobs as $job) {
            $ids = json_decode((string) ($job['voucher_ids'] ?? ''), true);
            if (is_array($ids)) {
                $total += count($ids);
            }
        }
        return $total;
    }

    public function getGenerateCounts(array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if (empty($studentIds)) {
            return [];
        }

        $counts = array_fill_keys($studentIds, 0);
        $jobs = $this->db->table('pdf_jobs')
            ->select('voucher_ids')
            ->where('status', 'done')
            ->get()
            ->getResultArray();

        foreach ($jobs as $job) {
            $ids = json_decode((string) ($job['voucher_ids'] ?? ''), true);
            if (!is_array($ids)) {
                continue;
            }

            foreach ($ids as $id) {
                $id = (int) $id;
                if (array_key_exists($id, $counts)) {
                    $counts[$id]++;
                }
            }
        }

        return $counts;
    }
}
