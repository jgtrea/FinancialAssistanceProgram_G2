<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;

class SchoolOptionModel extends Model
{
    public function getJuniorHighSchools(): array
    {
        return $this->getSchoolOptions('junior_high_schools');
    }

    public function getSeniorHighSchools(): array
    {
        return $this->getSchoolOptions('senior_high_schools');
    }

    public function juniorHighSchoolExists(?string $school): bool
    {
        return $this->schoolExists('junior_high_schools', $school, true);
    }

    public function seniorHighSchoolExists(?string $school): bool
    {
        return $this->schoolExists('senior_high_schools', $school, false);
    }

    private function getSchoolOptions(string $table): array
    {
        try {
            return $this->db->table($table)
                ->select('school_name')
                ->where('is_active', 1)
                ->orderBy('school_name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (DatabaseException $e) {
            return [];
        }
    }

    private function schoolExists(string $table, ?string $school, bool $allowEmpty): bool
    {
        $school = trim((string) $school);

        if ($school === '') {
            return $allowEmpty;
        }

        try {
            return $this->db->table($table)
                ->where('school_name', $this->upper($school))
                ->where('is_active', 1)
                ->countAllResults() > 0;
        } catch (DatabaseException $e) {
            return true;
        }
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
