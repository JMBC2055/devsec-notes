# Requisitos de Seguridad - Gestor de Notas

## Tipos de datos
- **Personales**: Nombres, correos electrónicos, direcciones IP.
- **Sensibles**: Contraseñas (hash), historial de cambios en notas.

## Acceso y permisos
| Rol       | Acceso a notas | Puede eliminar | Puede editar |
|-----------|----------------|----------------|--------------|
| `user`    | Solo sus notas | Sí             | Sí           |
| `admin`   | Todas las notas| Sí             | Sí           |

## Impacto de fallos
| Tipo de fallo         | Impacto esperado                     |
|-----------------------|------------------------------------|
| Confidencialidad      | Exposición de correos y contraseñas|
| Integridad            | Notas alteradas por usuarios no autorizados |
| Disponibilidad        | Caída del sistema por DDoS/ataques |
