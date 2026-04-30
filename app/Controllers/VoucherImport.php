<?php 

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\VoucherModel;
use App\Models\UserVoucherModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VoucherImport extends BaseController {

    public function index() {
        return view('FileConvertView');
    }

    public function import() {
        $file = $this->request->getFile('excel_file');

        // Basic validation to ensure a file was uploaded
        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Please upload a valid Excel file.');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $userModel = new UserModel();
        $voucherModel = new VoucherModel();
        $uvModel = new UserVoucherModel();
        
        $count = 0;
        // Start from $i = 1 to skip the header row
        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];

            // Skip empty rows
            if (empty($row[0])) continue;

            $userData = [
                'fullname'   => $row[2], 
                'gender'     => $row[5],
                'contact_no' => $row[8]
            ];
            $userId = $userModel->insert($userData);

            $voucherData = [
                'voucher_no'    => $row[0],
                'voucher_date'  => $row[1],
                'rank'          => $row[3],
                'gwa'           => $row[4],
                'jhr'           => $row[6],
                'preferred_shr' => $row[7],
                'remarks'       => $row[9]
            ];
            $voucherModel->insert($voucherData);

            $uvModel->insert([
                'user_id'    => $userId,
                'voucher_no' => $row[0]
            ]);
            
            $count++;
        }

        return view('FileConvertView', [
            'status'  => 'success',
            'message' => $count . ' records were successfully imported.'
        ]);
    }
}