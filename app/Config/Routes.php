<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ─── Public ───────────────────────────────────────────────────────────────────
$routes->get('/',         'Authentication::index');
$routes->post('auth_login', 'Authentication::authenticate');
$routes->get('logout',    'Authentication::logout');

$routes->get('generate-hash', function () {
    echo password_hash('test', PASSWORD_ARGON2ID);
});

// Import
$routes->get('import',      'VoucherImport::index');
$routes->post('import_data', 'VoucherImport::import');

// Admin routes
$routes->group('admin', function ($routes) {
    $routes->get('/', 'UsersController::index');
    $routes->get('dashboard', 'Admin\Dashboard::index');
    $routes->get('user_management', 'UsersController::index');
    $routes->get('user_management/form', 'UsersController::form');
    $routes->get('user_management/form/(:num)', 'UsersController::form/$1');
    $routes->post('user_management/save', 'UsersController::save');
    $routes->post('user_management/archive/(:num)', 'UsersController::archive/$1');
    $routes->get('archived_users', 'UsersController::archived');
    $routes->post('user_management/restore/(:num)', 'UsersController::restore/$1');
    $routes->get('audit-logs', 'AuditLogController::index');
});
$routes->get('audit-logs', 'AuditLogController::index');
$routes->get('admin/dashboard', 'Admin\Dashboard::index');
// Student routes
$routes->get('/students', 'StudentController::index');
$routes->get('/students/form', 'StudentController::form');
$routes->get('/students/form/(:num)', 'StudentController::form/$1');
$routes->post('/students/save', 'StudentController::save');
$routes->post('/students/archive/(:num)', 'StudentController::archive/$1');
$routes->post('/students/mark-generated/(:num)', 'StudentController::markGenerated/$1');

    // User management (madridbranch)
    $routes->get('user_management',               'UsersController::index');
    $routes->get('user_management/form',          'UsersController::form');
    $routes->get('user_management/form/(:num)',   'UsersController::form/$1');
    $routes->post('user_management/save',         'UsersController::save');
    $routes->post('user_management/archive/(:num)', 'UsersController::archive/$1');
    $routes->get('archived_users',                'UsersController::archived');
    $routes->post('user_management/restore/(:num)', 'UsersController::restore/$1');
    $routes->get('audit-logs',                    'AuditLogController::index');

    // Vouchers (admin)
    $routes->get('admin/vouchers',                      'Admin\Voucher::index');
    $routes->get('admin/vouchers/create',               'Admin\Voucher::create');
    $routes->post('admin/vouchers/store',               'Admin\Voucher::store');
    $routes->get('admin/vouchers/view/(:num)',          'Admin\Voucher::view/$1');
    $routes->get('admin/vouchers/edit/(:num)',          'Admin\Voucher::edit/$1');
    $routes->post('admin/vouchers/update/(:num)',       'Admin\Voucher::update/$1');
    $routes->post('admin/vouchers/generate-pdf',        'Admin\Voucher::generatePdf');
    $routes->get('admin/vouchers/pdf-status/(:num)',    'Admin\Voucher::checkPdfJob/$1');
    $routes->get('admin/vouchers/pdf-download/(:num)', 'Admin\Voucher::downloadPdf/$1');
    $routes->post('admin/vouchers/archive',             'Admin\Voucher::archive');

// Signatories
$routes->get('/signatories', 'SignatoryController::index');
$routes->get('/signatories/form', 'SignatoryController::form');
$routes->get('/signatories/form/(:num)', 'SignatoryController::form/$1');
$routes->post('/signatories/save', 'SignatoryController::save');
$routes->post('/signatories/deactivate/(:num)', 'SignatoryController::deactivate/$1');
    // Archive & Logs
    $routes->get('archive', 'ArchiveController::index');
    $routes->get('logs',    'Admin\Report::logs');


// ─── User / Staff ─────────────────────────────────────────────────────────────
$routes->group('user', function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'User\Dashboard::index');

    // Students / Vouchers
    $routes->get('vouchers',                     'User\Voucher::index');
    $routes->get('vouchers/create',              'User\Voucher::create');
    $routes->post('vouchers/store',              'User\Voucher::store');
    $routes->get('vouchers/view/(:num)',         'User\Voucher::view/$1');
    $routes->get('vouchers/edit/(:num)',         'User\Voucher::edit/$1');
    $routes->post('vouchers/update/(:num)',      'User\Voucher::update/$1');
    $routes->post('vouchers/generate-pdf',       'User\Voucher::generatePdf');
    $routes->post('/admin/vouchers/generate-pdf', 'VoucherController::generatePdf');
    $routes->get('vouchers/pdf-status/(:num)',   'User\Voucher::checkPdfJob/$1');
    $routes->get('vouchers/pdf-download/(:num)', 'User\Voucher::downloadPdf/$1');
    $routes->post('vouchers/archive',            'User\Voucher::archive');
});

// ─── Students (madridbranch) ──────────────────────────────────────────────────
$routes->get('students',              'StudentController::index');
$routes->get('students/form',        'StudentController::form');
$routes->get('students/form/(:num)', 'StudentController::form/$1');
$routes->post('students/save',       'StudentController::save');
$routes->post('students/delete/(:num)', 'StudentController::delete/$1');

// ─── Archive (madridbranch) ───────────────────────────────────────────────────
$routes->get('archive', 'ArchiveController::index');

// ─── Signatories (madridbranch) ───────────────────────────────────────────────
$routes->get('signatories',                          'SignatoryController::index');
$routes->get('signatories/edit/(:num)',              'SignatoryController::edit/$1');
$routes->post('signatories/save',                    'SignatoryController::save');
$routes->post('signatories/status/(:num)/(:alpha)', 'SignatoryController::setStatus/$1/$2');
