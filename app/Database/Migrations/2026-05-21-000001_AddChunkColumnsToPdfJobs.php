<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChunkColumnsToPdfJobs extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('parent_job_id', 'pdf_jobs')) {
            $this->forge->addColumn('pdf_jobs', [
                'parent_job_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'job_id',
                ],
            ]);
            $this->db->query('CREATE INDEX idx_pdf_jobs_parent ON pdf_jobs (parent_job_id)');
        }

        if (!$this->db->fieldExists('chunk_index', 'pdf_jobs')) {
            $this->forge->addColumn('pdf_jobs', [
                'chunk_index' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'parent_job_id',
                ],
            ]);
        }

        if (!$this->db->fieldExists('total_chunks', 'pdf_jobs')) {
            $this->forge->addColumn('pdf_jobs', [
                'total_chunks' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'chunk_index',
                ],
            ]);
        }
    }

    public function down()
    {
        foreach (['total_chunks', 'chunk_index', 'parent_job_id'] as $col) {
            if ($this->db->fieldExists($col, 'pdf_jobs')) {
                $this->forge->dropColumn('pdf_jobs', $col);
            }
        }
    }
}
