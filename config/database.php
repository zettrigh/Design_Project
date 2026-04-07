<?php
// config/database.php
// Conexión PDO a la base de datos usando el Patrón Singleton.
// Separa la configuración del resto de la aplicación (MVC Core).
// Crea la base de datos y la tabla automáticamente si no existen.
class Database {
    private static $instance = null;
    private $connection;

    private $host = 'localhost';
    private $db   = 'auth_system_db';
    private $user = 'root';
    private $pass = ''; // Modificar si XAMPP tiene clave
    private $charset = 'utf8mb4';

    private function __construct() {
        // Conectamos SIN especificar la base de datos al inicio para poder crearla si no existe
        $dsn = "mysql:host={$this->host};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Apagamos la emulación para usar Sentencias Preparadas reales de MySQL (medida vital contra SQLi)
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->connection = new PDO($dsn, $this->user, $this->pass, $options);
            
            // Reparación automática: Creamos la BD si no ha sido creada
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS `{$this->db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Seleccionamos la base de datos
            $this->connection->exec("USE `{$this->db}`");
            
            // Reparación automática: Creamos la tabla users si no ha sido creada
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
        } catch (\PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die("Error crítico: No se pudo conectar a la base de datos. Detalle: " . $e->getMessage());
        }
    }

    private function __clone() {}
    public function __wakeup() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
