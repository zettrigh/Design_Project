<?php
// routes/web.php
// ─────────────────────────────────────────────────────────────
// REGISTRO CENTRALIZADO DE RUTAS
//
// Para añadir un nuevo módulo:
//   1. Crear el controlador en App/Controllers/ con namespace App\Controllers
//   2. Registrar las rutas aquí con el FQCN completo
//      (el autoloader PSR-4 carga la clase automáticamente)
//
// Formato:
//   $router->get('uri',  'App\Controllers\NombreController', 'metodo');
//   $router->post('uri', 'App\Controllers\NombreController', 'metodo');
// ─────────────────────────────────────────────────────────────

// =============================================
//  Módulo: Autenticación
// =============================================
$router->get('login',     'App\Controllers\AuthController', 'login');
$router->post('login',    'App\Controllers\AuthController', 'login');

$router->get('register',  'App\Controllers\AuthController', 'register');
$router->post('register', 'App\Controllers\AuthController', 'register');

$router->get('dashboard', 'App\Controllers\DashboardController', 'index');
$router->get('logout',    'App\Controllers\DashboardController', 'logout');

// =============================================
//  Módulo: Catálogo y Reservas (Usuario)
// =============================================
$router->post('user/reserve', 'App\Controllers\DashboardController', 'reserveHairstyle');

// =============================================
//  Módulo: Gestión Administrativa (CRUD & Reservas)
// =============================================
$router->post('admin/hairstyles/store',  'App\Controllers\DashboardController', 'adminStoreHairstyle');
$router->post('admin/hairstyles/update', 'App\Controllers\DashboardController', 'adminUpdateHairstyle');
$router->post('admin/hairstyles/delete', 'App\Controllers\DashboardController', 'adminDeleteHairstyle');
$router->post('admin/reservations/update', 'App\Controllers\DashboardController', 'adminUpdateReservation');

