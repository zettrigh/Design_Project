<?php

namespace App\Models;

/**
 * App\Models\UserModel
 *
 * Capa de acceso a datos para la entidad User.
 * Recibe la conexión PDO por inyección de dependencias (SRP).
 *
 * Responsabilidades:
 *   - Consultas CRUD sobre la tabla `users`.
 *   - Validación de unicidad (email, username).
 *   - Gestión de roles (admin, worker, client).
 *
 * @package App\Models
 */
class UserModel
{
    /**
     * Conexión PDO activa.
     *
     * @var \PDO
     */
    private \PDO $db;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $dbConnection Conexión PDO activa.
     */
    public function __construct(\PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    /**
     * Verifica si un correo electrónico ya está registrado.
     *
     * @param string $email Correo electrónico a verificar.
     * @return bool True si el email ya existe.
     */
    public function isEmailTaken(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Verifica si un nombre de usuario ya está registrado.
     *
     * @param string $username Nombre de usuario a verificar.
     * @return bool True si el username ya existe.
     */
    public function isUsernameTaken(string $username): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE username = :username LIMIT 1"
        );
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Inserta un nuevo usuario en la base de datos.
     *
     * @param string $username       Nombre de usuario.
     * @param string $email          Correo electrónico.
     * @param string $hashedPassword Hash de la contraseña (bcrypt).
     * @param string $role           Rol del usuario ('client', 'worker', 'admin').
     * @return bool True si la inserción fue exitosa.
     */
    public function createUser(string $username, string $email, string $hashedPassword, string $role = 'client'): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password, role)
                 VALUES (:username, :email, :password, :role)"
            );
            $stmt->bindParam(':username', $username,       \PDO::PARAM_STR);
            $stmt->bindParam(':email',    $email,          \PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, \PDO::PARAM_STR);
            $stmt->bindParam(':role',     $role,           \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error creando usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los datos de un usuario por su email (para autenticación).
     *
     * @param string $email Correo electrónico del usuario.
     * @return array{id: int, username: string, password: string, role: string}|false
     */
    public function getUserByEmail(string $email): array|false
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, password, role FROM users WHERE email = :email LIMIT 1"
            );
            $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount() === 1 ? $stmt->fetch() : false;
        } catch (\PDOException $e) {
            error_log('Error buscando usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los datos de un usuario por su ID.
     *
     * @param int $id ID del usuario.
     * @return array{id: int, username: string, email: string, role: string, created_at: string}|false
     */
    public function getUserById(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, role, created_at FROM users WHERE id = :id LIMIT 1"
            );
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() === 1 ? $stmt->fetch() : false;
        } catch (\PDOException $e) {
            error_log('Error buscando usuario por ID: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los usuarios de un rol específico.
     *
     * @param string $role Rol a filtrar ('admin', 'worker', 'client').
     * @return array<int, array{id: int, username: string, email: string, role: string, created_at: string}>
     */
    public function getUsersByRole(string $role): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, role, created_at
                 FROM users
                 WHERE role = :role
                 ORDER BY id DESC"
            );
            $stmt->bindParam(':role', $role, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error obteniendo usuarios por rol: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza el nombre de usuario y email.
     *
     * @param int    $id       ID del usuario.
     * @param string $username Nuevo nombre de usuario.
     * @param string $email    Nuevo correo electrónico.
     * @return bool True si la actualización fue exitosa.
     */
    public function updateUser(int $id, string $username, string $email): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE users SET username = :username, email = :email WHERE id = :id"
            );
            $stmt->bindParam(':id',       $id,       \PDO::PARAM_INT);
            $stmt->bindParam(':username',  $username, \PDO::PARAM_STR);
            $stmt->bindParam(':email',     $email,    \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error actualizando usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la contraseña de un usuario.
     *
     * @param int    $id             ID del usuario.
     * @param string $hashedPassword Nuevo hash de contraseña.
     * @return bool True si la actualización fue exitosa.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE users SET password = :password WHERE id = :id"
            );
            $stmt->bindParam(':id',       $id,             \PDO::PARAM_INT);
            $stmt->bindParam(':password', $hashedPassword, \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error actualizando contraseña: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un usuario de la base de datos.
     *
     * @param int $id ID del usuario a eliminar.
     * @return bool True si la eliminación fue exitosa.
     */
    public function deleteUser(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error eliminando usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuenta los usuarios de un rol específico.
     *
     * @param string $role Rol a contar.
     * @return int Cantidad de usuarios con ese rol.
     */
    public function countUsersByRole(string $role): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = :role");
            $stmt->bindParam(':role', $role, \PDO::PARAM_STR);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('Error contando usuarios: ' . $e->getMessage());
            return 0;
        }
    }
}
