<?php

namespace App\Controllers;

use App\Models\UserModel;

// ─────────────────────────────────────────────────────────────
// App\Controllers\AuthController
// Maneja login, registro, dashboard y logout.
// ─────────────────────────────────────────────────────────────

class AuthController {

    private UserModel $userModel;

    public function __construct(\PDO $dbConnection) {
        $this->userModel = new UserModel($dbConnection);
    }

    // ── Helpers ──────────────────────────────────────────────

    // Sanitiza inputs HTTP para prevenir XSS
    private function sanitize(string $data): string {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    // Verifica que el usuario tenga sesión activa
    private function enforceSession(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /HomeWorks/Design_Project/login');
            exit;
        }
        // Timeout por inactividad (30 min)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $this->logout(true);
        }
        $_SESSION['last_activity'] = time();
    }

    // Renderiza una vista pasando variables al scope
    private function render(string $view, array $data = []): void {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }

    // ── Acciones ─────────────────────────────────────────────

    // GET / POST: Login
    public function login(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: /HomeWorks/Design_Project/dashboard');
            exit;
        }

        $error = '';
        if (isset($_GET['timeout'])) {
            $error = 'Tu sesión expiró por inactividad.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = $this->sanitize($_POST['email']    ?? '');
            $password =                $_POST['password']  ?? '';

            $user = $this->userModel->getUserByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true); // Prevención de Session Fixation
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['logged_in']     = true;
                $_SESSION['last_activity'] = time();

                header('Location: /HomeWorks/Design_Project/dashboard');
                exit;
            }

            $error = 'Credenciales vacías o no válidas.'; // Genérico (anti-enumeración)
        }

        $this->render('login', ['error' => $error]);
    }

    // GET / POST: Registro
    public function register(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: /HomeWorks/Design_Project/dashboard');
            exit;
        }

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username         = $this->sanitize($_POST['username']         ?? '');
            $email            = $this->sanitize($_POST['email']            ?? '');
            $password         =                $_POST['password']          ?? '';
            $password_confirm =                $_POST['password_confirm']  ?? '';

            if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
                $error = 'Todos los campos son obligatorios.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'El formato del correo electrónico no es válido.';
            } elseif (strlen($password) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== $password_confirm) {
                $error = 'Las contraseñas no coinciden.';
            } elseif ($this->userModel->isEmailTaken($email)) {
                $error = 'Este correo electrónico ya está registrado.';
            } elseif ($this->userModel->isUsernameTaken($username)) {
                $error = 'El nombre de usuario no está disponible.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                if ($this->userModel->createUser($username, $email, $hashedPassword)) {
                    $success = 'Registro completado con éxito. Ya puedes iniciar sesión.';
                    $_POST   = [];
                } else {
                    $error = 'Ocurrió un error en el servidor. Intenta de nuevo.';
                }
            }
        }

        $this->render('register', ['error' => $error, 'success' => $success]);
    }

    // GET: Dashboard
    public function dashboard(): void {
        $this->enforceSession();
        $this->render('dashboard', ['username' => $_SESSION['username']]);
    }

    // GET: Logout
    public function logout(bool $timeout = false): void {
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

        header($timeout ? 'Location: /HomeWorks/Design_Project/login?timeout=1' : 'Location: /HomeWorks/Design_Project/login');
        exit;
    }
}
