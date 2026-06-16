<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * App\Services\UserService
 *
 * Capa de servicio para la gestión de usuarios y trabajadores.
 * Centraliza las operaciones CRUD de usuarios con validación de negocio.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja lógica de gestión de usuarios/trabajadores.
 *   - DIP: Depende de UserModel (abstracción).
 *   - OCP: Extensible para nuevos tipos de usuario sin modificar código existente.
 *
 * @package App\Services
 */
class UserService
{
    /**
     * @var UserModel Modelo de acceso a datos de usuarios.
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
     * Obtiene todos los trabajadores registrados.
     *
     * @return array<int, array{id: int, username: string, email: string, role: string, created_at: string}>
     */
    public function getAllWorkers(): array
    {
        return $this->userModel->getUsersByRole('worker');
    }

    /**
     * Obtiene todos los clientes registrados.
     *
     * @return array<int, array{id: int, username: string, email: string, role: string, created_at: string}>
     */
    public function getAllClients(): array
    {
        return $this->userModel->getUsersByRole('client');
    }

    /**
     * Crea un nuevo trabajador con las validaciones de negocio.
     *
     * @param string $username Nombre de usuario.
     * @param string $email    Correo electrónico.
     * @param string $password Contraseña en texto plano (será hasheada).
     * @return array{success: bool, message: string}
     */
    public function createWorker(string $username, string $email, string $password): array
    {
        $username = $this->sanitize($username);
        $email    = $this->sanitize($email);

        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo electrónico no es válido.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if ($this->userModel->isEmailTaken($email)) {
            return ['success' => false, 'message' => 'Este correo electrónico ya está registrado.'];
        }

        if ($this->userModel->isUsernameTaken($username)) {
            return ['success' => false, 'message' => 'El nombre de usuario no está disponible.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->userModel->createUser($username, $email, $hashedPassword, 'worker')) {
            return ['success' => true, 'message' => 'Trabajador registrado con éxito.'];
        }

        return ['success' => false, 'message' => 'Error al registrar el trabajador en la base de datos.'];
    }

    /**
     * Actualiza los datos de un trabajador existente.
     *
     * @param int    $id       ID del trabajador.
     * @param string $username Nuevo nombre de usuario.
     * @param string $email    Nuevo correo electrónico.
     * @return array{success: bool, message: string}
     */
    public function updateWorker(int $id, string $username, string $email): array
    {
        $username = $this->sanitize($username);
        $email    = $this->sanitize($email);

        if ($id <= 0 || empty($username) || empty($email)) {
            return ['success' => false, 'message' => 'Faltan campos obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo electrónico no es válido.'];
        }

        // Verificar que no sea un email duplicado (excluyendo al propio usuario)
        $existingUser = $this->userModel->getUserById($id);
        if (!$existingUser) {
            return ['success' => false, 'message' => 'El trabajador no existe.'];
        }

        if ($existingUser['email'] !== $email && $this->userModel->isEmailTaken($email)) {
            return ['success' => false, 'message' => 'Este correo electrónico ya está en uso.'];
        }

        if ($existingUser['username'] !== $username && $this->userModel->isUsernameTaken($username)) {
            return ['success' => false, 'message' => 'Este nombre de usuario ya está en uso.'];
        }

        if ($this->userModel->updateUser($id, $username, $email)) {
            return ['success' => true, 'message' => 'Trabajador actualizado correctamente.'];
        }

        return ['success' => false, 'message' => 'Error al actualizar el trabajador.'];
    }

    /**
     * Elimina un trabajador del sistema.
     *
     * @param int $id ID del trabajador a eliminar.
     * @return array{success: bool, message: string}
     */
    public function deleteWorker(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID de trabajador inválido.'];
        }

        // No permitir eliminar si es el último admin
        $user = $this->userModel->getUserById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'El trabajador no existe.'];
        }

        if ($user['role'] === 'admin') {
            return ['success' => false, 'message' => 'No se puede eliminar un administrador desde esta sección.'];
        }

        if ($this->userModel->deleteUser($id)) {
            return ['success' => true, 'message' => 'Trabajador eliminado del sistema.'];
        }

        return ['success' => false, 'message' => 'Error al eliminar el trabajador.'];
    }

    /**
     * Resetea la contraseña de un trabajador.
     *
     * @param int    $id          ID del trabajador.
     * @param string $newPassword Nueva contraseña en texto plano.
     * @return array{success: bool, message: string}
     */
    public function resetWorkerPassword(int $id, string $newPassword): array
    {
        if ($id <= 0 || empty($newPassword)) {
            return ['success' => false, 'message' => 'Datos incompletos.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($this->userModel->updatePassword($id, $hashedPassword)) {
            return ['success' => true, 'message' => 'Contraseña actualizada correctamente.'];
        }

        return ['success' => false, 'message' => 'Error al actualizar la contraseña.'];
    }

    /**
     * Sanitiza un string de entrada HTTP.
     *
     * @param string $data Datos crudos.
     * @return string Datos sanitizados.
     */
    private function sanitize(string $data): string
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
