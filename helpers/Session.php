<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/helpers/Session.php
// DESCRIPCIÓN: Manejo seguro de sesiones
// ============================================================================

class Session {
    
    // Tiempos de expiración (en segundos)
    const INACTIVE_TIMEOUT = 1800;   // 30 minutos de inactividad
    const ABSOLUTE_TIMEOUT = 86400;  // 24 horas máximo desde inicio
    
    /**
     * Iniciar sesión segura
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            
            // === CONFIGURACIÓN SEGURA DE SESIÓN (Punto 6 del PDF) ===
            // HttpOnly: impide acceso desde JavaScript (XSS)
            ini_set('session.cookie_httponly', 1);
            
            // Solo cookies: evita session fixation vía URL
            ini_set('session.use_only_cookies', 1);
            
            // Strict mode: rechaza IDs de sesión no generados por el servidor
            ini_set('session.use_strict_mode', 1);
            
            // Secure: solo por HTTPS en producción
            // ⚠️ Cambiar a 1 cuando se use HTTPS en producción
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            ini_set('session.cookie_secure', $isSecure ? 1 : 0);
            
            // SameSite: previene CSRF
            ini_set('session.cookie_samesite', 'Strict');
            
            // Ruta de cookie restringida
            ini_set('session.cookie_path', '/');
            
            session_start();
            
            // Prevenir session fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['session_start_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Guardar fingerprint para detectar hijacking (IP + User-Agent)
                $_SESSION['session_fingerprint'] = self::generateFingerprint();
            } else {
                // Verificar timeout de inactividad
                if (time() - $_SESSION['last_activity'] > self::INACTIVE_TIMEOUT) {
                    self::destroy();
                    throw new Exception("Sesión expirada por inactividad");
                }
                
                // Verificar timeout absoluto
                if (time() - $_SESSION['session_start_time'] > self::ABSOLUTE_TIMEOUT) {
                    self::destroy();
                    throw new Exception("Sesión expirada (tiempo máximo alcanzado)");
                }
                
                // Verificar fingerprint (detectar session hijacking)
                if (self::generateFingerprint() !== ($_SESSION['session_fingerprint'] ?? '')) {
                    self::destroy();
                    throw new Exception("Posible secuestro de sesión detectado");
                }
                
                // Regenerar ID periódicamente (cada 15 minutos)
                if (time() - $_SESSION['last_activity'] > 900) {
                    session_regenerate_id(true);
                }
                
                // Actualizar última actividad
                $_SESSION['last_activity'] = time();
            }
        }
    }
    
    /**
     * Generar fingerprint único basado en IP + User-Agent
     * @return string
     */
    private static function generateFingerprint() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . $ua . $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
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
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Verificar si existe una clave
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Eliminar valor de sesión
     * @param string $key
     */
    public static function delete($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Destruir sesión completamente
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
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
        }
    }
    
    /**
     * Verificar si usuario está autenticado
     * @return bool
     */
    public static function isAuthenticated() {
        return self::has('user_id');
    }
    
    /**
     * Establecer mensaje flash
     * @param string $type (success, error, warning, info)
     * @param string $message
     */
    public static function setFlash($type, $message) {
        self::set('flash_' . $type, $message);
    }
    
    /**
     * Obtener y eliminar mensaje flash
     * @param string $type
     * @return string|null
     */
    public static function getFlash($type) {
        $message = self::get('flash_' . $type);
        self::delete('flash_' . $type);
        return $message;
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
    // Reversión: Eliminar métodos nuevos y este bloque si es necesario
    // ============================================================================
}
?>
