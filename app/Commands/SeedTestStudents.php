<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark seed:test-students N` — bulk-insert N eligible test students.
 *
 * Used to load-test the voucher generation pipeline. All inserted rows are
 * `eligibility_status='eligible'`, `voucher_status='not_generated'`,
 * `is_active=1`, so they show up as selectable in the generation UI.
 *
 * Inserts in 1000-row batches via insertBatch() for speed.
 *
 * Usage:
 *   php spark seed:test-students            # default 1000
 *   php spark seed:test-students 30000
 */
class SeedTestStudents extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'seed:test-students';
    protected $description = 'Insert N test student records (eligible, not generated). Default 1000.';

    private const FIRST_NAMES = [
        'Juan', 'Pedro', 'Jose', 'Maria', 'Ana', 'Luis', 'Carlos', 'Miguel',
        'Antonio', 'Ricardo', 'Eduardo', 'Francisco', 'Manuel', 'Roberto',
        'Sofia', 'Isabella', 'Camila', 'Valentina', 'Lucia', 'Elena',
        'Diego', 'Mateo', 'Sebastian', 'Andres', 'Gabriel', 'Daniel',
        'Patricia', 'Carmen', 'Rosa', 'Marta', 'Teresa', 'Laura',
    ];

    private const LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Lopez', 'Mendoza',
        'Torres', 'Flores', 'Rivera', 'Aquino', 'Castro', 'Mercado',
        'Villanueva', 'Dela Cruz', 'Ramos', 'Gonzales', 'Pascual',
        'Domingo', 'Aguilar', 'Lim', 'Tan', 'Ong', 'Sy', 'Co',
        'Soriano', 'Navarro', 'Rosales', 'Bernardo', 'Magtanggol',
    ];

    // Example fallback names kept for reference when manually seeding the
    // school table before running this command.
    private const JHS_SCHOOLS = [
        'STA. CRUZ NATIONAL HIGH SCHOOL',
        'UNION NATIONAL HIGH SCHOOL',
        'DEL PILAR NATIONAL HIGH SCHOOL',
        'SAN ISIDRO NATIONAL HIGH SCHOOL',
        'MADRID NATIONAL HIGH SCHOOL',
    ];

    private const SHS_SCHOOLS = [
        'MADRID NATIONAL HIGH SCHOOL - SENIOR HIGH',
        'SAN JUAN NATIONAL HIGH SCHOOL - SENIOR HIGH',
        'TANDAG NATIONAL HIGH SCHOOL - SENIOR HIGH',
        'STA. CRUZ NATIONAL HIGH SCHOOL - SENIOR HIGH',
        'SAN ISIDRO NATIONAL HIGH SCHOOL - SENIOR HIGH',
    ];

    private const SUFFIXES   = ['', '', '', '', '', '', '', 'JR.', 'SR.', 'II', 'III'];
    private const GENDERS    = ['MALE', 'FEMALE'];
    private const REMARKS    = ['PASSED', 'PASSED', 'PASSED', 'FOR REVIEW'];
    private const BATCH_SIZE = 1000;

    public function run(array $params)
    {
        $count = isset($params[0]) && is_numeric($params[0]) ? (int) $params[0] : 1000;
        if ($count < 1) {
            CLI::error('Count must be positive.');
            return EXIT_ERROR;
        }

        $db = \Config\Database::connect();

        if (!$db->tableExists('students')) {
            CLI::error("Table 'students' does not exist. Run migrations first.");
            return EXIT_ERROR;
        }

        $now        = date('Y-m-d H:i:s');
        $schoolYear = (int) date('Y') . '-' . (int) (date('Y') + 1);

        // Pull the live active school IDs from the DB so seeded students store
        // the same foreign-key values used by the application.
        $jhsSchools = $this->loadActiveSchools($db, 'JHS');
        $shsSchools = $this->loadActiveSchools($db, 'SHS');
        if (empty($jhsSchools) || empty($shsSchools)) {
            CLI::error("Active JHS and SHS rows are required in the 'school' table before seeding students.");
            return EXIT_ERROR;
        }
        CLI::write('Using ' . count($jhsSchools) . ' JHS and ' . count($shsSchools) . ' SHS school(s) from the database.');

        CLI::write("Inserting {$count} test students in batches of " . self::BATCH_SIZE . '...');
        $start = microtime(true);

        $inserted  = 0;
        $remaining = $count;

        while ($remaining > 0) {
            $batchSize = min(self::BATCH_SIZE, $remaining);
            $rows      = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $first  = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
                $last   = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
                $middle = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
                $suffix = self::SUFFIXES[array_rand(self::SUFFIXES)];

                $rows[] = [
                    'voucher_no'                   => null,
                    'voucher_date'                 => null,
                    'first_name'                   => strtoupper($first),
                    'middle_name'                  => strtoupper($middle),
                    'last_name'                    => strtoupper($last),
                    'suffix'                       => $suffix,
                    'rank_no'                      => null,
                    'gwa'                          => round(75 + mt_rand(0, 2400) / 100, 2),
                    'gender'                       => self::GENDERS[array_rand(self::GENDERS)],
                    'junior_high_school'           => $jhsSchools[array_rand($jhsSchools)],
                    'preferred_senior_high_school' => $shsSchools[array_rand($shsSchools)],
                    'contact_number'               => '09' . str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                    'remarks_status'               => self::REMARKS[array_rand(self::REMARKS)],
                    'school_year'                  => $schoolYear,
                    'eligibility_status'           => 'eligible',
                    'voucher_status'               => 'not_generated',
                    'is_active'                    => 1,
                    'created_at'                   => $now,
                    'updated_at'                   => $now,
                ];
            }

            $db->table('students')->insertBatch($rows);

            $inserted += $batchSize;
            $remaining -= $batchSize;
            CLI::write("  inserted {$inserted} / {$count}");
        }

        $elapsed = round(microtime(true) - $start, 2);
        CLI::write("Done. {$inserted} rows inserted in {$elapsed}s.");
        return EXIT_SUCCESS;
    }

    /**
     * Active school IDs for a level ('JHS'|'SHS') straight from the `school`
     * table. Returns [] if the table is absent or has no active rows.
     *
     * @return int[]
     */
    private function loadActiveSchools($db, string $level): array
    {
        if (!$db->tableExists('school')) {
            return [];
        }

        $rows = $db->table('school')
            ->select('school_id')
            ->where('school_level', $level)
            ->where('is_active', 1)
            ->where('school_id IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $names = [];
        foreach ($rows as $row) {
            $names[] = (int) $row['school_id'];
        }

        return $names;
    }
}
