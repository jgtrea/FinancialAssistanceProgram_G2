<?php

namespace App\Controllers;

use App\Models\VoucherModel;
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
        $count        = 0;

        // Collect all non-empty voucher numbers from the file first
        $fileVoucherNos = [];
        for ($i = 1; $i < count($sheetData); $i++) {
            $vno = trim((string) ($sheetData[$i][0] ?? ''));
            if ($vno !== '') {
                $fileVoucherNos[] = $vno;
            }
        }

        // Reject the entire import if any voucher number already exists
        if (!empty($fileVoucherNos)) {
            $existing = $voucherModel
                ->whereIn('voucher_no', $fileVoucherNos)
                ->findColumn('voucher_no');

            if (!empty($existing)) {
                $list = implode(', ', array_slice($existing, 0, 5));
                $more = count($existing) > 5 ? ' and ' . (count($existing) - 5) . ' more' : '';
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Import rejected: ' . count($existing) . ' duplicate voucher number(s) found ('
                               . $list . $more . '). Remove duplicates from the file and try again.',
                ]);
            }
        }

        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];

            $voucherNo   = trim((string) ($row[0] ?? ''));
            $voucherDate = trim((string) ($row[1] ?? ''));
            $fullName    = trim((string) ($row[2] ?? ''));

            if ($voucherNo === '' || $voucherDate === '' || $fullName === '') {
                continue;
            }

            // Validate date format
            if (!strtotime($voucherDate)) {
                continue;
            }

            $rankNo  = trim((string) ($row[3] ?? ''));
            $gwa     = trim((string) ($row[4] ?? ''));
            $gender  = strtoupper(trim((string) ($row[5] ?? '')));
            $jhsSchool = trim((string) ($row[6] ?? ''));
            $shsSchool = trim((string) ($row[7] ?? ''));
            $contact   = trim((string) ($row[8] ?? ''));
            $remarks   = strtoupper(trim((string) ($row[9] ?? '')));

            // Validate gender
            if ($gender !== '' && !in_array($gender, ['MALE', 'FEMALE'])) {
                $gender = '';
            }

            // Validate remarks
            if (!in_array($remarks, ['PASSED', 'FOR REVIEW', 'FAILED'])) {
                $remarks = '';
            }

            $nameParts  = explode(' ', $fullName);
            $firstName  = array_shift($nameParts) ?? '';
            $lastName   = !empty($nameParts) ? array_pop($nameParts) : '';
            $middleName = implode(' ', $nameParts);

            $voucherModel->insert([
                'voucher_no'                   => $voucherNo,
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
                'is_archived'                  => 0,
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
        $rows         = $voucherModel->getVouchersForListing();

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
}
