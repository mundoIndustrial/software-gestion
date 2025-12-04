# 🌱 USAR SEEDERS - SUPERVISOR_PEDIDOS

## 📋 Seeders Creados

Se han creado 3 seeders para facilitar la configuración del rol `supervisor_pedidos`:

### 1. **SupervisorPedidosRoleSeeder**
Crea el rol en la base de datos.

### 2. **AssignSupervisorPedidosRoleSeeder**
Asigna el rol a usuarios específicos.

### 3. **SetupSupervisorPedidosSeeder** (Maestro)
Ejecuta los 2 anteriores en orden.

---

## ⚡ Opción 1: Usar el Seeder Maestro (Recomendado)

### Paso 1: Ejecutar el seeder
```bash
php artisan db:seed --class=SetupSupervisorPedidosSeeder
```

### Resultado esperado
```
🚀 Iniciando configuración de Supervisor de Pedidos...

📝 Paso 1: Creando rol "supervisor_pedidos"...
✅ Rol "supervisor_pedidos" creado exitosamente.

👤 Paso 2: Asignando rol a usuarios...
✅ Rol 'supervisor_pedidos' asignado al usuario: Juan García (ID: 2)

✅ ¡Configuración completada exitosamente!
🌐 Accede a: http://localhost:8000/supervisor-pedidos/
```

### Paso 2: Acceder
```
http://localhost:8000/supervisor-pedidos/
```

---

## ⚡ Opción 2: Usar Seeders Individuales

### Paso 1: Crear el rol
```bash
php artisan db:seed --class=SupervisorPedidosRoleSeeder
```

Resultado:
```
✅ Rol "supervisor_pedidos" creado exitosamente.
```

### Paso 2: Asignar a usuario
```bash
php artisan db:seed --class=AssignSupervisorPedidosRoleSeeder
```

Resultado:
```
✅ Rol 'supervisor_pedidos' asignado al usuario: Juan García (ID: 2)
```

---

## 🔧 Personalizar Asignación de Rol

Si quieres asignar el rol a un usuario diferente, edita:

```
database/seeders/AssignSupervisorPedidosRoleSeeder.php
```

### Opción A: Por ID de usuario
```php
// Cambiar el ID (2) al ID del usuario que desees
$user = User::find(2);
```

### Opción B: Por email
Descomenta esta sección:
```php
$user = User::where('email', 'supervisor@example.com')->first();
if ($user) {
    $user->role_id = $roleId;
    $user->save();
    $this->command->info("✅ Rol 'supervisor_pedidos' asignado al usuario: {$user->name}");
}
```

### Opción C: Múltiples usuarios
Descomenta esta sección:
```php
$users = User::whereIn('id', [2, 3, 4])->get();
foreach ($users as $user) {
    $user->role_id = $roleId;
    $user->save();
    $this->command->info("✅ Rol asignado a: {$user->name}");
}
```

---

## 🔍 Verificar que Funcionó

### Verificar que el rol existe
```bash
php artisan tinker
```

```php
DB::table('roles')->where('name', 'supervisor_pedidos')->first();
```

Debería retornar:
```
{
  "id": 5,
  "name": "supervisor_pedidos",
  "description": "Supervisor de Pedidos de Producción",
  "requires_credentials": 0,
  "created_at": "2025-12-04 10:30:00",
  "updated_at": "2025-12-04 10:30:00"
}
```

### Verificar que el usuario tiene el rol
```php
$user = User::find(2);
$user->role_id; // Debería ser 5 (o el ID del rol)
```

---

## 🚀 Próximos Pasos

1. Ejecutar seeder maestro:
```bash
php artisan db:seed --class=SetupSupervisorPedidosSeeder
```

2. Acceder a:
```
http://localhost:8000/supervisor-pedidos/
```

3. ¡Listo! Ya puedes usar el rol supervisor_pedidos

---

## 🐛 Troubleshooting

### Error: "Class not found"
**Causa**: Seeder no está registrado
**Solución**: Ejecutar `composer dump-autoload`

```bash
composer dump-autoload
php artisan db:seed --class=SetupSupervisorPedidosSeeder
```

### Error: "Table 'roles' doesn't exist"
**Causa**: Migraciones no se han ejecutado
**Solución**: Ejecutar migraciones primero

```bash
php artisan migrate
php artisan db:seed --class=SetupSupervisorPedidosSeeder
```

### Error: "User with ID 2 not found"
**Causa**: El usuario con ID 2 no existe
**Solución**: Cambiar el ID en el seeder o crear el usuario primero

```php
// En AssignSupervisorPedidosRoleSeeder.php
$user = User::find(1); // Cambiar a un ID que exista
```

### El rol se creó pero no se asignó
**Causa**: El usuario especificado no existe
**Solución**: Verificar que el usuario existe

```bash
php artisan tinker
User::all(); // Ver todos los usuarios
```

---

## 📋 Checklist

- [ ] Ejecutar `php artisan migrate` (si no se ha hecho)
- [ ] Ejecutar `php artisan db:seed --class=SetupSupervisorPedidosSeeder`
- [ ] Verificar que el rol existe en la BD
- [ ] Verificar que el usuario tiene el rol
- [ ] Acceder a `/supervisor-pedidos/`
- [ ] Ver tabla de órdenes

---

## 📁 Archivos de Seeders

```
database/seeders/
├── SupervisorPedidosRoleSeeder.php
├── AssignSupervisorPedidosRoleSeeder.php
└── SetupSupervisorPedidosSeeder.php
```

---

## 💡 Notas

- Los seeders verifican si el rol ya existe antes de crearlo
- No crearán duplicados si se ejecutan múltiples veces
- El seeder maestro es la forma más rápida de configurar todo
- Personaliza `AssignSupervisorPedidosRoleSeeder.php` según tus necesidades

---

**Fecha**: Diciembre 2025
**Versión**: 1.0
**Estado**: ✅ Listo para usar
