<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/views/tags/manage.php
// DESCRIPCIÓN: Página para crear y eliminar etiquetas
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Etiquetas - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>🏷️ Etiquetas</h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-secondary">Cerrar Sesión</a>
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

        <!-- Formulario nueva etiqueta -->
        <div class="form-container" style="max-width:500px; margin-bottom:2rem;">
            <h2 style="margin-bottom:1rem;">Nueva etiqueta</h2>
            <form action="index.php?page=store-tag" method="POST" class="note-form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">

                <div class="form-group" style="display:flex; gap:1rem; align-items:flex-end;">
                    <div style="flex:1;">
                        <label for="tag_name">Nombre *</label>
                        <input type="text" id="tag_name" name="tag_name" required
                               maxlength="50" placeholder="Ej: trabajo, personal, urgente">
                    </div>
                    <div>
                        <label for="tag_color">Color</label>
                        <input type="color" id="tag_color" name="tag_color"
                               value="#3B82F6" style="width:50px; height:38px; border:1px solid var(--border-color); border-radius:6px; cursor:pointer;">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">➕ Crear etiqueta</button>
                </div>
            </form>
        </div>

        <!-- Lista de etiquetas existentes -->
        <h2 style="margin-bottom:1rem;">Mis etiquetas</h2>

        <?php if (empty($allTags)): ?>
            <div class="empty-state">
                <p>🏷️ Aún no tienes etiquetas creadas</p>
            </div>
        <?php else: ?>
            <div class="tags-manage-grid">
                <?php foreach ($allTags as $t): ?>
                    <div class="tag-manage-card">
                        <div class="tag-manage-left">
                            <span class="tag-badge"
                                  style="background-color:<?= htmlspecialchars($t['color']) ?>;">
                                <?= htmlspecialchars($t['name']) ?>
                            </span>
                            <small style="color:var(--text-muted);">
                                <?= $t['note_count'] ?> nota<?= $t['note_count'] != 1 ? 's' : '' ?>
                            </small>
                        </div>
                        <div class="tag-manage-right">
                            <a href="index.php?page=dashboard&tag_id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-secondary">
                                🔍 Ver notas
                            </a>
                            <a href="index.php?page=delete-tag&id=<?= $t['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar la etiqueta \'<?= htmlspecialchars(addslashes($t['name'])) ?>\'? Se quitará de todas las notas.')">
                                🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro - DevSecOps</p>
    </footer>
</body>
</html>
