<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/config/database.php
// DESCRIPCIÓN: Configuración de la base de datos
// ============================================================================

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Cargar variables de entorno
        $this->loadEnv();
        
        // Configuración de conexión
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];
        
        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Error de conexión a la base de datos. Por favor, inténtalo más tarde.");
        }
    }
    
    private function loadEnv() {
        // Primero intentar cargar desde variables de entorno del sistema
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'devsec_notes';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        
        // Si no están definidas, intentar cargar desde .env (solo para desarrollo)
        if (!getenv('DB_HOST')) {
            $this->loadEnvFromFile();
        }
    }
    
    private function loadEnvFromFile() {
        // Solo para desarrollo local (nunca para producción)
        if (file_exists(__DIR__ . '/.env')) {
            $env = parse_ini_file(__DIR__ . '/.env');
            
            $this->host = $env['DB_HOST'] ?? 'localhost';
            $this->db_name = $env['DB_NAME'] ?? 'devsec_notes';
            $this->username = $env['DB_USER'] ?? 'root';
            $this->password = $env['DB_PASSWORD'] ?? '';
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>

 */
