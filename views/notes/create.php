<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/views/notes/create.php
// DESCRIPCIÓN: Formulario para crear nueva nota
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Nota - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <h1>📝 Nueva Nota</h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-secondary">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main class="container">
        
        <?php if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form action="index.php?page=store-note" method="POST" class="note-form">
                
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                
                <!-- Título -->
                <div class="form-group">
                    <label for="title">Título *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        required 
                        maxlength="200"
                        placeholder="Título de la nota"
                        autofocus
                    >
                </div>
                
                <!-- Contenido -->
                <div class="form-group">
                    <label for="content">Contenido *</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        required 
                        rows="10"
                        placeholder="Escribe aquí el contenido de tu nota..."
                    ></textarea>
                </div>
                
                <!-- Recordatorio -->
                <div class="form-group">
                    <label for="reminder_date">Recordatorio (opcional)</label>
                    <input 
                        type="datetime-local" 
                        id="reminder_date" 
                        name="reminder_date"
                    >
                </div>
                
                <!-- Marcar como favorito -->
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_favorite" value="1">
                        <span>⭐ Marcar como favorito</span>
                    </label>
                </div>
                
                <!-- Botones -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar Nota
                    </button>
                    <a href="index.php?page=dashboard" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
                
            </form>
        </div>
        
    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro - DevSecOps</p>
    </footer>
</body>
</html>