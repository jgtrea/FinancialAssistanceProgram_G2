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

// Import / Export
$routes->post('import_data',     'VoucherImport::import', ['filter' => 'auth']);
$routes->get('vouchers/export',  'VoucherImport::export', ['filter' => 'auth']);

// Current user's account
$routes->get('profile',      'ProfileController::edit',    ['filter' => 'auth']);
$routes->get('profile/data', 'ProfileController::apiGet',  ['filter' => 'auth']);
$routes->post('profile',     'ProfileController::update',  ['filter' => 'auth']);

// ─── Admin ────────────────────────────────────────────────────────────────────
$routes->group('admin', ['filter' => 'role:admin'], function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // User management (madridbranch)
    $routes->get('user_management',               'UsersController::index');
    $routes->get('user_management/form',          'UsersController::form');
    $routes->get('user_management/form/(:num)',   'UsersController::form/$1');
    $routes->get('user_management/json/(:num)',   'UsersController::getJson/$1');
    $routes->post('user_management/save',         'UsersController::save');
    $routes->post('user_management/toggle/(:num)',      'UsersController::toggleStatus/$1');
    $routes->post('user_management/activate-multiple',  'UsersController::activateMultiple');
    $routes->post('user_management/deactivate-multiple','UsersController::deactivateMultiple');
    $routes->get('audit-logs',                    'AuditLogController::index');

    // Students
    $routes->get('students',                      'Admin\Voucher::index');
    $routes->get('students/datatable',            'Admin\Voucher::studentsDatatable');
    $routes->get('students/matching-ids',         'Admin\Voucher::studentsMatchingIds');
    $routes->get('students/create',               'Admin\Voucher::create');
    $routes->get('students/view/(:num)',          'Admin\Voucher::view/$1');
    $routes->get('students/edit/(:num)',          'Admin\Voucher::edit/$1');

    // Vouchers
    $routes->get('vouchers',                      'Admin\Voucher::generate');
    $routes->get('vouchers/create',               'Admin\Voucher::create');
    $routes->post('vouchers/store',               'Admin\Voucher::store');
    $routes->get('vouchers/view/(:num)',          'Admin\Voucher::view/$1');
    $routes->get('vouchers/edit/(:num)',          'Admin\Voucher::edit/$1');
    $routes->post('vouchers/update/(:num)',       'Admin\Voucher::update/$1');
    // DISABLED — old DB-backed pdf_jobs flow. Replaced by JSON-file queue below.
    // $routes->post('vouchers/generate-pdf',        'Admin\Voucher::generatePdf');
    // $routes->get('vouchers/pdf-status/(:num)',    'Admin\Voucher::checkPdfJob/$1');
    // $routes->get('vouchers/pdf-download/(:num)',  'Admin\Voucher::downloadPdf/$1');
    // JSON-file queue parallel flow
    $routes->post('vouchers/json-generate-pdf',       'Admin\Voucher::generateJsonPdf');
    $routes->get('vouchers/json-pdf-status/(:num)',   'Admin\Voucher::jsonPdfStatus/$1');
    $routes->get('vouchers/json-pdf-download/(:num)', 'Admin\Voucher::jsonPdfDownload/$1');
    $routes->post('vouchers/archive',             'Admin\Voucher::archive');
    $routes->post('vouchers/archive-all',         'Admin\Voucher::archiveAll');
    $routes->post('vouchers/archive-by-filter',   'Admin\Voucher::archiveByFilter');
    $routes->get('vouchers/count-matching',       'Admin\Voucher::countMatching');
    $routes->get('vouchers/archive-preview',      'Admin\Voucher::archivePreview');
    $routes->post('vouchers/activate-multiple',          'Admin\Voucher::activateMultiple');
    $routes->post('vouchers/deactivate-multiple',        'Admin\Voucher::deactivateMultiple');
    $routes->post('vouchers/activate-all',               'Admin\Voucher::activateAll');
    $routes->post('vouchers/deactivate-all',             'Admin\Voucher::deactivateAll');
    $routes->post('vouchers/toggle-active/(:num)',       'Admin\Voucher::toggleActive/$1');
    $routes->post('vouchers/toggle-eligibility/(:num)', 'Admin\Voucher::toggleEligibility/$1');
    // TEMP — remove after Archive All testing is done.
    $routes->post('vouchers/restore-all-archive', 'Admin\Voucher::restoreAllFromArchive');

    // Schools
    $routes->get('schools',                       'Admin\School::index');
    $routes->get('schools/json/(:num)',           'Admin\School::getJson/$1');
    $routes->post('schools/save',                 'Admin\School::save');
    $routes->post('schools/archive-multiple',     'Admin\School::archiveMultiple');
    $routes->post('schools/restore-multiple',     'Admin\School::restoreMultiple');
    $routes->get('schools/export',                'Admin\School::export');
    $routes->post('schools/import',               'Admin\School::importSchools');
    $routes->get('schools/import-template',       'Admin\School::importTemplate');
    $routes->get('schools/options',               'Admin\School::optionsJson');

    // Archive & Logs
    $routes->get('archive', 'ArchiveController::index');
    $routes->get('logs',    'Admin\Report::logs');
});

