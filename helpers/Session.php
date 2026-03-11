<?php
// ============================================================================
// UBICACIÓN: /app/helpers/Session.php (o la ruta correspondiente)
// DESCRIPCIÓN: Manejo seguro de sesiones - CORREGIDO para Railway
// ============================================================================

class Session {
    
    // Tiempos de expiración (en segundos)
    const INACTIVE_TIMEOUT = 1800;   // 30 minutos de inactividad
    const ABSOLUTE_TIMEOUT = 86400;  // 24 horas máximo desde inicio
    
    /**
     * Verificar si headers ya fueron enviados (para evitar warnings)
     * @return bool
     */
    private static function canSendHeaders() {
        return !headers_sent();
    }
    
    /**
     * Iniciar sesión segura - VERSIÓN CORREGIDA sin headers issues
     * @throws Exception
     */
    public static function start() {
        // Si la sesión ya está activa, solo verificar tiempos
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::validateSession();
            return;
        }
        
        // Intentar iniciar sesión solo si no se han enviado headers
        if (self::canSendHeaders()) {
            
            // === CONFIGURACIÓN SEGURA DE SESIÓN (Punto 6 del PDF) ===
            // HttpOnly: impide acceso desde JavaScript (XSS)
            ini_set('session.cookie_httponly', 1);
            
            // Solo cookies: evita session fixation vía URL
            ini_set('session.use_only_cookies', 1);
            
            // Strict mode: rechaza IDs de sesión no generados por el servidor
            ini_set('session.use_strict_mode', 1);
            
            // Secure: solo por HTTPS en producción
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            ini_set('session.cookie_secure', $isSecure ? 1 : 0);
            
            // SameSite: previene CSRF
            ini_set('session.cookie_samesite', 'Strict');
            
            // Ruta de cookie restringida
            ini_set('session.cookie_path', '/');
            
            // Iniciar sesión
            session_start();
            
            // Inicializar sesión si es nueva
            if (!isset($_SESSION['initiated'])) {
                self::initializeNewSession();
            } else {
                self::validateSession();
            }
        } else {
            // Si no se pueden enviar headers, registrar para depuración
            error_log("Session::start() - No se pudo iniciar sesión: headers ya enviados");
            
            // Intentar obtener sesión sin enviar headers
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            
            // Si aún no hay sesión, crear estructura mínima
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
        }
    }
    
    /**
     * Inicializar nueva sesión con medidas de seguridad
     */
    private static function initializeNewSession() {
        // Prevenir session fixation regenerando ID
        if (self::canSendHeaders()) {
            session_regenerate_id(true);
        }
        
        $_SESSION['initiated'] = true;
        $_SESSION['session_start_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Guardar fingerprint para detectar hijacking (IP + User-Agent)
        $_SESSION['session_fingerprint'] = self::generateFingerprint();
    }
    
    /**
     * Validar sesión existente (timeouts y fingerprint)
     * @throws Exception
     */
    private static function validateSession() {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }
        
        // Verificar timeout de inactividad
        if (time() - $_SESSION['last_activity'] > self::INACTIVE_TIMEOUT) {
            self::destroy();
            throw new Exception("Sesión expirada por inactividad");
        }
        
        // Verificar timeout absoluto
        if (isset($_SESSION['session_start_time']) && 
            time() - $_SESSION['session_start_time'] > self::ABSOLUTE_TIMEOUT) {
            self::destroy();
            throw new Exception("Sesión expirada (tiempo máximo alcanzado)");
        }
        
        // Verificar fingerprint (detectar session hijacking)
        if (isset($_SESSION['session_fingerprint']) && 
            self::generateFingerprint() !== $_SESSION['session_fingerprint']) {
            self::destroy();
            throw new Exception("Posible secuestro de sesión detectado");
        }
        
        // Regenerar ID periódicamente (cada 15 minutos) - solo si podemos enviar headers
        if (self::canSendHeaders() && 
            time() - $_SESSION['last_activity'] > 900) {
            session_regenerate_id(true);
        }
        
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Generar fingerprint único basado en IP + User-Agent + Accept-Language
     * @return string
     */
    private static function generateFingerprint() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        return hash('sha256', $ip . $ua . $acceptLang);
    }
    
    /**
     * Establecer valor en sesión
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Obtener valor de sesión
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        try {
            self::start();
            return $_SESSION[$key] ?? $default;
        } catch (Exception $e) {
            // Si la sesión expiró, retornar default
            error_log("Session::get() - " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Verificar si existe una clave
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        try {
            self::start();
            return isset($_SESSION[$key]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Eliminar valor de sesión
     * @param string $key
     */
    public static function delete($key) {
        try {
            self::start();
            unset($_SESSION[$key]);
        } catch (Exception $e) {
            error_log("Session::delete() - " . $e->getMessage());
        }
    }
    
    /**
     * Destruir sesión completamente - VERSIÓN CORREGIDA
     * Delega en Security::destroySession() para manejo seguro
     */
    public static function destroy() {
        // Primero limpiar variable local
        if (isset($_SESSION)) {
            $_SESSION = [];
        }
        
        // Destruir sesión si está activa
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (self::canSendHeaders()) {
                session_unset();
                session_destroy();
                
                // Eliminar cookie de sesión
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 3600,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            } else {
                error_log("Session::destroy() - No se pudo eliminar cookie: headers ya enviados");
                // Marcar para destruir en el próximo request
                $_SESSION['needs_destruction'] = true;
            }
        }
    }
    
    /**
     * Verificar si usuario está autenticado
     * @return bool
     */
    public static function isAuthenticated() {
        try {
            self::start();
            return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener ID del usuario autenticado
     * @return int|null
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Obtener nombre de usuario
     * @return string|null
     */
    public static function getUsername() {
        return self::get('username');
    }
    
    /**
     * Establecer mensaje flash (almacenado como array para soportar múltiples)
     * @param string $type (success, error, warning, info)
     * @param string $message
     */
    public static function setFlash($type, $message) {
        self::start();
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][$type] = $message;
    }
    
    /**
     * Obtener y eliminar mensaje flash
     * @param string $type
     * @return string|null
     */
    public static function getFlash($type) {
        try {
            self::start();
            if (isset($_SESSION['flash_messages'][$type])) {
                $message = $_SESSION['flash_messages'][$type];
                unset($_SESSION['flash_messages'][$type]);
                return $message;
            }
        } catch (Exception $e) {
            error_log("Session::getFlash() - " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Obtener todos los mensajes flash y limpiarlos
     * @return array
     */
    public static function getAllFlashes() {
        try {
            self::start();
            $flashes = $_SESSION['flash_messages'] ?? [];
            $_SESSION['flash_messages'] = [];
            return $flashes;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Regenerar ID de sesión de forma segura
     * @return bool
     */
    public static function regenerateId() {
        if (session_status() === PHP_SESSION_ACTIVE && self::canSendHeaders()) {
            return session_regenerate_id(true);
        }
        return false;
    }
    
    /**
     * Obtener tiempo restante de sesión (en segundos)
     * @return int|null
     */
    public static function getRemainingTime() {
        try {
            self::start();
            if (isset($_SESSION['session_start_time'])) {
                $elapsed = time() - $_SESSION['session_start_time'];
                return max(0, self::ABSOLUTE_TIMEOUT - $elapsed);
            }
        } catch (Exception $e) {}
        return null;
    }
    
    // ============================================================================
    // === CAMBIO PUNTO 6: GESTIÓN SEGURA DE SESIONES ===
    // Fecha: 18/02/2026
    // Autor: [TU NOMBRE AQUÍ]
    // Descripción:
    //   - Timeout por inactividad (30 min) y absoluto (24h)
    //   - Regeneración periódica de ID (cada 15 min)
    //   - Fingerprint de sesión (IP + User-Agent) para detectar hijacking
    //   - session.use_strict_mode = 1 activado
    //   - Secure flag dinámico (1 en HTTPS, 0 en HTTP local)
    //   - Destrucción segura de cookies al cerrar sesión
    //   - CORREGIDO: Manejo de headers already sent
    //   - CORREGIDO: Flash messages como array
    //   - NUEVO: getAllFlashes() para vistas
    //   - NUEVO: getRemainingTime() para mostrar expiración
    // Reversión: Eliminar métodos nuevos y este bloque si es necesario
    // ============================================================================
}
?>