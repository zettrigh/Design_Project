<?php

namespace App\Controllers;

use App\Services\AuthService;

/**
 * App\Controllers\AuthController
 *
 * Controlador "thin" dedicado exclusivamente a las rutas de autenticación:
 * login y registro. Toda la lógica de negocio está delegada a AuthService.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja petición HTTP de autenticación.
 *   - DIP: Depende de AuthService (inyectado vía constructor).
 *   - Thin Controller: Solo valida inputs básicos y delega al servicio.
 *
 * @package App\Controllers
 */
class AuthController
{
    /**
     * @var AuthService Servicio de autenticación.
     */
    private AuthService $authService;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $dbConnection Conexión PDO (necesaria para AuthService).
     */
    public function __construct(\PDO $dbConnection)
    {
        $userModel = new \App\Models\UserModel($dbConnection);
        $this->authService = new AuthService($userModel);
    }

    /**
     * Muestra el formulario de login o procesa las credenciales (GET/POST).
     *
     * @return void
     */
    public function login(): void
    {
        // Si ya tiene sesión, redirigir al dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');
            header('Location: ' . $baseUrl . '/dashboard');
            exit;
        }

        // Procesar formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $email    = $_POST['email']    ?? '';
            $password = $_POST['password'] ?? '';

            $result = $this->authService->login($email, $password);
            echo json_encode($result);
            exit;
        }

        // Mostrar formulario (GET)
        $this->render('login');
    }

    /**
     * Muestra el formulario de registro o procesa los datos (GET/POST).
     *
     * @return void
     */
    public function register(): void
    {
        // Si ya tiene sesión, redirigir al dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');
            header('Location: ' . $baseUrl . '/dashboard');
            exit;
        }

        // Procesar formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $username         = $_POST['username']         ?? '';
            $email            = $_POST['email']            ?? '';
            $password         = $_POST['password']         ?? '';
            $passwordConfirm  = $_POST['password_confirm'] ?? '';

            $result = $this->authService->register($username, $email, $password, $passwordConfirm);
            echo json_encode($result);
            exit;
        }

        // Mostrar formulario (GET)
        $this->render('register');
    }

    /**
     * Renderiza una vista PHP pasándole variables al scope.
     *
     * @param string $view Nombre de la vista (sin extensión).
     * @param array  $data Variables disponibles en la vista.
     * @return void
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }
}
