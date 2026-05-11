<?php

namespace App\Controllers\User;

use App\Libraries\VoucherPdf;
use App\Controllers\Admin\Voucher as AdminVoucher;

class Voucher extends AdminVoucher
{
    // ── List all vouchers (user view) ─────────────────────────────────────────
    public function index()
    {
        $students = $this->voucherModel->getVouchersForListing();

        return view('vouchers/index', [
            'title'    => 'Vouchers',
            'vouchers' => $students,
            'role'     => session()->get('role') ?: 'user',
        ]);
    }

    // ── Generate PDF ──────────────────────────────────────────────────────────
    public function generatePdf()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id');

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
        $students = $this->voucherModel->getVouchersByIds($ids);

        if (empty($students)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid students found.']);
        }

        try {
            $pdfBytes   = VoucherPdf::generate($students);
            $studentIds = array_column($students, 'student_id');
            $jobId      = $this->savePdfFile($studentIds, $userId, $pdfBytes);

            \Config\Database::connect()
                ->table('students')
                ->whereIn('student_id', $studentIds)
                ->update(['voucher_status' => 'generated']);

            return $this->response->setJSON([
                'success'      => true,
                'download_url' => site_url('user/vouchers/pdf-download/' . $jobId),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[generatePdf user] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
        }
    }

    // ── Archive selected students ─────────────────────────────────────────────
    public function archive()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id');
        $reason = $this->request->getPost('archive_reason') ?? 'Archived by user';

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
        $students = $this->voucherModel->getVouchersByIds($ids);
        $now      = date('Y-m-d H:i:s');
        $archived = 0;

        foreach ($students as $s) {
            $this->archiveModel->insert([
                'student_id'                   => $s['student_id'],
                'voucher_no'                   => $s['voucher_no'],
                'voucher_date'                 => $s['voucher_date'],
                'first_name'                   => $s['first_name'],
                'middle_name'                  => $s['middle_name'],
                'last_name'                    => $s['last_name'],
                'suffix'                       => $s['suffix'],
                'rank_no'                      => $s['rank_no'],
                'gwa'                          => $s['gwa'],
                'gender'                       => $s['gender'],
                'junior_high_school'           => $s['junior_high_school'],
                'preferred_senior_high_school' => $s['preferred_senior_high_school'],
                'contact_number'               => $s['contact_number'],
                'remarks_status'               => $s['remarks_status'],
                'school_year'                  => $s['school_year'],
                'eligibility_status'           => $s['eligibility_status'],
                'voucher_status'               => $s['voucher_status'],
                'archive_reason'               => $reason,
                'archived_by'                  => $userId,
                'archived_at'                  => $now,
            ]);

            $this->voucherModel->update($s['student_id'], ['is_archived' => 1]);
            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} student(s) archived successfully.",
        ]);
    }
}
