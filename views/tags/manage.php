<?php
require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas — Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tags.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>
                <span style="width:32px;height:32px;background:linear-gradient(135deg,#C9A84C,#E2C47A);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;">🏷️</span>
                Etiquetas
            </h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-sm btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-sm btn-secondary">Salir</a>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top:2rem;">

        <?php if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success = Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Nueva etiqueta -->
        <div class="form-container" style="max-width:520px; margin-bottom:2.5rem;">
            <h2 style="font-family:'Playfair Display',Georgia,serif; font-size:1.2rem; font-weight:600; color:var(--cream); margin-bottom:1.25rem; letter-spacing:-0.01em;">
                Nueva etiqueta
            </h2>
            <form action="index.php?page=store-tag" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

                <div style="display:flex; gap:1rem; align-items:flex-end;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label for="tag_name">Nombre</label>
                        <input type="text" id="tag_name" name="tag_name" required
                               maxlength="50" placeholder="trabajo, personal, urgente…">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="tag_color">Color</label>
                        <input type="color" id="tag_color" name="tag_color"
                               value="#C9A84C"
                               style="width:50px; height:42px; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer; background:transparent; padding:2px;">
                    </div>
                </div>

                <div class="form-actions" style="margin-top:1.25rem;">
                    <button type="submit" class="btn btn-primary">+ Crear etiqueta</button>
                </div>
            </form>
        </div>

        <!-- Lista de etiquetas -->
        <h2 style="font-family:'Playfair Display',Georgia,serif; font-size:1.2rem; font-weight:600; color:var(--cream); margin-bottom:1rem; letter-spacing:-0.01em;">
            Mis etiquetas
            <?php if (!empty($allTags)): ?>
                <span style="font-family:'DM Sans',sans-serif; font-size:0.8rem; font-weight:400; color:var(--muted); margin-left:8px;">
                    <?= count($allTags) ?> en total
                </span>
            <?php endif; ?>
        </h2>

        <?php if (empty($allTags)): ?>
            <div class="empty-state" style="max-width:520px;">
                <div style="font-size:2.5rem; margin-bottom:1rem; opacity:0.4;">🏷️</div>
                <p>Aún no tienes etiquetas creadas</p>
            </div>
        <?php else: ?>
            <div class="tags-manage-grid" style="max-width:700px;">
                <?php foreach ($allTags as $t): ?>
                    <div class="tag-manage-card">
                        <div class="tag-manage-left">
                            <span class="tag-badge"
                                  style="background-color:<?= htmlspecialchars($t['color']) ?>; font-size:0.78rem; padding:3px 12px;">
                                <?= htmlspecialchars($t['name']) ?>
                            </span>
                            <small style="color:var(--muted); font-size:0.8rem;">
                                <?= $t['note_count'] ?> nota<?= $t['note_count'] != 1 ? 's' : '' ?>
                            </small>
                        </div>
                        <div class="tag-manage-right">
                            <a href="index.php?page=dashboard&tag_id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-secondary">
                                Ver notas
                            </a>
                            <a href="index.php?page=delete-tag&id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar la etiqueta \'<?= htmlspecialchars(addslashes($t['name'])) ?>\'? Se quitará de todas las notas.')">
                                🗑️
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro</p>
    </footer>
</body>
</html>
