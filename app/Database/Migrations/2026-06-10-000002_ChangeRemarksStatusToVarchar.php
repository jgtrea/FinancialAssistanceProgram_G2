<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * students.remarks_status was ENUM('Passed','For Review','Failed') NOT NULL.
 * The import/edit vocabulary moved to free-text COMPLETE / INCOMPLETE / OTHERS,
 * which the ENUM silently coerced to '' on insert. Widen it to VARCHAR(100) NULL
 * (matching student_archive) and convert existing values to the new vocabulary.
 */
class ChangeRemarksStatusToVarchar extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('students')) {
            return;
        }

        $this->db->query("ALTER TABLE `students` MODIFY `remarks_status` VARCHAR(100) NULL DEFAULT NULL");

        // Convert old enum values to the new vocabulary on both tables.
        foreach (['students', 'student_archive'] as $table) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists('remarks_status', $table)) {
                continue;
            }
            $this->db->query("UPDATE `{$table}` SET `remarks_status` = 'COMPLETE'   WHERE UPPER(`remarks_status`) = 'PASSED'");
            $this->db->query("UPDATE `{$table}` SET `remarks_status` = 'INCOMPLETE' WHERE UPPER(`remarks_status`) = 'FOR REVIEW'");
            $this->db->query("UPDATE `{$table}` SET `remarks_status` = 'OTHERS'     WHERE UPPER(`remarks_status`) = 'FAILED'");
            $this->db->query("UPDATE `{$table}` SET `remarks_status` = NULL WHERE `remarks_status` = ''");
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('students')) {
            return;
        }
        // Best-effort revert of the vocabulary, then back to the original ENUM.
        $this->db->query("UPDATE `students` SET `remarks_status` = 'Passed'     WHERE UPPER(`remarks_status`) = 'COMPLETE'");
        $this->db->query("UPDATE `students` SET `remarks_status` = 'For Review' WHERE UPPER(`remarks_status`) IN ('INCOMPLETE','')  OR `remarks_status` IS NULL");
        $this->db->query("UPDATE `students` SET `remarks_status` = 'Failed'     WHERE UPPER(`remarks_status`) = 'OTHERS'");
        $this->db->query("ALTER TABLE `students` MODIFY `remarks_status` ENUM('Passed','For Review','Failed') NOT NULL DEFAULT 'For Review'");
    }
}
