<?php

namespace Core;

use Config\Environment;

/**
 * Core\Router
 *
 * Motor de enrutamiento ligero y escalable con soporte para middlewares.
 *
 * Responsabilidades:
 *   - Registrar rutas GET / POST con su FQCN de controlador y middlewares.
 *   - Resolver la URI entrante y despachar al controlador.
 *   - Ejecutar la cadena de middlewares antes de llegar al controlador.
 *   - Manejar URL base dinámica desde variables de entorno.
 *
 * Patrón de diseño: Chain of Responsibility (middlewares).
 *
 * @package Core
 */
class Router
{
    /**
     * Tabla de rutas indexada por método HTTP.
     * Cada ruta contiene: controller, method, middlewares.
     *
     * @var array<string, array<string, array{controller: string, method: string, middlewares: array<string>}>>
     */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * Conexión PDO compartida para inyectar en middlewares y controladores.
     *
     * @var \PDO
     */
    private \PDO $db;

    /**
     * URL base del proyecto (leída de .env o con valor por defecto).
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $db Conexión PDO activa.
     */
    public function __construct(\PDO $db)
    {
        $this->db      = $db;
        $this->baseUrl = Environment::get('BASE_URL', '/HomeWorks/Design_Project');
    }

    // ── Registro de rutas ────────────────────────────────────

    /**
     * Registra una ruta GET con middlewares opcionales.
     *
     * @param string   $uri         URI relativa (sin base URL).
     * @param string   $controller  FQCN completo del controlador.
     * @param string   $method      Nombre del método a invocar.
     * @param string[] $middlewares Nombres de clases middleware a ejecutar antes.
     * @return void
     */
    public function get(string $uri, string $controller, string $method, array $middlewares = []): void
    {
        $this->routes['GET'][$uri] = [
            'controller'  => $controller,
            'method'      => $method,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Registra una ruta POST con middlewares opcionales.
     *
     * @param string   $uri         URI relativa (sin base URL).
     * @param string   $controller  FQCN completo del controlador.
     * @param string   $method      Nombre del método a invocar.
     * @param string[] $middlewares Nombres de clases middleware a ejecutar antes.
     * @return void
     */
    public function post(string $uri, string $controller, string $method, array $middlewares = []): void
    {
        $this->routes['POST'][$uri] = [
            'controller'  => $controller,
            'method'      => $method,
            'middlewares' => $middlewares,
        ];
    }

    // ── Despacho ─────────────────────────────────────────────

    /**
     * Resuelve la URI entrante y ejecuta la cadena de middlewares + controlador.
     *
     * @return void
     */
    public function dispatch(): void
    {
        $uri        = $this->getUri();
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        if (isset($this->routes[$httpMethod][$uri])) {
            $route = $this->routes[$httpMethod][$uri];

            // Construir la cadena de middlewares
            $pipeline = $this->buildPipeline(
                $route['middlewares'],
                function () use ($route) {
                    $this->callAction($route['controller'], $route['method']);
                }
            );

            // Ejecutar la cadena
            $pipeline();
            return;
        }

        $this->handleNotFound($uri);
    }

    // ── Pipeline de Middlewares ──────────────────────────────

    /**
     * Construye una cadena de middlewares encapsulados (patrón cebolla).
     *
     * Cada middleware envuelve al siguiente, formando una cadena
     * que se ejecuta desde el exterior hacia el controlador final.
     *
     * @param string[] $middlewares Lista de FQCNs de middlewares.
     * @param callable $final       Callback del controlador (final de la cadena).
     * @return callable Pipeline listo para ejecutar.
     */
    private function buildPipeline(array $middlewares, callable $final): callable
    {
        // El pipeline se construye desde el final hacia el inicio
        $pipeline = $final;

        foreach (array_reverse($middlewares) as $middlewareEntry) {
            $db = $this->db;
            $pipeline = function () use ($middlewareEntry, $pipeline, $db) {
                // Convención:
                //   Sin parámetros  → necesita PDO  (ej: AuthMiddleware)
                //   Con parámetros  → necesita args  (ej: RoleMiddleware::class.':admin')
                $parts = explode(':', $middlewareEntry);
                $className = $parts[0];
                $params = array_slice($parts, 1);

                /** @var \Core\MiddlewareInterface $middleware */
                if (!class_exists($className)) {
                    return;
                }

                $middleware = count($params) > 0
                    ? new $className(...$params)   // RoleMiddleware: 'admin', 'worker', etc.
                    : new $className($db);         // AuthMiddleware: necesita PDO

                $middleware->handle($pipeline);
            };
        }

        return $pipeline;
    }

    // ── Helpers internos ─────────────────────────────────────

    /**
     * Extrae y limpia la URI de la petición actual.
     *
     * @return string URI limpia sin barra inicial/final.
     */
    private function getUri(): string
    {
        $uri = $_GET['url'] ?? '';
        $uri = trim($uri, '/');
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        return $uri;
    }

    /**
     * Instancia el controlador por su FQCN y ejecuta el método.
     *
     * @param string $fqcn      FQCN del controlador.
     * @param string $methodName Nombre del método a ejecutar.
     * @return void
     */
    private function callAction(string $fqcn, string $methodName): void
    {
        if (!class_exists($fqcn)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
            exit;
        }

        $controller = new $fqcn($this->db);

        if (!method_exists($controller, $methodName)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
            exit;
        }

        $controller->$methodName();
    }

    /**
     * Maneja rutas no encontradas (404 o redirección a login).
     *
     * @param string $uri URI que no coincidió con ninguna ruta registrada.
     * @return void
     */
    private function handleNotFound(string $uri): void
    {
        if ($uri === '') {
            header('Location: ' . $this->baseUrl . '/login');
            exit;
        }

        http_response_code(404);
        echo '<!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"><title>404 - No encontrado</title>
        <style>
            body { font-family: "Segoe UI", sans-serif; background: #0f0f0f; color: #e0e0e0;
                   display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
            .box { text-align:center; }
            h1   { font-size:5rem; margin:0; color:#7c3aed; }
            p    { font-size:1.2rem; opacity:.7; }
            a    { color:#a78bfa; text-decoration:none; }
            a:hover { text-decoration:underline; }
        </style>
        </head>
        <body>
            <div class="box">
                <h1>404</h1>
                <p>La página <strong>/' . htmlspecialchars($uri) . '</strong> no existe.</p>
                <a href="' . $this->baseUrl . '/login">← Volver al inicio</a>
            </div>
        </body>
        </html>';
        exit;
    }

    /**
     * Retorna la URL base configurada.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
