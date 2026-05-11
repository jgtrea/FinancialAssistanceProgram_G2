<?php


//students                                                                                      
$routes->get('/', 'StudentController::index');
$routes->get('/students', 'StudentController::index');
$routes->get('/students/form', 'StudentController::form');
$routes->get('/students/form/(:num)', 'StudentController::form/$1');
$routes->post('/students/save', 'StudentController::save');
$routes->post('/students/archive/(:num)', 'StudentController::archive/$1');

// Voucher generation from student
$routes->get('/students/voucher/(:num)', 'StudentController::voucher/$1');
$routes->post('/students/mark-generated/(:num)', 'StudentController::markGenerated/$1');

// Signatories
$routes->get('/signatories', 'SignatoryController::index');
$routes->get('/signatories/form', 'SignatoryController::form');
$routes->get('/signatories/form/(:num)', 'SignatoryController::form/$1');
$routes->post('/signatories/save', 'SignatoryController::save');
$routes->post('/signatories/deactivate/(:num)', 'SignatoryController::deactivate/$1');

// Archive
$routes->get('/archive', 'ArchiveController::index');