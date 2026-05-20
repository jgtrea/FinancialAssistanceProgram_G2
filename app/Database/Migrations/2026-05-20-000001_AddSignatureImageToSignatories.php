<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSignatureImageToSignatories extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('signature_image', 'signatories')) {
            $this->forge->addColumn('signatories', [
                'signature_image' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'position_title',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('signature_image', 'signatories')) {
            $this->forge->dropColumn('signatories', 'signature_image');
        }
    }
}
