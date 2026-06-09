<?php namespace App\Models;
use CodeIgniter\Model;

class UserLogin extends Model {
    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    protected $allowedFields = [
        'username',
        'first_name', 'middle_name', 'last_name',
        'email', 'password', 'role',
        'is_active', 'last_login', 'session_token', 'session_last_active',
    ];

    protected $beforeInsert = ['normalizeUppercase'];
    protected $beforeUpdate = ['normalizeUppercase'];
    protected $afterFind    = ['normalizeUppercaseResult'];

    protected array $uppercaseFields = ['first_name', 'middle_name', 'last_name'];
    protected array $lowercaseFields = ['email'];

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

        foreach ($this->lowercaseFields as $field) {
            if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                $data['data'][$field] = $this->lower(trim($data['data'][$field]));
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

        foreach ($this->lowercaseFields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->lower($row[$field]);
            }
        }

        return $row;
    }

    protected function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    protected function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}
