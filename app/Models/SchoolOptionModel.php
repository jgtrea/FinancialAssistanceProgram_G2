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
                ->select('school_name, acronym')
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
            return $this->db->table('school')
                ->where('school_level', $level)
                ->where('school_name', $this->upper($school))
                ->countAllResults() > 0;
        } catch (DatabaseException $e) {
            return true;
        }
    }

    public function addSchool(string $level, string $name): void
    {
        $name = $this->upper(trim($name));
        if ($name === '') return;

        $exists = $this->db->table('school')
            ->where('school_level', $level)
            ->where('school_name', $name)
            ->countAllResults() > 0;

        if (!$exists) {
            $this->db->table('school')->insert([
                'school_level' => $level,
                'school_name'  => $name,
                'acronym'      => $this->generateAcronym($name),
                'is_active'    => 1,
            ]);
        }
    }

    private function generateAcronym(string $name): string
    {
        $skip  = ['AND', 'THE', 'OF', 'A', 'AN', 'OR', 'FOR'];
        $parts = preg_split('/[\s\-]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach ($parts as $word) {
            $word = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $word));
            if ($word === '' || in_array($word, $skip, true)) continue;
            $initials .= $word[0];
        }
        return $initials;
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
