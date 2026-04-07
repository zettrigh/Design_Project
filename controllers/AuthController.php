<?php
// controllers/AuthController.php
// Capa CONTROLADOR: Procesa las peticiones HTTP, sanitiza los datos, interactúa con el Modelo
// y determina qué Vista se debe cargar.

require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $userModel;

    public function __construct($dbConnection) {
        $this->userModel = new UserModel($dbConnection);
    }

    // Sanitiza inputs HTTP para prevenir Cross-Site Scripting (XSS)
    private function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // Verifica que el usuario haya iniciado sesión
    private function enforceSession() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header("Location: index.php?action=login");
            exit;
        }

        // Timeout (Cierre por inactividad a los 30 min)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $this->logout(true); // Redirige a login por timeout
        }
        
        $_SESSION['last_activity'] = time();
    }

    // Acción: GET/POST Login
    public function login() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header("Location: index.php?action=dashboard");
            exit;
        }

        $error = '';
        if (isset($_GET['timeout'])) {
            $error = "Tu sesión expiró por inactividad.";
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $this->sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->getUserByEmail($email);

            // Verificamos si existe y si el hash coincide (Time-safe comparison)
            if ($user && password_verify($password, $user['password'])) {
                // Prevención de Session Fixation
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();

                header("Location: index.php?action=dashboard");
                exit;
            } else {
                // Error genérico anti-enumeración
                $error = 'Credenciales vacias o no validas.';
            }
        }

        // Cargar la vista pasándole las variables necesarias
        require_once __DIR__ . '/../views/login.php';
    }

    // Acción: GET/POST Registro
    public function register() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header("Location: index.php?action=dashboard");
            exit;
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $this->sanitize($_POST['username'] ?? '');
            $email = $this->sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            // 1. Validaciones para cada campo
            if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
                $error = 'Todos los campos son obligatorios.';
            }elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'El formato del correo electrónico no es válido.';
            } elseif (strlen($password) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== $password_confirm) {
                $error = 'Las contraseñas no coinciden.';
            } elseif ($this->userModel->isEmailTaken($email)) {
                $error = 'Este correo electrónico ya está registrado.';
            } elseif ($this->userModel->isUsernameTaken($username)) {
                $error = 'El nombre de usuario no está disponible.';
            } 
            // 2. Ejecución
            else {
                // Hash robusto usando PASSWORD_DEFAULT
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                if ($this->userModel->createUser($username, $email, $hashedPassword)) {
                    $success = 'Registro completado con éxito. Ya puedes iniciar sesión.';
                    $_POST = []; // Limpiar vista
                } else {
                    $error = 'Ocurrió un error en el servidor. Intenta de nuevo.';
                }
            }
        }

        require_once __DIR__ . '/../views/register.php';
    }

    // Acción: GET Dashboard
    public function dashboard() {
        // Enforce access control
        $this->enforceSession();

        $username = $_SESSION['username'];
        
        require_once __DIR__ . '/../views/dashboard.php';
    }

    // Acción: GET Logout
    public function logout($timeout = false) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
        }

        if ($timeout) {
            header("Location: index.php?action=login&timeout=1");
        } else {
            header("Location: index.php?action=login");
        }
        exit;
    }
}
