<?php

if (!function_exists('asset_versioned_url')) {
    function asset_versioned_url(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return $url;
        }

        $relative = ltrim($path, '/');
        $file = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($file)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'v=' . filemtime($file);
    }
}

function pre_style($mode = 'default_lay')
{
    $styles = [];

    switch ($mode) {
        case 'default_lay':
            $styles = [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
                base_url('assets/css/custom/font.css'),
                base_url('assets/css/shared.css'),
                base_url('assets/css/custom/login.css'),
                base_url('assets/css/custom/app.css'),
            ];
            break;
        case 'admin':
        case 'app':
            $styles = [
                base_url('assets/css/styles.css'),
                'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',
                'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                base_url('assets/css/custom/font.css'),
                base_url('assets/css/shared.css'),
                base_url('assets/css/custom/datatable.css'),
                base_url('assets/css/custom/layout.css'),
                base_url('assets/css/custom/dashboard.css'),
                base_url('assets/css/custom/form.css'),
                base_url('assets/css/custom/login.css'),
                base_url('assets/css/custom/audit-logs.css'),
                base_url('assets/css/custom/voucher-print.css'),
                base_url('assets/css/custom/app.css'),
            ];
            break;
        case 'sbadmin2':
            $styles = [];
            break;
    }

    foreach ($styles as $url) {
        echo '<link rel="stylesheet" href="' . esc(asset_versioned_url($url), 'attr') . '">' . PHP_EOL;
    }
}

function pre_script($mode = 'default_lay')
{
    $scripts = [];

    switch ($mode) {
        case 'default_lay':
            $scripts = [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
                'https://code.jquery.com/jquery-3.7.1.min.js',
            ];
            break;
        case 'admin':
        case 'app':
            $scripts = [
                'https://code.jquery.com/jquery-3.7.1.min.js',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
                'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js',
                'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
                'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                base_url('assets/js/scripts.js'),
                base_url('assets/js/custom/global.js'),
                base_url('assets/js/custom/datatable.js'),
                base_url('assets/js/custom/pdf-tracking.js'),
                base_url('assets/js/custom/layout.js'),
                base_url('assets/js/custom/modal_instance.js'),
                base_url('assets/js/custom/users-page.js'),
                base_url('assets/js/custom/voucher.js'),
                base_url('assets/js/custom/pdf-status.js'),
                base_url('assets/js/custom/users.js'),
                base_url('assets/js/custom/students.js'),
            ];
            break;
    }

    foreach ($scripts as $url) {
        echo '<script src="' . esc(asset_versioned_url($url), 'attr') . '"></script>' . PHP_EOL;
    }
}

if (!function_exists('modal_assets')) {
    function modal_assets(string ...$names): string
    {
        if (empty($names)) return '';
        return '<div data-vs-modals="' . implode(',', array_map('esc', $names)) . '" hidden></div>' . PHP_EOL;
    }
}

if (!function_exists('pre_modal')) {
    function pre_modal(string $mode = ''): string
    {
        switch ($mode) {
            case 'layout':
                return modal_assets('pdfStatusModal', 'accountModal');
            case 'vouchers':
                return modal_assets('archiveModal', 'infoModal', 'bulkAllModal', 'importModal', 'voucherModal', 'filterModal', 'exportVoucher');
            case 'generate':
                return modal_assets('exportVoucher');
            case 'users':
                return modal_assets('userModal');
            case 'archive':
                return modal_assets('archiveCurrentModal', 'archiveFilterModal');
            case 'audit':
                return modal_assets('auditFilterModal');
            case 'schools':
                return modal_assets('schoolArchiveModal', 'schoolExportModal', 'schoolModal', 'schoolImportModal');
            case 'signatories':
                return modal_assets('sigArchiveModal', 'signatoryModal');
            default:
                return '';
        }
    }
}

if (!function_exists('asset_icon')) {
    function asset_icon(string $name, array $attrs = []): string
    {
        $file = FCPATH . 'assets/icons/' . $name . '.svg';

        if (!file_exists($file)) {
            return '';
        }

        $raw = file_get_contents($file);

        // Extract inner content between the opening <svg ...> tag and </svg>
        if (!preg_match('/<svg[^>]*>(.*?)<\/svg>/s', $raw, $m)) {
            return '';
        }

        $inner = $m[1];

        $attrs = array_merge([
            'class'           => '',
            'width'           => '15',
            'height'          => '15',
            'viewBox'         => '0 0 24 24',
            'fill'            => 'none',
            'stroke'          => 'currentColor',
            'stroke-width'    => '2',
            'stroke-linecap'  => 'round',
            'stroke-linejoin' => 'round',
            'aria-hidden'     => 'true',
        ], $attrs);

        $attrHtml = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attrHtml .= ' ' . esc($key, 'attr') . '="' . esc((string) $value, 'attr') . '"';
        }

        return '<svg' . $attrHtml . '>' . $inner . '</svg>';
    }
}
