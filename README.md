# 📝 Gestor de Notas Seguro - Enfoque DevSecOps

Sistema de gestión de notas web con enfoque en seguridad desde el diseño.

## 🚀 Características

- ✅ Autenticación segura (bcrypt)
- ✅ Protección CSRF
- ✅ Prevención SQL Injection (PDO)
- ✅ Sanitización XSS
- ✅ Sistema de logs de seguridad
- ✅ Historial de cambios
- ✅ Búsqueda de notas
- ✅ Favoritos y archivado
- ✅ Recordatorios

## 📋 Requisitos

- PHP 7.4+
- MySQL 5.7+
- Apache (XAMPP)
- Extensiones PHP: PDO, pdo_mysql

## ⚙️ Instalación

### 1. Clonar/Copiar proyecto
```bash
# Copiar a la carpeta htdocs de XAMPP
C:/xampp/htdocs/gestor-notas/
```

### 2. Crear base de datos
```bash
# Abrir phpMyAdmin
http://localhost/phpmyadmin

# Ejecutar las tablas que ya tienes creadas
# (users, notes, tags, note_tags, note_history, security_logs)
```

### 3. Configurar base de datos
```php
// Editar: config/database.php
private $host = "localhost";
private $db_name = "devsec_notes";
private $username = "root";
private $password = "";  // Tu contraseña de MySQL
```

### 4. Acceder al sistema
```
http://localhost/gestor-notas/public/index.php
```

### 5. Credenciales de prueba
```
Email: admin@gestor.local
Password: Test123!
```

## 📁 Estructura del Proyecto
```
gestor-notas/
├── config/
│   └── database.php
├── controllers/
│   ├── AuthController.php
│   └── NoteController.php
├── helpers/
│   ├── Security.php
│   ├── Validator.php
│   └── Session.php
├── models/
│   ├── User.php
│   └── Note.php
├── views/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   └── notes/
│       ├── index.php
│       ├── create.php
│       ├── edit.php
│       └── search.php
├── public/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js
│   └── index.php
└── .htaccess
```

## 🔒 Seguridad Implementada

### Autenticación
- Hash de contraseñas con bcrypt (cost 12)
- Control de intentos fallidos
- Bloqueo temporal de cuentas
- Regeneración de ID de sesión

### Protección de Datos
- Prepared Statements (PDO)
- Sanitización de entradas
- Validación de datos
- Tokens CSRF

### Auditoría
- Registro de eventos de seguridad
- Historial de cambios en notas
- Tracking de IP y User Agent

## 🛠️ Uso del Sistema

### Registro
1. Ir a `http://localhost/gestor-notas/public/index.php?page=register`
2. Completar formulario
3. Contraseña debe tener: mayúsculas, minúsculas, números

### Login
1. Usar email y contraseña
2. Máximo 5 intentos fallidos
3. Bloqueo de 15 minutos tras exceder límite

### Crear Nota
1. Click en "Nueva Nota"
2. Completar título y contenido
3. Opcionalmente: marcar favorito, agregar recordatorio

### Buscar
1. Usar barra de búsqueda
2. Busca en título y contenido

## 📊 Tablas de Base de Datos

### users
- Información de usuarios
- Control de acceso

### notes
- Contenido de notas
- Metadatos

### tags
- Etiquetas de organización

### note_tags
- Relación notas-etiquetas

### note_history
- Auditoría de cambios

### security_logs
- Eventos de seguridad

## 🎯 Próximas Mejoras

- [ ] Sistema de etiquetas completo
- [ ] Exportar notas a PDF
- [ ] Compartir notas
- [ ] Modo oscuro
- [ ] API REST
- [ ] Notificaciones de recordatorios

## 👨‍💻 Autor

Proyecto DevSecOps - 2024

## 📄 Licencia

Este proyecto es de código abierto para fines educativos.
```

---

## ✅ **RESUMEN FINAL - ARCHIVOS CREADOS**

### **Total: 19 archivos**

1. `.htaccess` - Configuración Apache
2. `config/database.php` - Conexión BD
3. `helpers/Security.php` - Seguridad
4. `helpers/Validator.php` - Validación
5. `helpers/Session.php` - Sesiones
6. `models/User.php` - Modelo Usuario
7. `models/Note.php` - Modelo Nota
8. `controllers/AuthController.php` - Auth
9. `controllers/NoteController.php` - Notas
10. `public/index.php` - Router principal
11. `views/auth/register.php` - Registro
12. `views/auth/login.php` - Login
13. `views/notes/index.php` - Dashboard
14. `views/notes/create.php` - Crear nota
15. `views/notes/edit.php` - Editar nota
16. `views/notes/search.php` - Búsqueda
17. `public/css/style.css` - Estilos
18. `public/js/app.js` - JavaScript
19. `README.md` - Documentación

---

## 🎯 **PASOS PARA USAR**

1. **Crea las carpetas:**
```
C:/xampp/htdocs/gestor-notas/
    config/
    controllers/
    helpers/
    models/
    views/auth/
    views/notes/
    public/css/
    public/js/