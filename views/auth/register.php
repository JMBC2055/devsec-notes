<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/views/auth/register.php
// DESCRIPCIÓN: Vista de registro de usuarios
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>🔐 Registro</h1>
            <p class="subtitle">Crea tu cuenta para comenzar</p>
            
            <?php
            // Mostrar mensajes flash
            if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success = Session::getFlash('success')): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form action="index.php?page=register-process" method="POST" class="auth-form">
                
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                
                <!-- Nombre de usuario -->
                <div class="form-group">
                    <label for="username">Nombre de usuario</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        minlength="3"
                        maxlength="50"
                        placeholder="Tu nombre de usuario"
                    >
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="tu@email.com"
                    >
                </div>
                
                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        minlength="8"
                        placeholder="Mínimo 8 caracteres"
                    >
                    <small class="help-text">
                        Debe contener: mayúsculas, minúsculas y números
                    </small>
                </div>
                
                <!-- Confirmar contraseña -->
                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        placeholder="Repite tu contraseña"
                    >
                </div>
                
                <!-- Botón submit -->
                <button type="submit" class="btn btn-primary btn-block">
                    Registrarse
                </button>
            </form>
            
            <p class="auth-footer">
                ¿Ya tienes cuenta? 
                <a href="index.php?page=login">Inicia sesión aquí</a>
            </p>
        </div>
    </div>
</body>
</html>