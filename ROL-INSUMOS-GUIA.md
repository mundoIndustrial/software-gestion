# Guía del Rol Insumos

## Descripción General

El rol de **Insumos** es responsable de la gestión y control de materiales y recursos utilizados en la producción. Este rol tiene acceso a un dashboard dedicado y herramientas para gestionar el inventario.

## Características del Módulo

### 1. Dashboard de Insumos
- **Ubicación:** `/insumos/dashboard`
- **Funcionalidades:**
  - Vista general del estado de materiales
  - Materiales en stock
  - Alertas de bajo stock
  - Últimas entradas registradas
  - Acceso rápido al control de materiales

### 2. Control de Materiales
- **Ubicación:** `/insumos/materiales`
- **Funcionalidades:**
  - Listar todos los materiales registrados
  - Agregar nuevos materiales
  - Editar información de materiales
  - Eliminar materiales
  - Búsqueda y filtrado
  - Ver historial de movimientos

## Acceso al Módulo

### Requisitos
- Tener un usuario con rol `insumos`
- Estar autenticado en el sistema

### Pasos para Acceder

1. **Login:**
   - Ingresar al sistema con tus credenciales
   - Usuario con rol asignado como "insumos"

2. **Acceso al Dashboard:**
   - Una vez autenticado, navega a `/insumos/dashboard`
   - O accede desde el menú lateral si está disponible

3. **Control de Materiales:**
   - Haz clic en "Control de Materiales" en el sidebar
   - O navega directamente a `/insumos/materiales`

## Estructura del Menú

```
Insumos (Sidebar)
├── Dashboard
│   └── Vista general del estado
└── Insumos
    └── Control de Materiales
        ├── Ver todos
        ├── Agregar nuevo
        ├── Editar
        └── Eliminar
```

## Funcionalidades Principales

### Dashboard
- **Tarjetas de Información:**
  - Materiales en Stock
  - Bajo Stock (alertas)
  - Últimas Entradas
  - Control de Materiales

### Control de Materiales
- **Tabla con Columnas:**
  - Código del material
  - Nombre del material
  - Cantidad disponible
  - Unidad de medida
  - Precio unitario
  - Proveedor
  - Acciones (editar, eliminar)

## Creación de Usuario con Rol Insumos

### Opción 1: A través del Panel de Administración
1. Ir a Gestión de Usuarios
2. Crear nuevo usuario
3. Asignar rol "insumos"
4. Guardar cambios

### Opción 2: Por Base de Datos
```sql
INSERT INTO users (name, email, password, role, email_verified_at, created_at, updated_at)
VALUES (
    'Usuario Insumos',
    'insumos@mundoindustrial.co',
    BCRYPT('contraseña'),
    'insumos',
    NOW(),
    NOW(),
    NOW()
);
```

### Opción 3: Artisan Command
```bash
php artisan tinker
>>> User::create(['name' => 'Usuario Insumos', 'email' => 'insumos@mundoindustrial.co', 'role' => 'insumos', 'password' => Hash::make('contraseña')])
```

## Permisos del Rol

| Función | Permiso |
|---------|---------|
| Ver Dashboard | ✓ |
| Ver Materiales | ✓ |
| Agregar Materiales | ✓ |
| Editar Materiales | ✓ |
| Eliminar Materiales | ✓ |
| Ver Reportes | ✓ |

## Middleware de Protección

El acceso al módulo está protegido por:
- **Autenticación:** `auth` - Usuario debe estar logueado
- **Autorización:** `InsumosAccess` - Usuario debe tener rol 'insumos'

Cualquier intento de acceso no autorizado resultará en error 403.

## Rutas Disponibles

| Ruta | Método | Controlador | Nombre |
|------|--------|-------------|--------|
| `/insumos/dashboard` | GET | InsumosController@dashboard | insumos.dashboard |
| `/insumos/materiales` | GET | InsumosController@materiales | insumos.materiales.index |

## Soporte Técnico

Para reportar problemas o solicitar nuevas funcionalidades, contacta al equipo de desarrollo.

---
**Última actualización:** 28 de Noviembre, 2025
