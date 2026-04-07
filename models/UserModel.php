<?php
// models/UserModel.php
// Capa MODELO: Responsable de toda la interacción directa con la tabla 'users'.
class UserModel {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // Verifica si un correo electrónico ya existe.
    public function isEmailTaken($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Verifica si un nombre de usuario ya existe.
    public function isUsernameTaken($username) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Inserta un nuevo usuario (las validaciones se hacen en el Controlador).
    public function createUser($username, $email, $hashedPassword) {
        try {
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error creando usuario: ' . $e->getMessage());
            return false;
        }
    }

    // Obtiene los datos de un usuario por su email para autenticación.
    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, password FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                return $stmt->fetch();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error buscando usuario: ' . $e->getMessage());
            return false;
        }
    }
}
