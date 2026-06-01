<?php

function pre_style($mode = 'default_lay')
{
    $styles = [];

    if ($mode === 'default_lay') {
        $styles = [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            base_url('assets/css/custom/font.css'),
            base_url('assets/css/shared.css'),
            base_url('assets/css/custom/login.css'),
            base_url('assets/css/custom/app.css'),
        ];
    } elseif ($mode === 'admin' || $mode === 'app') {
        $styles = [
            base_url('assets/css/styles.css'),
            'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',
            'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css',
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
    } elseif ($mode === 'sbadmin2') {
        $styles = [];
    }

    foreach ($styles as $url) {
        echo '<link rel="stylesheet" href="' . esc($url, 'attr') . '">' . PHP_EOL;
    }
}

function pre_script($mode = 'default_lay')
{
    $scripts = [];

    if ($mode === 'default_lay') {
        $scripts = [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            'https://code.jquery.com/jquery-3.7.1.min.js',
        ];
    } elseif ($mode === 'admin' || $mode === 'app') {
        $scripts = [
            'https://code.jquery.com/jquery-3.7.1.min.js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js',
            'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
            'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',
            base_url('assets/js/scripts.js'),
            base_url('assets/js/custom/global.js'),
            base_url('assets/js/custom/datatable.js'),
            base_url('assets/js/custom/pdf-tracking.js'),
            base_url('assets/js/custom/layout.js'),
            base_url('assets/js/custom/users-page.js'),
            base_url('assets/js/custom/voucher.js'),
            base_url('assets/js/custom/pdf-status.js'),
            base_url('assets/js/custom/users.js'),
            base_url('assets/js/custom/students.js'),
        ];
    }

    foreach ($scripts as $url) {
        echo '<script src="' . esc($url, 'attr') . '"></script>' . PHP_EOL;
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
