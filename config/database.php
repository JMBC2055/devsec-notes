<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/config/database.php
// DESCRIPCIÓN: Configuración y conexión segura a la base de datos
// ============================================================================

class Database {
    
    // Configuración de conexión
    private $host = "localhost";
    private $db_name = "devsec_notes";
    private $username = "root";
    private $password = "";  // Contraseña de MySQL (vacía por defecto en XAMPP)
    private $charset = "utf8mb4";
    
    public $conn;
    
    /**
     * Obtener conexión PDO segura
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // DSN (Data Source Name)
            $dsn = "mysql:host=" . $this->host . 
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            // Opciones de PDO para seguridad
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Array asociativo
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reales
                PDO::ATTR_PERSISTENT         => false,                   // No conexiones persistentes
            ];
            
            // Crear conexión
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // Log del error (en producción guardar en archivo)
            error_log("Error de conexión: " . $e->getMessage());
            
            // Mensaje genérico al usuario (no exponer detalles)
            die("Error al conectar con la base de datos. Por favor, contacte al administrador.");
        }
        
        return $this->conn;
    }
    
    /**
     * Cerrar conexión
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>