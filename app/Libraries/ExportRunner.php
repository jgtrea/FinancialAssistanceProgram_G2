<?php

namespace App\Libraries;

use App\Models\VoucherModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * ExportRunner — process a single EXPORT job from the JSON queue.
 *
 * Builds the students xlsx/csv in the background worker and writes it under
 * writable/pdfs/ (same dir the PDF outputs use, so the existing sweep cleans it
 * up). The finished job exposes file_path; the browser downloads it via
 * JobController::download once status is 'done'.
 *
 * Ported from the old synchronous VoucherImport::export().
 */
class ExportRunner
{
    // Split-name template (round-trips back into import).
    private const HEADERS = [
        'CONTROL NO.', 'VOUCHER NO.', 'DATE', 'SURNAME', 'FIRSTNAME', 'MIDDLE NAME',
        'RANK', 'GWA', 'GENDER', 'JUNIOR HIGH SCHOOL', 'PREFERRED SENIOR HIGH SCHOOL',
        'CONTACT NUMBER', 'REMARKS / STATUS (COMPLETE / INCOMPLETE / OTHERS)', 'EVALUATED BY',
    ];

    public static function processClaimed(array $job): bool
    {
        $jobId  = (int) $job['job_id'];
        $format = ($job['payload']['format'] ?? 'xlsx') === 'csv' ? 'csv' : 'xlsx';
        $ids    = array_map('intval', $job['payload']['ids'] ?? []);
        $userId = isset($job['created_by']) ? (int) $job['created_by'] : null;

        try { \Config\Database::connect()->reconnect(); } catch (\Throwable $_) {}

        try {
            $filename = self::buildFile($ids, $format);

            log_action($userId ?? 0, 'EXPORT_RECORDS', "Exported students ({$format}) (queued job #{$jobId})");

            JsonPdfQueue::finishSingle($jobId, function (array $rec) use ($filename) {
                $rec['status']       = 'done';
                $rec['file_path']    = $filename;
                $rec['completed_at'] = date('Y-m-d H:i:s');
                return $rec;
            });

            return true;
        } catch (\Throwable $e) {
            log_message('error', "[ExportRunner] Job {$jobId}: " . $e->getMessage());

            $msg = $e->getMessage();
            JsonPdfQueue::finishSingle($jobId, function (array $rec) use ($msg) {
                $rec['status']        = 'failed';
                $rec['error_message'] = $msg;
                $rec['completed_at']  = date('Y-m-d H:i:s');
                return $rec;
            });

            return false;
        }
    }

    /**
     * Query rows (selected ids, or the full listing when none), build the
     * spreadsheet, write it to writable/pdfs/, and return the filename.
     */
    protected static function buildFile(array $ids, string $format): string
    {
        $voucherModel = new VoucherModel();
        $rows = ! empty($ids)
            ? $voucherModel->getVouchersByIds($ids)
            : $voucherModel->getVouchersForListing('', 0);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        foreach (self::HEADERS as $col => $h) {
            $sheet->getCell([$col + 1, 1])->setValue($h);
        }

        foreach ($rows as $ri => $r) {
            $row = $ri + 2;
            $sheet->getCell([1,  $row])->setValue($r['control_no'] ?? '');
            $sheet->getCell([2,  $row])->setValue($r['voucher_no'] ?? '');
            $sheet->getCell([3,  $row])->setValue($r['voucher_date'] ?? '');
            $sheet->getCell([4,  $row])->setValue($r['last_name'] ?? '');     // Surname
            $sheet->getCell([5,  $row])->setValue($r['first_name'] ?? '');    // Firstname
            $sheet->getCell([6,  $row])->setValue($r['middle_name'] ?? '');
            $sheet->getCell([7,  $row])->setValue($r['rank_no'] ?? '');
            $sheet->getCell([8,  $row])->setValue($r['gwa'] ?? '');
            $sheet->getCell([9,  $row])->setValue($r['gender'] ?? '');
            $sheet->getCell([10, $row])->setValue($r['junior_high_school'] ?? '');
            $sheet->getCell([11, $row])->setValue($r['preferred_senior_high_school'] ?? '');
            $sheet->getCell([12, $row])->setValue($r['contact_number'] ?? '');
            $sheet->getCell([13, $row])->setValue($r['remarks_status'] ?? '');
            $sheet->getCell([14, $row])->setValue($r['evaluated_by'] ?? '');
        }

        // Table styling (xlsx only — CsvWriter ignores it): bold/centered header
        // with a grey fill, thin borders on the whole grid, autosized columns.
        if ($format !== 'csv') {
            $lastCol = Coordinate::stringFromColumnIndex(count(self::HEADERS)); // 'N'
            $lastRow = count($rows) + 1;

            $header = "A1:{$lastCol}1";
            $sheet->getStyle($header)->getFont()->setBold(true);
            $sheet->getStyle($header)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getStyle($header)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D9D9D9');
            $sheet->getRowDimension(1)->setRowHeight(30);

            $all = "A1:{$lastCol}{$lastRow}";
            $sheet->getStyle($all)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($all)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            for ($c = 1; $c <= count(self::HEADERS); $c++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
            }
        }

        $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'students_export_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6)
            . ($format === 'csv' ? '.csv' : '.xlsx');

        if ($format === 'csv') {
            $writer = new CsvWriter($spreadsheet);
            $writer->setUseBOM(true);              // Excel: split columns + UTF-8 accents
            $writer->setEnclosureRequired(false);  // quote only when needed, cleaner
        } else {
            $writer = new Xlsx($spreadsheet);
        }
        $writer->save($dir . $filename);

        return $filename;
    }
}
