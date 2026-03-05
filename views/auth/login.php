<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/views/auth/login.php
// DESCRIPCIÓN: Vista de login (actualizada con enlace de recuperación)
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>📝 Gestor de Notas</h1>
            <p class="subtitle">Inicia sesión para continuar</p>

            <?php if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success = Session::getFlash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="index.php?page=login-process" method="POST" class="auth-form">

                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                           placeholder="tu@email.com" autofocus>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label for="password">Contraseña</label>
                        <a href="index.php?page=forgot-password"
                           style="font-size:0.82rem; color:var(--primary-color);">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    <input type="password" id="password" name="password"
                           required placeholder="Tu contraseña">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Iniciar Sesión
                </button>
            </form>

            <p class="auth-footer">
                ¿No tienes cuenta?
                <a href="index.php?page=register">Regístrate aquí</a>
            </p>

            <div class="demo-credentials">
                <small><strong>Cuenta de prueba:</strong></small><br>
                <small>Email: admin@gestor.local</small><br>
                <small>Password: Test123!</small>
            </div>
        </div>
    </div>
</body>
</html>
