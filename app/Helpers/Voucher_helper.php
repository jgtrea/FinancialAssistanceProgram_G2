<?php

/**
 * Voucher Helper
 * Provides: generate_voucher_no(), amount_to_words(), format_peso()
 *
 * Autoload in app/Config/Autoload.php or load per controller.
 */

// ── Generate unique voucher number ────────────────────────────────────────────

if (!function_exists('generate_voucher_no')) {
    /**
     * Generate a unique voucher number.
     * Format: VOC-{YEAR}-{6-digit padded sequence}
     * Example: VOC-2026-000042
     */
    function generate_voucher_no(): string
    {
        $db   = \Config\Database::connect();
        $year = date('Y');

        // Find the highest sequence for this year
        $last = $db->table('vouchers')
                   ->like('voucher_no', "VOC-{$year}-", 'after')
                   ->selectMax('voucher_no', 'last_no')
                   ->get()->getRow();

        $seq = 1;
        if ($last && $last->last_no) {
            $parts = explode('-', $last->last_no);
            $seq   = (int) end($parts) + 1;
        }

        return sprintf('VOC-%s-%06d', $year, $seq);
    }
}

// ── Amount to words (Philippine Peso) ────────────────────────────────────────

if (!function_exists('amount_to_words')) {
    /**
     * Convert a numeric amount to Philippine Peso words.
     * Example: 10000.00 → "TEN THOUSAND PESOS ONLY"
     */
    function amount_to_words(float $amount): string
    {
        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five',
            'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
            'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
        ];

        $tens = [
            '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty',
            'Sixty', 'Seventy', 'Eighty', 'Ninety',
        ];

        $convert = function (int $n) use ($ones, $tens, &$convert): string {
            if ($n === 0) return '';
            if ($n < 20)  return $ones[$n];
            if ($n < 100) return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
            if ($n < 1000) {
                return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $convert($n % 100) : '');
            }
            if ($n < 1_000_000) {
                return $convert((int)($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . $convert($n % 1000) : '');
            }
            if ($n < 1_000_000_000) {
                return $convert((int)($n / 1_000_000)) . ' Million' . ($n % 1_000_000 ? ' ' . $convert($n % 1_000_000) : '');
            }
            return $convert((int)($n / 1_000_000_000)) . ' Billion' . ($n % 1_000_000_000 ? ' ' . $convert($n % 1_000_000_000) : '');
        };

        $intPart  = (int) floor($amount);
        $decPart  = (int) round(($amount - $intPart) * 100);

        $words = trim($convert($intPart)) . ' Pesos';

        if ($decPart > 0) {
            $words .= ' and ' . trim($convert($decPart)) . ' Centavos';
        }

        return strtoupper($words . ' Only');
    }
}

// ── Format peso ───────────────────────────────────────────────────────────────

if (!function_exists('format_peso')) {
    /**
     * Format a number as Philippine Peso.
     * Example: 10000 → "₱ 10,000.00"
     */
    function format_peso(float $amount): string
    {
        return '₱ ' . number_format($amount, 2);
    }
}

// ── Voucher status label ──────────────────────────────────────────────────────

if (!function_exists('voucher_status_label')) {
    /**
     * Return a human-readable voucher status label.
     */
    function voucher_status_label(string $status): string
    {
        return match ($status) {
            'generated'     => 'Generated',
            'not_generated' => 'Pending',
            default         => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}

// ── Voucher status badge HTML ─────────────────────────────────────────────────

if (!function_exists('voucher_status_badge')) {
    /**
     * Return a status badge HTML span.
     */
    function voucher_status_badge(string $status): string
    {
        $label = voucher_status_label($status);
        return "<span class=\"vs-status-badge vs-status-{$status}\">{$label}</span>";
    }
}