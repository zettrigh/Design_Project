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

    public function getAllUsers(): array
    {
        return $this->userModel->getAllUsers();
    }

    public function getUserById(int $id): array|false
    {
        return $this->userModel->findById($id);
    }

    public function getAllWorkers(): array
    {
        return $this->userModel->getAllWorkers();
    }

    public function countByRole(string $role): int
    {
        return $this->userModel->countByRole($role);
    }

    public function createWorker(string $username, string $email, string $password): Result
    {
        if (strlen(trim($username)) < 3) {
            return Result::failure('El nombre de usuario debe tener al menos 3 caracteres.');
        }
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return Result::failure('El correo electrónico no es válido.');
        }
        if (strlen($password) < 6) {
            return Result::failure('La contraseña debe tener al menos 6 caracteres.');
        }

        if ($this->userModel->usernameExists(trim($username))) {
            return Result::failure('El nombre de usuario ya existe.');
        }
        if ($this->userModel->emailExists(trim($email))) {
            return Result::failure('El correo electrónico ya existe.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->userModel->createUser(trim($username), trim($email), $hashedPassword, 'worker');

        return Result::success(['user_id' => $userId], 'Trabajador creado exitosamente.');
    }

    public function updateWorker(int $id, string $username, string $email): Result
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            return Result::failure('Trabajador no encontrado.');
        }
        if (strlen(trim($username)) < 3) {
            return Result::failure('El nombre de usuario debe tener al menos 3 caracteres.');
        }
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return Result::failure('El correo electrónico no es válido.');
        }
        if (trim($username) !== $user['username'] && $this->userModel->usernameExists(trim($username))) {
            return Result::failure('El nombre de usuario ya está en uso.');
        }
        if (trim($email) !== $user['email'] && $this->userModel->emailExists(trim($email))) {
            return Result::failure('El correo electrónico ya está en uso.');
        }

        $updated = $this->userModel->updateUser($id, trim($username), trim($email));
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
        if (strlen($newPassword) < 6) {
            return Result::failure('La contraseña debe tener al menos 6 caracteres.');
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->userModel->updatePassword($id, $hashed);
        return $updated
            ? Result::success(null, 'Contraseña restablecida exitosamente.')
            : Result::failure('Error al restablecer la contraseña.');
    }

    public function promoteToWorker(int $userId): Result
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return Result::failure('Usuario no encontrado.');
        }
        if ($user['role'] === 'admin') {
            return Result::failure('No se puede cambiar el rol de un administrador.');
        }
        if ($user['role'] === 'worker') {
            return Result::failure('El usuario ya es un trabajador.');
        }

        $updated = $this->userModel->updateRole($userId, 'worker');
        return $updated
            ? Result::success(null, 'Usuario promovido a trabajador exitosamente.')
            : Result::failure('Error al promover al usuario.');
    }

    public function demoteToClient(int $userId): Result
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return Result::failure('Usuario no encontrado.');
        }
        if ($user['role'] === 'admin') {
            return Result::failure('No se puede cambiar el rol de un administrador.');
        }
        if ($user['role'] === 'client') {
            return Result::failure('El usuario ya es un cliente.');
        }

        $updated = $this->userModel->updateRole($userId, 'client');
        return $updated
            ? Result::success(null, 'Usuario cambiado a cliente exitosamente.')
            : Result::failure('Error al cambiar el rol del usuario.');
    }

    public function updateProfile(int $userId, string $username, string $email, ?string $password = null): Result
    {
        if (strlen(trim($username)) < 3) {
            return Result::failure('El nombre de usuario debe tener al menos 3 caracteres.');
        }
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return Result::failure('El correo electrónico no es válido.');
        }

        $current = $this->userModel->findById($userId);
        if (!$current) {
            return Result::failure('Usuario no encontrado.');
        }

        if (trim($username) !== $current['username'] && $this->userModel->usernameExists(trim($username))) {
            return Result::failure('El nombre de usuario ya está en uso.');
        }
        if (trim($email) !== $current['email'] && $this->userModel->emailExists(trim($email))) {
            return Result::failure('El correo electrónico ya está en uso.');
        }

        $updated = $this->userModel->updateUser($userId, trim($username), trim($email));
        if (!$updated) {
            return Result::failure('Error al actualizar el perfil.');
        }

        if ($password !== null && $password !== '') {
            if (strlen($password) < 6) {
                return Result::failure('La contraseña debe tener al menos 6 caracteres.');
            }
            $this->userModel->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        }

        return Result::success(null, 'Perfil actualizado exitosamente.');
    }

    public function getUsersByRole(string $role): array
    {
        return $this->userModel->getUsersByRole($role);
    }

    public function deleteUser(int $userId): Result
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return Result::failure('Usuario no encontrado.');
        }
        if ($user['role'] === 'admin') {
            return Result::failure('No se puede eliminar un administrador.');
        }

        $deleted = $this->userModel->deleteUser($userId);
        return $deleted
            ? Result::success(null, 'Usuario eliminado exitosamente.')
            : Result::failure('Error al eliminar el usuario.');
    }
}
