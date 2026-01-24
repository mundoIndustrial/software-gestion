#  IMPLEMENTACIÓN: ROL DESPACHO CON REDIRECCIÓN AUTOMÁTICA

**Fecha:** 23 de enero de 2026  
**Estado:**  COMPLETADO

---

##  Lo que se implementó

### 1️⃣ Seeder para el rol Despacho
**Archivo:** `database/seeders/DespachoRoleSeeder.php`

```php
Role::firstOrCreate(
    ['name' => 'Despacho'],
    [
        'description' => 'Usuario responsable de controlar entregas parciales',
        'requires_credentials' => false,
    ]
);
```

### 2️⃣ Middleware de protección
**Archivo:** `app/Http/Middleware/CheckDespachoRole.php`

- Verifica que el usuario esté autenticado ✓
- Verifica que tenga el rol Despacho ✓
- Retorna 403 si no tiene permisos ✓

### 3️⃣ Redirección automática en login
**Archivo:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

Cuando un usuario con rol "Despacho" inicia sesión:
```php
if ($roleName === 'Despacho') {
    return redirect(route('despacho.index'));
}
```

Redirige automáticamente a `/despacho`

### 4️⃣ Rutas protegidas
**Archivo:** `routes/despacho.php`

Todas las rutas del despacho ahora usan:
```php
Route::prefix('despacho')
    ->middleware(['auth', 'check.despacho.role'])
    ->group(function () { ... });
```

### 5️⃣ Registro de middleware
**Archivo:** `bootstrap/app.php`

```php
$middleware->alias([
    'check.despacho.role' => \App\Http\Middleware\CheckDespachoRole::class,
]);
```

---

## 🔧 Cómo usar

### Paso 1: Ejecutar el seeder
```bash
php artisan db:seed --class=DespachoRoleSeeder
```

**Resultado:**
```
 Rol Despacho creado/verificado correctamente
```

### Paso 2: Asignar el rol a un usuario

**Opción A: Via Artisan Tinker**
```bash
php artisan tinker
```

```php
$user = App\Models\User::find(1);  // ID del usuario
$despachoRole = App\Models\Role::where('name', 'Despacho')->first();

// Si roles_ids está vacío
$user->roles_ids = json_encode([$despachoRole->id]);

// Si ya tiene roles
$roles = json_decode($user->roles_ids, true) ?? [];
$roles[] = $despachoRole->id;
$user->roles_ids = json_encode($roles);

$user->save();
echo " Rol asignado a usuario {$user->name}";
```

**Opción B: Via SQL**
```sql
-- Obtener ID del rol Despacho
SELECT id FROM roles WHERE name = 'Despacho';  -- Por ejemplo: id = 10

-- Obtener usuario
SELECT id, name, roles_ids FROM users WHERE id = 1;

-- Actualizar roles (reemplaza [10] con el ID del rol Despacho obtenido)
UPDATE users SET roles_ids = JSON_ARRAY(10) WHERE id = 1;

-- O si el usuario ya tiene roles:
UPDATE users 
SET roles_ids = JSON_ARRAY_APPEND(roles_ids, '$', 10) 
WHERE id = 1;
```

---

## 🔐 Flujo de autenticación con Despacho

```
Usuario intenta login
    ↓
AuthenticatedSessionController::store()
    ↓
$user = Auth::user() → obtiene usuario autenticado
    ↓
¿Rol es "Despacho"?
    ↓
    ├─ SÍ → redirect(route('despacho.index'))
    │        ↓
    │        GET /despacho (con middleware)
    │        ↓
    │        CheckDespachoRole middleware
    │        ├─ ¿Autenticado? ✓
    │        ├─ ¿Tiene rol Despacho? ✓
    │        └─ Continuar → DespachoController::index()
    │
    └─ NO → Otras rutas según rol
             (asesor, contador, supervisor, etc.)
```

---

## 🛡️ Seguridad implementada

###  Autenticación
- Usuario debe estar logged in para acceder ✓
- Ruta sin `/login` redirige a login ✓

