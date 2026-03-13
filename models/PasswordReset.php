<?php
// ============================================================================
// UBICACIÓN: gestor-notas/models/PasswordReset.php
// DESCRIPCIÓN: Modelo para recuperación de contraseña
// VERSIÓN: 2.0 - Fix timezone UTC (compatible con Railway)
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Security.php';

class PasswordReset {

    private $conn;
    private $table = 'password_resets';

    const TOKEN_EXPIRY_MINUTES = 30;

    public function __construct() {
        $database   = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Generar token seguro y guardarlo en BD
     * Invalida cualquier token anterior del mismo usuario
     */
    public function createToken($userId) {
        $this->deleteByUser($userId);

        $token     = bin2hex(random_bytes(32)); // 64 caracteres hex
        // gmdate() usa UTC igual que el servidor MySQL en Railway
        $expiresAt = gmdate('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY_MINUTES * 60);

        $query = "INSERT INTO " . $this->table . " (user_id, token, expires_at)
                  VALUES (:user_id, :token, :expires_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id',    $userId);
        $stmt->bindParam(':token',      $token);
        $stmt->bindParam(':expires_at', $expiresAt);

        return $stmt->execute() ? $token : false;
    }

    /**
     * Validar token: existe, no expiró y no fue usado
     */
    public function validateToken($token) {
        $query = "SELECT pr.*, u.email, u.username
                  FROM " . $this->table . " pr
                  INNER JOIN users u ON pr.user_id = u.id
                  WHERE pr.token = :token
                    AND pr.used = 0
                    AND pr.expires_at > UTC_TIMESTAMP()
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar token como usado y actualizar la contraseña del usuario
     */
    public function resetPassword($token, $newPassword) {
        $resetData = $this->validateToken($token);

        if (!$resetData) {
            return false;
        }

        $hashedPassword = Security::hashPassword($newPassword);

        $updateQuery = "UPDATE users
                        SET password = :password,
                            failed_login_attempts = 0,
                            locked_until = NULL
                        WHERE id = :user_id";

        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':user_id',  $resetData['user_id']);

        if (!$stmt->execute()) {
            return false;
        }

        $usedQuery = "UPDATE " . $this->table . "
                      SET used = 1
                      WHERE token = :token";

        $stmt = $this->conn->prepare($usedQuery);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $this->logSecurityEvent($resetData['user_id'], 'PASSWORD_RESET',
            'Contraseña restablecida via token');

        return true;
    }

    /**
     * Obtener usuario por email
     */
    public function getUserByEmail($email) {
        $query = "SELECT id, username, email FROM users
                  WHERE email = :email AND is_active = 1 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Eliminar todos los tokens de un usuario
     */
    private function deleteByUser($userId) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    /**
     * Registrar evento de seguridad
     */
    private function logSecurityEvent($userId, $eventType, $description) {
        $query = "INSERT INTO security_logs
                  (user_id, event_type, event_description, ip_address, user_agent, severity_level)
                  VALUES (:user_id, :event_type, :description, :ip, :user_agent, 'MEDIUM')";

        $stmt = $this->conn->prepare($query);

        $ip        = Security::getClientIP();
        $userAgent = Security::getUserAgent();

        $stmt->bindParam(':user_id',     $userId);
        $stmt->bindParam(':event_type',  $eventType);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip',          $ip);
        $stmt->bindParam(':user_agent',  $userAgent);

        $stmt->execute();
    }
}
?>