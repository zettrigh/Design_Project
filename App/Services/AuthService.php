<?php

namespace App\Services;

use App\Models\UserModel;
use Core\Result;

class AuthService
{
    private UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function register(string $username, string $email, string $password, string $confirmPassword): Result
    {
        $username = trim($username);
        $email    = trim($email);

        if (strlen($username) < 3 || strlen($username) > 50) {
            return Result::failure('El nombre de usuario debe tener entre 3 y 50 caracteres.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Result::failure('El correo electrónico no es válido.');
        }
        if (strlen($password) < 6) {
            return Result::failure('La contraseña debe tener al menos 6 caracteres.');
        }
        if ($password !== $confirmPassword) {
            return Result::failure('Las contraseñas no coinciden.');
        }

        if ($this->userModel->usernameExists($username)) {
            return Result::failure('El nombre de usuario ya está registrado.');
        }
        if ($this->userModel->emailExists($email)) {
            return Result::failure('El correo electrónico ya está registrado.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->userModel->createUser($username, $email, $hashedPassword, 'client');

        if ($userId === false) {
            return Result::failure('Error al crear el usuario.');
        }

        return Result::success(['user_id' => $userId]);
    }

    public function login(string $email, string $password): Result
    {
        $email = trim($email);

        if (empty($email) || empty($password)) {
            return Result::failure('Todos los campos son obligatorios.');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return Result::failure('Credenciales inválidas.');
        }
        if (!password_verify($password, $user['password'])) {
            return Result::failure('Credenciales inválidas.');
        }

        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['last_activity'] = time();

        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');

        return Result::success([
            'user_id'  => (int) $user['id'],
            'role'     => $user['role'],
            'username' => $user['username'],
            'redirect' => $baseUrl . '/dashboard',
        ]);
    }
}
