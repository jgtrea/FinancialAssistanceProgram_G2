<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Login
$routes->get('/', 'Authentication::index');
$routes->post('auth_login', 'Authentication::authenticate');
$routes->get('logout', 'Authentication::logout');

// Import
$routes->get('import', 'VoucherImport::index');
$routes->post('import_data', 'VoucherImport::import');

// Admin routes
$routes->get('admin/user_management', 'UsersController::index');
$routes->get('admin/user_management/form', 'UsersController::form');
$routes->get('admin/user_management/form/(:num)', 'UsersController::form/$1');
$routes->post('admin/user_management/save', 'UsersController::save');
$routes->post('admin/user_management/delete/(:num)', 'UsersController::delete/$1');
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
$routes->get('/vouchers', 'VoucherController::index');
$routes->get('/vouchers/create/(:num)', 'VoucherController::create/$1');
$routes->post('/vouchers/store', 'VoucherController::store');

// Signatories
$routes->get('/signatories', 'SignatoryController::index');
$routes->get('/signatories/edit/(:num)', 'SignatoryController::edit/$1');
$routes->post('/signatories/save', 'SignatoryController::save');
$routes->post('/signatories/status/(:num)/(:alpha)', 'SignatoryController::setStatus/$1/$2');