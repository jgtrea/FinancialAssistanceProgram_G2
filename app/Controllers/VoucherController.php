<?php

namespace App\Controllers;

use App\Models\VoucherModel;
use App\Models\StudentModel;
use App\Models\SignatoryModel;
use App\Libraries\VoucherPdf;

class VoucherController extends BaseController
{
    public function index()
    {
        $voucherModel = new VoucherModel();

        return view('vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => $voucherModel->orderBy('voucher_id', 'DESC')->findAll(),
        ]);
    }

    public function create($studentId)
    {
        $studentModel   = new StudentModel();
        $signatoryModel = new SignatoryModel();
        $student        = $studentModel->find($studentId);

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Student not found.');
        }

        return view('vouchers/form', [
            'title'       => 'Generate Voucher',
            'student'     => $student,
            'signatories' => $signatoryModel->where('is_active', 1)->findAll(),
        ]);
    }

    public function store()
    {
        $voucherModel = new VoucherModel();

        $voucherId = $voucherModel->insert([
            'voucher_no'         => $this->request->getPost('voucher_no'),
            'voucher_date'       => $this->request->getPost('voucher_date'),
            'recipient_name'     => $this->request->getPost('recipient_name'),
            'senior_high_school' => $this->request->getPost('senior_high_school'),
            'amount_in_words'    => 'TEN THOUSAND PESOS ONLY',
            'amount'             => 10000,
            'signatory_1_id'     => $this->request->getPost('signatory_1_id'),
            'signatory_2_id'     => $this->request->getPost('signatory_2_id'),
            'signatory_3_id'     => $this->request->getPost('signatory_3_id'),
            'school_year'        => $this->request->getPost('school_year'),
            'voucher_status'     => 'generated',
            'created_by'         => session()->get('user_id'),
        ]);

        $this->writeAuditLog(
            'voucher_generated',
            'Generated voucher ' . $this->request->getPost('voucher_no') . ' for ' . $this->request->getPost('recipient_name'),
            $voucherId ? (int) $voucherId : null
        );

        return redirect()->to('/vouchers')->with('success', 'Voucher generated successfully.');
    }

    // ── Generate PDF and return a download URL (matches Admin\Voucher pattern) ─
    public function generatePdf()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id') ?? 1;

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $ids          = array_map('intval', (array) $ids);
        $studentModel = new StudentModel();

        // Use a method consistent with what VoucherPdf::generate() expects.
        // If StudentModel has getVouchersByIds(), prefer that; otherwise use whereIn.
        $students = method_exists($studentModel, 'getVouchersByIds')
            ? $studentModel->getVouchersByIds($ids)
            : $studentModel->whereIn('student_id', $ids)->findAll();

        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid students found.']);
        }

        try {
            $pdfBytes   = VoucherPdf::generate($students);
            $studentIds = array_column($students, 'student_id');
            $jobId      = $this->savePdfFile($ids, $userId, $pdfBytes);

            \Config\Database::connect()
                ->table('students')
                ->whereIn('student_id', $studentIds)
                ->update(['voucher_status' => 'generated']);

            return $this->response->setJSON([
                'success'      => true,
                'download_url' => site_url('user/vouchers/pdf-download/' . $jobId),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[generatePdf] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
        }
    }

    // ── Stream the generated PDF to the browser ────────────────────────────────
    public function downloadPdf(int $jobId)
    {
        $db     = \Config\Database::connect();
        $job    = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        $userId = session()->get('user_id') ?? 1;

        if (!$job || (int) $job->created_by !== $userId) {
            return redirect()->back()->with('error', 'PDF not found or access denied.');
        }

        if ($job->status !== 'done') {
            return redirect()->back()->with('error', 'PDF is not ready yet.');
        }

        $filePath = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR . $job->file_path;

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'PDF file is missing from storage.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }

    // ── Save generated PDF bytes to disk and record the job ───────────────────
    protected function savePdfFile(array $ids, int $userId, string $pdfBytes): int
    {
        $dir = WRITEPATH . 'pdfs' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $db = \Config\Database::connect();
        $db->table('pdf_jobs')->insert([
            'voucher_ids' => json_encode(array_values($ids)),
            'status'      => 'pending',
            'created_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $jobId    = (int) $db->insertID();
        $filename = 'vouchers_job' . $jobId . '_' . date('Ymd_His') . '.pdf';

        file_put_contents($dir . $filename, $pdfBytes);

        $db->table('pdf_jobs')
            ->where('job_id', $jobId)
            ->update([
                'status'       => 'done',
                'file_path'    => $filename,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

        return $jobId;
    }
}