<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/models/PasswordReset.php
// DESCRIPCIÓN: Modelo para recuperación de contraseña
// ============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Security.php';

class PasswordReset {

    private $conn;
    private $table = 'password_resets';

    // Minutos de validez del token
    const TOKEN_EXPIRY_MINUTES = 30;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Generar token seguro y guardarlo en BD
     * Invalida cualquier token anterior del mismo usuario
     *
     * @param int $userId
     * @return string|false  Token generado o false si falla
     */
    public function createToken($userId) {
        // Eliminar tokens anteriores del usuario
        $this->deleteByUser($userId);

        $token     = bin2hex(random_bytes(32)); // 64 caracteres hex
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY_MINUTES * 60);

        $query = "INSERT INTO " . $this->table . " (user_id, token, expires_at)
                  VALUES (:user_id, :token, :expires_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id',    $userId);
        $stmt->bindParam(':token',      $token);
        $stmt->bindParam(':expires_at', $expiresAt);

        if ($stmt->execute()) {
            return $token;
        }

        return false;
    }

    /**
     * Validar token: existe, no expiró y no fue usado
     *
     * @param string $token
     * @return array|false  Datos del reset o false
     */
    public function validateToken($token) {
        $query = "SELECT pr.*, u.email, u.username
                  FROM " . $this->table . " pr
                  INNER JOIN users u ON pr.user_id = u.id
                  WHERE pr.token = :token
                    AND pr.used = 0
                    AND pr.expires_at > NOW()
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar token como usado y actualizar la contraseña del usuario
     *
     * @param string $token
     * @param string $newPassword  Contraseña en texto plano (se hashea aquí)
     * @return bool
     */
    public function resetPassword($token, $newPassword) {
        $resetData = $this->validateToken($token);

        if (!$resetData) {
            return false;
        }

        $hashedPassword = Security::hashPassword($newPassword);

        // Actualizar contraseña
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

        // Marcar token como usado
        $usedQuery = "UPDATE " . $this->table . "
                      SET used = 1
                      WHERE token = :token";

        $stmt = $this->conn->prepare($usedQuery);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        // Log de seguridad
        $this->logSecurityEvent($resetData['user_id'], 'PASSWORD_RESET',
            'Contraseña restablecida via token');

        return true;
    }

    /**
     * Obtener email del usuario por su email (para buscar si existe)
     *
     * @param string $email
     * @return array|false
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
     *
     * @param int $userId
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
