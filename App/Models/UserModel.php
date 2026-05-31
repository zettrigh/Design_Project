<?php

namespace App\Models;

// ─────────────────────────────────────────────────────────────
// App\Models\UserModel
// Capa de acceso a datos para la entidad User.
// Recibe la conexión PDO por inyección de dependencias.
// ─────────────────────────────────────────────────────────────

class UserModel {

    private \PDO $db;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Verifica si un correo electrónico ya existe
    public function isEmailTaken(string $email): bool {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Verifica si un nombre de usuario ya existe
    public function isUsernameTaken(string $username): bool {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE username = :username LIMIT 1"
        );
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Inserta un nuevo usuario (las validaciones se hacen en el Controlador)
    public function createUser(string $username, string $email, string $hashedPassword): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password)
                 VALUES (:username, :email, :password)"
            );
            $stmt->bindParam(':username', $username,       \PDO::PARAM_STR);
            $stmt->bindParam(':email',    $email,          \PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error creando usuario: ' . $e->getMessage());
            return false;
        }
    }

    // Obtiene los datos de un usuario por su email (para autenticación)
    public function getUserByEmail(string $email): array|false {
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
}
