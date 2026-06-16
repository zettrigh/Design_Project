<?php

namespace Config;

/**
 * Config\Database
 *
 * Conexión PDO usando Patrón Singleton.
 * Crea la BD, tablas y seed data automáticamente si no existen.
 * Lee la configuración desde las variables de entorno (.env).
 *
 * Principios aplicados:
 *   - SRP: Solo gestiona la conexión y el esquema inicial de la BD.
 *   - Singleton: Asegura una única instancia de conexión PDO.
 *   - DRY: La configuración se centraliza en .env.
 *
 * @package Config
 */
class Database
{
    /**
     * Instancia única de la clase (Singleton).
     *
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Conexión PDO activa.
     *
     * @var \PDO
     */
    private \PDO $connection;

    /**
     * Constructor privado (Singleton).
     * Lee la configuración de .env y establece la conexión.
     *
     * @throws \PDOException Si la conexión a la BD falla.
     */
    private function __construct()
    {
        // Cargar variables de entorno si no se han cargado
        if (!Environment::has('DB_HOST')) {
            Environment::load(dirname(__DIR__) . '/.env');
        }

        $host    = Environment::get('DB_HOST', 'localhost');
        $db      = Environment::get('DB_NAME', 'auth_system_db');
        $user    = Environment::get('DB_USER', 'root');
        $pass    = (string) Environment::get('DB_PASS', '');
        $charset = Environment::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};charset={$charset}";

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES   => false,   // Prepared statements reales (anti-SQLi)
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        try {
            $this->connection = new \PDO($dsn, $user, $pass, $options);

            // Crear BD si no existe
            $this->connection->exec(
                "CREATE DATABASE IF NOT EXISTS `{$db}`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            $this->connection->exec("USE `{$db}`");

            // ── Crear/actualizar esquema ─────────────────────
            $this->migrateSchema();

            // ── Sembrar datos iniciales ──────────────────────
            $this->seedData();

        } catch (\PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Error crítico: No se pudo conectar a la base de datos.');
        }
    }

    /**
     * Crea las tablas del esquema si no existen y actualiza
     * las columnas de las tablas existentes si es necesario.
     *
     * @return void
     */
    private function migrateSchema(): void
    {
        // Tabla users
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                username   VARCHAR(50)  NOT NULL UNIQUE,
                email      VARCHAR(100) NOT NULL UNIQUE,
                password   VARCHAR(255) NOT NULL,
                role       ENUM('admin', 'worker', 'client') NOT NULL DEFAULT 'client',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Si la columna role es VARCHAR antiguo, migrarla a ENUM
        $result = $this->connection->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($result->rowCount() > 0) {
            $column = $result->fetch();
            if ($column && str_contains($column['Type'], 'varchar')) {
                try {
                    $this->connection->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'worker', 'client') NOT NULL DEFAULT 'client'");
                } catch (\PDOException $e) {
                    // Ignorar si ya es ENUM
                }
            }
        }

        // Tabla hairstyles
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS hairstyles (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                price       DECIMAL(10,2) NOT NULL,
                image_url   VARCHAR(255) NOT NULL,
                status      ENUM('active', 'inactive') DEFAULT 'active',
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Tabla reservations
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS reservations (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                user_id      INT NOT NULL,
                hairstyle_id INT NOT NULL,
                status       ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
                reserved_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (hairstyle_id) REFERENCES hairstyles(id) ON DELETE CASCADE
            )
        ");

        // Tabla payments (nueva)
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                reservation_id  INT NOT NULL,
                user_id         INT NOT NULL,
                amount          DECIMAL(10,2) NOT NULL,
                currency        VARCHAR(3) NOT NULL DEFAULT 'USD',
                exchange_rate   DECIMAL(10,6) NOT NULL DEFAULT 1.000000,
                amount_usd      DECIMAL(10,2) NOT NULL,
                payment_method  VARCHAR(50) NOT NULL DEFAULT 'stripe',
                transaction_id  VARCHAR(255) NOT NULL DEFAULT '',
                status          ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    /**
     * Siembra datos iniciales si las tablas están vacías:
     *   - Administrador por defecto
     *   - Peinados de ejemplo
     *
     * @return void
     */
    private function seedData(): void
    {
        // Sembrar administrador inicial si no existe
        $stmt = $this->connection->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $adminPassword = password_hash('admin1234', PASSWORD_DEFAULT);
            $insertAdmin = $this->connection->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (:username, :email, :password, :role)
            ");
            $insertAdmin->execute([
                ':username' => 'admin',
                ':email'    => 'admin@mimundotrenzas.com',
                ':password' => $adminPassword,
                ':role'     => 'admin',
            ]);
        }

        // Sembrar peinados iniciales si la tabla está vacía
        $stmt = $this->connection->query("SELECT id FROM hairstyles LIMIT 1");
        if ($stmt->rowCount() === 0) {
            $baseUrl = Environment::get('BASE_URL', '/HomeWorks/Design_Project');

            $initialHairstyles = [
                [
                    'name'        => 'Box Braids Elegantes',
                    'description' => 'Trenzas africanas clásicas y duraderas, tejidas a la perfección con extensiones de alta calidad. Aportan estilo y protección para tu cabello natural.',
                    'price'       => 87.00,
                    'image_url'   => $baseUrl . '/src/img/braid_box.png',
                    'status'      => 'active',
                ],
                [
                    'name'        => 'Cornrows Profesionales',
                    'description' => 'Trenzas pegadas al cuero cabelludo con diseños geométricos limpios. Estilo versátil, minimalista y de bajo mantenimiento.',
                    'price'       => 58.00,
                    'image_url'   => $baseUrl . '/src/img/braid_cornrows.png',
                    'status'      => 'active',
                ],
                [
                    'name'        => 'Trenza Francesa Doble',
                    'description' => 'Look deportivo y chic con dos trenzas de raíz que terminan en la nuca. Ideal para ocasiones casuales y eventos al aire libre.',
                    'price'       => 47.00,
                    'image_url'   => $baseUrl . '/src/img/braid_french.png',
                    'status'      => 'active',
                ],
                [
                    'name'        => 'Diosa Braids Luxury',
                    'description' => 'Trenzas estilo "Goddess" con extremos rizados sueltos y decoradas con anillos dorados de lujo. El peinado bohemio definitivo para destacar.',
                    'price'       => 116.00,
                    'image_url'   => $baseUrl . '/src/img/braid_goddess.png',
                    'status'      => 'active',
                ],
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
                    ':status'      => $style['status'],
                ]);
            }
        }
    }

    /**
     * Previene la clonación de la instancia (Singleton).
     *
     * @return void
     */
    private function __clone() {}

    /**
     * Previene la deserialización de la instancia (Singleton).
     *
     * @return void
     */
    public function __wakeup() {}

    /**
     * Retorna la instancia única de Database.
     *
     * @return Database
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna la conexión PDO activa.
     *
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}
