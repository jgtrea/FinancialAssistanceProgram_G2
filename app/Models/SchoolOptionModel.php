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
                ->select('school_id, school_name, acronym')
                ->where('school_level', $level)
                ->where('is_active', 1)
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
            $builder = $this->db->table('school')->where('school_level', $level);
            if (ctype_digit($school)) {
                $builder->where('school_id', (int) $school);
            } else {
                $builder->where('school_name', $this->upper($school));
            }

            return $builder->countAllResults() > 0;
        } catch (DatabaseException $e) {
            return true;
        }
    }

    public function addSchool(string $level, string $name): ?int
    {
        $name = $this->upper(trim($name));
        if ($name === '') return null;

        $existing = $this->db->table('school')
            ->select('school_id')
            ->where('school_level', $level)
            ->where('school_name', $name)
            ->get()
            ->getRowArray();

        if ($existing) {
            return (int) $existing['school_id'];
        }

        $this->db->table('school')->insert([
            'school_level' => $level,
            'school_name'  => $name,
            'acronym'      => '',
            'is_active'    => 1,
        ]);

        return (int) $this->db->insertID();
    }

    public function resolveSchoolId(string $level, ?string $value, bool $allowEmpty = false): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $allowEmpty ? null : 0;
        }

        if (ctype_digit($value)) {
            $row = $this->db->table('school')
                ->select('school_id')
                ->where('school_level', $level)
                ->where('school_id', (int) $value)
                ->get()
                ->getRowArray();

            return $row ? (int) $row['school_id'] : ($allowEmpty ? null : 0);
        }

        // Non-digit: match an existing school by exact NAME or ACRONYM (case-
        // insensitive) at this level before creating one. Voucher imports often
        // carry the acronym (e.g. "BCSTHS"), which must resolve to the real
        // school instead of spawning a duplicate named after the acronym.
        $upper = $this->db->escapeString($this->upper($value));
        $row   = $this->db->table('school')
            ->select('school_id')
            ->where('school_level', $level)
            ->where("(UPPER(school_name) = '{$upper}' OR UPPER(acronym) = '{$upper}')", null, false)
            ->get()
            ->getRowArray();

        if ($row) {
            return (int) $row['school_id'];
        }

        return $this->addSchool($level, $value);
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
