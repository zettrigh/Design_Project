<?php
// index.php
// ENRUTADOR PRINCIPAL (Front Controller)
// 
// Todas las peticiones del sistema ingresan por este archivo.
// Aquí se inicializa la sesión, se levanta la conexión compartida 
// y se delega la acción al AuthController.

session_start();

// Cargar Componentes MVC
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Inicialización de la base de datos (inyección de dependencias)
$db = Database::getInstance()->getConnection();
$authController = new AuthController($db);

// Simple enrutador por parámetro get 'action'
// Si usamos friendly URLs vía .htaccess, esto atrapa /login, /dashboard, etc.
$action = $_GET['action'] ?? 'login';

// Despachador (Dispatcher)
switch ($action) {
    case 'login':
        $authController->login();
        break;
    
    case 'register':
        $authController->register();
        break;
    
    case 'dashboard':
        $authController->dashboard();
        break;
    
    case 'logout':
        $authController->logout();
        break;
    
    default:
        // Por defecto volver al login si la acción no existe
        $authController->login();
        break;
}
