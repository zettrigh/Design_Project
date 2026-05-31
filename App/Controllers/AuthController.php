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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $email    = $this->sanitize($_POST['email']    ?? '');
            $password =                $_POST['password']  ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
                exit;
            }

            $user = $this->userModel->getUserByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true); // Prevención de Session Fixation
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['role']          = $user['role']; // Guardamos el rol en sesión
                $_SESSION['logged_in']     = true;
                $_SESSION['last_activity'] = time();

                echo json_encode(['success' => true, 'redirect' => '/HomeWorks/Design_Project/dashboard']);
                exit;
            }

            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas o no válidas.']);
            exit;
        }

        $this->render('login');
    }

    // GET / POST: Registro
    public function register(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: /HomeWorks/Design_Project/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            $username         = $this->sanitize($_POST['username']         ?? '');
            $email            = $this->sanitize($_POST['email']            ?? '');
            $password         =                $_POST['password']          ?? '';
            $password_confirm =                $_POST['password_confirm']  ?? '';

            if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
                exit;
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
                exit;
            } elseif (strlen($password) < 8) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
                exit;
            } elseif ($password !== $password_confirm) {
                echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
                exit;
            } elseif ($this->userModel->isEmailTaken($email)) {
                echo json_encode(['success' => false, 'message' => 'Este correo electrónico ya está registrado.']);
                exit;
            } elseif ($this->userModel->isUsernameTaken($username)) {
                echo json_encode(['success' => false, 'message' => 'El nombre de usuario no está disponible.']);
                exit;
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                if ($this->userModel->createUser($username, $email, $hashedPassword)) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Registro completado con éxito. Ya puedes iniciar sesión.',
                        'redirect' => '/HomeWorks/Design_Project/login'
                    ]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ocurrió un error en el servidor. Intenta de nuevo.']);
                    exit;
                }
            }
        }

        $this->render('register');
    }

}
