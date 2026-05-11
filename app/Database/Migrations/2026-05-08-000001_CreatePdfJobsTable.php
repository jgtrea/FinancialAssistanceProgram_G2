<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePdfJobsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'job_id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'voucher_ids' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'done', 'failed'],
                'default'    => 'pending',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'completed_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'error_message' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addPrimaryKey('job_id');
        $this->forge->createTable('pdf_jobs');
    }

    public function down(): void
    {
        $this->forge->dropTable('pdf_jobs', true);
    }
}
