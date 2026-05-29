<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds students.generate_count to make the listing query O(1) per student
 * instead of O(jobs * ids-per-job) via JSON scan.
 *
 * Backfills from existing pdf_jobs done rows on the way up so historical
 * counts survive the migration.
 */
class AddGenerateCountToStudents extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('generate_count', 'students')) {
            $this->forge->addColumn('students', [
                'generate_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'voucher_status',
                ],
            ]);
        }

        // Backfill — count each student_id's appearances across done pdf_jobs.
        if ($this->db->tableExists('pdf_jobs')) {
            $jobs = $this->db->table('pdf_jobs')
                ->select('voucher_ids')
                ->where('status', 'done')
                ->get()
                ->getResultArray();

            $counts = [];
            foreach ($jobs as $job) {
                $ids = json_decode((string) ($job['voucher_ids'] ?? ''), true);
                if (!is_array($ids)) {
                    continue;
                }
                foreach ($ids as $id) {
                    $id = (int) $id;
                    $counts[$id] = ($counts[$id] ?? 0) + 1;
                }
            }

            // Apply backfilled counts in 500-id batches.
            $batch = [];
            foreach ($counts as $studentId => $count) {
                $batch[] = ['student_id' => $studentId, 'generate_count' => $count];
                if (count($batch) >= 500) {
                    $this->db->table('students')->updateBatch($batch, 'student_id');
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $this->db->table('students')->updateBatch($batch, 'student_id');
            }
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('generate_count', 'students')) {
            $this->forge->dropColumn('students', 'generate_count');
        }
    }
}
