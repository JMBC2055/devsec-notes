<?php
// ============================================================================
// UBICACIÓN: gestor-notas/config/database.php
// DESCRIPCIÓN: Conexión PDO — funciona en XAMPP local y en Railway (producción)
// ============================================================================

class Database {

    // Railway inyecta: MYSQLHOST, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD, MYSQLPORT
    // XAMPP usa los valores por defecto del fallback
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $charset = "utf8mb4";

    public $conn;

    public function __construct() {
        $this->host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
        $this->db_name  = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'devsec_notes';
        $this->username = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
        $this->password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
        $this->port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306';
    }

    /**
     * Obtener conexión PDO segura
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host}"
                 . ";port={$this->port}"
                 . ";dbname={$this->db_name}"
                 . ";charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die("Error al conectar con la base de datos. Por favor, contacte al administrador.");
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
?>
