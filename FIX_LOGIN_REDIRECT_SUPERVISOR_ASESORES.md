# Fix: Redirección del Rol supervisor_asesores Después del Login

## Problema
Cuando un usuario con rol `supervisor_asesores` intenta iniciar sesión, no es redireccionado al dashboard correspondiente.

## Causa Raíz
En `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, el método `store()` no tenía una condición para manejar el rol `supervisor_asesores`.

## Solución Implementada

### Archivo Modificado: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

Se agregó la siguiente condición en el método `store()`:

```php
// Supervisor de Asesores - Supervisión de asesores, cotizaciones y pedidos
if ($roleName === 'supervisor_asesores') {
    return redirect(route('supervisor-asesores.dashboard', absolute: false));
}
```

### Ubicación en el flujo:
- Después de validar `supervisor_pedidos`
- Antes de validar `cortador`

## Flujo de Login Completo

```
1. Usuario ingresa credenciales en /login
2. AuthenticatedSessionController@store() procesa la autenticación
3. Se obtiene el usuario con Auth::user()
4. Se extrae el nombre del rol: $roleName = $user->role->name
5. Se valida el rol con una serie de if:
   - asesor → /asesores/dashboard
   - contador → /contador/dashboard
   - supervisor → /registros
   - supervisor_planta → /registros
   - insumos → /insumos/materiales
   - patronista → /insumos/materiales
   - aprobador_cotizaciones → /cotizaciones/pendientes
   - supervisor_pedidos → /supervisor-pedidos
   → supervisor_asesores → /supervisor-asesores/dashboard ✅ NUEVO
   - cortador → /operario/dashboard
   - costurero → /operario/dashboard
6. Por defecto (admin y otros) → /dashboard
```

## Rutas Disponibles para supervisor_asesores

Las siguientes rutas están disponibles en `routes/web.php`:

```php
Route::middleware(['auth', 'role:supervisor_asesores,admin'])->prefix('supervisor-asesores')->name('supervisor-asesores.')->group(function () {
    // Dashboard
    Route::get('/dashboard', ...) → supervisor-asesores.dashboard ✅
    Route::get('/dashboard-stats', ...) → supervisor-asesores.dashboard-stats
    
    // Cotizaciones
    Route::get('/cotizaciones', ...) → supervisor-asesores.cotizaciones.index
    Route::get('/cotizaciones/data', ...) → supervisor-asesores.cotizaciones.data
    
    // Pedidos
    Route::get('/pedidos', ...) → supervisor-asesores.pedidos.index
    Route::get('/pedidos/data', ...) → supervisor-asesores.pedidos.data
    
    // Asesores
    Route::get('/asesores', ...) → supervisor-asesores.asesores.index
    Route::get('/asesores/{id}', ...) → supervisor-asesores.asesores.show
    
    // Reportes
    Route::get('/reportes', ...) → supervisor-asesores.reportes.index
    
    // Perfil
    Route::get('/perfil', ...) → supervisor-asesores.profile.index
    Route::post('/perfil/password-update', ...) → supervisor-asesores.profile.password-update
});
```

## Pasos para Probar

### 1. Asignar rol al usuario en la BD

Ejecutar el script SQL:
```bash
# Abre el archivo SQL_SCRIPT en tu cliente MySQL/MariaDB
# O ejecuta directamente en la consola:
```

```sql
UPDATE users 
SET roles_ids = JSON_ARRAY(
    (SELECT id FROM roles WHERE name = 'supervisor_asesores' LIMIT 1)
)
WHERE id = 1;  -- Cambiar 1 por el ID del usuario
```

### 2. Verificar asignación
```sql
SELECT id, name, email, roles_ids FROM users WHERE id = 1;
```
**Debe mostrar roles_ids con el ID del rol supervisor_asesores**

### 3. Cerrar sesión y limpiar
```
- Hacer logout (o cerrar navegador)
- Limpiar caché del navegador (Ctrl + Shift + Delete)
- Cerrar todas las pestañas
```

### 4. Intentar login nuevamente
```
- Ir a /login
- Ingresar credenciales del usuario con rol supervisor_asesores
- Debes ser redireccionado a /supervisor-asesores/dashboard ✅
```

## Verificación del Código

### AuthenticatedSessionController
✅ Condición para `supervisor_asesores` agregada
✅ Redirige a `supervisor-asesores.dashboard`
✅ Mantiene el formato de código consistente

### Rutas en web.php
✅ Ruta `/supervisor-asesores/dashboard` existe
✅ Middleware `role:supervisor_asesores,admin` protege la ruta
✅ Nombre de ruta es `supervisor-asesores.dashboard`

### Controlador SupervisorAsesoresController
✅ Método `dashboard()` implementado
✅ Retorna vista `supervisor-asesores.dashboard`

## Logs de Depuración (En AuthenticatedSessionController.php)

El controlador incluye logs que registran:
- `user_id`
- `roles_ids` (array JSON)
- `role` (objeto o relación)
- `role_name` (nombre extracto)

Puedes ver estos logs en `storage/logs/laravel.log`:
```
[2025-12-14 XX:XX:XX] local.INFO: Login usuario {"user_id":1,"roles_ids":"[7]","role":"object","role_name":"supervisor_asesores"}
[2025-12-14 XX:XX:XX] local.INFO: Rol detectado {"roleName":"supervisor_asesores"}
```

## Archivos Modificados

1. **app/Http/Controllers/Auth/AuthenticatedSessionController.php**
   - Línea ~95: Agregada condición para `supervisor_asesores`
   - Causa: Faltaba el caso específico para este rol

## Próximas Verificaciones

- [ ] Usuario puede iniciar sesión sin errores
- [ ] Es redireccionado automáticamente a `/supervisor-asesores/dashboard`
- [ ] Puede ver el dashboard con estadísticas de asesores
- [ ] Puede acceder a Cotizaciones, Pedidos, Asesores, Reportes, Perfil
- [ ] Middleware protege rutas no autorizadas

## Rollback (Si es necesario)

Si necesitas deshacer los cambios:
```php
// Simplemente elimina estas líneas del AuthenticatedSessionController:
if ($roleName === 'supervisor_asesores') {
    return redirect(route('supervisor-asesores.dashboard', absolute: false));
}
```

Pero esto volvería el problema anterior (sin redirección específica).
