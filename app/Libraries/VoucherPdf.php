<?php

namespace App\Libraries;

use Mpdf\Mpdf;

class VoucherPdf
{
    // ── Adjust x and y (mm) to align text with the blank lines on the template ─
    public const Y_VOUCHER_NO = 38;   // y — Voucher No. + Date
    public const Y_RECIPIENT  = 48;   // y — Recipient name
    public const Y_SCHOOL     = 58;   // y — School name

    public const X_VOUCHER_NO = 40;   // x — Voucher No.
    public const X_DATE       = 165;  // x — Date
    public const X_RECIPIENT  = 55;   // x — Recipient name
    public const X_SCHOOL     = 55;   // x — School name
    // ─────────────────────────────────────────────────────────────────────────────

    public const SLOT_HEIGHT  = 99;   // mm — each voucher slot height
    public const FONT_SIZE    = 12;   // pt

    public static function generate(array $vouchers): string
    {
        $bgPath = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'voucher_bg.png';
        $hasBg  = file_exists($bgPath);

        $tmpDir = WRITEPATH . 'mpdf_tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'margin_right'  => 0,
            'tempDir'       => $tmpDir,
        ]);

        $mpdf->SetAutoPageBreak(false);

        $pages = array_chunk($vouchers, 3);

        foreach ($pages as $page) {
            $mpdf->AddPage();
            $mpdf->SetFont('Arial', '', self::FONT_SIZE);
            $mpdf->SetTextColor(17, 17, 17);

            // Always render all 3 slots — background shows even for empty slots
            for ($slotIdx = 0; $slotIdx < 3; $slotIdx++) {
                $st = $slotIdx * self::SLOT_HEIGHT;
                $v  = $page[$slotIdx] ?? null;

                if ($hasBg) {
                    $mpdf->Image($bgPath, 0, $st, 210, self::SLOT_HEIGHT, 'PNG');
                }

                if ($v !== null) {
                    $mpdf->Text(self::X_VOUCHER_NO, $st + self::Y_VOUCHER_NO, $v['voucher_no'] ?? '');
                    $mpdf->Text(self::X_DATE,        $st + self::Y_VOUCHER_NO, date('m/d/Y', strtotime($v['voucher_date'] ?? 'now')));
                    $mpdf->Text(self::X_RECIPIENT,   $st + self::Y_RECIPIENT,  $v['recipient_name'] ?? '');
                    $mpdf->Text(self::X_SCHOOL,      $st + self::Y_SCHOOL,     $v['senior_high_school'] ?? '');
                }
            }
        }

        return $mpdf->Output('', 'S');
    }
}
