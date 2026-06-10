<?php

namespace App\Libraries;

use App\Models\SchoolOptionModel;
use App\Models\VoucherModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ImportRunner — process a single IMPORT job from the JSON queue.
 *
 * The upload was saved to writable/imports/ by the controller; this runner
 * parses + validates + inserts the rows in the background worker. Validation
 * rejects surface as the job's error_message (polled by the browser); success
 * stores result.imported.
 *
 * Real file layout (the BISTECH sheet):
 *   - A few title/blank rows on top before the header row.
 *   - A leading row-number column (ignored).
 *   - Columns: Control No. | Full Name | Rank | GWA | Gender | JHS |
 *     Contact Number | Preferred Senior School | Remarks / Status | Evaluated By
 *   - FULL NAME is one cell "Surname, Firstname, Middle".
 *   - No Voucher No. / Date columns.
 *
 * Columns are located by NAME (header detected anywhere in the first rows), so
 * the leading blank "#" column and minor ordering differences don't matter.
 *
 * Required per row: Surname, Firstname, Rank, GWA, Gender.
 * Optional: Control No. (unique when present), JHS, Preferred Senior, Contact,
 * Remarks/Status (free text), Evaluated By.
 */
class ImportRunner
{
    // Accepted header text (normalised) → internal field key. normHeader()
    // lowercases, strips a trailing "(...)" note, collapses whitespace.
    private const HEADER_ALIASES = [
        'control no.'                  => 'control',
        'control no'                   => 'control',
        'control number'               => 'control',
        'full name'                    => 'fullname',
        'name'                         => 'fullname',
        'surname'                      => 'surname',
        'last name'                    => 'surname',
        'firstname'                    => 'first',
        'first name'                   => 'first',
        'middle name'                  => 'middle',
        'middle'                       => 'middle',
        'voucher no.'                  => 'voucher',
        'voucher no'                   => 'voucher',
        'voucher number'               => 'voucher',
        'date'                         => 'date',
        'voucher date'                 => 'date',
        'rank'                         => 'rank',
        'rank no.'                     => 'rank',
        'rank no'                      => 'rank',
        'gwa'                          => 'gwa',
        'gender'                       => 'gender',
        'sex'                          => 'gender',
        'jhs'                          => 'jhs',
        'junior high school'           => 'jhs',
        'contact number'               => 'contact',
        'contact no.'                  => 'contact',
        'contact'                      => 'contact',
        'preferred senior school'      => 'shs',
        'preferred senior high school' => 'shs',
        'remarks / status'             => 'remarks',
        'remarks/status'               => 'remarks',
        'remarks'                      => 'remarks',
        'status'                       => 'remarks',
        'evaluated by'                 => 'evaluated',
    ];

    // Field keys that MUST have a column in the header.
    private const REQUIRED_COLUMNS = ['fullname', 'rank', 'gwa', 'gender'];

    public static function processClaimed(array $job): bool
    {
        $jobId    = (int) $job['job_id'];
        $filePath = (string) ($job['payload']['file_path'] ?? '');
        $clientNm = (string) ($job['payload']['original_name'] ?? 'upload');
        $userId   = isset($job['created_by']) ? (int) $job['created_by'] : null;

        try { \Config\Database::connect()->reconnect(); } catch (\Throwable $_) {}

        try {
            $res      = self::importFile($jobId, $filePath);
            $imported = (int) $res['imported'];
            $skipped  = (int) $res['skipped'];

            log_action($userId ?? 0, 'IMPORT_RECORDS', "Imported {$imported} record(s), skipped {$skipped}, from {$clientNm} (queued job #{$jobId})");

            JsonPdfQueue::finishSingle($jobId, function (array $rec) use ($imported, $skipped, $res) {
                $rec['status']       = 'done';
                $rec['result']       = ['imported' => $imported, 'skipped' => $skipped, 'reasons' => $res['reasons']];
                $rec['completed_at'] = date('Y-m-d H:i:s');
                return $rec;
            });

            self::cleanupUpload($filePath);
            return true;
        } catch (\Throwable $e) {
            log_message('error', "[ImportRunner] Job {$jobId}: " . $e->getMessage());

            $msg = $e->getMessage();
            JsonPdfQueue::finishSingle($jobId, function (array $rec) use ($msg) {
                $rec['status']        = 'failed';
                $rec['error_message'] = $msg;
                $rec['completed_at']  = date('Y-m-d H:i:s');
                return $rec;
            });

            self::cleanupUpload($filePath);
            return false;
        }
    }

