<?php
require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña — Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">

            <div class="auth-logo">
                <div class="auth-logo-icon">🔐</div>
            </div>

            <h1>Nueva contraseña</h1>
            <p class="subtitle">Elige una contraseña segura</p>

            <?php if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success = Session::getFlash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="index.php?page=reset-password-process" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password"
                           required minlength="8" placeholder="Mínimo 8 caracteres" autofocus>
                    <small class="help-text">Debe incluir mayúsculas, minúsculas y números</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar contraseña</label>
                    <input type="password" id="password_confirm" name="confirm_password"
                           required placeholder="Repite la contraseña">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Cambiar contraseña
                </button>
            </form>

            <p class="auth-footer">
                <a href="index.php?page=login">← Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
