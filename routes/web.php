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

$router->get('dashboard', 'App\Controllers\AuthController', 'dashboard');
$router->get('logout',    'App\Controllers\AuthController', 'logout');

// =============================================
//  Módulo: Libros (BookController) — EJEMPLO
// =============================================
// 1. Crear App/Controllers/BookController.php con namespace App\Controllers
// 2. Descomentar las rutas:
//
// $router->get('books',         'App\Controllers\BookController', 'index');
// $router->get('books/create',  'App\Controllers\BookController', 'create');
// $router->post('books/store',  'App\Controllers\BookController', 'store');
// $router->get('books/show',    'App\Controllers\BookController', 'show');
// $router->post('books/delete', 'App\Controllers\BookController', 'delete');

// =============================================
//  Módulo: Préstamos (LoanController) — EJEMPLO
// =============================================
// $router->get('loans',         'App\Controllers\LoanController', 'index');
// $router->post('loans/create', 'App\Controllers\LoanController', 'create');
// $router->post('loans/return', 'App\Controllers\LoanController', 'returnBook');
