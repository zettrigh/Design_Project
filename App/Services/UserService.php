<?php

namespace App\Services;

use App\Models\UserModel;
use Core\Result;

class UserService
{
    private UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function getUserById(int $id): array|false
    {
        return $this->userModel->findById($id);
    }

    public function getAllWorkers(): array
    {
        return $this->userModel->getAllWorkers();
    }

    public function createWorker(string $username, string $email, string $password): Result
    {
        $username = trim($username);
        $email    = trim($email);

        if (strlen($username) < 3) {
            return Result::failure('El nombre de usuario debe tener al menos 3 caracteres.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Result::failure('El correo electrónico no es válido.');
        }
        if (strlen($password) < 8) {
            return Result::failure('La contraseña debe tener al menos 8 caracteres.');
        }

        if ($this->userModel->usernameExists($username)) {
            return Result::failure('El nombre de usuario ya existe.');
        }
        if ($this->userModel->emailExists($email)) {
            return Result::failure('El correo electrónico ya existe.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->userModel->createUser($username, $email, $hashedPassword, 'worker');

        return Result::success(['user_id' => $userId], 'Trabajador creado exitosamente.');
    }

    public function updateWorker(int $id, string $username, string $email): Result
    {
        $username = trim($username);
        $email    = trim($email);

        $user = $this->userModel->findById($id);
        if (!$user) {
            return Result::failure('Trabajador no encontrado.');
        }
        if (strlen($username) < 3) {
            return Result::failure('El nombre de usuario debe tener al menos 3 caracteres.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Result::failure('El correo electrónico no es válido.');
        }
        if ($username !== $user['username'] && $this->userModel->usernameExists($username)) {
            return Result::failure('El nombre de usuario ya está en uso.');
        }
        if ($email !== $user['email'] && $this->userModel->emailExists($email)) {
            return Result::failure('El correo electrónico ya está en uso.');
        }

        $updated = $this->userModel->updateUser($id, $username, $email);
        return $updated
            ? Result::success(null, 'Trabajador actualizado exitosamente.')
            : Result::failure('Error al actualizar el trabajador.');
    }

    public function deleteWorker(int $id): Result
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return Result::failure('Trabajador no encontrado.');
        }
        if ($user['role'] !== 'worker') {
            return Result::failure('El usuario no es un trabajador.');
        }

        $deleted = $this->userModel->deleteUser($id);
        return $deleted
            ? Result::success(null, 'Trabajador eliminado exitosamente.')
            : Result::failure('Error al eliminar el trabajador.');
    }

    public function resetWorkerPassword(int $id, string $newPassword): Result
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return Result::failure('Trabajador no encontrado.');
        }
        if (strlen($newPassword) < 8) {
            return Result::failure('La contraseña debe tener al menos 8 caracteres.');
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->userModel->updatePassword($id, $hashed);
        return $updated
            ? Result::success(null, 'Contraseña restablecida exitosamente.')
            : Result::failure('Error al restablecer la contraseña.');
    }
}
