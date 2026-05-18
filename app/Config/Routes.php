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

// ─── Admin ────────────────────────────────────────────────────────────────────
$routes->group('admin', ['filter' => 'role:admin'], function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // User management (madridbranch)
    $routes->get('user_management',               'UsersController::index');
    $routes->get('user_management/form',          'UsersController::form');
    $routes->get('user_management/form/(:num)',   'UsersController::form/$1');
    $routes->post('user_management/save',         'UsersController::save');
    $routes->post('user_management/archive/(:num)', 'UsersController::archive/$1');
    $routes->get('archived_users',                'UsersController::archived');
    $routes->post('user_management/restore/(:num)', 'UsersController::restore/$1');
    $routes->get('audit-logs',                    'AuditLogController::index');

    // Students
    $routes->get('students',                      'Admin\Voucher::index');
    $routes->get('students/create',               'Admin\Voucher::create');
    $routes->post('students/store',               'Admin\Voucher::store');
    $routes->get('students/view/(:num)',          'Admin\Voucher::view/$1');
    $routes->get('students/edit/(:num)',          'Admin\Voucher::edit/$1');
    $routes->post('students/update/(:num)',       'Admin\Voucher::update/$1');
    $routes->post('students/generate-pdf',        'Admin\Voucher::generatePdf');
    $routes->get('students/pdf-status/(:num)',    'Admin\Voucher::checkPdfJob/$1');
    $routes->get('students/pdf-download/(:num)', 'Admin\Voucher::downloadPdf/$1');
    $routes->post('students/archive',             'Admin\Voucher::archive');

    // Archive & Logs
    $routes->get('archive', 'Admin\Archive::index');
    $routes->get('logs',    'Admin\Report::logs');
});

// ─── User / Staff ─────────────────────────────────────────────────────────────
$routes->group('user', ['filter' => 'auth'], function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'User\Dashboard::index');

    // Students
    $routes->get('students',                      'User\Voucher::index');
    $routes->get('students/create',               'User\Voucher::create');
    $routes->post('students/store',               'User\Voucher::store');
    $routes->get('students/view/(:num)',          'User\Voucher::view/$1');
    $routes->get('students/edit/(:num)',          'User\Voucher::edit/$1');
    $routes->post('students/update/(:num)',       'User\Voucher::update/$1');
    $routes->post('students/generate-pdf',        'User\Voucher::generatePdf');
    $routes->get('students/pdf-status/(:num)',    'User\Voucher::checkPdfJob/$1');
    $routes->get('students/pdf-download/(:num)',  'User\Voucher::downloadPdf/$1');
    $routes->post('students/archive',             'User\Voucher::archive');
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
