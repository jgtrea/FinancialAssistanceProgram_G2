<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSessionLastActiveToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('session_last_active', 'users')) {
            $this->forge->addColumn('users', [
                'session_last_active' => [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'session_token',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('session_last_active', 'users')) {
            $this->forge->dropColumn('users', 'session_last_active');
        }
    }
}
