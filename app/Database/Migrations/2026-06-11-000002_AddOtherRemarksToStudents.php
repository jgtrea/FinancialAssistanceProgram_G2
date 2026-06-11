<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOtherRemarksToStudents extends Migration
{
    public function up()
    {
        foreach (['students', 'student_archive'] as $table) {
            if (! $this->db->tableExists($table) || $this->db->fieldExists('other_remarks', $table)) {
                continue;
            }

            $fields = [
                'other_remarks' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'remarks_status',
                ],
            ];

            $this->forge->addColumn($table, $fields);

            if ($this->db->fieldExists('voucher_status', $table)) {
                $this->db->query("
                    UPDATE `{$table}`
                    SET `other_remarks` = `voucher_status`,
                        `voucher_status` = 'not_generated'
                    WHERE UPPER(`remarks_status`) = 'OTHERS'
                      AND `voucher_status` IS NOT NULL
                      AND `voucher_status` NOT IN ('not_generated', 'generated')
                ");
            }
        }
    }

    public function down()
    {
        foreach (['students', 'student_archive'] as $table) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists('other_remarks', $table)) {
                continue;
            }

            $this->forge->dropColumn($table, 'other_remarks');
        }
    }
}
