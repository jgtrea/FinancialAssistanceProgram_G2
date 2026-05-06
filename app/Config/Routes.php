<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Route to show the upload form
$routes->get('/', 'VoucherImport::index');

// Route to process the uploaded file
$routes->post('import-data', 'VoucherImport::import');

//student routes

$routes->get('/students', 'StudentController::index');
$routes->get('/students/form', 'StudentController::form');
$routes->get('/students/form/(:num)', 'StudentController::form/$1');
$routes->post('/students/save', 'StudentController::save');
$routes->post('/students/delete/(:num)', 'StudentController::delete/$1');

//archiving
$routes->get('/archive', 'ArchiveController::index');


// vouchers
$routes->get('/vouchers', 'VoucherController::index');
$routes->get('/vouchers/create/(:num)', 'VoucherController::create/$1');
$routes->post('/vouchers/store', 'VoucherController::store');

// signatories
$routes->get('/signatories', 'SignatoryController::index');
$routes->get('/signatories/edit/(:num)', 'SignatoryController::edit/$1');
$routes->post('/signatories/save', 'SignatoryController::save');
$routes->post('/signatories/status/(:num)/(:alpha)', 'SignatoryController::setStatus/$1/$2');

// audit logs
$routes->get('/audit-logs', 'AuditLogController::index');
