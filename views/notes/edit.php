<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/views/notes/edit.php
// DESCRIPCIÓN: Formulario para editar nota existente (con etiquetas)
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Nota - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1>✏️ Editar Nota</h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-secondary">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="container">

        <?php if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form action="index.php?page=update-note" method="POST" class="note-form">

                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">

                <!-- Título -->
                <div class="form-group">
                    <label for="title">Título *</label>
                    <input type="text" id="title" name="title" required
                           maxlength="200" value="<?= htmlspecialchars($note['title']) ?>" autofocus>
                </div>

                <!-- Contenido -->
                <div class="form-group">
                    <label for="content">Contenido *</label>
                    <textarea id="content" name="content" required
                              rows="10"><?= htmlspecialchars($note['content']) ?></textarea>
                </div>

                <!-- Etiquetas -->
                <div class="form-group">
                    <label>🏷️ Etiquetas
                        <small style="font-weight:normal; color:var(--text-muted);">
                            — <a href="index.php?page=manage-tags">Gestionar etiquetas</a>
                        </small>
                    </label>
                    <div class="tags-selector">
                        <?php if (empty($allTags)): ?>
                            <span class="tags-selector-empty">
                                No tienes etiquetas aún.
                                <a href="index.php?page=manage-tags">Crear una</a>
                            </span>
                        <?php else: ?>
                            <?php foreach ($allTags as $t): ?>
                                <label>
                                    <input type="checkbox" name="tag_ids[]" value="<?= $t['id'] ?>"
                                        <?= in_array($t['id'], $noteTagIds) ? 'checked' : '' ?>>
                                    <span class="tag-badge" style="background-color:<?= htmlspecialchars($t['color']) ?>;">
                                        <?= htmlspecialchars($t['name']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recordatorio -->
                <div class="form-group">
                    <label for="reminder_date">Recordatorio (opcional)</label>
                    <input type="datetime-local" id="reminder_date" name="reminder_date"
                           value="<?= $note['reminder_date'] ? date('Y-m-d\TH:i', strtotime($note['reminder_date'])) : '' ?>">
                </div>

                <!-- Favorito -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_favorite" value="1"
                               <?= $note['is_favorite'] ? 'checked' : '' ?>>
                        <span>⭐ Marcar como favorito</span>
                    </label>
                </div>

                <!-- Info -->
                <div class="note-info">
                    <small>
                        <strong>Creada:</strong> <?= date('d/m/Y H:i', strtotime($note['created_at'])) ?><br>
                        <?php if ($note['updated_at']): ?>
                            <strong>Última actualización:</strong> <?= date('d/m/Y H:i', strtotime($note['updated_at'])) ?>
                        <?php endif; ?>
                    </small>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Actualizar Nota</button>
                    <a href="index.php?page=dashboard" class="btn btn-secondary">Cancelar</a>
                    <a href="index.php?page=delete-note&id=<?= $note['id'] ?>"
                       class="btn btn-danger"
                       onclick="return confirm('¿Eliminar esta nota permanentemente?')">
                        🗑️ Eliminar
                    </a>
                </div>

            </form>
        </div>

    </main>

    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro - DevSecOps</p>
    </footer>
</body>
</html>
