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
//  Módulo: Cliente (Reservas + Pagos + Agenda)
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
$router->post('client/available-slots',      'App\\Controllers\\ClientController', 'getAvailableSlots', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':client',
]);

// =============================================
//  Módulo: Trabajador (CRUD Peinados + Reservas + Agenda)
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

// Agenda del trabajador
$router->post('worker/schedule/get',             'App\\Controllers\\WorkerController', 'getMySchedule', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':worker',
]);
$router->post('worker/schedule/update',          'App\\Controllers\\WorkerController', 'updateMySchedule', [
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

// Horarios de atención del negocio
$router->get('admin/business-hours',             'App\\Controllers\\AdminController', 'getBusinessHours', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/business-hours/update',     'App\\Controllers\\AdminController', 'updateBusinessHours', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/schedule/overview',         'App\\Controllers\\AdminController', 'getScheduleOverview', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/workers/schedule',          'App\\Controllers\\AdminController', 'getWorkerSchedule', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);

// Pagos
$router->post('admin/payments/list',             'App\\Controllers\\AdminController', 'listPayments', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);

// Tipo de cambio
$router->get('admin/exchange-rate',              'App\\Controllers\\AdminController', 'getExchangeRate', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
$router->post('admin/exchange-rate/set',         'App\\Controllers\\AdminController', 'setExchangeRate', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
]);
