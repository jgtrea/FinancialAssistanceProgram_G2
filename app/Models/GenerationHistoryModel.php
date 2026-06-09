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
        if (empty($students)) {
            return;
        }

        $db = \Config\Database::connect(null, false);
        try { $db->reconnect(); } catch (\Throwable $_) {}

        if (!$db->tableExists($this->table)) {
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
            $db->table($this->table)->insertBatch($rows);
        }
    }

    // $limit <= 0 returns every record for this student (no cap).
    public function getRecentForStudent(int $studentId, int $limit = 5): array
    {
        if (!$this->db->tableExists($this->table)) {
            return [];
        }

        $hasGeneratedBy = $this->db->fieldExists('generated_by', $this->table);

        $builder = $this->db->table($this->table . ' gh');
        if ($hasGeneratedBy) {
            $builder->select("
                    gh.*,
                    COALESCE(
                        NULLIF(TRIM(CONCAT_WS(' ', NULLIF(u.first_name,''), NULLIF(u.middle_name,''), NULLIF(u.last_name,''))), ''),
                        NULLIF(u.username, ''),
                        NULLIF(u.email, ''),
                        CASE WHEN gh.generated_by IS NOT NULL THEN CONCAT('User #', gh.generated_by) ELSE NULL END
                    ) AS full_name
                ", false)
                    ->join('users u', 'u.user_id = gh.generated_by', 'left');
        } else {
            $builder->select('gh.*');
        }

        $builder->where('gh.student_id', $studentId)
            ->orderBy('gh.generated_at', 'DESC')
            ->orderBy('gh.generation_history_id', 'DESC');

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }
}
