<?php

function pre_style($mode = 'default_lay')
{
    $styles = [];

    if ($mode === 'default_lay') {
        $styles = [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            base_url('css/font.css'),
            base_url('css/style.css'),
            base_url('css/layout.css'),
            base_url('css/app.css'),
        ];
    } elseif ($mode === 'admin' || $mode === 'app') {
        $styles = [
            base_url('template/css/styles.css'),
            'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',
            'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css',
            base_url('css/font.css'),
            base_url('css/style.css'),
            base_url('css/layout.css'),
            base_url('css/app.css'),
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
            base_url('template/js/scripts.js'),
            base_url('js/script.js'),
            base_url('js/users_m.js'),
            base_url('js/students.js'),
        ];
    }

    foreach ($scripts as $url) {
        echo '<script src="' . esc($url, 'attr') . '"></script>' . PHP_EOL;
    }
}

if (!function_exists('asset_icon')) {
    function asset_icon(string $name, array $attrs = []): string
    {
        $icons = [
            'add'       => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
            'archive'   => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
            'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'download'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'import'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
            'logs'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/>',
            'sign'      => '<path d="M16 3l5 5L8 21H3v-5L16 3z"/><path d="M14 5l5 5"/>',
            'students'  => '<path d="M17 18a5 5 0 0 0-10 0"/><circle cx="12" cy="8" r="4"/><path d="M4 22h16"/>',
            'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'voucher'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
            'voucher-add' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>',
        ];

        if (!isset($icons[$name])) {
            return '';
        }

        $attrs = array_merge([
            'class' => '',
            'width' => '15',
            'height' => '15',
            'viewBox' => '0 0 24 24',
            'fill' => 'none',
            'stroke' => 'currentColor',
            'stroke-width' => '2',
            'stroke-linecap' => 'round',
            'stroke-linejoin' => 'round',
            'aria-hidden' => 'true',
        ], $attrs);

        $attrHtml = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attrHtml .= ' ' . esc($key, 'attr') . '="' . esc((string) $value, 'attr') . '"';
        }

        return '<svg' . $attrHtml . '>' . $icons[$name] . '</svg>';
    }
}
