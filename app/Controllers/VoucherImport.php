<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\VoucherModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VoucherImport extends BaseController
{
    public function index()
    {
        return redirect()->to('/students');
    }

    public function import()
    {
        $file = $this->request->getFile('excel_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Please upload a valid Excel file.');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Only .xlsx or .xls files are allowed.');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheetData   = $spreadsheet->getActiveSheet()->toArray();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to read file: ' . $e->getMessage());
        }

        $studentModel = new StudentModel();
        $voucherModel = new VoucherModel();

        $count      = 0;
        $errors     = [];
        $schoolYear = '2025-2026';
        $createdBy  = session()->get('user_id');

        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];

            if (empty($row[0]) && empty($row[2])) continue;

            try {
                $studentId = $studentModel->insert([
                    'voucher_no'                   => $row[0] ?? null,
                    'voucher_date'                 => $row[1] ?? date('Y-m-d'),
                    'full_name'                    => $row[2] ?? null,
                    'rank_no'                      => $row[3] ?? null,
                    'gwa'                          => $row[4] ?? null,
                    'gender'                       => $row[5] ?? null,
                    'junior_high_school'           => $row[6] ?? null,
                    'preferred_senior_high_school' => $row[7] ?? null,
                    'contact_number'               => $row[8] ?? null,
                    'remarks_status'               => $row[9] ?? null,
                    'school_year'                  => $schoolYear,
                    'eligibility_status'           => 'eligible',
                    'is_archived'                  => 0
                ]);

                if (!$studentId) {
                    $errors[] = "Row " . ($i + 1) . ": Failed to insert student.";
                    continue;
                }

                $voucherId = $voucherModel->insert([
                    'voucher_no'         => $row[0] ?? null,
                    'voucher_date'       => $row[1] ?? date('Y-m-d'),
                    'recipient_name'     => $row[2] ?? null,
                    'senior_high_school' => $row[7] ?? null,
                    'amount_in_words'    => '',
                    'amount'             => 0,
                    'created_by'         => $createdBy,
                    'school_year'        => $schoolYear,
                    'voucher_status'     => 'not_generated',
                    'student_id'         => $studentId
                ]);

                if (!$voucherId) {
                    $errors[] = "Row " . ($i + 1) . ": Failed to insert voucher.";
                    continue;
                }

                $count++;

            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return redirect()->to('/students')->with('error', implode('<br>', $errors));
        }

        $this->writeAuditLog(
            'records_imported',
            'Imported ' . $count . ' student/voucher record(s) from ' . $file->getClientName() . '.'
        );

        return redirect()->to('/students')->with('success', $count . ' records successfully imported.');
    }
}
