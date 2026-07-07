<?php

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/bootstrap.php';

use Config\Database;
use Config\Environment;
use Core\Middleware\SecurityHeadersMiddleware;

Environment::load(__DIR__ . '/.env');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

SecurityHeadersMiddleware::send();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uri = trim($_GET['url'] ?? '', '/');
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(419);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF token inválido o expirado. Recarga la página e intenta de nuevo.']);
        exit;
    }
}

$db     = Database::getInstance()->getConnection();
$router = new Router($db);

require_once __DIR__ . '/routes/web.php';

$router->dispatch();
