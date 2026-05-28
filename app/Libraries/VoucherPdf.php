<?php

namespace App\Libraries;

use App\Models\SignatoryModel;
use Mpdf\Mpdf;

/**
 * VoucherPdf — pure PDF renderer for voucher batches.
 *
 * Given an array of student rows, draws three vouchers per A4 page over a
 * pre-designed background image, then stamps signature blocks at the bottom.
 * Returns the PDF as a binary string — does NOT write to disk and does NOT
 * touch any pdf_jobs / queue state. That orchestration lives in
 * App\Libraries\PdfJobRunner.
 *
 * Layout is done with absolute mPDF coordinates. All measurements are in mm
 * relative to the slot's top-left corner; the X/Y constants below position
 * each field, and SLOT_HEIGHT * slotIdx shifts everything down per slot.
 */
class VoucherPdf
{
    // Y offsets for the dynamic text within one voucher slot.
    public const Y_VOUCHER_NO = 38;
    public const Y_RECIPIENT  = 48;
    public const Y_SCHOOL     = 58;

    // X positions for those same fields. X_DATE pins the date to the right edge.
    public const X_VOUCHER_NO = 40;
    public const X_DATE       = 165;
    public const X_RECIPIENT  = 55;
    public const X_SCHOOL     = 55;

    // Three slots stack on one A4 page (3 * 99 ≈ 297 mm).
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

    /**
     * Render every passed-in student into a single PDF and return its bytes.
     * Students are paginated 3-per-page; the last page may have empty slots
     * (the background image is still painted so trailing slots look right).
     *
     * @param array $students Rows from VoucherModel::getVouchersByIds() with
     *                        voucher_no, voucher_date, full_name and
     *                        preferred_senior_high_school populated.
     * @return string Binary PDF content (caller decides where to save it).
     */
    public static function generate(array $students): string
    {
        // Voucher form artwork. If missing, slots render with a blank
        // background instead of failing — handy for local dev.
        $bgPath = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'voucher_bg.png';
        $hasBg  = file_exists($bgPath);

        // mPDF needs a writable scratch dir for its font cache.
        $tmpDir = WRITEPATH . 'mpdf_tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // Zero margins, every field is placed absolutely so default
        // margins would only get in the way.
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

        // We manage page transitions ourselves below; mPDF must not insert
        // its own page breaks mid-slot.
        $mpdf->SetAutoPageBreak(false);

        $signatories = self::loadSignatories();
        $pages       = array_chunk($students, 3); // 3 vouchers per A4 page

        foreach ($pages as $page) {
            $mpdf->AddPage();
            $mpdf->SetFont('Arial', '', self::FONT_SIZE);
            $mpdf->SetTextColor(17, 17, 17); // near-black

            for ($slotIdx = 0; $slotIdx < 3; $slotIdx++) {
                $st = $slotIdx * self::SLOT_HEIGHT;  // slot's top Y on the page
                $s  = $page[$slotIdx] ?? null;       // null when fewer than 3 students remain

                // Always paint the background, even on empty trailing slots,
                // so the page looks visually consistent.
                if ($hasBg) {
                    $mpdf->Image($bgPath, 0, $st, 210, self::SLOT_HEIGHT, 'PNG');
                }

                // Dynamic text fields — only drawn when there's a student in this slot.
                if ($s !== null) {
                    $mpdf->SetFont('Arial', '', self::FONT_SIZE);
                    $mpdf->Text(self::X_VOUCHER_NO, $st + self::Y_VOUCHER_NO, $s['voucher_no'] ?? '');
                    $mpdf->Text(self::X_DATE,        $st + self::Y_VOUCHER_NO, date('m/d/Y', strtotime($s['voucher_date'] ?? 'now')));
                    $mpdf->Text(self::X_RECIPIENT,   $st + self::Y_RECIPIENT,  self::formatVoucherName($s['full_name'] ?? ''));
                    $mpdf->Text(self::X_SCHOOL,      $st + self::Y_SCHOOL,     $s['preferred_senior_high_school'] ?? '');
                }

                // Signatures are drawn on every slot — including empty ones —
                // so the bottom of the page always shows the officials.
                self::renderSignatures($mpdf, $signatories, $st);
            }
        }

        // 'S' = return PDF as a string instead of streaming it.
        return $mpdf->Output('', 'S');
    }

