#  Setup Rol Costura-Reflectivo

## Descripción

Se ha configurado un nuevo rol **costura-reflectivo** con acceso especial a la sección de operarios. El costura-reflectivo puede:

-  Ver recibos de costura reflexiva
-  Acceder al dashboard de operarios
-  Ver sus pedidos asignados (CUALQUIER proceso donde sea el encargado)
-  Navegar por el módulo de recibos dinámicos

## Cambios Realizados

### 1. **Base de Datos - Rol Creado**
   - Seeder: `database/seeders/CrearRolesOperariosSeeder.php`
   - Rol creado: `costura-reflectivo`
   - Descripción: "Operario encargado del área de costura reflexiva"
   - Sin credenciales requeridas

### 2. **Middleware - Actualizado**
   - Archivo: `app/Http/Middleware/OperarioAccess.php`
   - Ahora verifica: `['cortador', 'costurero', 'bodeguero', 'costura-reflectivo']`
   - Protege todas las rutas del operario

### 3. **Servicio de Operarios - Actualizado**
   - Archivo: `app/Application/Operario/Services/ObtenerPedidosOperarioService.php`
   - Método `obtenerTipoOperario()`: Ahora reconoce costura-reflectivo
   - Método `obtenerAreaOperario()`: costura-reflectivo → Área "Costura-Reflectivo"
   - Método `obtenerPedidosDelOperario()`: Usa lógica especial para buscar CUALQUIER proceso del usuario

### 4. **Controlador de Autenticación - Actualizado**
   - Archivo: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
   - Al login, costura-reflectivo redirige a `/operario/dashboard`

## Cómo Usar

### Asignar Rol a un Usuario

**Opción 1: Tinker (Laravel REPL)**
```bash
php artisan tinker
$user = App\Models\User::find(1);  # Reemplaza 1 con ID del usuario
$user->roles()->attach(App\Models\Role::where('name', 'costura-reflectivo')->first());
exit
```

**Opción 2: SQL Directo**
```sql
-- Supongamos user_id = 5 y role_id = (buscar el id del rol costura-reflectivo)
INSERT INTO role_user (user_id, role_id, created_at, updated_at) 
VALUES (5, role_id, NOW(), NOW());
```

**Opción 3: Crear Seeder Personalizado**
```bash
php artisan make:seeder AssignCosturaReflectivoRoleSeeder
```

### Verificar Asignación

```bash
php artisan tinker
$user = App\Models\User::find(1);
$user->roles()->pluck('name')->toArray();  # Debería mostrar ['costura-reflectivo']
exit
```

## Rutas Disponibles para Costura-Reflectivo

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/operario/dashboard` | GET | Dashboard principal |
| `/operario/mis-pedidos` | GET | Listado de pedidos |
| `/operario/pedido/{numero}` | GET | Detalle del pedido |
| `/operario/api/pedidos` | GET | API JSON de pedidos |
| `/operario/api/pedido/{numero}` | GET | API JSON detalle |
| `/operario/api/novedades/{numero}` | GET | API novedades |

## Comportamiento Especial

### Obtención de Pedidos

El costura-reflectivo tiene una lógica especial para obtener pedidos:

1. **BUSCA TODOS los pedidos** en la base de datos
2. **FILTRA** por procesos donde él sea el `encargado`
3. No importa el tipo de proceso (Corte, Bordado, Estampado, etc.)
4. Si es encargado de CUALQUIER proceso, ve ese pedido

Esto permite máxima flexibilidad para operarios que trabajan en múltiples procesos.

### Lógica de Filtrado en `obtenerPedidosCosturaReflectivo()`

```php
// Obtiene TODOS los pedidos
$todosPedidos = PedidoProduccion::with(['prendas'])->get();

// Filtra por: usuario sea encargado de CUALQUIER proceso
$pedidos = $todosPedidos->filter(function ($pedido) use ($usuarioNormalizado) {
    $procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)->get();
    
    // Busca CUALQUIER proceso donde el usuario sea el encargado
    return $procesos->contains(function ($proceso) use ($usuarioNormalizado) {
        if (!$proceso->encargado) return false;
        return strtolower(trim($proceso->encargado)) === $usuarioNormalizado;
    });
});
```

## Testing

```bash
# 1. Crear usuario de prueba
php artisan tinker
$user = App\Models\User::create([
    'name' => 'Operario Costura Reflexiva',
    'email' => 'costura-reflectivo@test.com',
    'password' => bcrypt('password'),
]);
$user->roles()->attach(App\Models\Role::where('name', 'costura-reflectivo')->first());
exit

# 2. Acceder a http://localhost:8000/operario/dashboard con las credenciales
# Email: costura-reflectivo@test.com
# Password: password
```

## Notas Importantes

-  costura-reflectivo hereda el acceso a través del middleware `operario-access`
-  Los pedidos se filtran dinámicamente basándose en el campo `encargado` de `procesos_prenda`
-  La comparación de nombres es case-insensitive y se normalizan espacios
-  Compatible con sistema de herencia de roles si se configura en `config/role-hierarchy.php`
-  El redirect en el login es automático hacia `/operario/dashboard`

## Archivos Modificados

1. `database/seeders/CrearRolesOperariosSeeder.php` 
2. `app/Http/Middleware/OperarioAccess.php` 
3. `app/Http/Controllers/Auth/AuthenticatedSessionController.php` 
4. `app/Application/Operario/Services/ObtenerPedidosOperarioService.php` 

---

**Fecha de Implementación:** 4 de Febrero de 2026
**Versión:** 1.0
**Estado:**  Completado - Listo para usar
