<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds students.control_no and students.evaluated_by (and the same on
 * student_archive so archived rows keep them). control_no is the school's
 * control number (e.g. "2026-008") used as a unique human identifier; a UNIQUE
 * index enforces no duplicates (MySQL allows multiple NULLs, so blank control
 * numbers don't collide). evaluated_by records who evaluated the student
 * (e.g. "STAFF 1").
 */
class AddControlNoAndEvaluatedByToStudents extends Migration
{
    public function up()
    {
        foreach (['students', 'student_archive'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $cols = [];
            if (! $this->db->fieldExists('control_no', $table)) {
                $cols['control_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'voucher_no',
                ];
            }
            if (! $this->db->fieldExists('evaluated_by', $table)) {
                $cols['evaluated_by'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ];
            }
            if (! empty($cols)) {
                $this->forge->addColumn($table, $cols);
            }
        }

        // Unique control numbers on the live table. Multiple NULLs are allowed by
        // MySQL, so students without a control_no don't conflict.
        if ($this->db->tableExists('students') && $this->db->fieldExists('control_no', 'students')) {
            $indexes = array_map(
                static fn ($i) => $i->name,
                $this->db->getIndexData('students')
            );
            if (! in_array('uniq_students_control_no', $indexes, true)) {
                $this->db->query('ALTER TABLE `students` ADD UNIQUE `uniq_students_control_no` (`control_no`)');
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('students')) {
            $indexes = array_map(static fn ($i) => $i->name, $this->db->getIndexData('students'));
            if (in_array('uniq_students_control_no', $indexes, true)) {
                $this->db->query('ALTER TABLE `students` DROP INDEX `uniq_students_control_no`');
            }
        }
        foreach (['students', 'student_archive'] as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            foreach (['control_no', 'evaluated_by'] as $col) {
                if ($this->db->fieldExists($col, $table)) {
                    $this->forge->dropColumn($table, $col);
                }
            }
        }
    }
}
