<?php

define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/bootstrap.php';

use Config\Database;
use Config\Environment;
use Core\Router;

Environment::load(__DIR__ . '/.env');

session_start();

$db     = Database::getInstance()->getConnection();
$router = new Router($db);

require_once __DIR__ . '/routes/web.php';

$router->dispatch();
