<?php

namespace App\Controllers;

use App\Models\VoucherModel;
use App\Models\SchoolOptionModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;

class VoucherImport extends BaseController
{
    // Expected header row (lowercase, trimmed) for format validation
    private const EXPECTED_HEADERS = [
        'voucher no.',
        'voucher date',
        'full name',
        'rank no.',
        'gwa',
        'gender',
        'junior high school',
        'preferred senior high school',
        'contact number',
        'remarks',
    ];

    public function index()
    {
        return view('FileConvertView');
    }

    public function import()
    {
        $file = $this->request->getFile('excel_file');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Please upload a valid file.']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only .xlsx, .xls, or .csv files are allowed.']);
        }

        try {
            if ($ext === 'csv') {
                $sheetData = $this->parseCsv($file->getTempName());
            } else {
                $spreadsheet = IOFactory::load($file->getTempName());
                $sheetData   = $spreadsheet->getActiveSheet()->toArray();
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to read file: ' . $e->getMessage()]);
        }

        if (empty($sheetData)) {
            return $this->response->setJSON(['success' => false, 'message' => 'The file is empty.']);
        }

        // Validate header row
        $headerError = $this->validateHeaders($sheetData[0]);
        if ($headerError) {
            return $this->response->setJSON(['success' => false, 'message' => $headerError]);
        }

        $voucherModel = new VoucherModel();
        $schoolOptions = new SchoolOptionModel();
        $count        = 0;

        // Collect non-empty voucher numbers and names first so duplicates fail before inserts start.
        $fileVoucherNos = [];
        $seenVoucherNos = [];
        $fileNames = [];
        $seenNames = [];
        for ($i = 1; $i < count($sheetData); $i++) {
            $vno = trim((string) ($sheetData[$i][0] ?? ''));
            $fullName = trim((string) ($sheetData[$i][2] ?? ''));
            if ($vno !== '') {
                $key = strtoupper($vno);
                if (isset($seenVoucherNos[$key])) {
                    return $this->importRowError(
                        $i,
                        'Duplicate voucher number "' . $vno . '" for "' . ($fullName ?: 'unknown student')
                        . '" also appears on row ' . $seenVoucherNos[$key] . '.'
                    );
                }
                $seenVoucherNos[$key] = $i + 1;
                $fileVoucherNos[] = $vno;
            }

            if ($fullName !== '') {
                $nameKey = $this->normalizeName($fullName);
                if (isset($seenNames[$nameKey])) {
                    return $this->importRowError(
                        $i,
                        'Duplicate student name "' . $fullName . '" with voucher number "' . ($vno ?: 'blank')
                        . '" also appears on row ' . $seenNames[$nameKey] . '.'
                    );
                }
                $seenNames[$nameKey] = $i + 1;
                $fileNames[$nameKey] = [
                    'name'       => $fullName,
                    'voucher_no' => $vno,
                    'row'        => $i + 1,
                ];
            }
        }

        // Reject the entire import if any voucher number already exists.
        if (!empty($fileVoucherNos)) {
            $existing = $voucherModel
                ->select('voucher_no, first_name, middle_name, last_name, suffix')
                ->whereIn('voucher_no', $fileVoucherNos)
                ->findAll();

            if (!empty($existing)) {
                $examples = array_map(function ($row) {
                    return '"' . ($row['voucher_no'] ?? '') . '" for "' . $this->formatStudentFullName($row) . '"';
                }, array_slice($existing, 0, 5));
                $list = implode(', ', $examples);
                $more = count($existing) > 5 ? ' and ' . (count($existing) - 5) . ' more' : '';
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Import rejected: ' . count($existing) . ' duplicate voucher number(s) found ('
                               . $list . $more . '). Remove duplicates from the file and try again.',
                ]);
            }
        }

        if (!empty($fileNames)) {
            $existingStudent = $this->findExistingStudentByNames(array_keys($fileNames), $voucherModel);
            if ($existingStudent !== null) {
                $nameKey = $existingStudent['normalized_name'];
                $incoming = $fileNames[$nameKey] ?? ['name' => $existingStudent['full_name'], 'voucher_no' => ''];
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Import rejected: student "' . $incoming['name'] . '" with voucher number "'
                               . ($incoming['voucher_no'] ?: 'blank') . '" already exists as "'
                               . $existingStudent['full_name'] . '" with voucher number "'
                               . ($existingStudent['voucher_no'] ?: 'blank') . '".',
                ]);
            }
        }

        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];

            $voucherNo   = trim((string) ($row[0] ?? ''));
            $voucherDate = trim((string) ($row[1] ?? ''));
            $fullName    = trim((string) ($row[2] ?? ''));

            if ($voucherDate === '' || $fullName === '') {
                continue;
            }

            // Validate date format
            if (!$this->isValidDate($voucherDate)) {
                return $this->importRowError($i, 'Voucher date must be a valid date.');
            }

            $rankNo  = trim((string) ($row[3] ?? ''));
            $gwa     = trim((string) ($row[4] ?? ''));
            $gender  = strtoupper(trim((string) ($row[5] ?? '')));
            $jhsSchool = trim((string) ($row[6] ?? ''));
            $shsSchool = trim((string) ($row[7] ?? ''));
            $contact   = trim((string) ($row[8] ?? ''));
            $remarks   = strtoupper(trim((string) ($row[9] ?? '')));

            if ($voucherNo !== '' && strlen($voucherNo) > 50) {
                return $this->importRowError($i, 'Voucher number must be 50 characters or fewer.');
            }

            // Validate gender
            if ($gender !== '' && !in_array($gender, ['MALE', 'FEMALE'])) {
                return $this->importRowError($i, 'Gender must be MALE, FEMALE, or blank.');
            }

            // Validate remarks
            if ($remarks !== '' && !in_array($remarks, ['PASSED', 'FOR REVIEW', 'FAILED'], true)) {
                return $this->importRowError($i, 'Remarks must be PASSED, FOR REVIEW, FAILED, or blank.');
            }

            if ($rankNo !== '' && (!ctype_digit($rankNo) || (int) $rankNo < 1 || (int) $rankNo > 999999)) {
                return $this->importRowError($i, 'Rank number must be a positive whole number.');
            }

            if ($gwa !== '' && (!is_numeric($gwa) || (float) $gwa < 0 || (float) $gwa > 100)) {
                return $this->importRowError($i, 'GWA must be a number from 0 to 100.');
            }

            if ($contact !== '' && (strlen($contact) > 30 || !preg_match('/^[0-9+().\-\s]+$/', $contact))) {
                return $this->importRowError($i, 'Contact number has invalid characters or is too long.');
            }

            if (!$schoolOptions->juniorHighSchoolExists($jhsSchool) || !$schoolOptions->seniorHighSchoolExists($shsSchool)) {
                return $this->importRowError($i, 'School names must exist in the school dropdown tables.');
            }

            $nameParts  = explode(' ', $fullName);
            $firstName  = array_shift($nameParts) ?? '';
            $lastName   = !empty($nameParts) ? array_pop($nameParts) : '';
            $middleName = implode(' ', $nameParts);

            if ($firstName === '' || $lastName === '') {
                return $this->importRowError($i, 'Full Name must include at least first and last name.');
            }

            if (strlen($firstName) > 100 || strlen($middleName) > 100 || strlen($lastName) > 100) {
                return $this->importRowError($i, 'Student name parts must be 100 characters or fewer.');
            }

            if (strlen($jhsSchool) > 200 || strlen($shsSchool) > 200) {
                return $this->importRowError($i, 'School names must be 200 characters or fewer.');
            }

            $voucherModel->insert([
                'voucher_no'                   => $voucherNo !== '' ? $voucherNo : null,
                'voucher_date'                 => date('Y-m-d', strtotime($voucherDate)),
                'first_name'                   => strtoupper($firstName),
                'middle_name'                  => strtoupper($middleName),
                'last_name'                    => strtoupper($lastName),
                'suffix'                       => '',
                'rank_no'                      => is_numeric($rankNo) ? (int) $rankNo : null,
                'gwa'                          => is_numeric($gwa) ? (float) $gwa : null,
                'gender'                       => $gender,
                'junior_high_school'           => strtoupper($jhsSchool),
                'preferred_senior_high_school' => strtoupper($shsSchool),
                'contact_number'               => $contact,
                'remarks_status'               => $remarks,
                'school_year'                  => date('Y'),
                'eligibility_status'           => 'eligible',
                'voucher_status'               => 'not_generated',
                'is_active'                    => 1,
            ]);

            $count++;
        }

        log_action(
            session()->get('user_id'),
            'IMPORT_RECORDS',
            "Imported {$count} student/voucher record(s) from " . $file->getClientName()
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$count} record(s) imported successfully.",
            'count'   => $count,
        ]);
    }

    public function export()
    {
        $format = $this->request->getGet('format') ?? 'xlsx';
        if (!in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $voucherModel = new VoucherModel();
        $ids = $this->parseSelectedIds((string) $this->request->getGet('ids'));
        $rows = !empty($ids)
            ? $voucherModel->getVouchersByIds($ids)
            : $voucherModel->getVouchersForListing('', 0);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headers = [
            'Voucher No.', 'Voucher Date', 'Full Name', 'Rank No.', 'GWA',
            'Gender', 'Junior High School', 'Preferred Senior High School',
            'Contact Number', 'Remarks', 'School Year', 'Eligibility', 'Voucher Status',
        ];

        foreach ($headers as $col => $h) {
            $sheet->getCell([$col + 1, 1])->setValue($h);
        }

        foreach ($rows as $ri => $r) {
            $row = $ri + 2;
            $sheet->getCell([1,  $row])->setValue($r['voucher_no'] ?? '');
            $sheet->getCell([2,  $row])->setValue($r['voucher_date'] ?? '');
            $sheet->getCell([3,  $row])->setValue($r['full_name'] ?? '');
            $sheet->getCell([4,  $row])->setValue($r['rank_no'] ?? '');
            $sheet->getCell([5,  $row])->setValue($r['gwa'] ?? '');
            $sheet->getCell([6,  $row])->setValue($r['gender'] ?? '');
            $sheet->getCell([7,  $row])->setValue($r['junior_high_school'] ?? '');
            $sheet->getCell([8,  $row])->setValue($r['preferred_senior_high_school'] ?? '');
            $sheet->getCell([9,  $row])->setValue($r['contact_number'] ?? '');
            $sheet->getCell([10, $row])->setValue($r['remarks_status'] ?? '');
            $sheet->getCell([11, $row])->setValue($r['school_year'] ?? '');
            $sheet->getCell([12, $row])->setValue($r['eligibility_status'] ?? '');
            $sheet->getCell([13, $row])->setValue($r['voucher_status'] ?? '');
        }

        $filename = 'students_export_' . date('Ymd_His');

        ob_start();

        if ($format === 'csv') {
            (new CsvWriter($spreadsheet))->save('php://output');
            $body = ob_get_clean();
            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"")
                ->setHeader('Cache-Control', 'no-store')
                ->setBody($body);
        }

        (new Xlsx($spreadsheet))->save('php://output');
        $body = ob_get_clean();
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}.xlsx\"")
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($body);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function parseCsv(string $path): array
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

    private function validateHeaders(array $headerRow): ?string
    {
        $actual = array_map(fn($h) => strtolower(trim((string) $h)), $headerRow);

        // Pad/slice to expected count for comparison
        $actual = array_slice($actual, 0, count(self::EXPECTED_HEADERS));

        if ($actual !== self::EXPECTED_HEADERS) {
            $expected = implode(', ', array_map(fn($h) => '"' . $h . '"', self::EXPECTED_HEADERS));
            return "File format does not match the expected template. "
                 . "Required columns (in order): {$expected}.";
        }

        return null;
    }

    private function importRowError(int $rowIndex, string $message)
    {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Import rejected on row ' . ($rowIndex + 1) . ': ' . $message,
        ]);
    }

    private function parseSelectedIds(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $ids = array_filter(array_map('intval', explode(',', $raw)), static fn($id) => $id > 0);
        return array_values(array_unique($ids));
    }

    private function normalizeName(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private function formatStudentFullName(array $row): string
    {
        $parts = [
            $row['first_name'] ?? '',
            $row['middle_name'] ?? '',
            $row['last_name'] ?? '',
            $row['suffix'] ?? '',
        ];

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts, static fn($part) => trim((string) $part) !== ''))));
    }

    private function findExistingStudentByNames(array $normalizedNames, VoucherModel $voucherModel): ?array
    {
        $nameLookup = array_fill_keys($normalizedNames, true);
        $rows = $voucherModel
            ->select('voucher_no, first_name, middle_name, last_name, suffix')
            ->findAll();

        foreach ($rows as $row) {
            $fullName = $this->formatStudentFullName($row);
            $normalized = $this->normalizeName($fullName);
            if (isset($nameLookup[$normalized])) {
                return [
                    'voucher_no'      => $row['voucher_no'] ?? '',
                    'full_name'       => $fullName,
                    'normalized_name' => $normalized,
                ];
            }
        }

        return null;
    }

    private function isValidDate(string $value): bool
    {
        if (trim($value) === '') {
            return false;
        }

        return strtotime($value) !== false;
    }
}
