# 🔄 MIGRACIÓN DE ROLES - Guía Completa

## 📋 Objetivo

Migrar los datos existentes de `role_id` a `roles_ids` (JSON) manteniendo la relación con la tabla `roles`.

---

## 🏗️ Estructura Actual vs Nueva

### Antes (Solo role_id)

```
users tabla:
├── id (PK)
├── role_id (FK → roles.id) ← UN SOLO ROL
└── ...

roles tabla:
├── id (PK)
├── name (admin, supervisor, etc.)
└── ...

Relación: User → Role (1:1 o 1:N)
```

### Después (role_id + roles_ids)

```
users tabla:
├── id (PK)
├── role_id (FK → roles.id) ← ROL PRINCIPAL (opcional)
├── roles_ids (JSON) ← MÚLTIPLES ROLES [1, 3, 5]
└── ...

roles tabla:
├── id (PK)
├── name (admin, supervisor, etc.)
└── ...

Relación: User → Role (1:N vía roles_ids)
```

---

## 🚀 Pasos de Migración

### Paso 1: Ejecutar Migración de Datos

```bash
php artisan migrate --path=database/migrations/2025_12_02_000003_migrate_role_id_to_roles_ids.php
```

**Qué hace:**
- Copia `role_id` a `roles_ids` como array JSON
- Usuarios con `role_id = 1` → `roles_ids = [1]`
- Usuarios sin `role_id` → `roles_ids = []`

### Paso 2: Verificar Migración

```bash
php artisan tinker
```

```php
// Ver un usuario
$user = User::find(1);
dd($user->roles_ids); // [1, 3, 5]

// Ver todos los usuarios
User::all()->pluck('role_id', 'roles_ids');

exit
```

### Paso 3: Actualizar Relaciones en Models

**Ya está hecho en:**
- `app/Models/User.php` - Métodos para múltiples roles
- `app/Models/Role.php` - Métodos para obtener usuarios

---

## 📊 Relaciones Después de la Migración

### Relación User → Role (Múltiples)

```php
// app/Models/User.php

public function roles()
{
    // Obtiene todos los roles del usuario
    if (!empty($this->roles_ids)) {
        return Role::whereIn('id', $this->roles_ids)->get();
    }

    if ($this->role_id) {
        return collect([$this->role]);
    }

    return collect([]);
}
```

### Relación Role → User (Múltiples)

```php
// app/Models/Role.php

public function usersWithJsonRole()
{
    // Obtiene todos los usuarios que tienen este rol en roles_ids
    return User::whereJsonContains('roles_ids', $this->id)->get();
}

public function allUsers()
{
    // Obtiene todos los usuarios que tienen este rol (role_id + roles_ids)
    return User::where('role_id', $this->id)
        ->orWhereJsonContains('roles_ids', $this->id)
        ->get();
}
```

---

## 💻 Ejemplos de Uso

### Obtener Roles de un Usuario

```php
$user = User::find(1);

// Obtener todos los roles
$roles = $user->roles(); // Collection de Role

// Iterar
foreach ($user->roles() as $role) {
    echo $role->name; // admin, supervisor, asesor
}

// Obtener nombres
$user->roles()->pluck('name'); // ['admin', 'supervisor']

// Obtener IDs
$user->roles()->pluck('id'); // [1, 3, 5]
```

### Obtener Usuarios de un Rol

```php
$role = Role::find(1); // admin

// Usuarios con este rol (solo role_id)
$role->users(); // Collection

// Usuarios con este rol (solo roles_ids)
$role->usersWithJsonRole(); // Collection

// Usuarios con este rol (role_id + roles_ids)
$role->allUsers(); // Collection

// Contar usuarios
$role->countAllUsers(); // int
```

### Verificar Relación

```php
$user = User::find(1);
$role = Role::find(1);

// ¿El usuario tiene este rol?
$user->hasRole($role->id); // bool
$user->hasRole($role->name); // bool

// ¿El rol tiene este usuario?
$role->allUsers()->contains($user); // bool
```

---

## 🔄 Sincronización Bidireccional

### Agregar Rol a Usuario

```php
$user = User::find(1);
$role = Role::find(3);

// Agregar rol
$user->addRole($role->id);

// Verificar relación inversa
$role->allUsers()->contains($user); // true
```

### Eliminar Rol de Usuario

```php
$user = User::find(1);
$role = Role::find(3);

// Eliminar rol
$user->removeRole($role->id);

// Verificar relación inversa
$role->allUsers()->contains($user); // false
```

---

## 📊 Queries Útiles

### Obtener Usuarios por Rol

```php
// Usuarios que tienen rol 'admin' (ID = 1)
$admins = User::where('role_id', 1)
    ->orWhereJsonContains('roles_ids', 1)
    ->get();

// Usando Role model
$role = Role::where('name', 'admin')->first();
$admins = $role->allUsers();
```

### Obtener Usuarios con Múltiples Roles

