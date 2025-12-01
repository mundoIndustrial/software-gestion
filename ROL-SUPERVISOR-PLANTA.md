# 🏭 ROL SUPERVISOR DE PLANTA - GUÍA COMPLETA

## 📋 Descripción General

El rol **supervisor_planta** es un rol de acceso completo que combina:
- ✅ Acceso a TODAS las funciones del sistema
- ✅ Acceso completo al módulo de **Insumos**
- ✅ Supervisión de producción en tiempo real
- ✅ Gestión de órdenes y pedidos
- ✅ Administración de entregas

## 🎯 Características del Rol

### Acceso Completo a TODO el Sistema
- ✅ Dashboard completo
- ✅ Gestión de Órdenes (Pedidos)
- ✅ Gestión de Bodega
- ✅ Entregas (Pedidos y Bodega)
- ✅ Tableros de Producción
- ✅ Balanceo de Líneas
- ✅ Vistas (Corte, Costura, Control de Calidad)
- ✅ **Gestión de Insumos** (Materiales, Dashboard, Metrajes)
- ✅ **Gestión de Usuarios** (Crear, editar, eliminar usuarios)
- ✅ **Configuración del Sistema** (Parámetros, backups, etc.)

### Módulos Disponibles
El supervisor_planta tiene acceso completo a:
- **Insumos**: Materiales, Dashboard, Metrajes
- **Usuarios**: Gestión completa de usuarios
- **Configuración**: Parámetros del sistema, backups, migraciones
- **Producción**: Órdenes, Bodega, Entregas, Tableros
- **Vistas**: Corte, Costura, Control de Calidad

## 📁 Estructura de Carpetas

```
resources/views/supervisor_planta/
├── layout.blade.php          # Layout principal
├── sidebar.blade.php         # Menú lateral personalizado
└── dashboard.blade.php       # Dashboard del rol
```

## 🔐 Configuración Técnica

### 1. Rol en Base de Datos
```php
// Agregado en RolesSeeder.php
\App\Models\Role::create([
    'name' => 'supervisor_planta',
    'description' => 'Supervisor de planta (acceso completo + insumos)',
    'requires_credentials' => true,
]);
```

### 2. Middleware Registrado
```php
// En bootstrap/app.php
'supervisor-planta' => \App\Http\Middleware\SupervisorPlantaAccess::class,
```

### 3. Redirección Post-Login
```php
// En AuthenticatedSessionController.php
if ($user->role->name === 'supervisor_planta') {
    return redirect()->intended(route('dashboard', absolute: false));
}
```

## 📊 Menú Lateral

El menú incluye ACCESO COMPLETO a:

```
📊 Dashboard
├── 📋 Gestionar Órdenes
│   ├── Pedidos
│   └── Bodega
├── 🚚 Entregas
│   ├── Pedidos
│   └── Bodega
├── 📈 Tableros
├── ⏱️ Balanceo
├── 👁️ Vistas
│   ├── Corte
│   ├── Costura
│   ├── Corte Bodega
│   ├── Costura Bodega
│   └── Control de Calidad
├── 📦 Insumos (ACCESO COMPLETO) ✨ VISIBLE EN SIDEBAR
│   └── Materiales
├── 👥 Usuarios (ACCESO COMPLETO)
├── ⚙️ Configuración (ACCESO COMPLETO)
└── 🚪 Salir
```

## 🚀 Cómo Crear un Usuario con Rol Supervisor de Planta

### Opción 1: Desde la Interfaz
1. Ir a **Usuarios** (solo admin)
2. Hacer clic en **Crear Usuario**
3. Completar datos:
   - Nombre
   - Email
   - Contraseña
   - **Rol**: Seleccionar "supervisor_planta"
4. Guardar

### Opción 2: Desde Base de Datos
```sql
INSERT INTO users (name, email, password, role_id, created_at, updated_at)
VALUES (
    'Juan Supervisor',
    'juan@example.com',
    bcrypt('password123'),
    (SELECT id FROM roles WHERE name = 'supervisor_planta'),
    NOW(),
    NOW()
);
```

