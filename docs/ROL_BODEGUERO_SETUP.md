#  Setup Rol Bodeguero

## Descripción

Se ha configurado un nuevo rol **bodeguero** con acceso similar a cortador y costurero. El bodeguero puede:

-  Ver recibos de costura/bodega
-  Ver recibos de corte/bodega  
-  Acceder al dashboard de operarios
-  Ver sus pedidos asignados
-  Navegar por el módulo de recibos dinámicos

## Cambios Realizados

### 1. **Base de Datos - Rol Creado**
   - Seeder: `database/seeders/CrearRolesOperariosSeeder.php`
   - Rol creado: `bodeguero`
   - Descripción: "Operario encargado de la bodega - Visualización de recibos"
   - Sin credenciales requeridas

### 2. **Middleware - Actualizado**
   - Archivo: `app/Http/Middleware/OperarioAccess.php`
   - Ahora verifica: `['cortador', 'costurero', 'bodeguero']`
   - Protege todas las rutas del operario

### 3. **Servicio de Operarios - Actualizado**
   - Archivo: `app/Application/Operario/Services/ObtenerPedidosOperarioService.php`
   - Método `obtenerTipoOperario()`: Ahora reconoce bodeguero
   - Método `obtenerAreaOperario()`: Bodeguero → Área "Bodega"

### 4. **Sidebar - Actualizado**
   - Archivo: `resources/views/layouts/sidebar.blade.php`
   - Bodeguero ve solo: Corte Bodega y Costura Bodega
   - Cortador/Costurero ven sus vistas correspondientes

## Cómo Usar

### Asignar Rol a un Usuario

**Opción 1: Tinker (Laravel REPL)**
```bash
php artisan tinker
$user = App\Models\User::find(1);  # Reemplaza 1 con ID del usuario
$user->roles()->attach(App\Models\Role::where('name', 'bodeguero')->first());
exit
```

**Opción 2: SQL Directo**
```sql
-- Supongamos user_id = 5 y role_id = 7
INSERT INTO role_user (user_id, role_id, created_at, updated_at) 
VALUES (5, 7, NOW(), NOW());
```

**Opción 3: Crear Seeder Personalizado**
```bash
php artisan make:seeder AssignBodegueroRoleSeeder
```

### Verificar Asignación

```bash
php artisan tinker
$user = App\Models\User::find(1);
$user->roles()->pluck('name')->toArray();  # Debería mostrar ['bodeguero']
exit
```

## Rutas Disponibles para Bodeguero

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/operario/dashboard` | GET | Dashboard principal |
| `/operario/mis-pedidos` | GET | Listado de pedidos |
| `/operario/pedido/{numero}` | GET | Detalle del pedido |
| `/operario/api/pedidos` | GET | API JSON de pedidos |
| `/operario/api/pedido/{numero}` | GET | API JSON detalle |
| `/operario/api/novedades/{numero}` | GET | API novedades |

## Vistas Disponibles

Bodeguero puede acceder a:
-  Costura Bodega (`/vistas?tipo=bodega`)
- ✂️ Corte Bodega (`/vistas?tipo=corte&origen=bodega`)

## Recibos Disponibles

El módulo de recibos (`PedidosRecibosModule`) genera automáticamente:
- Recibos de COSTURA-BODEGA (si `de_bodega=1`)
- Recibos de CORTE-BODEGA
- Todos los procesos asociados (Bordado, Estampado, etc.)

## Testing

```bash
# 1. Crear usuario de prueba
php artisan tinker
$user = App\Models\User::create([
    'name' => 'Bodeguero Test',
    'email' => 'bodeguero@test.com',
    'password' => bcrypt('password'),
]);
$user->roles()->attach(App\Models\Role::where('name', 'bodeguero')->first());
exit

# 2. Acceder a http://localhost/operario/dashboard con las credenciales
# Email: bodeguero@test.com
# Password: password
```

## Notas Importantes

-  Bodeguero hereda el acceso a través del middleware `operario-access`
-  Los recibos se generan dinámicamente según `de_bodega` flag
-  El sidebar filtra opciones automáticamente según el rol
-  Compatible con sistema de herencia de roles si se configura en `config/role-hierarchy.php`

## Archivos Modificados

1. `database/seeders/CrearRolesOperariosSeeder.php` 
2. `app/Http/Middleware/OperarioAccess.php` 
3. `app/Application/Operario/Services/ObtenerPedidosOperarioService.php` 
4. `resources/views/layouts/sidebar.blade.php` 

---

**Fecha de Implementación:** 4 de Febrero de 2026
**Versión:** 1.0
