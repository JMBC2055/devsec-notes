<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/helpers/Session.php
// DESCRIPCIÓN: Manejo seguro de sesiones
// ============================================================================

class Session {
    
    /**
     * Iniciar sesión segura
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            
            // Configuración segura de sesión
            ini_set('session.cookie_httponly', 1);  // No accesible por JavaScript
            ini_set('session.use_only_cookies', 1); // Solo usar cookies
            ini_set('session.cookie_secure', 0);    // 0 para HTTP, 1 para HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Prevenir session fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
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
        self::start();
        session_unset();
        session_destroy();
        
        // Eliminar cookie de sesión
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
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
}
?>