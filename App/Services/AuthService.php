<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * App\Services\AuthService
 *
 * Capa de servicio dedicada a la lógica de autenticación:
 * login, registro y gestión de sesiones.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja autenticación y sesión.
 *   - DIP: Depende de UserModel (abstracción de acceso a datos).
 *   - DRY: Centraliza validaciones y lógica de sesión reutilizable.
 *
 * @package App\Services
 */
class AuthService
{
    /**
     * Modelo de acceso a datos de usuarios.
     *
     * @var UserModel
     */
    private UserModel $userModel;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param UserModel $userModel Instancia del modelo de usuarios.
     */
    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Autentica un usuario con email y contraseña.
     *
     * Realiza las siguientes validaciones:
     *   1. Campos no vacíos.
     *   2. Formato válido de email.
     *   3. Existencia del usuario en la BD.
     *   4. Verificación del hash de contraseña.
     *
     * Si es exitoso, inicia la sesión con regeneración de ID.
     *
     * @param string $email    Correo electrónico del usuario.
     * @param string $password Contraseña en texto plano.
     * @return array{success: bool, message: string, redirect?: string}
     */
    public function login(string $email, string $password): array
    {
        // Sanitizar email
        $email = $this->sanitize($email);

        // Validaciones básicas
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo electrónico no es válido.'];
        }

        // Buscar usuario
        $user = $this->userModel->getUserByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas o no válidas.'];
        }

        // Iniciar sesión de forma segura
        $this->startSession($user);

        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');

        return [
            'success'  => true,
            'message'  => 'Inicio de sesión exitoso.',
            'redirect' => $baseUrl . '/dashboard'
        ];
    }

    /**
     * Registra un nuevo usuario en el sistema.
     *
     * Valida: campos obligatorios, formato email, longitud de contraseña,
     * coincidencia de contraseñas, y unicidad de email/username.
     *
     * @param string $username         Nombre de usuario.
     * @param string $email            Correo electrónico.
     * @param string $password         Contraseña en texto plano.
     * @param string $passwordConfirm  Confirmación de contraseña.
     * @return array{success: bool, message: string, redirect?: string}
     */
    public function register(
        string $username,
        string $email,
        string $password,
        string $passwordConfirm
    ): array {
        // Sanitizar inputs
        $username = $this->sanitize($username);
        $email    = $this->sanitize($email);

        // Validaciones
        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo electrónico no es válido.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if ($this->userModel->isEmailTaken($email)) {
            return ['success' => false, 'message' => 'Este correo electrónico ya está registrado.'];
        }

        if ($this->userModel->isUsernameTaken($username)) {
            return ['success' => false, 'message' => 'El nombre de usuario no está disponible.'];
        }

        // Crear usuario con rol por defecto (client)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->userModel->createUser($username, $email, $hashedPassword, 'client')) {
            $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');

            return [
                'success'  => true,
                'message'  => 'Registro completado con éxito. Ya puedes iniciar sesión.',
                'redirect' => $baseUrl . '/login'
            ];
        }

        return ['success' => false, 'message' => 'Ocurrió un error en el servidor. Intenta de nuevo.'];
    }

    /**
     * Inicia la sesión de forma segura después de autenticar.
     *
     * Regenera el ID de sesión para prevenir Session Fixation.
     *
     * @param array{id: int, username: string, role: string} $user Datos del usuario.
     * @return void
     */
    private function startSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['logged_in']     = true;
        $_SESSION['last_activity'] = time();
    }

    /**
     * Destruye completamente la sesión del usuario.
     *
     * @return void
     */
    public function logout(): void
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

    /**
     * Sanitiza un string de entrada HTTP para prevenir XSS.
     *
     * @param string $data Datos crudos del usuario.
     * @return string Datos sanitizados.
     */
    private function sanitize(string $data): string
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
