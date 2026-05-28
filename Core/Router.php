<?php

namespace Core;

// ─────────────────────────────────────────────────────────────
// Core\Router
// Motor de enrutamiento ligero y escalable.
//
// Responsabilidades:
//   - Registrar rutas GET / POST con su FQCN de controlador.
//   - Resolver la URI entrante y despachar al controlador.
//   - Instanciar controladores inyectando la conexión PDO.
// ─────────────────────────────────────────────────────────────

class Router {

    /** @var array<string, array> Tabla de rutas indexada por método HTTP */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    // ── Registro de rutas ────────────────────────────────────

    /** Registra una ruta GET. $controller debe ser el FQCN completo. */
    public function get(string $uri, string $controller, string $method): void {
        $this->routes['GET'][$uri] = ['controller' => $controller, 'method' => $method];
    }

    /** Registra una ruta POST. $controller debe ser el FQCN completo. */
    public function post(string $uri, string $controller, string $method): void {
        $this->routes['POST'][$uri] = ['controller' => $controller, 'method' => $method];
    }

    // ── Despacho ─────────────────────────────────────────────

    public function dispatch(): void {
        $uri        = $this->getUri();
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        if (isset($this->routes[$httpMethod][$uri])) {
            $route = $this->routes[$httpMethod][$uri];
            $this->callAction($route['controller'], $route['method']);
            return;
        }

        $this->handleNotFound($uri);
    }

    // ── Helpers internos ─────────────────────────────────────

    private function getUri(): string {
        $uri = $_GET['url'] ?? '';
        $uri = trim($uri, '/');
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        return $uri;
    }

    /**
     * Instancia el controlador por su FQCN y ejecuta el método.
     * El autoloader PSR-4 se encarga de localizar y cargar
     * el archivo de la clase automáticamente.
     */
    private function callAction(string $fqcn, string $methodName): void {
        if (!class_exists($fqcn)) {
            die("Error: La clase '{$fqcn}' no existe. Verifica que el namespace y el directorio coincidan (PSR-4).");
        }

        $controller = new $fqcn($this->db);

        if (!method_exists($controller, $methodName)) {
            die("Error: El método '{$methodName}' no existe en '{$fqcn}'.");
        }

        $controller->$methodName();
    }

    private function handleNotFound(string $uri): void {
        if ($uri === '') {
            header('Location: /HomeWorks/Design_Project/login');
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
                <a href="/HomeWorks/Design_Project/login">← Volver al inicio</a>
            </div>
        </body>
        </html>';
        exit;
    }
}
