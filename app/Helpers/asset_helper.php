<?php

function pre_style($mode = 'default_lay') {
    $styles = [];
    if ($mode == 'default_lay') {
        $styles = [
            base_url('css/sb-admin-2.min.css'),
            base_url('css/font.css'),
            base_url('css/layout.css'),
            base_url('css/app.css'),
        ];        
    } elseif ($mode == 'sbadmin2') {
        $styles = [
            base_url('css/sb-admin-2.min.css'),
            base_url('css/font.css'),
            base_url('css/layout.css'),
            base_url('css/app.css'),
            'https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i'
        ];
    }

    foreach ($styles as $url) {
        echo '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
    }
}
