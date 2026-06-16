<?php

namespace Core\Middleware;

use Config\Environment;
use Core\MiddlewareInterface;

/**
 * Core\Middleware\RoleMiddleware
 *
 * Verifica que el usuario autenticado tenga uno de los roles permitidos.
 * Implementa la lógica de herencia de roles:
 *   - admin hereda permisos de worker
 *   - worker tiene permisos operativos (CRUD catálogo + reservas)
 *   - client tiene permisos de visualización y reserva
 *
 * Patrón de diseño: Chain of Responsibility.
 *
 * @package Core\Middleware
 */
class RoleMiddleware implements MiddlewareInterface
{
    /**
     * Lista de roles permitidos para la ruta actual.
     *
     * @var array<string>
     */
    private array $allowedRoles;

    /**
     * Mapa de herencia de roles. Un rol superior hereda los permisos de los inferiores.
     *
     * @var array<string, array<string>>
     */
    private const ROLE_HIERARCHY = [
        'admin'  => ['admin', 'worker', 'client'],
        'worker' => ['worker', 'client'],
        'client' => ['client'],
    ];

    /**
     * Constructor que recibe los roles permitidos para la ruta.
     *
     * @param string ...$roles Roles que pueden acceder (ej: 'admin', 'worker', 'client').
     */
    public function __construct(string ...$roles)
    {
        $this->allowedRoles = $roles;
    }

    /**
     * Verifica si el rol del usuario está dentro de los roles permitidos,
     * considerando la jerarquía de herencia.
     *
     * @param callable $next Siguiente middleware o controlador.
     * @return void
     */
    public function handle(callable $next): void
    {
        $userRole = $_SESSION['role'] ?? '';

        if (empty($userRole)) {
            $this->denyAccess('No se pudo determinar tu rol de usuario.');
            return;
        }

        // Verificar si el rol del usuario (o alguno de sus roles heredados) está permitido
        $userInheritedRoles = self::ROLE_HIERARCHY[$userRole] ?? [$userRole];
        $hasAccess = !empty(array_intersect($this->allowedRoles, $userInheritedRoles));

        if (!$hasAccess) {
            $this->denyAccess('No tienes permisos para acceder a esta sección.');
            return;
        }

        $next();
    }

    /**
     * Deniega el acceso y retorna al dashboard o retorna JSON de error.
     *
     * @param string $message Mensaje de error.
     * @return void
     */
    private function denyAccess(string $message): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $baseUrl = Environment::get('BASE_URL', '/HomeWorks/Design_Project');

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => false,
                'message'  => $message,
                'redirect' => $baseUrl . '/dashboard'
            ]);
            exit;
        }

        header('Location: ' . $baseUrl . '/dashboard');
        exit;
    }
}
