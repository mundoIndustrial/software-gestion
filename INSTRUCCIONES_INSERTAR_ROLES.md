# Instrucciones para Insertar los Roles

## Opción 1: Ejecutar el Seeder (Recomendado)

### Si ya ejecutaste el RolesSeeder actualizado:
```bash
php artisan db:seed --class=RolesSeeder
```

Esto insertará automáticamente ambos roles:
- `supervisor_asesores`
- `costurero`

### O ejecutar el seeder específico:
```bash
php artisan db:seed --class=AddSupervisorAsesoresAndCostureroRoleSeeder
```

### O ejecutar todos los seeders:
```bash
php artisan db:seed
```

---

## Opción 2: Ejecutar SQL Directamente

Si prefieres ejecutar SQL directamente sin usar seeders:

```sql
-- Insertar rol supervisor_asesores
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at)
VALUES (
    'supervisor_asesores',
    'Supervisor de Asesores - Gestión de cotizaciones y pedidos de todos los asesores',
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    description = 'Supervisor de Asesores - Gestión de cotizaciones y pedidos de todos los asesores',
    requires_credentials = 1,
    updated_at = NOW();

-- Insertar rol costurero
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at)
VALUES (
    'costurero',
    'Operario de costura',
    0,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    description = 'Operario de costura',
    requires_credentials = 0,
    updated_at = NOW();
```

---

## Verificación

Después de insertar los roles, verifica que se crearon correctamente:

```bash
# En tu aplicación Laravel:
php artisan tinker
> App\Models\Role::where('name', 'supervisor_asesores')->first();
> App\Models\Role::where('name', 'costurero')->first();
```

O con SQL:
```sql
SELECT id, name, description, requires_credentials 
FROM roles 
WHERE name IN ('supervisor_asesores', 'costurero')
ORDER BY name;
```

---

## Asignar Roles a Usuarios

Una vez que los roles están creados, puedes asignarlos a usuarios:

### En base de datos:
```sql
-- Obtener ID del rol
SELECT id FROM roles WHERE name = 'supervisor_asesores';

-- Asignar a un usuario (reemplaza 1 con el ID del usuario y 5 con el ID del rol)
UPDATE users SET roles_ids = JSON_ARRAY_APPEND(roles_ids, '$', 5) WHERE id = 1;
```

### Desde Laravel (Tinker):
```bash
php artisan tinker

# Obtener usuario
$user = App\Models\User::find(1);

# Obtener rol
$role = App\Models\Role::where('name', 'supervisor_asesores')->first();

# Asignar rol (si usa JSON array)
$roles = $user->roles_ids ?? [];
if (!in_array($role->id, $roles)) {
    $roles[] = $role->id;
    $user->update(['roles_ids' => $roles]);
}

# Verificar
$user->refresh();
dd($user->roles_ids);
```

---

## Cambios Realizados

### 1. RolesSeeder.php
- Actualizado para incluir los dos nuevos roles
- Se ejecuta automáticamente cuando haces `php artisan db:seed`

### 2. AddSupervisorAsesoresAndCostureroRoleSeeder.php
- Nuevo seeder específico para estos roles
- Uso: `php artisan db:seed --class=AddSupervisorAsesoresAndCostureroRoleSeeder`

### 3. SQL_INSERT_ROLES.sql
- Sentencias SQL directas para insertar los roles
- Usar en herramientas como phpMyAdmin, DBeaver, etc.

---

## ✅ Estado

- [x] RolesSeeder actualizado
- [x] Nuevo seeder creado
- [x] Archivo SQL generado
- [x] Instrucciones documentadas

**Próximo paso**: Ejecuta uno de los comandos anteriores para insertar los roles en tu base de datos.
