<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table      = 'students';
    protected $primaryKey = 'student_id';

    protected $allowedFields = [
        'voucher_no',
        'control_no',
        'voucher_date',
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
        'other_remarks',
        'school_year',
        // 'eligibility_status',
        'voucher_status',
        'is_active',
        'generated_at',
    ];

    protected $beforeInsert = ['normalizeUppercase'];
    protected $beforeUpdate = ['normalizeUppercase'];
    protected $afterFind    = ['normalizeUppercaseResult'];

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
        'other_remarks',
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

        return $row;
    }

    protected function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
