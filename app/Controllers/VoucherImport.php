<?php 

namespace App\Controllers;

use App\Models\StudentModel;
use App\Models\VoucherModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VoucherImport extends BaseController {

    public function index() {
        return view('FileConvertView');
    }

    public function import() {
        $file = $this->request->getFile('excel_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Please upload a valid Excel file.');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $studentModel = new StudentModel();
        $voucherModel = new VoucherModel();

        $count = 0;
        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];

            if (empty($row[0])) {
                continue;
            }

            $voucherNo = trim((string) ($row[0] ?? ''));
            $voucherDate = trim((string) ($row[1] ?? ''));
            $fullName = trim((string) ($row[2] ?? ''));
            $rankNo = trim((string) ($row[3] ?? ''));
            $gwa = trim((string) ($row[4] ?? ''));
            $gender = trim((string) ($row[5] ?? ''));
            $juniorHighSchool = trim((string) ($row[6] ?? ''));
            $preferredSeniorHighSchool = trim((string) ($row[7] ?? ''));
            $contactNumber = trim((string) ($row[8] ?? ''));
            $remarksStatus = trim((string) ($row[9] ?? ''));

            if ($voucherNo === '' || $voucherDate === '' || $fullName === '') {
                continue;
            }

            // Skip duplicate voucher records
            if ($voucherModel->where('voucher_no', $voucherNo)->first()) {
                continue;
            }

            $studentId = $studentModel->insert([
                'voucher_no'                => $voucherNo,
                'voucher_date'              => $voucherDate,
                'full_name'                 => $fullName,
                'rank_no'                   => is_numeric($rankNo) ? (int) $rankNo : null,
                'gwa'                       => is_numeric($gwa) ? (float) $gwa : null,
                'gender'                    => $gender,
                'junior_high_school'        => $juniorHighSchool,
                'preferred_senior_high_school' => $preferredSeniorHighSchool,
                'contact_number'            => $contactNumber,
                'remarks_status'            => $remarksStatus,
                'school_year'               => date('Y'),
                'eligibility_status'        => 'eligible',
            ]);

            $voucherModel->insert([
                'voucher_no'        => $voucherNo,
                'voucher_date'      => $voucherDate,
                'recipient_name'    => $fullName,
                'senior_high_school'=> $preferredSeniorHighSchool,
                'amount_in_words'   => '',
                'amount'            => 0.00,
                'created_by'        => null,
                'signatory_1_id'    => null,
                'signatory_2_id'    => null,
                'signatory_3_id'    => null,
                'school_year'       => date('Y'),
                'voucher_status'    => 'not_generated',
                'student_id'        => $studentId,
            ]);

            $count++;
        }

        return view('FileConvertView', [
            'status'  => 'success',
            'message' => $count . ' records were successfully imported.'
        ]);
    }
}