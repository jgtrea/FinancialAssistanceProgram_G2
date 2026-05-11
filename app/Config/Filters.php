<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use App\Filters\AuthFilter;
use App\Filters\RoleFilter;

class Filters extends BaseConfig
{
    /**
     * Register your filters here with a short alias.
     */
    public array $aliases = [
        'csrf'      => \CodeIgniter\Filters\CSRF::class,
        'toolbar'   => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'  => \CodeIgniter\Filters\Honeypot::class,
        'auth'      => AuthFilter::class,   // <-- added
        'role'      => RoleFilter::class,   // <-- added
    ];

    /**
     * Filters that run on every request.
     * 'before' runs before the controller, 'after' runs after.
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            'csrf' => ['except' => ['api/*']],
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public array $methods = [];

    /**
     * Route-specific filters are handled in Routes.php using filter groups.
     * See Routes.php for usage of 'auth' and 'role:admin'.
     */
    public array $filters = [];
}