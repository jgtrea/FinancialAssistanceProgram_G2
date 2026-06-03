<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolModel extends Model
{
    protected $table      = 'school';
    protected $primaryKey = 'school_id';
    protected $returnType = 'array';
    protected $allowedFields = ['school_name', 'school_level', 'is_active', 'acronym'];

    public function getSchoolsForListing(string $keyword = '', array $filters = []): array
    {
        $builder = $this->db->table('school')
            ->select('school_id, school_name, acronym, school_level, is_active')
            ->orderBy('school_level', 'ASC')
            ->orderBy('school_name', 'ASC');

        if ($keyword !== '') {
            $builder->groupStart()
                    ->like('school_name', $keyword)
                    ->orLike('acronym', $keyword)
                    ->groupEnd();
        }

        if (!empty($filters['level'])) {
            $builder->where('school_level', $filters['level']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('is_active', (int) $filters['status']);
        }

        return $builder->get()->getResultArray();
    }

    public function nameExistsForLevel(string $level, string $name, ?int $excludeId = null): bool
    {
        $upper = function_exists('mb_strtoupper') ? mb_strtoupper(trim($name), 'UTF-8') : strtoupper(trim($name));
        $builder = $this->db->table('school')
            ->where('school_level', $level)
            ->where('school_name', $upper);
        if ($excludeId !== null) {
            $builder->where('school_id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }
}
