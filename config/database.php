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
                    role       VARCHAR(20)  DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Si la columna role no existe, agregarla
            $result = $this->connection->query("SHOW COLUMNS FROM users LIKE 'role'");
            if ($result->rowCount() === 0) {
                $this->connection->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER password");
            }

            // Crear tabla hairstyles si no existe
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS hairstyles (
                    id          INT AUTO_INCREMENT PRIMARY KEY,
                    name        VARCHAR(100) NOT NULL,
                    description TEXT NOT NULL,
                    price       DECIMAL(10,2) NOT NULL,
                    image_url   VARCHAR(255) NOT NULL,
                    status      VARCHAR(20) DEFAULT 'active',
                    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Crear tabla reservations si no existe
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS reservations (
                    id           INT AUTO_INCREMENT PRIMARY KEY,
                    user_id      INT NOT NULL,
                    hairstyle_id INT NOT NULL,
                    status       VARCHAR(20) DEFAULT 'pending',
                    reserved_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (hairstyle_id) REFERENCES hairstyles(id) ON DELETE CASCADE
                )
            ");

            // Sembrar administrador inicial si no existe
            $stmt = $this->connection->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $adminUsername = 'admin';
                $adminEmail = 'admin@mimundotrenzas.com';
                $adminPassword = password_hash('admin1234', PASSWORD_DEFAULT);
                $adminRole = 'admin';
                $insertAdmin = $this->connection->prepare("
                    INSERT INTO users (username, email, password, role)
                    VALUES (:username, :email, :password, :role)
                ");
                $insertAdmin->execute([
                    ':username' => $adminUsername,
                    ':email'    => $adminEmail,
                    ':password' => $adminPassword,
                    ':role'     => $adminRole
                ]);
            }

            // Sembrar peinados iniciales si la tabla está vacía
            $stmt = $this->connection->query("SELECT id FROM hairstyles LIMIT 1");
            if ($stmt->rowCount() === 0) {
                $initialHairstyles = [
                    [
                        'name' => 'Box Braids Elegantes',
                        'description' => 'Trenzas africanas clásicas y duraderas, tejidas a la perfección con extensiones de alta calidad. Aportan estilo y protección para tu cabello natural.',
                        'price' => 85.00,
                        'image_url' => '/HomeWorks/Design_Project/src/img/braid_box.png',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Cornrows Profesionales',
                        'description' => 'Trenzas pegadas al cuero cabelludo con diseños geométricos limpios. Estilo versátil, minimalista y de bajo mantenimiento.',
                        'price' => 60.00,
                        'image_url' => '/HomeWorks/Design_Project/src/img/braid_cornrows.png',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Trenza Francesa Doble',
                        'description' => 'Look deportivo y chic con dos trenzas de raíz que terminan en la nuca. Ideal para ocasiones casuales y eventos al aire libre.',
                        'price' => 45.00,
                        'image_url' => '/HomeWorks/Design_Project/src/img/braid_french.png',
                        'status' => 'active'
                    ],
                    [
                        'name' => 'Diosa Braids Luxury',
                        'description' => 'Trenzas estilo "Goddess" con extremos rizados sueltos y decoradas con anillos dorados de lujo. El peinado bohemio definitivo para destacar.',
                        'price' => 110.00,
                        'image_url' => '/HomeWorks/Design_Project/src/img/braid_goddess.png',
                        'status' => 'active'
                    ]
                ];

                $insertHairstyle = $this->connection->prepare("
                    INSERT INTO hairstyles (name, description, price, image_url, status)
                    VALUES (:name, :description, :price, :image_url, :status)
                ");

                foreach ($initialHairstyles as $style) {
                    $insertHairstyle->execute([
                        ':name'        => $style['name'],
                        ':description' => $style['description'],
                        ':price'       => $style['price'],
                        ':image_url'   => $style['image_url'],
                        ':status'      => $style['status']
                    ]);
                }
            }

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
