<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * rank_no was INT — but tie ranks come in as half values (e.g. 10.5, 16.5).
 * Widen to DECIMAL(7,1) on students + student_archive so decimal ranks import.
 */
class ChangeRankNoToDecimal extends Migration
{
    public function up()
    {
        foreach (['students', 'student_archive'] as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('rank_no', $table)) {
                $this->db->query("ALTER TABLE `{$table}` MODIFY `rank_no` DECIMAL(7,1) NULL DEFAULT NULL");
            }
        }
    }

    public function down()
    {
        foreach (['students', 'student_archive'] as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('rank_no', $table)) {
                $this->db->query("ALTER TABLE `{$table}` MODIFY `rank_no` INT(11) NULL DEFAULT NULL");
            }
        }
    }
}
