<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

/**
 * App/Config/Autoload.php
 *
 * Add your helpers to the $helpers array so they are available
 * globally without needing to call helper() in every controller.
 */
class Autoload extends AutoloadConfig
{
    /**
     * PSR4 namespace mappings.
     * These are already set by CI4 by default.
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
        'Config'      => APPPATH . 'Config',
    ];

    /**
     * Helpers to autoload on every request.
     * Add your custom helper names here (without the _helper.php suffix).
     */
    public $helpers = [
        'asset',
        'audit',    // app/Helpers/audit_helper.php   → log_action()
        'voucher',  // app/Helpers/voucher_helper.php  → generate_voucher_no(), amount_to_words()
        'user',     // app/Helpers/user_helper.php     → current_user(), is_admin()
        'url',      // CI4 built-in: base_url(), site_url()
        'form',     // CI4 built-in: csrf_field(), form_error()
<<<<<<< HEAD
        'html',     // CI4 built-in: script_tag(), link_tag()
    ];
}
