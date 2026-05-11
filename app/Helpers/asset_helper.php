<?php

function pre_style($mode = 'default_lay') {
    $styles = [];
    if ($mode == 'default_lay') {
        $styles = [
            base_url('css/font.css'),
            base_url('css/layout.css'),

            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
        ];        
    } elseif ($mode == 'sbadmin2') {
        $styles = [
            base_url('vendor/fontawesome-free/css/all.min.css'),            
            base_url('css/sb-admin-2.min.css'),    

            base_url('vendor/jquery/jquery.min.js'),
            base_url('vendor/bootstrap/js/bootstrap.bundle.min.js'),
            base_url('vendor/jquery-easing/jquery.easing.min.js'),
            base_url('js/sb-admin-2.min.js'),

            'https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i'
        ];
    }

    foreach ($styles as $url) {
        echo '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
    }
}