    /**
     * Parse, validate, and insert the file. Throws \RuntimeException only on
     * file-level failures (missing file, bad header). Per-row problems (invalid
     * data or already-existing) are skipped, not fatal. Returns
     * ['imported' => int, 'skipped' => int, 'reasons' => string[]].
     */
    protected static function importFile(int $jobId, string $filePath): array
    {
        if ($filePath === '' || ! is_file($filePath)) {
            throw new \RuntimeException('Uploaded file is missing from storage.');
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $sheetData = self::parseCsv($filePath);
        } else {
            $spreadsheet = IOFactory::load($filePath);
            $sheetData   = $spreadsheet->getActiveSheet()->toArray();
        }

        if (empty($sheetData)) {
            throw new \RuntimeException('The file is empty.');
        }

        // Locate the header row (skips the title/blank rows on top) and map each
        // known column to its index.
        [$headerIdx, $colMap] = self::locateHeader($sheetData);
        if ($headerIdx === null) {
            throw new \RuntimeException('Could not find the header row. Expected columns like Rank, GWA, Gender and either "Full Name" or "Surname"/"Firstname".');
        }
        // Name comes from a single "Full Name" column OR split Surname/Firstname.
        $hasName = isset($colMap['fullname']) || isset($colMap['surname']);
        foreach (['rank' => 'Rank', 'gwa' => 'GWA'] as $need => $label) {
            if (! isset($colMap[$need])) {
                throw new \RuntimeException('File format does not match: missing the "' . $label . '" column.');
            }
        }
        if (! $hasName) {
            throw new \RuntimeException('File format does not match: missing a "Full Name" or "Surname" column.');
        }

        $get = static function (array $row, array $colMap, string $key): string {
            return isset($colMap[$key]) ? trim((string) ($row[$colMap[$key]] ?? '')) : '';
        };

        $voucherModel  = new VoucherModel();
        $schoolOptions = new SchoolOptionModel();

        $dataRows = array_slice($sheetData, $headerIdx + 1);

        // Load existing keys ONCE (single scan) so we SKIP rows that already
        // exist instead of rejecting the whole file.
        $existingControls = [];
        $existingVouchers = [];
        $existingNames    = [];
        foreach ($voucherModel->select('control_no, voucher_no, first_name, middle_name, last_name, suffix')->findAll() as $r) {
            if (! empty($r['control_no'])) {
                $existingControls[strtoupper(trim((string) $r['control_no']))] = true;
            }
            if (! empty($r['voucher_no'])) {
                $existingVouchers[strtoupper(trim((string) $r['voucher_no']))] = true;
            }
            $existingNames[self::normalizeName(self::formatStudentFullName($r))] = true;
        }

        $totalRows = count($dataRows);
        JsonPdfQueue::setProgress($jobId, 0, max(1, $totalRows));

        $seenControl = [];
        $seenVoucher = [];
        $seenNames   = [];
        $imported    = 0;
        $skipped     = 0;
        $reasons     = [];
        $addReason   = static function (string $r) use (&$reasons) {
            if (count($reasons) < 10) {
                $reasons[] = $r;
            }
        };

        foreach ($dataRows as $n => $row) {
            [$surname, $first, $middle] = self::extractName($row, $colMap);
            if ($surname === '' && $first === '') {
                continue; // wholly blank row — not counted
            }
            $rowNo    = $headerIdx + 2 + $n;
            $fullName = self::joinName($surname, $first, $middle);

            $control = $get($row, $colMap, 'control');
            $vno     = $get($row, $colMap, 'voucher');
            $date    = $get($row, $colMap, 'date');
            $rank    = $get($row, $colMap, 'rank');
            $gwa     = $get($row, $colMap, 'gwa');
            $gender  = strtoupper($get($row, $colMap, 'gender'));
            $jhs     = $get($row, $colMap, 'jhs');
            $shs     = $get($row, $colMap, 'shs');
            $contact = $get($row, $colMap, 'contact');
            $remarks = strtoupper($get($row, $colMap, 'remarks'));
            $evalBy  = $get($row, $colMap, 'evaluated');

            // ── Skip rows with invalid required/optional fields ──────────────
            $err = null;
            if ($first === '') {
                $err = 'missing first name';
            } elseif ($rank === '' || ! is_numeric($rank) || (float) $rank <= 0 || (float) $rank > 999999) {
                $err = 'invalid Rank';
            } elseif ($gwa === '' || ! is_numeric($gwa) || (float) $gwa < 0 || (float) $gwa > 100) {
                $err = 'invalid GWA';
            } elseif ($gender !== '' && ! in_array($gender, ['MALE', 'FEMALE'], true)) {
                $err = 'invalid Gender';
            } elseif (strlen($surname) > 100 || strlen($first) > 100 || strlen($middle) > 100) {
                $err = 'name too long';
            } elseif ($control !== '' && strlen($control) > 50) {
                $err = 'control number too long';
            } elseif ($contact !== '' && (strlen($contact) > 30 || ! preg_match('/^[0-9+().\-\s]+$/', $contact))) {
                $err = 'invalid contact number';
            } elseif (strlen($jhs) > 200 || strlen($shs) > 200) {
                $err = 'school name too long';
            } elseif (strlen($remarks) > 100) {
                $err = 'remarks too long';
            } elseif (strlen($evalBy) > 150) {
                $err = 'evaluated by too long';
            } elseif ($vno !== '' && strlen($vno) > 50) {
                $err = 'voucher number too long';
            } elseif ($date !== '' && strtotime($date) === false) {
                $err = 'invalid date';
            }
            if ($err !== null) {
                $skipped++;
                $addReason("Row {$rowNo} ({$fullName}): {$err}");
                continue;
            }

            // ── Skip duplicates (already in DB or earlier in this file) ───────
            $ckey = $control !== '' ? strtoupper($control) : null;
            $vkey = $vno !== ''     ? strtoupper($vno)     : null;
            $nkey = self::normalizeName($first . ' ' . $middle . ' ' . $surname);

            if ($ckey !== null && (isset($existingControls[$ckey]) || isset($seenControl[$ckey]))) {
                $skipped++; $addReason("Row {$rowNo} ({$fullName}): control number already exists"); continue;
            }
            if ($vkey !== null && (isset($existingVouchers[$vkey]) || isset($seenVoucher[$vkey]))) {
                $skipped++; $addReason("Row {$rowNo} ({$fullName}): voucher number already exists"); continue;
            }
            if (isset($existingNames[$nkey]) || isset($seenNames[$nkey])) {
                $skipped++; $addReason("Row {$rowNo} ({$fullName}): student already exists"); continue;
            }

            // Blank school → NULL (not 0) so the FK to `school` isn't violated.
            $jhsId = $jhs !== '' ? ($schoolOptions->resolveSchoolId('JHS', $jhs, false) ?: null) : null;
            $shsId = $shs !== '' ? ($schoolOptions->resolveSchoolId('SHS', $shs, false) ?: null) : null;

            $voucherModel->insert([
                'control_no'                   => $control !== '' ? $control : null,
                'voucher_no'                   => $vno !== '' ? $vno : null,
                'voucher_date'                 => $date !== '' ? date('Y-m-d', strtotime($date)) : null,
                'first_name'                   => strtoupper($first),
                'middle_name'                  => strtoupper($middle),
                'last_name'                    => strtoupper($surname),
                'suffix'                       => '',
                'rank_no'                      => (float) $rank,
                'gwa'                          => (float) $gwa,
                'gender'                       => $gender,
                'junior_high_school'           => $jhsId,
                'preferred_senior_high_school' => $shsId,
                'contact_number'               => $contact,
                'remarks_status'               => $remarks !== '' ? $remarks : null,
                'evaluated_by'                 => $evalBy !== '' ? $evalBy : null,
                'school_year'                  => null,
                'eligibility_status'           => 'eligible',
                'voucher_status'               => 'not_generated',
                'is_active'                    => 1,
            ]);

            // Track within-file keys so later rows don't re-insert the same one.
            if ($ckey !== null) $seenControl[$ckey] = true;
            if ($vkey !== null) $seenVoucher[$vkey] = true;
            $seenNames[$nkey] = true;

            $imported++;
            if (($imported + $skipped) % 100 === 0) {
                JsonPdfQueue::setProgress($jobId, $n + 1, max(1, $totalRows));
            }
        }

        JsonPdfQueue::setProgress($jobId, $totalRows, max(1, $totalRows));
        return ['imported' => $imported, 'skipped' => $skipped, 'reasons' => $reasons];
    }

