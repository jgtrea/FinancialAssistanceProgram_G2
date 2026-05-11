<?php

namespace App\Controllers\User;

use App\Libraries\VoucherPdf;
use App\Controllers\Admin\Voucher as AdminVoucher;

/**
 * User\Voucher extends Admin\Voucher but scopes
 * all queries to the currently logged-in user.
 */
class Voucher extends AdminVoucher
{
    // ── List only this user's vouchers ────────────────────────────────────────
    public function index()
    {
        $userId   = session()->get('user_id');
        $vouchers = $this->voucherModel->getVouchersForListing($userId);

        return view('vouchers/index', [
            'title'    => 'My Vouchers',
            'vouchers' => $vouchers,
            'role'     => session()->get('role') ?: 'admin',
        ]);
    }

    // ── Generate PDF — only for vouchers owned by this user ───────────────────
    public function generatePdf()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id');

        if (empty($ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No vouchers selected.']);
        }

        $ids      = array_map('intval', (array) $ids);
        $vouchers = $this->voucherModel->getVouchersByIds($ids);
        $vouchers = array_values(array_filter($vouchers, fn($v) => (int) $v['created_by'] === $userId));

        if (empty($vouchers)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid vouchers found.']);
        }

        try {
            $pdfBytes    = VoucherPdf::generate($vouchers);
            $filteredIds = array_column($vouchers, 'voucher_id');
            $jobId       = $this->savePdfFile($filteredIds, $userId, $pdfBytes);

            return $this->response->setJSON([
                'success'      => true,
                'download_url' => site_url('user/vouchers/pdf-download/' . $jobId),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[generatePdf user] ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'PDF generation failed: ' . $e->getMessage()]);
        }
    }

    // ── Archive — only user's own vouchers ───────────────────────────────────
    public function archive()
    {
        $ids    = $this->request->getPost('voucher_ids');
        $userId = session()->get('user_id');
        $reason = $this->request->getPost('archive_reason') ?? 'Archived by user';

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No vouchers selected.',
            ]);
        }

        $ids      = array_map('intval', (array) $ids);
        $vouchers = $this->voucherModel->getVouchersByIds($ids);
        $vouchers = array_filter($vouchers, fn($v) => (int)$v['created_by'] === $userId);
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

            $archived++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$archived} voucher(s) archived successfully.",
        ]);
    }
}