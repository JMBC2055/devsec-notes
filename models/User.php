<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/models/User.php
// DESCRIPCIÓN: Modelo de usuarios
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Security.php';

class User {
    
    private $conn;
    private $table = 'users';
    
    // Propiedades
    public $id;
    public $username;
    public $email;
    public $password;
    public $role;          // === NUEVO: campo role para autorización ===
    public $is_active;
    public $created_at;
    
    /**
     * Constructor
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Registrar nuevo usuario
     * @return bool
     */
    public function register() {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password, role, is_active) 
                  VALUES (:username, :email, :password, :role, 1)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash de contraseña
        $hashedPassword = Security::hashPassword($this->password);
        
        // Bind de parámetros
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindValue(':role', 'user'); // === NUEVO: role por defecto 'user' ===
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Registrar en security_logs
            $this->logSecurityEvent('REGISTER_SUCCESS', 'Usuario registrado exitosamente');
            return true;
        }
        
        return false;
    }
    
    /**
     * Login de usuario
     * @return array|false
     */
    public function login() {
        $query = "SELECT id, username, email, password, role, is_active, 
                         failed_login_attempts, locked_until
                  FROM " . $this->table . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si cuenta está bloqueada
            if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
                $this->logSecurityEvent('ACCOUNT_LOCKED', 'Intento de login en cuenta bloqueada', $row['id']);
                return false;
            }
            
            // Verificar si cuenta está activa
            if (!$row['is_active']) {
                return false;
            }
            
            // Verificar contraseña
            if (Security::verifyPassword($this->password, $row['password'])) {
                
                // Reset de intentos fallidos
                $this->resetFailedAttempts($row['id']);
                
                // Actualizar último login
                $this->updateLastLogin($row['id']);
                
                // Log de login exitoso
                $this->logSecurityEvent('LOGIN_SUCCESS', 'Login exitoso', $row['id']);
                
                return [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'role' => $row['role'] // === NUEVO: devolver role para almacenar en sesión ===
                ];
            } else {
                // Incrementar intentos fallidos
                $this->incrementFailedAttempts($row['id']);
                
                // Log de login fallido
                $this->logSecurityEvent('LOGIN_FAILED', 'Contraseña incorrecta', $row['id']);
                return false;
            }
        }
        
        // Usuario no encontrado
        $this->logSecurityEvent('LOGIN_FAILED', 'Email no encontrado: ' . $this->email);
        return false;
    }
    
    /**
     * Verificar si email ya existe
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Verificar si username ya existe
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Incrementar intentos fallidos de login
     * @param int $userId
     */
    private function incrementFailedAttempts($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET failed_login_attempts = failed_login_attempts + 1,
                      locked_until = CASE 
                          WHEN failed_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                          ELSE NULL 
                      END
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
    
    /**
     * Reset de intentos fallidos
     * @param int $userId
     */
    private function resetFailedAttempts($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET failed_login_attempts = 0, locked_until = NULL 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
    
    /**
     * Actualizar último login
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET last_login = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
    
    /**
     * Registrar evento de seguridad
     * @param string $eventType
     * @param string $description
     * @param int|null $userId
     */
    private function logSecurityEvent($eventType, $description, $userId = null) {
        $query = "INSERT INTO security_logs 
                  (user_id, event_type, event_description, ip_address, user_agent, severity_level) 
                  VALUES (:user_id, :event_type, :description, :ip, :user_agent, :severity)";
        
        $stmt = $this->conn->prepare($query);
        
        $ip = Security::getClientIP();
        $userAgent = Security::getUserAgent();
        $severity = ($eventType === 'LOGIN_FAILED' || $eventType === 'ACCOUNT_LOCKED') ? 'MEDIUM' : 'LOW';
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':event_type', $eventType);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':user_agent', $userAgent);
        $stmt->bindParam(':severity', $severity);
        
        $stmt->execute();
    }

    /**
     * Generar token de recuperación
     * @param string $email
     * @return string|false
     */
    public function generateResetToken($email) {
        if (!$this->emailExists($email)) {
            return false;
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $query = "UPDATE " . $this->table . " 
                  SET reset_token = :token, reset_expires = :expires 
                  WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':email', $email);
        
        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

    /**
     * Validar token de recuperación
     * @param string $token
     * @return array|false
     */
    public function validateResetToken($token) {
        $query = "SELECT id, email, reset_expires FROM " . $this->table . " 
                  WHERE reset_token = :token AND reset_expires > NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Restablecer contraseña
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword($userId, $newPassword) {
        $hashedPassword = Security::hashPassword($newPassword);
        
        $query = "UPDATE " . $this->table . " 
                  SET password = :password, reset_token = NULL, reset_expires = NULL 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $userId);
        
        return $stmt->execute();
    }
}
?>
