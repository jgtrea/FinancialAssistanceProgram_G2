<?php

namespace App\Libraries;

use Mpdf\Mpdf;

class VoucherPdf
{
    public const Y_VOUCHER_NO = 38;
    public const Y_RECIPIENT  = 48;
    public const Y_SCHOOL     = 58;

    public const X_VOUCHER_NO = 40;
    public const X_DATE       = 165;
    public const X_RECIPIENT  = 55;
    public const X_SCHOOL     = 55;

    public const SLOT_HEIGHT  = 99;
    public const FONT_SIZE    = 12;

    public static function generate(array $students): string
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

        $pages = array_chunk($students, 3);

        foreach ($pages as $page) {
            $mpdf->AddPage();
            $mpdf->SetFont('Arial', '', self::FONT_SIZE);
            $mpdf->SetTextColor(17, 17, 17);

            for ($slotIdx = 0; $slotIdx < 3; $slotIdx++) {
                $st = $slotIdx * self::SLOT_HEIGHT;
                $s  = $page[$slotIdx] ?? null;

                if ($hasBg) {
                    $mpdf->Image($bgPath, 0, $st, 210, self::SLOT_HEIGHT, 'PNG');
                }

                if ($s !== null) {
                    $mpdf->Text(self::X_VOUCHER_NO, $st + self::Y_VOUCHER_NO, $s['voucher_no'] ?? '');
                    $mpdf->Text(self::X_DATE,        $st + self::Y_VOUCHER_NO, date('m/d/Y', strtotime($s['voucher_date'] ?? 'now')));
                    $mpdf->Text(self::X_RECIPIENT,   $st + self::Y_RECIPIENT,  $s['full_name'] ?? '');
                    $mpdf->Text(self::X_SCHOOL,      $st + self::Y_SCHOOL,     $s['preferred_senior_high_school'] ?? '');
                }
            }
        }

        return $mpdf->Output('', 'S');
    }
}
