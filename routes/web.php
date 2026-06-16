<?php
/**
 * routes/web.php
 * ─────────────────────────────────────────────────────────────
 * REGISTRO CENTRALIZADO DE RUTAS CON MIDDLEWARES
 *
 * Cada ruta puede tener un array de middlewares que se ejecutan
 * en orden antes de llegar al controlador. Los middlewares
 * verifican sesión (AuthMiddleware) y permisos de rol (RoleMiddleware).
 *
 * Para añadir un nuevo módulo:
 *   1. Crear el controlador en App/Controllers/
 *   2. Registrar las rutas aquí con middlewares apropiados
 * ─────────────────────────────────────────────────────────────
 */

use Core\Middleware\AuthMiddleware;
use Core\Middleware\RoleMiddleware;

// =============================================
//  Módulo: Autenticación (Público)
// =============================================
$router->get('login',     'App\\Controllers\\AuthController', 'login');
$router->post('login',    'App\\Controllers\\AuthController', 'login');

$router->get('register',  'App\\Controllers\\AuthController', 'register');
$router->post('register', 'App\\Controllers\\AuthController', 'register');

// =============================================
//  Módulo: Dashboard y Sesión (Autenticado)
// =============================================
$router->get('dashboard', 'App\\Controllers\\DashboardController', 'index', [
    AuthMiddleware::class,
]);
$router->get('logout',    'App\\Controllers\\DashboardController', 'logout');

// =============================================
//  Módulo: Cliente (Reservas + Pagos)
// =============================================
$router->post('client/reserve',              'App\\Controllers\\ClientController', 'reserveHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':client',
]);
$router->post('client/process-payment',      'App\\Controllers\\ClientController', 'processPayment', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':client',
]);
$router->post('client/payments',             'App\\Controllers\\ClientController', 'getPayments', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':client',
]);

// =============================================
//  Módulo: Trabajador (CRUD Peinados + Reservas)
// =============================================
$router->post('worker/hairstyles/store',         'App\\Controllers\\WorkerController', 'storeHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':worker',
]);
$router->post('worker/hairstyles/update',        'App\\Controllers\\WorkerController', 'updateHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':worker',
]);
$router->post('worker/hairstyles/delete',        'App\\Controllers\\WorkerController', 'deleteHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':worker',
]);
$router->post('worker/reservations/update',      'App\\Controllers\\WorkerController', 'updateReservation', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':worker',
]);

// =============================================
//  Módulo: Administrador (CRUD Trabajadores + Hereda Worker)
// =============================================
// Gestión de trabajadores (exclusivo admin)
$router->post('admin/workers/list',              'App\\Controllers\\AdminController', 'listWorkers', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/workers/store',             'App\\Controllers\\AdminController', 'storeWorker', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/workers/update',            'App\\Controllers\\AdminController', 'updateWorker', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/workers/delete',            'App\\Controllers\\AdminController', 'deleteWorker', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/workers/reset-password',    'App\\Controllers\\AdminController', 'resetWorkerPassword', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);

// CRUD Peinados (admin hereda worker)
$router->post('admin/hairstyles/store',          'App\\Controllers\\AdminController', 'storeHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/hairstyles/update',         'App\\Controllers\\AdminController', 'updateHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/hairstyles/delete',         'App\\Controllers\\AdminController', 'deleteHairstyle', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);

// Reservas (admin hereda worker)
$router->post('admin/reservations/update',       'App\\Controllers\\AdminController', 'updateReservation', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);

// Pagos
$router->post('admin/payments/list',             'App\\Controllers\\AdminController', 'listPayments', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
