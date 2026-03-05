<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/helpers/Security.php
// DESCRIPCIÓN: Funciones de seguridad (CSRF, XSS, sanitización, validación)
// ============================================================================

class Security {
    
    /**
     * Generar token CSRF
     * @return string
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF (timing-safe)
     * @param string $token
     * @return bool
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitizar input para salida HTML (prevenir XSS)
     * ⚠️ USO: Solo para insertar en HTML body/texto.
     *        NO usar para atributos HTML sin comillas adicionales.
     *        NO usar para contexto JavaScript/CSS.
     * @param string $data
     * @return string
     */
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Sanitizar texto plano (elimina TODAS las etiquetas HTML)
     * Útil para campos que nunca deben contener HTML (ej: username, título)
     * @param string $data
     * @return string
     */
    public static function sanitizePlainText($data) {
        $data = strip_tags($data);
        $data = trim($data);
        return $data;
    }
    
    /**
     * Sanitizar array completo
     * @param array $data
     * @return array
     */
    public static function sanitizeArray($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitize($value);
            }
        }
        return $sanitized;
    }
    
    /**
     * Validar URL de redirección (prevenir open redirect)
     * @param string $url URL proporcionada por el usuario
     * @param array $allowedDomains Dominios permitidos
     * @return string|false URL validada o false si es insegura
     */
    public static function validateRedirectUrl($url, $allowedDomains = ['localhost', '127.0.0.1']) {
        if (empty($url)) {
            return false;
        }
        
        // URLs relativas son siempre seguras
        if (strpos($url, 'http') !== 0) {
            return $url;
        }
        
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }
        
        $host = strtolower(trim($parsed['host']));
        foreach ($allowedDomains as $allowed) {
            if ($host === strtolower($allowed) || preg_match('/(^|\.)' . preg_quote($allowed, '/') . '$/i', $host)) {
                return $url;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitizar ruta de archivo (prevenir path traversal)
     * @param string $userPath Ruta proporcionada por el usuario
     * @param string $baseDir Directorio base permitido
     * @return string|false Ruta absoluta segura o false si es inválida
     */
    public static function sanitizeFilePath($userPath, $baseDir) {
        // Normalizar rutas
        $baseDir = realpath($baseDir);
        $userPath = preg_replace('#/{2,}#', '/', str_replace('\\', '/', $userPath));
        
        // Construir ruta completa y resolver ..
        $fullPath = realpath($baseDir . '/' . $userPath);
        
        // Verificar que esté dentro del directorio base
        if ($fullPath === false || strpos($fullPath, $baseDir) !== 0) {
            return false;
        }
        
        return $fullPath;
    }
    
    /**
     * Hash de contraseña seguro (bcrypt)
     * Cumple con requisito PDF: algoritmo diseñado para contraseñas + salt único
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verificar contraseña
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Obtener IP del cliente (validada)
     * @return string
     */
    public static function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Obtener User Agent
     * @return string
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Regenerar ID de sesión (prevenir session fixation)
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    // ============================================================================
    // === CAMBIO SEGURIDAD: CUMPLIMIENTO REQUISITOS PDF ===
    // Fecha: 16/02/2026
    // Autor: [TU NOMBRE AQUÍ]
    // Descripción:
    //   - sanitizePlainText(): elimina HTML para campos sin formato
    //   - validateRedirectUrl(): previene ataques de open redirect (PDF punto 3.3)
    //   - sanitizeFilePath(): previene path traversal (PDF punto 3.3)
    //   - Documentación clara de contexto de uso para sanitize()
    //   - bcrypt con cost=12 cumple punto 4 del PDF
    //   - CSRF con hash_equals() cumple punto 6 del PDF
    // Reversión: Eliminar métodos nuevos y este bloque si es necesario
    // ============================================================================
}
?>
    
