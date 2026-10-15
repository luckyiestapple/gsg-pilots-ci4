<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Pilots::index');
$routes->get('pilots', 'Pilots::index');
$routes->get('dbtest', 'Pilots::dbtest');