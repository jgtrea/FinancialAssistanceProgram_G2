<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;

class SchoolOptionModel extends Model
{
    protected $table = 'school';

    public function getJuniorHighSchools(): array
    {
        return $this->getSchoolOptions('JHS');
    }

    public function getSeniorHighSchools(): array
    {
        return $this->getSchoolOptions('SHS');
    }

    public function juniorHighSchoolExists(?string $school): bool
    {
        return $this->schoolExists('JHS', $school, true);
    }

    public function seniorHighSchoolExists(?string $school): bool
    {
        return $this->schoolExists('SHS', $school, false);
    }

    private function getSchoolOptions(string $level): array
    {
        try {
            return $this->db->table('school')
                ->select('school_name')
                ->where('school_level', $level)
                ->orderBy('school_name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (DatabaseException $e) {
            return [];
        }
    }

    private function schoolExists(string $level, ?string $school, bool $allowEmpty): bool
    {
        $school = trim((string) $school);

        if ($school === '') {
            return $allowEmpty;
        }

        try {
            return $this->db->table('school')
                ->where('school_level', $level)
                ->where('school_name', $this->upper($school))
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
