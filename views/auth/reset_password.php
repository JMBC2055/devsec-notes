<?php
// ============================================================================
// UBICACIÓN: gestor-notas/views/auth/reset_password.php
// DESCRIPCIÓN: Formulario para ingresar la nueva contraseña
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">

            <h1>🔒 Nueva Contraseña</h1>
            <p class="subtitle">
                Hola <strong><?= htmlspecialchars($resetData['username'] ?? '') ?></strong>,
                elige una contraseña segura para tu cuenta
            </p>

            <?php if ($error = Session::getFlash('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="index.php?page=reset-password-process" method="POST" class="auth-form">

                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="token"      value="<?= htmlspecialchars($token ?? '') ?>">

                <!-- Nueva contraseña -->
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password"
                           required placeholder="Mínimo 8 caracteres" autofocus>
                    <small style="color:var(--text-muted);">
                        Debe tener: mayúsculas, minúsculas y números
                    </small>
                </div>

                <!-- Confirmar contraseña -->
                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           required placeholder="Repite la contraseña">
                </div>

                <!-- Indicador visual de fortaleza -->
                <div class="form-group">
                    <div id="password-strength" style="height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden; margin-top:-8px;">
                        <div id="strength-bar" style="height:100%; width:0; transition:width 0.3s, background 0.3s;"></div>
                    </div>
                    <small id="strength-text" style="color:var(--text-muted);"></small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    💾 Guardar nueva contraseña
                </button>

            </form>

            <p class="auth-footer">
                <a href="index.php?page=login">← Volver al login</a>
            </p>

        </div>
    </div>

    <script>
    document.getElementById('password').addEventListener('input', function () {
        const val  = this.value;
        const bar  = document.getElementById('strength-bar');
        const text = document.getElementById('strength-text');

        let score = 0;
        if (val.length >= 8)          score++;
        if (/[A-Z]/.test(val))        score++;
        if (/[a-z]/.test(val))        score++;
        if (/[0-9]/.test(val))        score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { label: '',           color: '#e5e7eb', pct: '0%'   },
            { label: 'Muy débil',  color: '#ef4444', pct: '20%'  },
            { label: 'Débil',      color: '#f97316', pct: '40%'  },
            { label: 'Regular',    color: '#eab308', pct: '60%'  },
            { label: 'Fuerte',     color: '#22c55e', pct: '80%'  },
            { label: 'Muy fuerte', color: '#16a34a', pct: '100%' },
        ];

        bar.style.width      = levels[score].pct;
        bar.style.background = levels[score].color;
        text.textContent     = levels[score].label;
        text.style.color     = levels[score].color;
    });
    </script>
</body>
</html>