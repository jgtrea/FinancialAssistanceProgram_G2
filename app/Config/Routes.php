<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Login
$routes->get('/', 'Authentication::index');
$routes->post('auth_login', 'Authentication::authenticate');
$routes->get('logout', 'Authentication::logout');

$routes->get('generate-hash', function() {
    echo password_hash('test', PASSWORD_ARGON2ID);
});

// Import
$routes->get('import', 'VoucherImport::index');
$routes->post('import_data', 'VoucherImport::import');

// Admin routes
$routes->get('admin/user_management', 'UsersController::index');
$routes->get('admin/user_management/form', 'UsersController::form');
$routes->get('admin/user_management/form/(:num)', 'UsersController::form/$1');
$routes->post('admin/user_management/save', 'UsersController::save');
$routes->post('admin/user_management/archive/(:num)', 'UsersController::archive/$1');
$routes->get('admin/archived_users', 'UsersController::archived');
$routes->post('admin/user_management/restore/(:num)', 'UsersController::restore/$1');
$routes->get('admin/audit-logs', 'AuditLogController::index');

// Student routes
$routes->get('/students', 'StudentController::index');
$routes->get('/students/form', 'StudentController::form');
$routes->get('/students/form/(:num)', 'StudentController::form/$1');
$routes->post('/students/save', 'StudentController::save');
$routes->post('/students/delete/(:num)', 'StudentController::delete/$1');

// Archiving
$routes->get('/archive', 'ArchiveController::index');

// Vouchers
    $routes->get('vouchers',                   'Admin\Voucher::index');
    $routes->get('vouchers/create',            'Admin\Voucher::create');
    $routes->post('vouchers/store',            'Admin\Voucher::store');
    $routes->get('vouchers/view/(:num)',       'Admin\Voucher::view/$1');
    $routes->get('vouchers/edit/(:num)',       'Admin\Voucher::edit/$1');
    $routes->post('vouchers/update/(:num)',    'Admin\Voucher::update/$1');
    $routes->post('vouchers/generate-pdf',       'Admin\Voucher::generatePdf');
    $routes->get('vouchers/pdf-status/(:num)',   'Admin\Voucher::checkPdfJob/$1');
    $routes->get('vouchers/pdf-download/(:num)', 'Admin\Voucher::downloadPdf/$1');
    $routes->post('vouchers/archive',            'Admin\Voucher::archive');

    $routes->get('vouchers',                   'User\Voucher::index');
    $routes->get('vouchers/view/(:num)',       'User\Voucher::view/$1');
    $routes->post('vouchers/generate-pdf',       'User\Voucher::generatePdf');
    $routes->get('vouchers/pdf-status/(:num)',   'User\Voucher::checkPdfJob/$1');
    $routes->get('vouchers/pdf-download/(:num)', 'User\Voucher::downloadPdf/$1');
    $routes->post('vouchers/archive',            'User\Voucher::archive');

// Signatories
$routes->get('/signatories', 'SignatoryController::index');
$routes->get('/signatories/edit/(:num)', 'SignatoryController::edit/$1');
$routes->post('/signatories/save', 'SignatoryController::save');
$routes->post('/signatories/status/(:num)/(:alpha)', 'SignatoryController::setStatus/$1/$2');