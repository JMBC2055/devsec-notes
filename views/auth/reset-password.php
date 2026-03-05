<?php
require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0 <title>Restablecer contraseña - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>🔄 Restablecer contraseña</h1>
            <p class="subtitle">Ingresa tu nueva contraseña</p>

            <?php if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="index.php?page=reset-password-process" method="POST" class="auth-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Mínimo 8 caracteres"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        placeholder="Repite la contraseña"
                    >
                </div>

                <button type="submit" class="btn btn-success btn-block">
                    Restablecer contraseña
                </button>
            </form>

            <p class="auth-footer">
                <a href="index.php?page=login">Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
