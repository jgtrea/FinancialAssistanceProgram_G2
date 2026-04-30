<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Route to show the upload form
$routes->get('/', 'VoucherImport::index');

// Route to process the uploaded file
$routes->post('import-data', 'VoucherImport::import');