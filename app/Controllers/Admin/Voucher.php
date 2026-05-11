<?php

namespace App\Controllers\Admin;

use App\Libraries\VoucherPdf;
use App\Models\ArchiveModel;
use App\Models\VoucherModel;
use CodeIgniter\Controller;

class Voucher extends Controller
{
    protected VoucherModel $voucherModel;
    protected ArchiveModel $archiveModel;

    public function __construct()
    {
        $this->voucherModel = new VoucherModel();
        $this->archiveModel = new ArchiveModel();
    }

    protected function getFallbackUserId(): int
    {
        $db   = \Config\Database::connect();
        $user = $db->table('users')
            ->select('user_id')
            ->where('is_active', 1)
            ->orderBy('user_id', 'ASC')
            ->limit(1)
            ->get()
            ->getRow();

        return $user->user_id ?? 1;
    }

    protected function getCurrentUserId(): int
    {
        return session()->get('user_id') ?? $this->getFallbackUserId();
    }

    // ── List all vouchers ──────────────────────────────────────────────────────
    public function index()
    {
        $vouchers = $this->voucherModel->getVouchersForListing();

        return view('vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => $vouchers,
            'role'     => session()->get('role') ?: 'admin',
        ]);
    }

    // ── Show create form ───────────────────────────────────────────────────────
    public function create()
    {
        helper(['form']);

        $db          = \Config\Database::connect();
        $students    = $db->table('students')->select('student_id, full_name')->orderBy('full_name', 'ASC')->get()->getResultArray();
        $signatories = $db->table('signatories')->select('signatory_id, full_name')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();

        return view('vouchers/form', [
            'title'       => 'Add Voucher',
            'action'      => site_url('admin/vouchers/store'),
            'voucher'     => [],
            'students'    => $students,
            'signatories' => $signatories,
            'validation'  => \Config\Services::validation(),
        ]);
    }

    // ── Persist a new voucher ──────────────────────────────────────────────────
    public function store()
    {
        helper(['form']);

        $rules = [
            'voucher_no'         => 'required|max_length[50]',
            'voucher_date'       => 'required|valid_date',
            'recipient_name'     => 'required|max_length[200]',
            'senior_high_school' => 'required|max_length[200]',
            'amount'             => 'required|numeric',
            'amount_in_words'    => 'required|max_length[255]',
            'school_year'        => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->create();
        }

        $this->voucherModel->insert([
            'voucher_no'         => $this->request->getPost('voucher_no'),
            'voucher_date'       => $this->request->getPost('voucher_date'),
            'recipient_name'     => $this->request->getPost('recipient_name'),
            'senior_high_school' => $this->request->getPost('senior_high_school'),
            'amount'             => $this->request->getPost('amount'),
            'amount_in_words'    => $this->request->getPost('amount_in_words'),
            'school_year'        => $this->request->getPost('school_year'),
            'voucher_status'     => $this->request->getPost('voucher_status') ?: 'not_generated',
            'student_id'         => $this->request->getPost('student_id') ?: null,
            'signatory_1_id'     => $this->request->getPost('signatory_1_id') ?: null,
            'signatory_2_id'     => $this->request->getPost('signatory_2_id') ?: null,
            'signatory_3_id'     => $this->request->getPost('signatory_3_id') ?: null,
            'created_by'         => $this->getCurrentUserId(),
        ]);

        return redirect()->to(site_url('admin/vouchers'))->with('message', 'Voucher created successfully.');
    }

    // ── Show a voucher detail page ─────────────────────────────────────────────
    public function view(int $id)
    {
        $voucher = $this->voucherModel->getVoucherWithSignatories($id);

        if (!$voucher) {
            return redirect()->to(site_url('admin/vouchers'))->with('error', 'Voucher not found.');
        }

        return view('vouchers/view', ['title' => 'Voucher Details', 'voucher' => $voucher]);
    }

    // ── Show edit form ─────────────────────────────────────────────────────────
    public function edit(int $id)
    {
        helper(['form']);

        $voucher = $this->voucherModel->getVoucherWithSignatories($id);
        if (!$voucher) {
            return redirect()->to(site_url('admin/vouchers'))->with('error', 'Voucher not found.');
        }

        $db          = \Config\Database::connect();
        $students    = $db->table('students')->select('student_id, full_name')->orderBy('full_name', 'ASC')->get()->getResultArray();
        $signatories = $db->table('signatories')->select('signatory_id, full_name')->where('is_active', 1)->orderBy('full_name', 'ASC')->get()->getResultArray();

        return view('vouchers/form', [
            'title'       => 'Edit Voucher',
            'action'      => site_url('admin/vouchers/update/' . $id),
            'voucher'     => $voucher,
            'students'    => $students,
            'signatories' => $signatories,
            'validation'  => \Config\Services::validation(),
        ]);
    }

    // ── Persist voucher edits ──────────────────────────────────────────────────
    public function update(int $id)
    {
        helper(['form']);

        $rules = [
            'voucher_no'         => 'required|max_length[50]',
            'voucher_date'       => 'required|valid_date',
            'recipient_name'     => 'required|max_length[200]',
            'senior_high_school' => 'required|max_length[200]',
            'amount'             => 'required|numeric',
            'amount_in_words'    => 'required|max_length[255]',
            'school_year'        => 'required|max_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->edit($id);
        }

        $this->voucherModel->update($id, [
            'voucher_no'         => $this->request->getPost('voucher_no'),
            'voucher_date'       => $this->request->getPost('voucher_date'),
            'recipient_name'     => $this->request->getPost('recipient_name'),
            'senior_high_school' => $this->request->getPost('senior_high_school'),
            'amount'             => $this->request->getPost('amount'),
            'amount_in_words'    => $this->request->getPost('amount_in_words'),
            'school_year'        => $this->request->getPost('school_year'),
            'voucher_status'     => $this->request->getPost('voucher_status') ?: 'not_generated',
            'student_id'         => $this->request->getPost('student_id') ?: null,
            'signatory_1_id'     => $this->request->getPost('signatory_1_id') ?: null,
            'signatory_2_id'     => $this->request->getPost('signatory_2_id') ?: null,
            'signatory_3_id'     => $this->request->getPost('signatory_3_id') ?: null,
        ]);

        return redirect()->to(site_url('admin/vouchers'))->with('message', 'Voucher updated successfully.');
    }

    // ── Generate PDF synchronously and return download URL ────────────────────
    public function generatePdf()
    {
        $ids = $this->request->getPost('voucher_ids');

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No vouchers selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
        $vouchers = $this->voucherModel->getVouchersByIds($ids);

        if (empty($vouchers)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Selected vouchers not found.']);
        }

        try {
            $pdfBytes = VoucherPdf::generate($vouchers);
            $jobId    = $this->savePdfFile($ids, $this->getCurrentUserId(), $pdfBytes);

            return $this->response->setJSON([
                'success'      => true,
                'download_url' => site_url('admin/vouchers/pdf-download/' . $jobId),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[generatePdf] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
        }
    }

    // ── Poll job status (AJAX GET) ─────────────────────────────────────────────
    public function checkPdfJob(int $jobId)
    {
        $db  = \Config\Database::connect();
        $job = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();

        if (!$job) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $role   = session()->get('role') ?: 'admin';
        $userId = $this->getCurrentUserId();

        if ($role !== 'admin' && (int) $job->created_by !== $userId) {
            return $this->response->setJSON(['status' => 'forbidden']);
        }

        $prefix      = $role === 'admin' ? 'admin' : 'user';
        $downloadUrl = $job->status === 'done'
            ? site_url("{$prefix}/vouchers/pdf-download/{$jobId}")
            : null;

        return $this->response->setJSON([
            'status'       => $job->status,
            'download_url' => $downloadUrl,
            'error'        => $job->error_message,
        ]);
    }

    // ── Stream the generated PDF to the browser ────────────────────────────────
    public function downloadPdf(int $jobId)
    {
        $db     = \Config\Database::connect();
        $job    = $db->table('pdf_jobs')->where('job_id', $jobId)->get()->getRow();
        $role   = session()->get('role') ?: 'admin';
        $userId = $this->getCurrentUserId();

        if (!$job || ($role !== 'admin' && (int) $job->created_by !== $userId)) {
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

    // ── Archive selected vouchers ──────────────────────────────────────────────
    public function archive()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $reason = $this->request->getPost('archive_reason') ?? 'Archived by admin';

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No vouchers selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
        $vouchers = $this->voucherModel->getVouchersByIds($ids);
        $userId   = session()->get('user_id');
        $now      = date('Y-m-d H:i:s');
        $archived = 0;

        foreach ($vouchers as $v) {
            $this->archiveModel->insert([
                'voucher_id'         => $v['voucher_id'],
                'voucher_no'         => $v['voucher_no'],
                'recipient_name'     => $v['recipient_name'],
                'senior_high_school' => $v['senior_high_school'],
                'amount_in_words'    => $v['amount_in_words'],
                'amount'             => $v['amount'],
                'school_year'        => $v['school_year'],
                'voucher_status'     => $v['voucher_status'],
                'archive_reason'     => $reason,
                'archived_by'        => $userId,
                'archived_at'        => $now,
            ]);

            log_action($userId, 'ARCHIVE_VOUCHER',
                "Voucher {$v['voucher_no']} archived by user ID {$userId}",
                $v['voucher_id']);

            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} voucher(s) archived successfully.",
        ]);
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

    // ── Build PDF bytes via shared library (kept for direct use) ──────────────
    protected function buildPdf(array $vouchers): \CodeIgniter\HTTP\Response
    {
        $filename = 'vouchers_' . date('Ymd_His') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody(VoucherPdf::generate($vouchers));
    }
}
