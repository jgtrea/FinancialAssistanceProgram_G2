<?php

function pre_style($mode = 'default_lay') {
    $styles = [];
    if ($mode == 'default_lay') {
        $styles = [
            base_url('css/font.css'),
            base_url('css/layout.css'),
            base_url('css/layout.css'),

            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
        ];        
    } 

    foreach ($styles as $url) {
        echo '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
    }
}