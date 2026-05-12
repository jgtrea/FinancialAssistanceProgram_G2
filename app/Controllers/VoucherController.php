<?php

namespace App\Controllers;

use App\Models\VoucherModel;
use App\Models\StudentModel;
use App\Models\SignatoryModel;

class VoucherController extends BaseController
{
    public function index()
    {
        $voucherModel = new VoucherModel();

        return view('vouchers/index', [
            'title' => 'Vouchers',
            'vouchers' => $voucherModel->orderBy('voucher_id', 'DESC')->findAll()
        ]);
    }

    public function create($studentId)
    {
        $studentModel = new StudentModel();
        $signatoryModel = new SignatoryModel();
        $student = $studentModel->find($studentId);

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Student not found.');
        }

        return view('vouchers/form', [
            'title' => 'Generate Voucher',
            'student' => $student,
            'signatories' => $signatoryModel
                ->where('is_active', 1)
                ->findAll()
        ]);
    }

    public function store()
    {
        $voucherModel = new VoucherModel();

        $voucherId = $voucherModel->insert([
            'voucher_no' => $this->request->getPost('voucher_no'),
            'voucher_date' => $this->request->getPost('voucher_date'),
            'recipient_name' => $this->request->getPost('recipient_name'),
            'senior_high_school' => $this->request->getPost('senior_high_school'),
            'amount_in_words' => 'TEN THOUSAND PESOS ONLY',
            'amount' => 10000,

            'signatory_1_id' => $this->request->getPost('signatory_1_id'),
            'signatory_2_id' => $this->request->getPost('signatory_2_id'),
            'signatory_3_id' => $this->request->getPost('signatory_3_id'),

            'school_year' => $this->request->getPost('school_year'),
            'voucher_status' => 'generated',

            'created_by' => 1
        ]);

        $this->writeAuditLog(
            'voucher_generated',
            'Generated voucher ' . $this->request->getPost('voucher_no') . ' for ' . $this->request->getPost('recipient_name'),
            $voucherId ? (int) $voucherId : null
        );

        return redirect()->to('/vouchers')
            ->with('success', 'Voucher generated successfully.');
    }
}
