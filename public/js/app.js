// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/public/js/app.js
// DESCRIPCIÓN: JavaScript para funcionalidades adicionales
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ==================== AUTO-HIDE ALERTS ====================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000); // 5 segundos
    });
    
    
    // ==================== CONFIRMACIÓN DE ELIMINACIÓN ====================
    const deleteLinks = document.querySelectorAll('a[href*="delete-note"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('⚠️ ¿Estás seguro de eliminar esta nota? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    
    // ==================== CONTADOR DE CARACTERES ====================
    const titleInput = document.getElementById('title');
    if (titleInput) {
        const maxLength = titleInput.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'help-text';
        counter.style.float = 'right';
        titleInput.parentElement.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - titleInput.value.length;
            counter.textContent = `${remaining} caracteres restantes`;
            counter.style.color = remaining < 20 ? 'var(--danger-color)' : 'var(--text-muted)';
        }
        
        titleInput.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    
    // ==================== VALIDACIÓN DE CONTRASEÑAS ====================
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput && confirmPasswordInput) {
        const form = passwordInput.closest('form');
        
        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                confirmPasswordInput.focus();
            }
        });
        
        // Indicador de fortaleza de contraseña
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength';
        strengthIndicator.style.marginTop = '0.5rem';
        passwordInput.parentElement.appendChild(strengthIndicator);
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    message = '🔴 Débil';
                    strengthIndicator.style.color = 'var(--danger-color)';
                    break;
                case 2:
                case 3:
                    message = '🟡 Media';
                    strengthIndicator.style.color = 'var(--warning-color)';
                    break;
                case 4:
                case 5:
                    message = '🟢 Fuerte';
                    strengthIndicator.style.color = 'var(--success-color)';
                    break;
            }
            
            strengthIndicator.textContent = password ? `Fortaleza: ${message}` : '';
        });
    }
    
    
    // ==================== BÚSQUEDA EN TIEMPO REAL (OPCIONAL) ====================
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Aquí podrías implementar búsqueda AJAX en tiempo real
                console.log('Buscando:', this.value);
            }, 500);
        });
    }
    
    
    // ==================== ANIMACIONES DE ENTRADA ====================
    const noteCards = document.querySelectorAll('.note-card');
    noteCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
});


// ==================== FUNCIONES GLOBALES ====================

/**
 * Formatear fecha
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('es-ES', options);
}

/**
 * Copiar al portapapeles
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Copiado al portapapeles');
    }).catch(err => {
        console.error('Error al copiar:', err);
    });
}