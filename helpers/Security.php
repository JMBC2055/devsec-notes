<?php
// ============================================================================
// UBICACIÓN: /app/helpers/Security.php (o la ruta correspondiente en Railway)
// DESCRIPCIÓN: Funciones de seguridad (CSRF, XSS, sanitización, validación)
// CORREGIDO: Errores de headers already sent y regeneración de sesión
// ============================================================================

class Security {
    
    /**
     * Iniciar sesión de manera segura si no está iniciada
     * Esta función auxiliar evita duplicar código
     */
    private static function secureSessionStart() {
        if (session_status() === PHP_SESSION_NONE) {
            // Asegurar que no haya salida antes de iniciar sesión
            if (!headers_sent()) {
                session_start();
            } else {
                // Si ya se enviaron headers, no podemos iniciar sesión
                // Esto debería loguearse para depuración
                error_log("ADVERTENCIA: No se pudo iniciar sesión - headers ya enviados");
            }
        }
    }
    
    /**
     * Generar token CSRF
     * @return string
     */
    public static function generateCSRFToken() {
        self::secureSessionStart();
        
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
        self::secureSessionStart();
        
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
     * === CORRECCIÓN: Regenerar ID de sesión de forma segura ===
     * Versión mejorada que previene errores de "headers already sent"
     * y sigue buenas prácticas de seguridad
     * 
     * @param bool $deleteOldSession Eliminar datos de sesión antigua
     * @return bool True si se regeneró, False si no fue posible
     */
    public static function regenerateSession($deleteOldSession = true) {
        // Verificar si la sesión está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Intentar iniciar sesión si no hay headers enviados
            if (!headers_sent()) {
                session_start();
            } else {
                error_log("Security::regenerateSession: No se puede iniciar sesión - headers ya enviados");
                return false;
            }
        }
        
        // Verificar nuevamente si la sesión está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log("Security::regenerateSession: No se pudo activar la sesión");
            return false;
        }
        
        // Verificar si podemos regenerar (no headers sent)
        if (headers_sent()) {
            error_log("Security::regenerateSession: No se puede regenerar - headers ya enviados");
            
            // Estrategia alternativa: marcar para regenerar en el próximo request
            $_SESSION['needs_regeneration'] = true;
            return false;
        }
        
        // Guardar datos de sesión actual si es necesario
        $sessionData = $_SESSION;
        
        // Regenerar ID de sesión
        $regenerated = session_regenerate_id($deleteOldSession);
        
        if ($regenerated) {
            // Si teníamos datos que restaurar, los restauramos
            if (!$deleteOldSession && !empty($sessionData)) {
                $_SESSION = $sessionData;
            }
            
            // Eliminar bandera de regeneración pendiente si existe
            unset($_SESSION['needs_regeneration']);
            
            error_log("Security::regenerateSession: ID de sesión regenerado exitosamente");
        } else {
            error_log("Security::regenerateSession: Falló la regeneración del ID de sesión");
        }
        
        return $regenerated;
    }
    
    /**
     * === NUEVO: Regenerar sesión si es necesario ===
     * Esta función debe llamarse al principio de páginas sensibles
     * para regenerar la sesión si quedó pendiente
     */
    public static function regenerateIfNeeded() {
        if (session_status() === PHP_SESSION_ACTIVE && 
            isset($_SESSION['needs_regeneration']) && 
            $_SESSION['needs_regeneration'] === true) {
            
            unset($_SESSION['needs_regeneration']);
            self::regenerateSession(true);
        }
    }
    
    /**
     * === NUEVO: Destruir sesión de forma segura ===
     * Para logout sin errores de headers
     * 
     * @return bool
     */
    public static function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Limpiar variable de sesión
            $_SESSION = [];
            
            // Eliminar cookie de sesión
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            // Destruir sesión
            if (!headers_sent()) {
                session_destroy();
                return true;
            } else {
                error_log("Security::destroySession: No se pudo destruir - headers ya enviados");
                // Marcar para destruir en el próximo request
                $_SESSION['needs_destruction'] = true;
            }
        }
        return false;
    }
    
    /**
     * === NUEVO: Establecer mensaje flash ===
     * Mensaje que persiste solo un request (útil para redirecciones)
     * 
     * @param string $key Tipo de mensaje (success, error, info)
     * @param string $message Contenido del mensaje
     */
    public static function setFlashMessage($key, $message) {
        self::secureSessionStart();
        $_SESSION['flash'][$key] = $message;
    }
    
    /**
     * === NUEVO: Obtener y limpiar mensaje flash ===
     * 
     * @param string $key Tipo de mensaje
     * @return string|null
     */
    public static function getFlashMessage($key) {
        self::secureSessionStart();
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
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
    //   - CORRECCIÓN: regenerateSession() segura sin headers issues
    //   - NUEVO: Métodos para manejo seguro de sesiones en producción
    // Reversión: Eliminar métodos nuevos y este bloque si es necesario
    // ============================================================================
}
?>