    protected static function cleanupUpload(string $filePath): void
    {
        if ($filePath !== '' && is_file($filePath)) {
            @unlink($filePath);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Find the header row within the first ~30 rows and map known columns to
     * their indexes. Returns [headerRowIndex|null, ['field' => colIndex, ...]].
     */
    private static function locateHeader(array $sheetData): array
    {
        $limit = min(count($sheetData), 30);
        for ($r = 0; $r < $limit; $r++) {
            $map = [];
            foreach ($sheetData[$r] as $c => $cell) {
                $norm  = self::normHeader((string) $cell);
                if ($norm === '') {
                    continue;
                }
                $field = self::HEADER_ALIASES[$norm] ?? self::fuzzyField($norm);
                if ($field !== null && ! isset($map[$field])) {
                    $map[$field] = $c;
                }
            }
            // A real header row has rank + gwa plus a name column — either a
            // single "Full Name" or split Surname/Firstname. (Gender optional.)
            $hasName = isset($map['fullname']) || isset($map['surname']);
            if ($hasName && isset($map['rank'], $map['gwa'])) {
                return [$r, $map];
            }
        }
        return [null, []];
    }

    private static function labelFor(string $field): string
    {
        return [
            'fullname' => 'Full Name', 'rank' => 'Rank', 'gwa' => 'GWA',
            'gender' => 'Gender', 'control' => 'Control No.',
        ][$field] ?? $field;
    }

    /**
     * Best-effort header match when no exact alias hits — tolerates combined
     * name columns ("Surname, First Name, Middle Name"), typos ("Junior High
     * Scool"), and minor wording differences.
     */
    private static function fuzzyField(string $norm): ?string
    {
        $has = static fn (string $n) => strpos($norm, $n) !== false;

        if ($has('surname') && $has(',')) return 'fullname';   // "surname, first name, middle name"
        if ($has('full name') || $has('fullname')) return 'fullname';
        if ($has('junior high') || $has('junior hs') || $norm === 'jhs') return 'jhs';
        if ($has('senior high') || ($has('preferred') && $has('senior'))) return 'shs';
        if ($has('evaluat')) return 'evaluated';
        if ($has('remark') || $has('status')) return 'remarks';
        if ($has('contact')) return 'contact';
        if ($has('control')) return 'control';
        if ($has('voucher') && ! $has('date')) return 'voucher';
        if ($has('date')) return 'date';
        if ($has('gwa') || $has('grade')) return 'gwa';
        if ($has('gender') || $norm === 'sex') return 'gender';
        if ($has('rank')) return 'rank';

        return null;
    }

    /**
     * Extract [surname, first, middle] from a row, supporting BOTH layouts:
     *   - a single "Full Name" column ("Surname, Firstname, Middle"), or
     *   - separate Surname / Firstname / Middle Name columns.
     */
    private static function extractName(array $row, array $colMap): array
    {
        // A lone "-" means "no value" in the ranking sheets → treat as blank.
        $clean = static fn (string $v) => $v === '-' ? '' : $v;

        if (isset($colMap['fullname'])) {
            [$s, $f, $m] = self::splitFullName(trim((string) ($row[$colMap['fullname']] ?? '')));
            return [$clean($s), $clean($f), $clean($m)];
        }
        $surname = isset($colMap['surname']) ? trim((string) ($row[$colMap['surname']] ?? '')) : '';
        $first   = isset($colMap['first'])   ? trim((string) ($row[$colMap['first']]   ?? '')) : '';
        $middle  = isset($colMap['middle'])  ? trim((string) ($row[$colMap['middle']]  ?? '')) : '';
        return [$clean($surname), $clean($first), $clean($middle)];
    }

    /** Build a display "Surname, Firstname, Middle" for messages. */
    private static function joinName(string $surname, string $first, string $middle): string
    {
        $parts = array_filter([$surname, $first, $middle], static fn ($p) => $p !== '');
        return implode(', ', $parts);
    }

    /**
     * Split "Surname, Firstname, Middle" into [surname, first, middle]. Falls
     * back gracefully when commas are missing or parts are absent.
     */
    private static function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', '', ''];
        }
        $parts = array_map('trim', explode(',', $fullName));
        $surname = $parts[0] ?? '';
        $first   = $parts[1] ?? '';
        $middle  = isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : '';
        return [$surname, $first, $middle];
    }

