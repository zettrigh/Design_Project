<?php

require_once __DIR__ . '/Core/Result.php';
require_once __DIR__ . '/Core/MiddlewareInterface.php';
require_once __DIR__ . '/Core/Router.php';
require_once __DIR__ . '/Core/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Core/Middleware/RoleMiddleware.php';
require_once __DIR__ . '/Core/Middleware/SecurityHeadersMiddleware.php';

require_once __DIR__ . '/Config/Environment.php';
require_once __DIR__ . '/Config/Database.php';

require_once __DIR__ . '/App/Models/UserModel.php';
require_once __DIR__ . '/App/Models/HairstyleModel.php';
require_once __DIR__ . '/App/Models/ReservationModel.php';
require_once __DIR__ . '/App/Models/PaymentModel.php';
require_once __DIR__ . '/App/Models/BusinessHoursModel.php';
require_once __DIR__ . '/App/Models/WorkerScheduleModel.php';
require_once __DIR__ . '/App/Models/ExchangeRateModel.php';

require_once __DIR__ . '/App/Services/AuthService.php';
require_once __DIR__ . '/App/Services/HairstyleService.php';
require_once __DIR__ . '/App/Services/ReservationService.php';
require_once __DIR__ . '/App/Services/PaymentService.php';
require_once __DIR__ . '/App/Services/UserService.php';
require_once __DIR__ . '/App/Services/ScheduleService.php';
require_once __DIR__ . '/App/Services/ExchangeRateService.php';

require_once __DIR__ . '/App/Controllers/DashboardController.php';
require_once __DIR__ . '/App/Controllers/AuthController.php';
require_once __DIR__ . '/App/Controllers/ClientController.php';
require_once __DIR__ . '/App/Controllers/WorkerController.php';
require_once __DIR__ . '/App/Controllers/AdminController.php';

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool
    {
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('h')) {
    function h(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('validate_url')) {
    function validate_url(string $url, string $scheme = 'https'): string
    {
        $allowedSchemes = ['http', 'https'];
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), $allowedSchemes, true)) {
            return '';
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
    }
}
