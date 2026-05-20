<?php

namespace App\Libraries;

use App\Models\SignatoryModel;
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

    // Signature block (per signatory column)
    public const SIG_IMG_WIDTH   = 38;
    public const SIG_IMG_HEIGHT  = 14;
    public const SIG_Y_IMG       = 72;
    public const SIG_Y_NAME      = 88;
    public const SIG_Y_TITLE     = 92;
    public const SIG_NAME_FONT   = 8;
    public const SIG_TITLE_FONT  = 7;

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

        $signatories = self::loadSignatories();
        $pages       = array_chunk($students, 3);

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
                    $mpdf->SetFont('Arial', '', self::FONT_SIZE);
                    $mpdf->Text(self::X_VOUCHER_NO, $st + self::Y_VOUCHER_NO, $s['voucher_no'] ?? '');
                    $mpdf->Text(self::X_DATE,        $st + self::Y_VOUCHER_NO, date('m/d/Y', strtotime($s['voucher_date'] ?? 'now')));
                    $mpdf->Text(self::X_RECIPIENT,   $st + self::Y_RECIPIENT,  self::formatVoucherName($s['full_name'] ?? ''));
                    $mpdf->Text(self::X_SCHOOL,      $st + self::Y_SCHOOL,     $s['preferred_senior_high_school'] ?? '');
                }

                self::renderSignatures($mpdf, $signatories, $st);
            }
        }

        return $mpdf->Output('', 'S');
    }

    protected static function loadSignatories(): array
    {
        $rows = (new SignatoryModel())
            ->where('is_active', 1)
            ->orderBy('signatory_id', 'ASC')
            ->findAll();

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR;

        foreach ($rows as &$row) {
            $row['_signature_path'] = '';
            if (!empty($row['signature_image'])) {
                $path = $dir . basename($row['signature_image']);
                if (is_file($path)) {
                    $row['_signature_path'] = $path;
                }
            }
        }

        return array_slice($rows, 0, 3);
    }

    protected static function renderSignatures(Mpdf $mpdf, array $signatories, float $slotTop): void
    {
        if (empty($signatories)) {
            return;
        }

        // Three evenly-spaced columns across the 210mm page width.
        $columnCenters = [35.0, 105.0, 175.0];

        foreach ($signatories as $idx => $sig) {
            if (!isset($columnCenters[$idx])) {
                break;
            }

            $cx = $columnCenters[$idx];

            if (!empty($sig['_signature_path'])) {
                $imgX = $cx - (self::SIG_IMG_WIDTH / 2);
                $imgY = $slotTop + self::SIG_Y_IMG;
                $ext  = strtoupper(pathinfo($sig['_signature_path'], PATHINFO_EXTENSION));
                if ($ext === 'JPG') {
                    $ext = 'JPEG';
                }

                $mpdf->Image(
                    $sig['_signature_path'],
                    $imgX,
                    $imgY,
                    self::SIG_IMG_WIDTH,
                    self::SIG_IMG_HEIGHT,
                    $ext ?: ''
                );
            }

            $fullName = self::formatSignatoryName($sig);
            $title    = (string) ($sig['position_title'] ?? '');

            $mpdf->SetFont('Arial', 'B', self::SIG_NAME_FONT);
            self::drawCenteredText($mpdf, $fullName, $cx, $slotTop + self::SIG_Y_NAME);

            $mpdf->SetFont('Arial', '', self::SIG_TITLE_FONT);
            self::drawCenteredText($mpdf, $title, $cx, $slotTop + self::SIG_Y_TITLE);
        }
    }

    protected static function drawCenteredText(Mpdf $mpdf, string $text, float $cx, float $y): void
    {
        if ($text === '') {
            return;
        }
        $width = $mpdf->GetStringWidth($text);
        $mpdf->Text($cx - ($width / 2), $y, $text);
    }

    protected static function formatSignatoryName(array $sig): string
    {
        $middle = trim((string) ($sig['middle_name'] ?? ''));
        $middleInitial = $middle !== '' ? strtoupper(substr($middle, 0, 1)) . '.' : '';

        $parts = array_filter([
            'HON.',
            $sig['first_name'] ?? '',
            $middleInitial,
            $sig['last_name'] ?? '',
            $sig['suffix'] ?? '',
        ], static fn ($v) => trim((string) $v) !== '');

        return strtoupper(implode(' ', $parts));
    }

    protected static function formatVoucherName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return '';
        }

        $name = function_exists('mb_convert_case')
            ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8')
            : ucwords(strtolower($name));

        return str_replace([' Jr.', ' Sr.', ' Ii', ' Iii', ' Iv'], [' Jr.', ' Sr.', ' II', ' III', ' IV'], $name);
    }
}
