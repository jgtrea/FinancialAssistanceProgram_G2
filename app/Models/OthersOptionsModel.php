<?php

namespace App\Models;

use CodeIgniter\Model;

class OthersOptionsModel extends Model
{
    protected $table      = 'others_options';
    protected $primaryKey = 'id';
    protected $allowedFields = ['context', 'value', 'created_by'];
    protected $useTimestamps = false;

    /**
     * Save a custom option value for a given context.
     * Uses INSERT IGNORE so duplicates are silently skipped.
     */
    public function saveOption(string $context, string $value, ?int $userId = null): void
    {
        $value = trim($value);
        if ($value === '' || $value === '__OTHER__') {
            return;
        }
        $this->db->query(
            'INSERT IGNORE INTO others_options (context, value, created_by) VALUES (?, ?, ?)',
            [$context, $value, $userId]
        );
    }

    public function getOptions(string $context): array
    {
        $rows = $this->where('context', $context)
                     ->orderBy('value', 'ASC')
                     ->findAll();
        return array_column($rows, 'value');
    }

    public function getAllGrouped(): array
    {
        $rows = $this->orderBy('context', 'ASC')
                     ->orderBy('value', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['context']][] = $row;
        }
        return $grouped;
    }
}
