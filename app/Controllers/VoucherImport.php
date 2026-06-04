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
    // Required columns in order (lowercase, trimmed). Suffix is optional and
    // may appear at position 5 (between last name and rank no.).
    private const REQUIRED_HEADERS = [
        'voucher no.',
        'voucher date',
        'first name',
        'middle name',
        'last name',
        'rank no.',
        'gwa',
        'sex',
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

        $headers   = array_map(fn($h) => strtolower(trim((string) $h)), $sheetData[0]);
        $hasSuffix = isset($headers[5]) && $headers[5] === 'suffix';
        // Column offsets shift by 1 after last name when suffix column is present.
        $off = $hasSuffix ? 1 : 0;

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
            $fn  = strtoupper(trim((string) ($sheetData[$i][2] ?? '')));
            $mn  = strtoupper(trim((string) ($sheetData[$i][3] ?? '')));
            $ln  = strtoupper(trim((string) ($sheetData[$i][4] ?? '')));
            $fullName = trim($fn . ($mn !== '' ? ' ' . $mn : '') . ($ln !== '' ? ' ' . $ln : ''));
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
            $firstName   = strtoupper(trim((string) ($row[2] ?? '')));
            $middleName  = strtoupper(trim((string) ($row[3] ?? '')));
            $lastName    = strtoupper(trim((string) ($row[4] ?? '')));
            $suffix      = $hasSuffix ? strtoupper(trim((string) ($row[5] ?? ''))) : '';

            if ($voucherDate === '' || $firstName === '' || $lastName === '') {
                continue;
            }

            // Validate date format
            if (!$this->isValidDate($voucherDate)) {
                return $this->importRowError($i, 'Voucher date must be a valid date.');
            }

            $rankNo    = trim((string) ($row[5 + $off] ?? ''));
            $gwa       = trim((string) ($row[6 + $off] ?? ''));
            $gender    = strtoupper(trim((string) ($row[7 + $off] ?? '')));
            $jhsSchool = trim((string) ($row[8 + $off] ?? ''));
            $shsSchool = trim((string) ($row[9 + $off] ?? ''));
            $contact   = trim((string) ($row[10 + $off] ?? ''));
            $remarks   = strtoupper(trim((string) ($row[11 + $off] ?? '')));

            if ($voucherNo !== '' && strlen($voucherNo) > 50) {
                return $this->importRowError($i, 'Voucher number must be 50 characters or fewer.');
            }

            if ($firstName === '' || $lastName === '') {
                return $this->importRowError($i, 'First Name and Last Name are required.');
            }

            if (strlen($firstName) > 100 || strlen($middleName) > 100 || strlen($lastName) > 100) {
                return $this->importRowError($i, 'Student name parts must be 100 characters or fewer.');
            }

            $allowedSuffixes = ['JR.', 'SR.', 'II', 'III', 'IV'];
            if ($suffix !== '' && !in_array($suffix, $allowedSuffixes, true)) {
                return $this->importRowError($i, 'Suffix must be one of: ' . implode(', ', $allowedSuffixes) . ', or blank.');
            }

            // Validate gender
            if ($gender !== '' && !in_array($gender, ['MALE', 'FEMALE'])) {
                return $this->importRowError($i, 'Sex must be MALE, FEMALE, or blank.');
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

            if (strlen($jhsSchool) > 200 || strlen($shsSchool) > 200) {
                return $this->importRowError($i, 'School names must be 200 characters or fewer.');
            }

            // Auto-add new schools to the school table and store only their IDs on students.
            $jhsSchoolId = $schoolOptions->resolveSchoolId('JHS', $jhsSchool, false);
            $shsSchoolId = $schoolOptions->resolveSchoolId('SHS', $shsSchool, false);

            $voucherModel->insert([
                'voucher_no'                   => $voucherNo !== '' ? $voucherNo : null,
                'voucher_date'                 => date('Y-m-d', strtotime($voucherDate)),
                'first_name'                   => $firstName,
                'middle_name'                  => $middleName,
                'last_name'                    => $lastName,
                'suffix'                       => $suffix,
                'rank_no'                      => is_numeric($rankNo) ? (int) $rankNo : null,
                'gwa'                          => is_numeric($gwa) ? (float) $gwa : null,
                'gender'                       => $gender,
                'junior_high_school'           => $jhsSchoolId,
                'preferred_senior_high_school' => $shsSchoolId,
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
            'Voucher No.', 'Voucher Date',
            'First Name', 'Middle Name', 'Last Name', 'Suffix',
            'Rank No.', 'GWA', 'Sex',
            'Junior High School', 'Preferred Senior High School',
            'Contact Number', 'Remarks', 'School Year', 'Eligibility', 'Voucher Status',
        ];

        foreach ($headers as $col => $h) {
            $sheet->getCell([$col + 1, 1])->setValue($h);
        }

        foreach ($rows as $ri => $r) {
            $row = $ri + 2;
            $sheet->getCell([1,  $row])->setValue($r['voucher_no'] ?? '');
            $sheet->getCell([2,  $row])->setValue($r['voucher_date'] ?? '');
            $sheet->getCell([3,  $row])->setValue($r['first_name'] ?? '');
            $sheet->getCell([4,  $row])->setValue($r['middle_name'] ?? '');
            $sheet->getCell([5,  $row])->setValue($r['last_name'] ?? '');
            $sheet->getCell([6,  $row])->setValue($r['suffix'] ?? '');
            $sheet->getCell([7,  $row])->setValue($r['rank_no'] ?? '');
            $sheet->getCell([8,  $row])->setValue($r['gwa'] ?? '');
            $sheet->getCell([9,  $row])->setValue($r['gender'] ?? '');
            $sheet->getCell([10, $row])->setValue($r['junior_high_school'] ?? '');
            $sheet->getCell([11, $row])->setValue($r['preferred_senior_high_school'] ?? '');
            $sheet->getCell([12, $row])->setValue($r['contact_number'] ?? '');
            $sheet->getCell([13, $row])->setValue($r['remarks_status'] ?? '');
            $sheet->getCell([14, $row])->setValue($r['school_year'] ?? '');
            $sheet->getCell([15, $row])->setValue($r['eligibility_status'] ?? '');
            $sheet->getCell([16, $row])->setValue($r['voucher_status'] ?? '');
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
        $hasSuffix = isset($actual[5]) && $actual[5] === 'suffix';

        // Build the expected sequence: insert 'suffix' at position 5 if present.
        $expected = self::REQUIRED_HEADERS;
        if ($hasSuffix) {
            array_splice($expected, 5, 0, ['suffix']);
        }

        $check = array_slice($actual, 0, count($expected));
        if ($check !== $expected) {
            $req = implode(', ', array_map(fn($h) => '"' . $h . '"', self::REQUIRED_HEADERS));
            return "File format does not match the expected template. "
                 . "Required columns (in order): {$req}. "
                 . '"Suffix" is optional and may appear between "Last Name" and "Rank No.".';
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
