<?php
require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña — Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">

            <div class="auth-logo">
                <div class="auth-logo-icon">🔑</div>
            </div>

            <h1>Recuperar acceso</h1>
            <p class="subtitle">Enviaremos un enlace a tu correo</p>

            <?php if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success = Session::getFlash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="index.php?page=forgot-password-process" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

                <div class="form-group">
                    <label for="email">Email de tu cuenta</label>
                    <input type="email" id="email" name="email" required
                           placeholder="tu@email.com" autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Enviar enlace de recuperación
                </button>
            </form>

            <p class="auth-footer">
                <a href="index.php?page=login">← Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
