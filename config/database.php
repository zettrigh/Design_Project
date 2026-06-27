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
        $port    = Environment::get('DB_PORT', '3306');
        $db      = Environment::get('DB_NAME', 'auth_system_db');
        $user    = Environment::get('DB_USER', 'root');
        $pass    = (string) Environment::get('DB_PASS', '');
        $charset = Environment::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};charset={$charset}";

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
            // Si es error de tablespace corrupto (1813, 1932),
            // dropear tablas corruptas una por una y reintentar
            $msg = $e->getMessage();
            if (str_contains($msg, '1813') || str_contains($msg, '1932') || str_contains($msg, 'doesn\'t exist in engine')) {
                try {
                    // Extraer nombre de tabla del mensaje de error
                    preg_match('/table\s+[\'`]?(?:\w+\.)?(\w+)[\'`]?/i', $msg, $m);
                    $corruptedTable = $m[1] ?? '';
                    if ($corruptedTable) {
                        try {
                            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");
                            $this->connection->exec("DROP TABLE IF EXISTS `{$corruptedTable}`");
                            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
                        } catch (\PDOException $eDrop) {
                            // Si no se puede dropear, intentar con todos los métodos
                            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");
                            foreach (['users', 'hairstyles', 'reservations', 'business_hours', 'worker_schedules', 'payments'] as $tbl) {
                                try { $this->connection->exec("DROP TABLE IF EXISTS `{$tbl}`"); } catch (\PDOException $eInner) {}
                            }
                            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
                        }
                    } else {
                        // No se pudo extraer nombre: dropear todas
                        $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");
                        foreach (['users', 'hairstyles', 'reservations', 'business_hours', 'worker_schedules', 'payments'] as $tbl) {
                            try { $this->connection->exec("DROP TABLE IF EXISTS `{$tbl}`"); } catch (\PDOException $eInner) {}
                        }
                        $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
                    }
                    $this->migrateSchema();
                    $this->seedData();
                    return;
                } catch (\PDOException $e2) {
                    error_log('Database Recovery Error: ' . $e2->getMessage());
                }
            }
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
        // Helper: crear tabla con detección de corrupción (errores 1813, 1932)
        $createTable = function (string $sql): void {
            // Extraer el nombre de la tabla del CREATE TABLE
            preg_match('/CREATE TABLE (?:IF NOT EXISTS )?(\w+)/i', $sql, $m);
            $tableName = $m[1] ?? '';
            try {
                $this->connection->exec($sql);
            } catch (\PDOException $e) {
                $msg = $e->getMessage();
                if ($tableName && (str_contains($msg, 'doesn\'t exist in engine') || str_contains($msg, '1813'))) {
                    try { $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0"); } catch (\PDOException $eInner) {}
                    $this->connection->exec("DROP TABLE IF EXISTS `{$tableName}`");
                    try { $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch (\PDOException $eInner) {}
                    $this->connection->exec($sql);
                } else {
                    throw $e;
                }
            }
        };

        // Tabla users
        $createTable("
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
        try {
            $result = $this->connection->query("SHOW COLUMNS FROM users LIKE 'role'");
            if ($result->rowCount() > 0) {
                $column = $result->fetch();
                if ($column && str_contains($column['Type'], 'varchar')) {
                    $this->connection->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'worker', 'client') NOT NULL DEFAULT 'client'");
                }
            }
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'doesn\'t exist in engine')) {
                $this->connection->exec("DROP TABLE IF EXISTS users");
                return; // Recreará todo en la próxima solicitud
            }
        }

        // Tabla hairstyles
        $createTable("
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

        // Tabla reservations base (sin columnas nuevas primero para compatibilidad)
        $createTable("
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

        // Migrar reservations: agregar columnas de agenda solo si no existen
        $columns = $this->connection->query("SHOW COLUMNS FROM reservations");
        $existingCols = [];
        while ($col = $columns->fetch()) {
            $existingCols[] = $col['Field'];
        }

        if (!in_array('worker_id', $existingCols)) {
            try {
                $this->connection->exec("ALTER TABLE reservations ADD COLUMN worker_id INT DEFAULT NULL AFTER hairstyle_id");
                $this->connection->exec("ALTER TABLE reservations ADD FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE SET NULL");
            } catch (\PDOException $e) {}
        }
        if (!in_array('appointment_date', $existingCols)) {
            try {
                $this->connection->exec("ALTER TABLE reservations ADD COLUMN appointment_date DATE DEFAULT NULL AFTER worker_id");
            } catch (\PDOException $e) {}
        }
        if (!in_array('appointment_time', $existingCols)) {
            try {
                $this->connection->exec("ALTER TABLE reservations ADD COLUMN appointment_time TIME DEFAULT NULL AFTER appointment_date");
            } catch (\PDOException $e) {}
        }
        if (!in_array('end_time', $existingCols)) {
            try {
                $this->connection->exec("ALTER TABLE reservations ADD COLUMN end_time TIME DEFAULT NULL AFTER appointment_time");
            } catch (\PDOException $e) {}
        }

        // Migrar hairstyles: agregar duration_minutes si no existe
        $hColumns = $this->connection->query("SHOW COLUMNS FROM hairstyles");
        $hExistingCols = [];
        while ($col = $hColumns->fetch()) {
            $hExistingCols[] = $col['Field'];
        }
        if (!in_array('duration_minutes', $hExistingCols)) {
            try {
                $this->connection->exec("ALTER TABLE hairstyles ADD COLUMN duration_minutes INT NOT NULL DEFAULT 60 AFTER price");
            } catch (\PDOException $e) {}
        }

        // Tabla business_hours (horarios de atención del negocio)
        $createTable("
            CREATE TABLE IF NOT EXISTS business_hours (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                day_of_week TINYINT NOT NULL,
                open_time   TIME NOT NULL,
                close_time  TIME NOT NULL,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE KEY uq_day (day_of_week)
            )
        ");

        // Tabla worker_schedules (disponibilidad individual de cada trabajador)
        $createTable("
            CREATE TABLE IF NOT EXISTS worker_schedules (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                worker_id   INT NOT NULL,
                day_of_week TINYINT NOT NULL,
                start_time  TIME NOT NULL,
                end_time    TIME NOT NULL,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uq_worker_day (worker_id, day_of_week)
            )
        ");

        // Tabla exchange_rates (tasa de cambio manual USD → VES)
        $createTable("
            CREATE TABLE IF NOT EXISTS exchange_rates (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                from_currency VARCHAR(3) NOT NULL DEFAULT 'USD',
                to_currency   VARCHAR(3) NOT NULL DEFAULT 'VES',
                rate          DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by    INT DEFAULT NULL,
                UNIQUE KEY uq_pair (from_currency, to_currency)
            )
        ");

        // Tabla payments (nueva)
        $createTable("
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

        // Sembrar horarios de atención por defecto (Lun-Sáb 9:00-18:00)
        $stmt = $this->connection->query("SELECT id FROM business_hours LIMIT 1");
        if ($stmt->rowCount() === 0) {
            $insertHours = $this->connection->prepare("
                INSERT INTO business_hours (day_of_week, open_time, close_time, is_active)
                VALUES (:day_of_week, :open_time, :close_time, 1)
            ");
            // Lunes(1) a Viernes(5): 9:00-18:00, Sábado(6): 9:00-14:00, Domingo(0): inactivo
            $days = [
                1 => ['09:00', '18:00'],
                2 => ['09:00', '18:00'],
                3 => ['09:00', '18:00'],
                4 => ['09:00', '18:00'],
                5 => ['09:00', '18:00'],
                6 => ['09:00', '14:00'],
            ];
            foreach ($days as $day => $hours) {
                $insertHours->execute([
                    ':day_of_week' => $day,
                    ':open_time'   => $hours[0],
                    ':close_time'  => $hours[1],
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
