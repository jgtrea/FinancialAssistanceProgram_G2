<?php

namespace App\Controllers;

use App\Models\VoucherModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VoucherImport extends BaseController
{
    public function index()
    {
        return view('FileConvertView');
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

        $voucherModel = new VoucherModel();
        $count        = 0;

        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];

            if (empty($row[0])) continue;

            $voucherNo   = trim((string) ($row[0] ?? ''));
            $voucherDate = trim((string) ($row[1] ?? ''));
            $fullName    = trim((string) ($row[2] ?? ''));
            $rankNo      = trim((string) ($row[3] ?? ''));
            $gwa         = trim((string) ($row[4] ?? ''));
            $gender      = trim((string) ($row[5] ?? ''));
            $jhsSchool   = trim((string) ($row[6] ?? ''));
            $shsSchool   = trim((string) ($row[7] ?? ''));
            $contact     = trim((string) ($row[8] ?? ''));
            $remarks     = trim((string) ($row[9] ?? ''));

            if ($voucherNo === '' || $voucherDate === '' || $fullName === '') continue;

            if ($voucherModel->where('voucher_no', $voucherNo)->first()) continue;

            $nameParts  = explode(' ', $fullName);
            $firstName  = array_shift($nameParts) ?? '';
            $lastName   = !empty($nameParts) ? array_pop($nameParts) : '';
            $middleName = implode(' ', $nameParts);

            $voucherModel->insert([
                'voucher_no'                   => $voucherNo,
                'voucher_date'                 => $voucherDate,
                'first_name'                   => $firstName,
                'middle_name'                  => $middleName,
                'last_name'                    => $lastName,
                'suffix'                       => '',
                'rank_no'                      => is_numeric($rankNo) ? (int) $rankNo : null,
                'gwa'                          => is_numeric($gwa) ? (float) $gwa : null,
                'gender'                       => $gender,
                'junior_high_school'           => $jhsSchool,
                'preferred_senior_high_school' => $shsSchool,
                'contact_number'               => $contact,
                'remarks_status'               => $remarks,
                'school_year'                  => date('Y'),
                'eligibility_status'           => 'eligible',
                'voucher_status'               => 'not_generated',
                'is_archived'                  => 0,
            ]);

            $count++;
        }

        return view('FileConvertView', [
            'status'  => 'success',
            'message' => $count . ' records were successfully imported.',
        ]);
    }
}
