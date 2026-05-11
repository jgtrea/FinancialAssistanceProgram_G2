<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ─── Public ───────────────────────────────────────────────────────────────────
$routes->get('/',      'Admin\Dashboard::index');
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

// ─── Admin ────────────────────────────────────────────────────────────────────
$routes->group('admin', function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // Users CRUD
    $routes->get('users',                      'Admin\UserManager::index');
    $routes->get('users/create',               'Admin\UserManager::create');
    $routes->post('users/store',               'Admin\UserManager::store');
    $routes->get('users/edit/(:num)',          'Admin\UserManager::edit/$1');
    $routes->post('users/update/(:num)',       'Admin\UserManager::update/$1');
    $routes->post('users/delete/(:num)',       'Admin\UserManager::delete/$1');
    $routes->post('users/toggle-status/(:num)','Admin\UserManager::toggleStatus/$1');

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

    // Students
    $routes->get('students',                   'Admin\Student::index');
    $routes->get('students/view/(:num)',       'Admin\Student::view/$1');

    // Signatories
    $routes->get('signatories',                'Admin\Signatory::index');
    $routes->get('signatories/create',         'Admin\Signatory::create');
    $routes->post('signatories/store',         'Admin\Signatory::store');
    $routes->get('signatories/edit/(:num)',    'Admin\Signatory::edit/$1');
    $routes->post('signatories/update/(:num)','Admin\Signatory::update/$1');
    $routes->post('signatories/delete/(:num)','Admin\Signatory::delete/$1');

    // Archive
    $routes->get('archive', 'Admin\Archive::index');

    // Logs
    $routes->get('logs', 'Admin\Report::logs');
});

// ─── User ─────────────────────────────────────────────────────────────────────
$routes->group('user', function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'User\Dashboard::index');

    // Vouchers
    $routes->get('vouchers',                   'User\Voucher::index');
    $routes->get('vouchers/view/(:num)',       'User\Voucher::view/$1');
    $routes->post('vouchers/generate-pdf',       'User\Voucher::generatePdf');
    $routes->get('vouchers/pdf-status/(:num)',   'User\Voucher::checkPdfJob/$1');
    $routes->get('vouchers/pdf-download/(:num)', 'User\Voucher::downloadPdf/$1');
    $routes->post('vouchers/archive',            'User\Voucher::archive');

    // Records
    $routes->get('records',           'User\Record::index');
    $routes->post('records/generate', 'User\Record::generate');
});