```php
// Usuarios que tienen roles 1 Y 3
$users = User::whereJsonContains('roles_ids', 1)
    ->whereJsonContains('roles_ids', 3)
    ->get();

// O usando método del modelo
$role1 = Role::find(1);
$role3 = Role::find(3);

$users = $role1->allUsers()
    ->intersect($role3->allUsers());
```

### Contar Usuarios por Rol

```php
// Contar admins
$adminCount = User::where('role_id', 1)
    ->orWhereJsonContains('roles_ids', 1)
    ->count();

// Usando Role model
$role = Role::find(1);
$adminCount = $role->countAllUsers();
```

### Roles Más Usados

```php
// Obtener roles con más usuarios
$roles = Role::all()->map(function ($role) {
    return [
        'name' => $role->name,
        'user_count' => $role->countAllUsers(),
    ];
})->sortByDesc('user_count');

dd($roles);
```

---

## 🔐 Backward Compatibility

El sistema mantiene **compatibilidad hacia atrás**:

```php
$user = User::find(1);

// Método antiguo (sigue funcionando)
$user->role; // Retorna el Role del campo role_id

// Método nuevo (múltiples roles)
$user->roles(); // Retorna Collection con todos los roles
```

---

## 📝 Estructura de Datos Después

### Usuario 1 (Admin + Supervisor + Asesor)

```json
{
  "id": 1,
  "name": "Juan",
  "role_id": 1,
  "roles_ids": [1, 3, 5]
}
```

### Usuario 2 (Solo Contador)

```json
{
  "id": 2,
  "name": "María",
  "role_id": 2,
  "roles_ids": [2]
}
```

### Usuario 3 (Sin Rol)

```json
{
  "id": 3,
  "name": "Carlos",
  "role_id": null,
  "roles_ids": []
}
```

---

## 🧪 Testing

### Test: Migración de Datos

```php
public function test_role_id_migrated_to_roles_ids()
{
    // Crear usuario con role_id
    $user = User::factory()->create(['role_id' => 1]);

    // Ejecutar migración
    $this->artisan('migrate', [
        '--path' => 'database/migrations/2025_12_02_000003_migrate_role_id_to_roles_ids.php'
    ]);

    // Verificar que roles_ids contiene el role_id
    $user->refresh();
    $this->assertContains(1, $user->roles_ids);
}
```

### Test: Relación Role → User

```php
public function test_role_has_all_users()
{
    $role = Role::find(1);
    $user = User::factory()->create(['role_id' => 1]);

    // Verificar relación
    $this->assertTrue($role->allUsers()->contains($user));
}
```

### Test: Relación User → Role

```php
public function test_user_has_role()
{
    $user = User::factory()->create(['role_id' => 1]);

    // Verificar relación
    $this->assertTrue($user->hasRole(1));
    $this->assertTrue($user->roles()->contains(Role::find(1)));
}
```

---

## ⚠️ Consideraciones Importantes

### 1. Mantener role_id

**Por qué:** Algunos sistemas pueden depender de `role_id` como "rol principal"

```php
// role_id = rol principal
// roles_ids = todos los roles (incluyendo el principal)

$user->role_id = 1; // Rol principal: admin
$user->roles_ids = [1, 3, 5]; // Todos los roles
```

### 2. Sincronización

**Importante:** Cuando cambies roles, actualiza ambos campos:

```php
// ✅ Correcto
$user->role_id = 1;
$user->roles_ids = [1, 3, 5];
$user->save();

// ❌ Incorrecto (inconsistencia)
$user->roles_ids = [1, 3, 5];
$user->save(); // role_id sigue siendo el antiguo
```

### 3. Queries Complejas

**Usa métodos del modelo en lugar de queries directas:**

```php
// ✅ Mejor
$role->allUsers();

// ❌ Menos legible
User::where('role_id', $role->id)
    ->orWhereJsonContains('roles_ids', $role->id)
    ->get();
```

---

## 📈 Ventajas de la Migración

✅ Soporta múltiples roles por usuario
✅ Mantiene compatibilidad con role_id
✅ Relaciones bidireccionales
✅ Queries eficientes con JSON
✅ Fácil de mantener
✅ Escalable

---

## 🔄 Revertir Migración

Si necesitas revertir:

```bash
php artisan migrate:rollback --path=database/migrations/2025_12_02_000003_migrate_role_id_to_roles_ids.php
```

Esto copiará el primer rol de `roles_ids` de vuelta a `role_id`.

---

## 📚 Archivos Relacionados

- `database/migrations/2025_12_02_000002_add_roles_ids_to_users_table.php` - Crear columna
- `database/migrations/2025_12_02_000003_migrate_role_id_to_roles_ids.php` - Migrar datos
- `app/Models/User.php` - Métodos para múltiples roles
- `app/Models/Role.php` - Métodos para obtener usuarios
- `MULTIPLES-ROLES-GUIA.md` - Guía completa

---

**Estado:** ✅ MIGRACIÓN LISTA

**Fecha:** 2 de Diciembre de 2025

**Versión:** 1.0
