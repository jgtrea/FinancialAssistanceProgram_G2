<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `students.suffix` and `signatories.prefix`/`suffix`/`degree` were still
 * fixed MySQL ENUM columns from before the others_options "Others" catalog
 * feature existed. Any custom value outside the old hardcoded enum list gets
 * silently rejected or dropped by MySQL, or throws (e.g. NOT NULL suffix
 * rejecting the NULL the controllers send for a blank value) — so those two
 * forms could never actually persist a catalog-driven value. `users.suffix`
 * was already VARCHAR, which is why My Account / Add User worked fine.
 * Widths match each controller's own `max_length` validation rule.
 */
class ConvertPrefixSuffixDegreeToVarchar extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('students') && $this->db->fieldExists('suffix', 'students')) {
            $this->forge->modifyColumn('students', [
                'suffix' => ['name' => 'suffix', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            ]);
        }

        if ($this->db->tableExists('signatories')) {
            $fields = [];
            if ($this->db->fieldExists('prefix', 'signatories')) {
                $fields['prefix'] = ['name' => 'prefix', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true];
            }
            if ($this->db->fieldExists('suffix', 'signatories')) {
                $fields['suffix'] = ['name' => 'suffix', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true];
            }
            if ($this->db->fieldExists('degree', 'signatories')) {
                $fields['degree'] = ['name' => 'degree', 'type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
            }
            if ($fields) {
                $this->forge->modifyColumn('signatories', $fields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('students') && $this->db->fieldExists('suffix', 'students')) {
            $this->forge->modifyColumn('students', [
                'suffix' => ['name' => 'suffix', 'type' => "ENUM('','JR.','SR.','II','III','IV')", 'null' => true],
            ]);
        }

        if ($this->db->tableExists('signatories')) {
            $fields = [];
            if ($this->db->fieldExists('prefix', 'signatories')) {
                $fields['prefix'] = ['name' => 'prefix', 'type' => "ENUM('DR.','ENGR.','HON.','MR.','MRS.','MS.','PROF.')", 'null' => true];
            }
            if ($this->db->fieldExists('suffix', 'signatories')) {
                $fields['suffix'] = ['name' => 'suffix', 'type' => "ENUM('','JR.','SR.','II','III','IV','V','CPA','LPT','MD','PHD')", 'null' => false, 'default' => ''];
            }
            if ($this->db->fieldExists('degree', 'signatories')) {
                $fields['degree'] = ['name' => 'degree', 'type' => "ENUM('Elementary','High School','Vocational','Associate','Bachelor','BSc','BA','Master','MSc','MA','MBA','Doctorate','PhD','MD','JD','LLB','DDS','EdD','Other','None')", 'null' => false, 'default' => 'None'];
            }
            if ($fields) {
                $this->forge->modifyColumn('signatories', $fields);
            }
        }
    }
}
