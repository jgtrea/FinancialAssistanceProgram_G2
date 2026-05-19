<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferenceColumnsToAuditLog extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('student_id', 'audit_log')) {
            $this->forge->addColumn('audit_log', [
                'student_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'user_id',
                ],
            ]);
        }

        if (!$this->db->fieldExists('voucher_id', 'audit_log')) {
            $this->forge->addColumn('audit_log', [
                'voucher_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'student_id',
                ],
            ]);
        }
    }

    public function down()
    {
        // Keep existing audit history columns intact on rollback.
    }
}