###  Autorización
- Solo rol "Despacho" puede acceder ✓
- Otros roles obtienen error 403 ✓
- roles_ids verificado en cada request ✓

###  Redirección inteligente
- Cada rol va a su dashboard ✓
- No se puede "forzar" otras rutas ✓
- Logout limpia sesión ✓

---

## 📊 Rutas del módulo Despacho (protegidas)

```
GET    /despacho              → despacho.index
GET    /despacho/{id}         → despacho.show
POST   /despacho/{id}/guardar → despacho.guardar
GET    /despacho/{id}/print   → despacho.print

Todas requieren:
  ✓ auth (estar autenticado)
  ✓ check.despacho.role (tener rol Despacho)
```

---

## ✨ Funcionalidades

### DespachoController (minimista en Infrastructure)
```php
public function index()
    // Listar todos los pedidos disponibles para despacho

public function show(PedidoProduccion $pedido)
    // Mostrar vista interactiva de despacho

public function guardarDespacho(Request $request, PedidoProduccion $pedido)
    // Guardar parciales de despacho (delega a UseCase)

public function printDespacho(PedidoProduccion $pedido)
    // Vista para imprimir control de entregas
```

---

## 🧪 Pruebas

### Test 1: Usuario sin autenticación
```bash
curl http://localhost/despacho
# Esperado: Redirige a /login
```

### Test 2: Usuario autenticado SIN rol Despacho
```bash
# Loguea como usuario "asesor"
# Intenta: GET /despacho
# Esperado: Error 403 "No tienes permiso"
```

### Test 3: Usuario con rol Despacho
```bash
# Loguea como usuario con rol Despacho
# Sistema redirige automáticamente a: GET /despacho
# Esperado: ✓ Carga la lista de pedidos
```

### Test 4: Guardar despacho
```bash
# POST /despacho/123/guardar
# Body: { despachos: [...] }
# Esperado: JSON con resultado de GuardarDespachoUseCase
```

---

## 📝 Base de datos

### Tabla `roles`
```sql
id | name | description | requires_credentials
10 | Despacho | Usuario responsable de... | 0
```

### Tabla `users`
```sql
id | name | email | roles_ids | ...
1 | Juan Pérez | juan@company.com | [10] | ...
   → roles_ids contiene array JSON con ID del rol
```

---

## 🔄 Integración con DDD

El middleware se integra perfectamente con la arquitectura DDD:

```
HTTP Request
    ↓
Middleware CheckDespachoRole (Infrastructure)
    ├─ Verifica autenticación ✓
    ├─ Verifica autorización ✓
    └─ Permite continuar o rechaza
    ↓
DespachoController (Infrastructure adapter)
    ↓
UseCase (Application layer)
    ↓
DomainService (Domain layer)
```

---

##  Próximos pasos opcionales

1. **Crear comando Artisan para asignar roles:**
   ```bash
   php artisan user:assign-role {user_id} {role_name}
   ```

2. **Agregar menu condicional en layout:**
   ```blade
   @if(auth()->user()->hasRole('Despacho'))
       <a href="{{ route('despacho.index') }}">📦 Despacho</a>
   @endif
   ```

3. **Auditoría de despachos procesados:**
   ```php
   // Tabla: despacho_historico
   // Guardar cada despacho realizado para trazabilidad
   ```

4. **Notificaciones por email:**
   ```php
   // Notificar cuando se guarde un despacho
   // Enviar resumen diario de despachos
   ```

---

##  Checklist

-  Seeder DespachoRoleSeeder creado
-  Middleware CheckDespachoRole creado
-  Redirección en AuthenticatedSessionController
-  Rutas protegidas en routes/despacho.php
-  Middleware registrado en bootstrap/app.php
-  Documentación completa

---

## 📞 Soporte

Si necesitas:
- **Asignar rol a usuario existente:** Ver "Opción B: Via SQL"
- **Crear nuevo usuario con rol:** Asignar roles_ids al crear
- **Cambiar redirección:** Editar AuthenticatedSessionController
- **Cambiar validación de rol:** Editar CheckDespachoRole

---

**Implementación completada:** 23 de enero de 2026   
**Estado:** Listo para usar
