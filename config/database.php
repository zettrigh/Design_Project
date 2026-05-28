<?php

namespace Config;

// ─────────────────────────────────────────────────────────────
// Config\Database
// Conexión PDO usando Patrón Singleton.
// Crea la BD y la tabla automáticamente si no existen.
// ─────────────────────────────────────────────────────────────

class Database {

    private static ?Database $instance = null;
    private \PDO $connection;

    private string $host    = 'localhost';
    private string $db      = 'auth_system_db';
    private string $user    = 'root';
    private string $pass    = '';           // Ajustar si XAMPP tiene clave
    private string $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};charset={$this->charset}";

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES   => false,   // Sentencias preparadas reales (anti-SQLi)
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        try {
            $this->connection = new \PDO($dsn, $this->user, $this->pass, $options);

            // Crear BD si no existe
            $this->connection->exec(
                "CREATE DATABASE IF NOT EXISTS `{$this->db}`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            $this->connection->exec("USE `{$this->db}`");

            // Crear tabla users si no existe
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    username   VARCHAR(50)  NOT NULL UNIQUE,
                    email      VARCHAR(100) NOT NULL UNIQUE,
                    password   VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

        } catch (\PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Error crítico: No se pudo conectar a la base de datos.');
        }
    }

    private function __clone() {}
    public function __wakeup() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO {
        return $this->connection;
    }
}
