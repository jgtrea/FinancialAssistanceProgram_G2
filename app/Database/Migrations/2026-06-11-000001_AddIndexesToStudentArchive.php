<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Indexes for the archive listing on student_archive (grows to hundreds of
 * thousands of rows). Before these, every datatable page filtered/sorted the
 * whole table (~2.4s/draw) and the filter-dropdown DISTINCT scans took ~2s each.
 *
 *   idx_sa_sy_archived (school_year, archived_at)
 *       — covers the listing's WHERE school_year = ? ORDER BY archived_at.
 *   idx_sa_jhs (junior_high_school)
 *   idx_sa_shs (preferred_senior_high_school)
 *       — turn the distinct-school lookups into loose index scans (~10ms).
 */
class AddIndexesToStudentArchive extends Migration
{
    private const INDEXES = [
        'idx_sa_sy_archived' => 'school_year, archived_at',
        'idx_sa_jhs'         => 'junior_high_school',
        'idx_sa_shs'         => 'preferred_senior_high_school',
    ];

    public function up()
    {
        if (!$this->db->tableExists('student_archive')) {
            return;
        }
        foreach (self::INDEXES as $name => $cols) {
            if (!$this->indexExists($name)) {
                $this->db->query("CREATE INDEX {$name} ON student_archive ({$cols})");
            }
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('student_archive')) {
            return;
        }
        foreach (array_keys(self::INDEXES) as $name) {
            if ($this->indexExists($name)) {
                $this->db->query("DROP INDEX {$name} ON student_archive");
            }
        }
    }

    private function indexExists(string $name): bool
    {
        $row = $this->db->query(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
             LIMIT 1',
            ['student_archive', $name]
        )->getRow();

        return $row !== null;
    }
}
