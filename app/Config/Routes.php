    <?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ==============================
// ROUTE WEB UTAMA
// ==============================

// Halaman utama
$routes->get('/', 'Pilots::index');

// Halaman pilots yang sudah ada
$routes->get('pilots', 'Pilots::index');

// Test koneksi database
$routes->get('dbtest', 'Pilots::dbtest');


// ==============================
// API PILOTS
// ==============================

$routes->resource('api/pilots', [
    'controller' => 'Api\Pilots',
    'only'       => ['index', 'show', 'create', 'update', 'delete'],
    'placeholder' => '[0-9]+',
]);


// ==============================
// API COUNTRIES
// ==============================

$routes->resource('api/countries', [
    'controller' => 'Api\Countries',
    'only'       => ['index', 'show', 'create', 'update', 'delete'],
    'placeholder' => '[0-9]+',
]);


// ==============================
// API RANKS
// ==============================

$routes->resource('api/ranks', [
    'controller' => 'Api\Ranks',
    'only'       => ['index', 'show', 'create', 'update', 'delete'],
    'placeholder' => '[0-9]+',
]);