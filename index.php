<?php
define('ACCESS_ALLOWED', true);
/**
 * index.php — FRONT CONTROLLER
 * ─────────────────────────────────────────────────────────────
 * Punto de entrada único. Utiliza autoloader PSR-4 para la
 * carga automática de clases basada en namespaces.
 *
 * Flujo:
 *   1. Cargar variables de entorno (.env)
 *   2. Iniciar sesión
 *   3. Cargar autoloader PSR-4
 *   4. Obtener conexión PDO (via Environment config)
 *   5. Instanciar Router
 *   6. Registrar rutas
 *   7. Despachar petición
 * ─────────────────────────────────────────────────────────────
 */

// ── Cargar Variables de Entorno ─────────────────────────────
require_once __DIR__ . '/autoload.php';

use Config\Database;
use Config\Environment;
use Core\Router;

Environment::load(__DIR__ . '/.env');

session_start();

// ── Bootstrap ───────────────────────────────────────────────
$db     = Database::getInstance()->getConnection();
$router = new Router($db);

require_once __DIR__ . '/routes/web.php';

$router->dispatch();
