<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSessionTokenToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('session_token', 'users')) {
            $this->forge->addColumn('users', [
                'session_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                    'after'      => 'last_login',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('session_token', 'users')) {
            $this->forge->dropColumn('users', 'session_token');
        }
    }
}
