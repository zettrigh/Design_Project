<?php

namespace Core\Middleware;

use Core\MiddlewareInterface;

/**
 * Core\Middleware\AuthMiddleware
 *
 * Verifica que el usuario tenga una sesión activa y válida.
 * Si no existe sesión o el usuario ya no está en la BD,
 * redirige al login o retorna un JSON de error (para AJAX).
 *
 * Patrón de diseño: Chain of Responsibility.
 *
 * @package Core\Middleware
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var \PDO Conexión a la base de datos para validar usuario.
     */
    private \PDO $db;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $db Conexión PDO activa.
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Maneja la verificación de sesión antes de permitir acceso.
     *
     * @param callable $next Siguiente middleware o controlador.
     * @return void
     */
    public function handle(callable $next): void
    {
        // Verificar sesión básica
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            $this->rejectRequest('Sesión no iniciada o inválida.');
            return;
        }

        // Validar que el usuario exista en la BD y sincronizar datos
        $userId = intval($_SESSION['user_id']);
        $stmt = $this->db->prepare("SELECT id, role, username FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            $this->destroySession();
            $this->rejectRequest('El usuario no existe en el sistema.');
            return;
        }

        // Sincronizar datos de sesión desde la BD
        $_SESSION['role']     = $user['role'];
        $_SESSION['username'] = $user['username'];

        // Verificar timeout de inactividad (30 minutos)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $this->destroySession();
            $this->rejectRequest('Tu sesión ha expirado por inactividad.');
            return;
        }

        $_SESSION['last_activity'] = time();

        // Continuar al siguiente middleware/controlador
        $next();
    }

    /**
     * Envía una respuesta de rechazo (JSON o redirect según el tipo de petición).
     *
     * @param string $message Mensaje de error para el usuario.
     * @return void
     */
    private function rejectRequest(string $message): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

        if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => false,
                'message'  => $message,
                'redirect' => Environment::get('BASE_URL', '/HomeWorks/Design_Project') . '/login'
            ]);
            exit;
        }

        header('Location: ' . Environment::get('BASE_URL', '/HomeWorks/Design_Project') . '/login');
        exit;
    }

    /**
     * Destruye completamente la sesión del usuario.
     *
     * @return void
     */
    private function destroySession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();
        }
    }
}