    private static function parseCsv(string $path): array
    {
        $rows = [];
        if (($fh = fopen($path, 'r')) === false) {
            throw new \RuntimeException('Cannot open CSV file.');
        }
        while (($row = fgetcsv($fh)) !== false) {
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    /**
     * Normalise a header cell for matching: lowercase, strip a trailing
     * "(...)" note, collapse whitespace, trim.
     */
    private static function normHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/\([^)]*\)/', '', $h) ?? $h;      // drop "(complete / ...)"
        $h = preg_replace('/\s+/', ' ', $h) ?? $h;
        return rtrim(trim($h), ': ');                        // drop trailing colon ("evaluated by:")
    }

    private static function normalizeName(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private static function formatStudentFullName(array $row): string
    {
        $parts = [$row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? ''];
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts, static fn ($p) => trim((string) $p) !== ''))));
    }

    private static function findExistingStudentByNames(array $normalizedNames, VoucherModel $voucherModel): ?array
    {
        $nameLookup = array_fill_keys($normalizedNames, true);
        $rows = $voucherModel->select('first_name, middle_name, last_name, suffix')->findAll();

        foreach ($rows as $row) {
            $fullName   = self::formatStudentFullName($row);
            $normalized = self::normalizeName($fullName);
            if (isset($nameLookup[$normalized])) {
                return ['full_name' => $fullName, 'normalized_name' => $normalized];
            }
        }

        return null;
    }
}
