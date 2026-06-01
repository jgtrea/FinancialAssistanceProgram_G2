<?php

namespace App\Models;

use CodeIgniter\Model;

class GenerationHistoryModel extends Model
{
    protected $table         = 'generation_history';
    protected $primaryKey    = 'generation_history_id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'student_id',
        'voucher_no',
        'pdf_job_id',
        'generation_source',
        'generated_by',
        'generated_at',
        'created_at',
    ];

    public function recordMany(array $students, ?int $userId, ?int $jobId, string $source, ?string $generatedAt = null): void
    {
        if (empty($students) || !$this->db->tableExists($this->table)) {
            return;
        }

        $generatedAt = $generatedAt ?: date('Y-m-d H:i:s');
        $rows = [];

        foreach ($students as $student) {
            $studentId = (int) ($student['student_id'] ?? 0);
            if ($studentId <= 0) {
                continue;
            }

            $rows[] = [
                'student_id'         => $studentId,
                'voucher_no'         => $student['voucher_no'] ?? null,
                'pdf_job_id'         => $jobId,
                'generation_source'  => $source,
                'generated_by'       => $userId,
                'generated_at'       => $generatedAt,
                'created_at'         => $generatedAt,
            ];
        }

        if (!empty($rows)) {
            $this->insertBatch($rows);
        }
    }

    public function getRecentForStudent(int $studentId, int $limit = 5): array
    {
        if (!$this->db->tableExists($this->table)) {
            return [];
        }

        $hasGeneratedBy = $this->db->fieldExists('generated_by', $this->table);

        $builder = $this->db->table($this->table . ' gh');
        if ($hasGeneratedBy) {
            $builder->select('gh.*, u.username')
                    ->join('users u', 'u.user_id = gh.generated_by', 'left');
        } else {
            $builder->select('gh.*');
        }

        return $builder
            ->where('gh.student_id', $studentId)
            ->orderBy('gh.generated_at', 'DESC')
            ->orderBy('gh.generation_history_id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
