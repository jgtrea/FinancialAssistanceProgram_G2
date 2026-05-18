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
    } elseif ($mode === 'admin') {
        $styles = [
            base_url('template/css/styles.css'),
            'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',
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
            base_url('js/nav.js'),
        ];
    } elseif ($mode === 'admin') {
        $scripts = [
            'https://code.jquery.com/jquery-3.7.1.min.js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
            'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js',
            base_url('template/js/scripts.js'),
            base_url('js/script.js'),
            base_url('js/users_m.js'),
        ];
    }

    foreach ($scripts as $url) {
        echo '<script src="' . esc($url, 'attr') . '"></script>' . PHP_EOL;
    }
}
