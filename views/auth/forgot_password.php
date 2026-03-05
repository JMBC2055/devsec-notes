<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/views/auth/forgot_password.php
// DESCRIPCIÓN: Formulario para solicitar recuperación de contraseña
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">

            <h1>🔑 Recuperar Contraseña</h1>
            <p class="subtitle">Ingresa tu email y te enviaremos un enlace para crear una nueva contraseña</p>

            <?php if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success = Session::getFlash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="index.php?page=forgot-password-process" method="POST" class="auth-form">

                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

                <div class="form-group">
                    <label for="email">Email registrado</label>
                    <input type="email" id="email" name="email" required
                           placeholder="tu@email.com" autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    📧 Enviar enlace de recuperación
                </button>

            </form>

            <p class="auth-footer">
                <a href="index.php?page=login">← Volver al login</a>
            </p>

            <!-- Nota para desarrollo local -->
            <div class="demo-credentials" style="margin-top:1rem;">
                <small><strong>⚙️ Modo local (XAMPP):</strong></small><br>
                <small>Si el email no llega, copia el token del archivo de log:</small><br>
                <small><code>C:/xampp/php/logs/php_error_log</code></small><br>
                <small>Busca la línea: <code>[PASSWORD_RESET]</code></small>
            </div>

        </div>
    </div>
</body>
</html>