    /**
     * Pull every active + selected signatory row and resolve each one's
     * signature image to an absolute on-disk path (or '' if the file is
     * missing on disk). Returns them already assigned to fixed columns by
     * title — see assignSignatorySlots().
     */
    protected static function loadSignatories(): array
    {
        $rows = (new SignatoryModel())
            ->where('is_active', 1)
            ->where('is_selected', 1) // admins can toggle which signatories appear
            ->orderBy('signatory_id', 'ASC')
            ->findAll();

        $dir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR;

        foreach ($rows as &$row) {
            $row['_signature_path'] = '';
            if (!empty($row['signature_image'])) {
                // basename() guards against any stray directory components in the DB value.
                $path = $dir . basename($row['signature_image']);
                if (is_file($path)) {
                    $row['_signature_path'] = $path;
                }
            }
        }

        return self::assignSignatorySlots($rows);
    }

    /**
     * Place signatories in fixed columns by position title:
     *   left   = Chairman, Committee on Education
     *   middle = City Mayor
     *   right  = City Vice Mayor
     * Any unrecognized titles fall into the remaining slots in DB order.
     */
    protected static function assignSignatorySlots(array $rows): array
    {
        $slots = [null, null, null]; // [left, middle, right]
        $leftovers = [];

        foreach ($rows as $row) {
            $title = strtolower((string) ($row['position_title'] ?? ''));

            // Note: the "vice mayor" check MUST come before plain "mayor",
            // otherwise "Vice Mayor" would match the latter first.
            if (strpos($title, 'chairman') !== false) {
                if ($slots[0] === null) { $slots[0] = $row; continue; }
            } elseif (strpos($title, 'vice') !== false && strpos($title, 'mayor') !== false) {
                if ($slots[2] === null) { $slots[2] = $row; continue; }
            } elseif (strpos($title, 'mayor') !== false) {
                if ($slots[1] === null) { $slots[1] = $row; continue; }
            }

            $leftovers[] = $row;
        }

        // Fill any remaining empty slots from leftovers in DB order.
        foreach ($slots as $idx => $slot) {
            if ($slot === null && !empty($leftovers)) {
                $slots[$idx] = array_shift($leftovers);
            }
        }

        return $slots;
    }

    /**
     * Stamp the three signature columns (image + name + title) into one slot.
     * Coordinates are relative to $slotTop so the same code works for every
     * slot on every page.
     */
    protected static function renderSignatures(Mpdf $mpdf, array $signatories, float $slotTop): void
    {
        if (empty($signatories)) {
            return;
        }

        // Three evenly-spaced columns across the 210mm page width.
        $columnCenters = [35.0, 105.0, 175.0];

        foreach ($signatories as $idx => $sig) {
            if (!isset($columnCenters[$idx]) || $sig === null) {
                continue;
            }

            $cx = $columnCenters[$idx];

            if (!empty($sig['_signature_path'])) {
                // Centre the image horizontally on the column.
                $imgX = $cx - (self::SIG_IMG_WIDTH / 2);
                $imgY = $slotTop + self::SIG_Y_IMG;
                $ext  = strtoupper(pathinfo($sig['_signature_path'], PATHINFO_EXTENSION));
                // mPDF expects 'JPEG' not 'JPG' for the image-type hint.
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

    /**
     * Place a string centred on $cx by measuring its rendered width.
     * mPDF's Text() positions by the top-left corner, hence the manual offset.
     */
    protected static function drawCenteredText(Mpdf $mpdf, string $text, float $cx, float $y): void
    {
        if ($text === '') {
            return;
        }
        $width = $mpdf->GetStringWidth($text);
        $mpdf->Text($cx - ($width / 2), $y, $text);
    }

    /**
     * Build a signatory's display name: PREFIX FIRST M. LAST SUFFIX (uppercased).
     * Middle name becomes a single-letter initial; any blank parts are dropped.
     */
    protected static function formatSignatoryName(array $sig): string
    {
        $middle = trim((string) ($sig['middle_name'] ?? ''));
        $middleInitial = $middle !== '' ? strtoupper(substr($middle, 0, 1)) . '.' : '';

        $parts = array_filter([
            $sig['prefix'] ?? '',
            $sig['first_name'] ?? '',
            $middleInitial,
            $sig['last_name'] ?? '',
            $sig['suffix'] ?? '',
        ], static fn ($v) => trim((string) $v) !== '');

        $name   = strtoupper(implode(' ', $parts));
        $degree = strtoupper(trim((string) ($sig['degree'] ?? '')));
        if ($degree !== '' && $degree !== 'NONE') {
            $name .= ', ' . $degree;
        }

        return $name;
    }

    /**
     * Normalize a student's full name for the recipient line: collapse runs of
     * whitespace, title-case (Unicode-aware where possible), then re-fix the
     * roman-numeral suffixes that title-casing would have broken ("Iii" → "III").
     */
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
