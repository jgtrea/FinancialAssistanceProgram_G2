<?php

namespace App\Models;

use CodeIgniter\Model;

class OthersOptionsModel extends Model
{
    protected $table      = 'others_options';
    protected $primaryKey = 'id';
    protected $allowedFields = ['context', 'value', 'is_active', 'created_by'];
    protected $useTimestamps = false;

    public function saveOption(string $context, string $value, ?int $userId = null): void
    {
        $value = trim($value);
        if ($value === '' || $value === '__OTHER__') {
            return;
        }
        $existing = $this->where('context', $context)->where('value', $value)->first();
        if ($existing) {
            if (!(int) $existing['is_active']) {
                $this->update($existing['id'], ['is_active' => 1]);
            }
            return;
        }
        $this->insert(['context' => $context, 'value' => $value, 'is_active' => 1, 'created_by' => $userId]);
    }

    public function getOptions(string $context): array
    {
        $rows = $this->where('context', $context)
                     ->where('is_active', 1)
                     ->orderBy('value', 'ASC')
                     ->findAll();
        return array_column($rows, 'value');
    }

    public function getAllGrouped(): array
    {
        $rows = $this->where('is_active', 1)
                     ->orderBy('context', 'ASC')
                     ->orderBy('value', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['context']][] = $row;
        }
        return $grouped;
    }

    public function getAllArchivedGrouped(): array
    {
        $rows = $this->where('is_active', 0)
                     ->orderBy('context', 'ASC')
                     ->orderBy('value', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['context']][] = $row;
        }
        return $grouped;
    }

    public function getAllForTable(): array
    {
        return $this->db->query(
            "SELECT * FROM others_options
             WHERE context IN ('suffix', 'prefix', 'degree')
             ORDER BY is_active DESC, FIELD(context, 'suffix', 'prefix', 'degree') ASC, value ASC"
        )->getResultArray();
    }

    public function isDuplicate(string $context, string $value, ?int $excludeId = null): bool
    {
        $q = $this->where('context', $context)->where('value', $value);
        if ($excludeId !== null) {
            $q->where('id !=', $excludeId);
        }
        return $q->countAllResults() > 0;
    }

    public function updateOption(int $id, string $value): void
    {
        $this->update($id, ['value' => $value]);
    }

    public function deactivate(int $id): void
    {
        $this->update($id, ['is_active' => 0]);
    }

    public function activate(int $id): void
    {
        $this->update($id, ['is_active' => 1]);
    }
}
