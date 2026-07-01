<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOthersOptionsTable extends Migration
{
    /**
     * Baseline catalog values that used to be hardcoded in the Suffix/Prefix/
     * Degree <select> templates. Seeded here so the Admin > Others Options
     * page (deactivate/activate) is the single source of truth for them.
     */
    private array $defaults = [
        'suffix' => ['JR.', 'SR.', 'II', 'III', 'IV', 'V'],
        'prefix' => ['DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'],
        'degree' => ['MPA', 'BSc', 'BA', 'Master', 'MSc', 'MA', 'MBA', 'Doctorate', 'PhD', 'MD', 'JD', 'LLB', 'DDS', 'EdD'],
    ];

    public function up()
    {
        if (!$this->db->tableExists('others_options')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'context'    => ['type' => 'VARCHAR', 'constraint' => 50],
                'value'      => ['type' => 'VARCHAR', 'constraint' => 255],
                'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['context', 'value'], 'uq_context_value');
            $this->forge->createTable('others_options', true);
        }

        foreach ($this->defaults as $context => $values) {
            foreach ($values as $value) {
                $this->db->query(
                    'INSERT IGNORE INTO others_options (context, value, is_active) VALUES (?, ?, 1)',
                    [$context, $value]
                );
            }
        }
    }

    public function down()
    {
        foreach ($this->defaults as $context => $values) {
            $this->db->table('others_options')
                ->whereIn('value', $values)
                ->where('context', $context)
                ->delete();
        }
    }
}