### Opción 3: Desde Tinker
```bash
php artisan tinker
>>> $role = \App\Models\Role::where('name', 'supervisor_planta')->first();
>>> \App\Models\User::create([
    'name' => 'Juan Supervisor',
    'email' => 'juan@example.com',
    'password' => bcrypt('password123'),
    'role_id' => $role->id,
]);
```

## 📝 Archivos Modificados/Creados

### Creados
- ✅ `app/Http/Middleware/SupervisorPlantaAccess.php`
- ✅ `resources/views/supervisor_planta/layout.blade.php`
- ✅ `resources/views/supervisor_planta/sidebar.blade.php`
- ✅ `resources/views/supervisor_planta/dashboard.blade.php`

### Modificados
- ✅ `database/seeders/RolesSeeder.php` - Agregó rol
- ✅ `bootstrap/app.php` - Registró middleware
- ✅ `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - Redirección
- ✅ `resources/views/layouts/sidebar.blade.php` - Actualización de lógica

## 🔄 Flujo de Login

```
1. Usuario ingresa credenciales
2. Sistema valida credenciales
3. Sistema verifica rol del usuario
4. Si rol = 'supervisor_planta':
   └─ Redirige a /dashboard
5. Dashboard muestra todas las opciones disponibles
6. Usuario puede acceder a cualquier módulo
```

## 🎨 Interfaz

### Dashboard Personalizado
- Tarjetas de acceso rápido a módulos principales
- Información de permisos disponibles
- Diseño responsive
- Tema claro/oscuro soportado

### Sidebar Personalizado
- Menú completo con todas las opciones
- Submúes para Órdenes, Entregas, Vistas e Insumos
- Iconos descriptivos
- Toggle de tema

## ✅ Garantías

✅ Rol creado y registrado correctamente
✅ Middleware configurado
✅ Redirección post-login funcional
✅ Carpeta de vistas creada
✅ Layout personalizado
✅ Sidebar con acceso a TODO el sistema
✅ Dashboard informativo con todas las opciones
✅ Acceso completo a TODAS las funciones
✅ Acceso completo a Insumos
✅ Acceso completo a Usuarios
✅ Acceso completo a Configuración
✅ Acceso completo a Producción, Entregas, Tableros, Balanceo
✅ Acceso completo a Vistas (Corte, Costura, Control de Calidad)

## 🔍 Verificación

Para verificar que todo está funcionando:

1. **Crear usuario de prueba**:
   ```bash
   php artisan tinker
   >>> $role = \App\Models\Role::where('name', 'supervisor_planta')->first();
   >>> \App\Models\User::create(['name' => 'Test', 'email' => 'test@test.com', 'password' => bcrypt('test'), 'role_id' => $role->id]);
   ```

2. **Iniciar sesión** con el usuario de prueba

3. **Verificar**:
   - ✅ Se redirige a `/dashboard`
   - ✅ Sidebar muestra todas las opciones
   - ✅ Puede acceder a Insumos
   - ✅ Puede acceder a todas las demás funciones

## 📞 Soporte

Si hay problemas:

1. Verificar que el rol existe:
   ```bash
   php artisan tinker
   >>> \App\Models\Role::where('name', 'supervisor_planta')->first();
   ```

2. Verificar que el usuario tiene el rol:
   ```bash
   >>> \App\Models\User::find(1)->role;
   ```

3. Limpiar caché:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. Ejecutar migraciones si es necesario:
   ```bash
   php artisan migrate
   ```

## 🎯 Próximos Pasos (Opcional)

1. Personalizar dashboard con widgets específicos
2. Agregar reportes de insumos
3. Agregar notificaciones en tiempo real
4. Crear vistas específicas para supervisor_planta
5. Agregar auditoría de acciones

---

**Fecha**: 1 de Diciembre de 2025
**Estado**: ✅ COMPLETADO
**Versión**: 1.0
