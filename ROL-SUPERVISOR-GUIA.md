# Rol Supervisor - Guía de Implementación

## 📋 Descripción General

Se ha implementado un nuevo rol **"Supervisor"** que permite a los usuarios:
- ✅ **Ver solo** la sección "Gestión de Órdenes" en el sidebar
- ✅ **Acceso de lectura** a la lista de pedidos
- ✅ **Ver detalles** de cada pedido
- ❌ **No puede editar** nada (POST, PATCH, DELETE bloqueados)
- ❌ **No puede acceder** a otras secciones (Dashboard, Entregas, Tableros, Vistas, Balanceo, etc.)

## 🔧 Cambios Realizados

### 1. Base de Datos - Nuevo Rol
**Archivo**: `database/seeders/RolesSeeder.php`

Se agregó el rol "supervisor" al seeder:
```php
\App\Models\Role::create([
    'name' => 'supervisor',
    'description' => 'Supervisor de gestión de órdenes (solo lectura)',
    'requires_credentials' => true,
]);
```

**Ejecutar migraciones**:
```bash
php artisan migrate:fresh --seed
```

### 2. Sidebar - Menú Simplificado
**Archivo**: `resources/views/layouts/sidebar.blade.php`

Para supervisores, el sidebar muestra solo:
- ✅ Gestión de Órdenes (sin submenú, directo a pedidos)
- ✅ Salir

Para otros roles (admin, operador, cortador):
- Menú completo con todos los submenús

```blade
@if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
    <!-- Menú simplificado para supervisores -->
    <li class="menu-item">
        <a href="{{ route('registros.index') }}" ...>
            <span>Gestión de Órdenes</span>
        </a>
    </li>
@else
    <!-- Menú completo para otros roles -->
@endif
```

### 3. Middleware - Control de Lectura
**Archivo**: `app/Http/Middleware/SupervisorReadOnly.php`

Bloquea cualquier intento de modificación (POST, PATCH, DELETE):
```php
if (auth()->user()->role->name === 'supervisor') {
    if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
        return response()->json(['error' => 'Los supervisores solo tienen acceso de lectura'], 403);
    }
}
```

### 4. Middleware - Control de Acceso
**Archivo**: `app/Http/Middleware/SupervisorAccessControl.php`

Bloquea acceso a rutas no permitidas para supervisores:
```php
$allowedRoutes = [
    'registros.index',      // Ver lista de pedidos
    'registros.show',       // Ver detalle de pedido
    'registros.next-pedido',
    'registros.entregas',
    'api.registros-por-orden',
];
```

### 5. Rutas - Middlewares Aplicados
**Archivo**: `routes/web.php`

Las rutas de registros (pedidos) tienen ambos middlewares:
```php
Route::middleware(['auth', 'supervisor-readonly'])->group(function () {
    Route::get('/registros', ...);
    Route::get('/registros/{pedido}', ...);
    // ... más rutas
});
```

Todas las otras rutas tienen el middleware de control de acceso:
```php
Route::middleware(['auth', 'supervisor-access'])->group(function () {
    // Usuarios, Dashboard, Entregas, etc.
});
```

### 6. Registro de Middlewares
**Archivo**: `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'supervisor-readonly' => \App\Http\Middleware\SupervisorReadOnly::class,
        'supervisor-access' => \App\Http\Middleware\SupervisorAccessControl::class,
    ]);
})
```

## 👤 Cómo Crear un Usuario Supervisor

### Opción 1: Desde la interfaz (Admin)
1. Ir a **Usuarios** (solo visible para admin)
2. Crear nuevo usuario
3. Seleccionar rol: **Supervisor**
4. Guardar

### Opción 2: Desde la base de datos
```sql
INSERT INTO users (name, email, password, role_id, created_at, updated_at)
VALUES (
    'Juan Supervisor',
    'supervisor@example.com',
    'hash_de_contraseña',
    4,  -- ID del rol supervisor
    NOW(),
    NOW()
);
```

### Opción 3: Desde Tinker
```bash
php artisan tinker
```

```php
$supervisor_role = \App\Models\Role::where('name', 'supervisor')->first();
\App\Models\User::create([
    'name' => 'Juan Supervisor',
    'email' => 'supervisor@example.com',
    'password' => bcrypt('password123'),
    'role_id' => $supervisor_role->id,
]);
```

## 🔐 Seguridad

### Lo que está protegido:

1. **Lectura protegida**
   - Solo GET, HEAD, OPTIONS permitidos
   - POST, PATCH, DELETE retornan 403

2. **Acceso a rutas**
   - Solo puede acceder a rutas de registros/pedidos
   - Otras rutas retornan 403

3. **Interfaz de usuario**
   - Sidebar oculta opciones no permitidas
   - Botones de edición no se muestran

### Ejemplo de respuesta bloqueada:
```json
{
    "error": "Los supervisores solo tienen acceso de lectura"
}
```

## 📊 Rutas Permitidas para Supervisores

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/registros` | GET | Ver lista de pedidos |
| `/registros/{pedido}` | GET | Ver detalle de pedido |
| `/registros/next-pedido` | GET | Obtener siguiente pedido |
| `/registros/{pedido}/entregas` | GET | Ver entregas del pedido |
| `/api/registros-por-orden/{pedido}` | GET | API de registros por orden |

## 🚫 Rutas Bloqueadas para Supervisores

| Sección | Rutas |
|---------|-------|
| Dashboard | Todas |
| Usuarios | Todas |
| Entregas | Todas |
| Tableros | Todas |
| Vistas | Todas |
| Balanceo | Todas |
| Configuración | Todas |
| Edición de pedidos | POST, PATCH, DELETE en `/registros` |

## 🧪 Pruebas

### Verificar que el rol existe:
```bash
php artisan tinker
\App\Models\Role::where('name', 'supervisor')->first();
```

### Verificar que el middleware está registrado:
```bash
php artisan route:list | grep supervisor
```

### Probar acceso como supervisor:
1. Crear usuario supervisor
2. Iniciar sesión
3. Verificar que solo ve "Gestión de Órdenes"
4. Intentar acceder a `/dashboard` → Debe retornar 403
5. Intentar editar un pedido → Debe retornar 403

## 📝 Notas Importantes

- El rol supervisor se crea automáticamente al ejecutar `php artisan migrate:fresh --seed`
- Si ya tienes datos, ejecuta solo el seeder: `php artisan db:seed --class=RolesSeeder`
- Los supervisores pueden ver la información pero no modificarla
- El acceso está protegido tanto en frontend (sidebar) como en backend (middleware)
- Los intentos de acceso no autorizado se registran en los logs

## 🔄 Cambios Futuros

Si necesitas:
- **Agregar más permisos**: Modifica `SupervisorAccessControl.php`
- **Cambiar rutas permitidas**: Edita el array `$allowedRoutes`
- **Agregar más roles**: Agrega en `RolesSeeder.php`
- **Personalizar sidebar**: Modifica `sidebar.blade.php`

## ❓ Preguntas Frecuentes

**P: ¿Puede un supervisor crear pedidos?**
R: No, solo puede verlos. POST está bloqueado.

**P: ¿Puede un supervisor ver el dashboard?**
R: No, el acceso está bloqueado por middleware.

**P: ¿Qué pasa si intenta acceder a una ruta no permitida?**
R: Recibe un error 403 con mensaje "Acceso denegado".

**P: ¿Cómo cambio los permisos de un supervisor?**
R: Modifica los middlewares en `bootstrap/app.php` o las rutas en `routes/web.php`.