// ─── User / Staff ─────────────────────────────────────────────────────────────
$routes->group('user', ['filter' => 'auth'], function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'User\Dashboard::index');

    // Audit Logs (own only)
    $routes->get('audit-logs', 'AuditLogController::index');

    // Students
    $routes->get('students',                      'User\Voucher::index');
    $routes->get('students/datatable',            'User\Voucher::studentsDatatable');
    $routes->get('students/matching-ids',         'User\Voucher::studentsMatchingIds');
    $routes->get('students/create',               'User\Voucher::create');
    $routes->get('students/view/(:num)',          'User\Voucher::view/$1');
    $routes->get('students/edit/(:num)',          'User\Voucher::edit/$1');

    // Vouchers
    $routes->get('vouchers',                      'User\Voucher::generate');
    $routes->get('vouchers/create',               'User\Voucher::create');
    $routes->post('vouchers/store',               'User\Voucher::store');
    $routes->get('vouchers/view/(:num)',          'User\Voucher::view/$1');
    $routes->get('vouchers/edit/(:num)',          'User\Voucher::edit/$1');
    $routes->post('vouchers/update/(:num)',       'User\Voucher::update/$1');
    // DISABLED — old DB-backed pdf_jobs flow. Replaced by JSON-file queue below.
    // $routes->post('vouchers/generate-pdf',        'User\Voucher::generatePdf');
    // $routes->get('vouchers/pdf-status/(:num)',    'User\Voucher::checkPdfJob/$1');
    // $routes->get('vouchers/pdf-download/(:num)',  'User\Voucher::downloadPdf/$1');
    // JSON-file queue parallel flow
    $routes->post('vouchers/json-generate-pdf',       'User\Voucher::generateJsonPdf');
    $routes->get('vouchers/json-pdf-status/(:num)',   'User\Voucher::jsonPdfStatus/$1');
    $routes->get('vouchers/json-pdf-download/(:num)', 'User\Voucher::jsonPdfDownload/$1');
    $routes->post('vouchers/archive',             'User\Voucher::archive');
    $routes->post('vouchers/archive-all',         'User\Voucher::archiveAll');
    $routes->post('vouchers/archive-by-filter',   'User\Voucher::archiveByFilter');
    $routes->get('vouchers/count-matching',       'User\Voucher::countMatching');
    $routes->get('vouchers/archive-preview',      'User\Voucher::archivePreview');
    $routes->post('vouchers/activate-multiple',          'User\Voucher::activateMultiple');
    $routes->post('vouchers/deactivate-multiple',        'User\Voucher::deactivateMultiple');
    $routes->post('vouchers/activate-all',               'User\Voucher::activateAll');
    $routes->post('vouchers/deactivate-all',             'User\Voucher::deactivateAll');
    $routes->post('vouchers/toggle-active/(:num)',       'User\Voucher::toggleActive/$1');
    $routes->post('vouchers/toggle-eligibility/(:num)', 'User\Voucher::toggleEligibility/$1');
    // TEMP — remove after Archive All testing is done.
    $routes->post('vouchers/restore-all-archive', 'User\Voucher::restoreAllFromArchive');

    // Schools (read + write for user role)
    $routes->get('schools',                       'Admin\School::index');
    $routes->get('schools/json/(:num)',           'Admin\School::getJson/$1');
    $routes->post('schools/save',                 'Admin\School::save');
    $routes->post('schools/archive-multiple',     'Admin\School::archiveMultiple');
    $routes->post('schools/restore-multiple',     'Admin\School::restoreMultiple');
    $routes->get('schools/export',                'Admin\School::export');
    $routes->post('schools/import',               'Admin\School::importSchools');
    $routes->get('schools/import-template',       'Admin\School::importTemplate');
    $routes->get('schools/options',               'Admin\School::optionsJson');
});

// ─── Students (madridbranch) ──────────────────────────────────────────────────
$routes->get('students',                'StudentController::index');
$routes->get('students/form',           'StudentController::form');
$routes->get('students/form/(:num)',    'StudentController::form/$1');
$routes->get('students/json/(:num)',    'StudentController::getJson/$1');
$routes->post('students/save',          'StudentController::save');
$routes->post('students/archive/(:num)', 'StudentController::archive/$1');

// ─── Archive (madridbranch) ───────────────────────────────────────────────────
$routes->get('archive', 'ArchiveController::index');

// ─── Signatories (madridbranch) ───────────────────────────────────────────────
$routes->get('signatories',                           'SignatoryController::index');
$routes->get('signatories/form',                      'SignatoryController::form');
$routes->get('signatories/form/(:num)',               'SignatoryController::form/$1');
$routes->get('signatories/edit/(:num)',               'SignatoryController::form/$1');
$routes->get('signatories/json/(:num)',               'SignatoryController::getJson/$1');
$routes->get('signatories/signature/(:num)',          'SignatoryController::signature/$1');
$routes->post('signatories/save',                     'SignatoryController::save');
$routes->post('signatories/deactivate/(:num)',        'SignatoryController::deactivate/$1');
$routes->post('signatories/restore/(:num)',           'SignatoryController::restore/$1');
$routes->post('signatories/status/(:num)/(:alpha)',  'SignatoryController::setStatus/$1/$2');
$routes->post('signatories/archive-multiple',         'SignatoryController::archiveMultiple');
