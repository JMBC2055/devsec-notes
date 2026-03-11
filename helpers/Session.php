<?php
// ============================================================================
// UBICACIÓN: /app/helpers/Session.php (o la ruta correspondiente)
// DESCRIPCIÓN: Manejo seguro de sesiones - CORREGIDO para Railway
// VERSIÓN: 2.0 - Compatible con Railway
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
     * Detectar si estamos en entorno Railway
     * @return bool
     */
    private static function isRailway() {
        return getenv('RAILWAY_ENVIRONMENT') !== false || 
               getenv('RAILWAY_SERVICE_NAME') !== false ||
               (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']));
    }
    
    /**
     * Verificar si la IP está en el rango de Railway
     * @param string $ip
     * @return bool
     */
    private static function isRailwayIP($ip) {
        // Rango de Railway: 100.64.0.0/10
        if (strpos($ip, '100.64.') === 0 || strpos($ip, '100.65.') === 0 || 
            strpos($ip, '100.66.') === 0 || strpos($ip, '100.67.') === 0 ||
            strpos($ip, '100.68.') === 0 || strpos($ip, '100.69.') === 0 ||
            strpos($ip, '100.70.') === 0 || strpos($ip, '100.71.') === 0 ||
            strpos($ip, '100.72.') === 0 || strpos($ip, '100.73.') === 0 ||
            strpos($ip, '100.74.') === 0 || strpos($ip, '100.75.') === 0 ||
            strpos($ip, '100.76.') === 0 || strpos($ip, '100.77.') === 0 ||
            strpos($ip, '100.78.') === 0 || strpos($ip, '100.79.') === 0 ||
            strpos($ip, '100.80.') === 0 || strpos($ip, '100.81.') === 0 ||
            strpos($ip, '100.82.') === 0 || strpos($ip, '100.83.') === 0 ||
            strpos($ip, '100.84.') === 0 || strpos($ip, '100.85.') === 0 ||
            strpos($ip, '100.86.') === 0 || strpos($ip, '100.87.') === 0 ||
            strpos($ip, '100.88.') === 0 || strpos($ip, '100.89.') === 0 ||
            strpos($ip, '100.90.') === 0 || strpos($ip, '100.91.') === 0 ||
            strpos($ip, '100.92.') === 0 || strpos($ip, '100.93.') === 0 ||
            strpos($ip, '100.94.') === 0 || strpos($ip, '100.95.') === 0 ||
            strpos($ip, '100.96.') === 0 || strpos($ip, '100.97.') === 0 ||
            strpos($ip, '100.98.') === 0 || strpos($ip, '100.99.') === 0 ||
            strpos($ip, '100.100.') === 0 || strpos($ip, '100.101.') === 0 ||
            strpos($ip, '100.102.') === 0 || strpos($ip, '100.103.') === 0 ||
            strpos($ip, '100.104.') === 0 || strpos($ip, '100.105.') === 0 ||
            strpos($ip, '100.106.') === 0 || strpos($ip, '100.107.') === 0 ||
            strpos($ip, '100.108.') === 0 || strpos($ip, '100.109.') === 0 ||
            strpos($ip, '100.110.') === 0 || strpos($ip, '100.111.') === 0 ||
            strpos($ip, '100.112.') === 0 || strpos($ip, '100.113.') === 0 ||
            strpos($ip, '100.114.') === 0 || strpos($ip, '100.115.') === 0 ||
            strpos($ip, '100.116.') === 0 || strpos($ip, '100.117.') === 0 ||
            strpos($ip, '100.118.') === 0 || strpos($ip, '100.119.') === 0 ||
            strpos($ipmessage: , '100.120.') === 0 || strpos($ip, '100.121.') === 0 ||
            strpos($ip, '100.122.') === 0 || strpos($ip, '100.123.') === 0 ||
            strpos($ip, '100.124.') === 0 || strpos($ip, '100.125.') === 0 ||
            strpos($ip, '100.126.') === 0 || strpos($ip, '100.127.') === 0) {
            return true;
        }
        
        // También permitir IPs locales y de desarrollo
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']);
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
            
            // === CONFIGURACIÓN SEGURA DE SESIÓN ===
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
     * Generar fingerprint único basado en IP + User-Agent + Accept-Language
     * @return string
     */
    private static function generateFingerprint() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        // En Railway, la IP puede ser proxy, usar X-Forwarded-For si existe
        if (self::isRailway() && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwardedIps[0]); // Primera IP es la real del cliente
        }
        
        return hash('sha256', $ip . $ua . $acceptLang);
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
        
        // ===== VERIFICACIÓN DE FINGERPRINT CORREGIDA PARA RAILWAY =====
        if (isset($_SESSION['session_fingerprint'])) {
            $currentFingerprint = self::generateFingerprint();
            
            // Si estamos en Railway y el fingerprint no coincide, verificar si es por cambio de IP
            if (self::isRailway() && $currentFingerprint !== $_SESSION['session_fingerprint']) {
                // En Railway, permitimos cambios de IP dentro del mismo rango
                $oldIp = $_SESSION['original_ip'] ?? null;
                $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
                
                // Si no tenemos la IP original guardada, la guardamos
                if (!isset($_SESSION['original_ip'])) {
                    $_SESSION['original_ip'] = $currentIp;
                    $_SESSION['session_fingerprint'] = $currentFingerprint;
                }
                // Si la IP actual está en rango de Railway, permitimos el acceso
                else if (self::isRailwayIP($currentIp)) {
                    // Actualizamos el fingerprint pero mantenemos la sesión
                    error_log("Session: IP cambiada en Railway de " . ($_SESSION['original_ip'] ?? 'unknown') . " a $currentIp - Permitido");
                    $_SESSION['session_fingerprint'] = $currentFingerprint;
                }
                // Si no es Railway IP, entonces sí es posible hijacking
                else {
                    self::destroy();
                    throw new Exception("Posible secuestro de sesión detectado");
                }
            }
            // Si no estamos en Railway, validación estricta normal
            else if (!self::isRailway() && $currentFingerprint !== $_SESSION['session_fingerprint']) {
                self::destroy();
                throw new Exception("Posible secuestro de sesión detectado");
            }
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
    // === CAMBIO: VERSIÓN COMPATIBLE CON RAILWAY ===
    // Fecha: 11/03/2026
    // Cambios realizados:
    //   - Agregada detección de entorno Railway
    //   - Validación flexible de IP en Railway (permite rango 100.64.0.0/10)
    //   - Manejo de X-Forwarded-For para IP real del cliente
    //   - No lanza error de hijacking por cambios de IP en Railway
    //   - Guarda IP original para tracking
    // ============================================================================
}
?>