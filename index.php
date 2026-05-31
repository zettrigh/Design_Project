<?php
define('ACCESS_ALLOWED', true);
// index.php — FRONT CONTROLLER
// ─────────────────────────────────────────────────────────────
// Punto de entrada único. Utiliza autoloader PSR-4 para la
// carga automática de clases basada en namespaces.
//
// Para añadir un módulo:
//   1. Crear controlador en App/Controllers/ con namespace App\Controllers
//   2. Crear modelo en App/Models/ con namespace App\Models (si aplica)
//   3. Registrar las rutas en routes/web.php
// ─────────────────────────────────────────────────────────────

session_start();

// ── Autoloader PSR-4 ────────────────────────────────────────
require_once __DIR__ . '/autoload.php';

// ── Bootstrap ───────────────────────────────────────────────
use Config\Database;
use Core\Router;

$db     = Database::getInstance()->getConnection();
$router = new Router($db);

require_once __DIR__ . '/routes/web.php';

$router->dispatch();
