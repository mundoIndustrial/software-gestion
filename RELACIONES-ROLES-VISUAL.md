# 📊 RELACIONES DE ROLES - Diagrama Visual

## 🏗️ Estructura de Base de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    TABLA: roles                             │
├─────────────────────────────────────────────────────────────┤
│ id (PK)  │ name        │ description         │ requires_cred │
├──────────┼─────────────┼─────────────────────┼───────────────┤
│ 1        │ admin       │ Administrador       │ true          │
│ 2        │ contador    │ Contador            │ true          │
│ 3        │ supervisor  │ Supervisor          │ true          │
│ 4        │ insumos     │ Gestor de Insumos   │ true          │
│ 5        │ asesor      │ Asesor de Ventas    │ true          │
└──────────┴─────────────┴─────────────────────┴───────────────┘
```

```
┌──────────────────────────────────────────────────────────────────────────┐
│                         TABLA: users                                     │
├──────────────────────────────────────────────────────────────────────────┤
│ id │ name  │ email              │ role_id │ roles_ids        │ ...      │
├────┼───────┼────────────────────┼─────────┼──────────────────┼──────────┤
│ 1  │ Juan  │ juan@example.com   │ 1       │ [1, 3, 5]        │ ...      │
│ 2  │ María │ maria@example.com  │ 2       │ [2, 4]           │ ...      │
│ 3  │ Carlos│ carlos@example.com │ NULL    │ []               │ ...      │
│ 4  │ Ana   │ ana@example.com    │ 1       │ [1]              │ ...      │
└────┴───────┴────────────────────┴─────────┴──────────────────┴──────────┘
```

---

## 🔗 Relaciones

### Relación 1: User → Role (Rol Principal)

```
User (role_id)  ──FK──→  Role (id)

Juan (role_id=1)  ──→  admin (id=1)
María (role_id=2) ──→  contador (id=2)
Carlos (role_id=NULL) ──→ (sin rol principal)
```

### Relación 2: User → Roles (Múltiples Roles)

```
User (roles_ids JSON)  ──JSON_CONTAINS──→  Role (id)

Juan [1, 3, 5]  ──→  admin (1)
                ──→  supervisor (3)
                ──→  asesor (5)

María [2, 4]    ──→  contador (2)
                ──→  insumos (4)

Carlos []       ──→  (sin roles)
```

### Relación 3: Role → Users (Múltiples)

```
Role (id)  ──JSON_CONTAINS──→  User (roles_ids)

admin (1)       ──→  Juan [1, 3, 5]
                ──→  Ana [1]

contador (2)    ──→  María [2, 4]

supervisor (3)  ──→  Juan [1, 3, 5]

insumos (4)     ──→  María [2, 4]

asesor (5)      ──→  Juan [1, 3, 5]
```

---

## 📈 Ejemplo Completo: Usuario Juan

### Datos en BD

```json
{
  "id": 1,
  "name": "Juan",
  "email": "juan@example.com",
  "role_id": 1,
  "roles_ids": [1, 3, 5],
  "created_at": "2025-12-02T09:00:00Z"
}
```

### Relaciones

```
Juan (User)
├── role() ──→ Role (id=1, name='admin')
│
└── roles() ──→ Collection [
    ├── Role (id=1, name='admin')
    ├── Role (id=3, name='supervisor')
    └── Role (id=5, name='asesor')
]
```

### Consultas

```php
$user = User::find(1); // Juan

// Obtener rol principal
$user->role; // Role {id: 1, name: 'admin'}

// Obtener todos los roles
$user->roles(); // Collection [
//   Role {id: 1, name: 'admin'},
//   Role {id: 3, name: 'supervisor'},
//   Role {id: 5, name: 'asesor'}
// ]

// Verificar roles
$user->hasRole('admin'); // true
$user->hasRole('supervisor'); // true
$user->hasRole('contador'); // false

// Obtener nombres
$user->roles()->pluck('name'); // ['admin', 'supervisor', 'asesor']
```

---

## 🔄 Ejemplo Completo: Rol Admin

### Datos en BD

```json
{
  "id": 1,
  "name": "admin",
  "description": "Administrador",
  "requires_credentials": true
}
```

### Relaciones

```
Role: admin (id=1)
├── users() ──→ Collection [
│   └── User {id: 4, name: 'Ana', role_id: 1}
]
│
├── usersWithJsonRole() ──→ Collection [
│   ├── User {id: 1, name: 'Juan', roles_ids: [1, 3, 5]}
│   └── User {id: 4, name: 'Ana', roles_ids: [1]}
]
│
└── allUsers() ──→ Collection [
    ├── User {id: 1, name: 'Juan', roles_ids: [1, 3, 5]}
    ├── User {id: 4, name: 'Ana', roles_ids: [1]}
]
```

### Consultas

```php
$role = Role::find(1); // admin

// Usuarios con role_id = 1
$role->users(); // Collection [Ana]

// Usuarios con 1 en roles_ids
$role->usersWithJsonRole(); // Collection [Juan, Ana]

// Todos los usuarios con este rol
$role->allUsers(); // Collection [Juan, Ana]

// Contar usuarios
$role->countAllUsers(); // 2

// Verificar si usuario tiene este rol
$role->allUsers()->contains(User::find(1)); // true
```

---

## 📊 Matriz de Relaciones

| Usuario | role_id | roles_ids | admin | contador | supervisor | insumos | asesor |
|---------|---------|-----------|-------|----------|------------|---------|--------|
| Juan    | 1       | [1,3,5]   | ✅    | ❌       | ✅         | ❌      | ✅     |
| María   | 2       | [2,4]     | ❌    | ✅       | ❌         | ✅      | ❌     |
| Carlos  | NULL    | []        | ❌    | ❌       | ❌         | ❌      | ❌     |
| Ana     | 1       | [1]       | ✅    | ❌       | ❌         | ❌      | ❌     |

---

## 🔀 Flujo de Sincronización

### Agregar Rol

```
User.addRole(3)
    ↓
roles_ids = [1, 3, 5] → [1, 3, 5, 3] (sin duplicados)
    ↓
Guardado en BD
    ↓
Role.allUsers() incluye este usuario
```

### Eliminar Rol

```
User.removeRole(3)
    ↓
roles_ids = [1, 3, 5] → [1, 5]
    ↓
Guardado en BD
    ↓
Role.allUsers() NO incluye este usuario (si no tiene otros roles)
```

### Reemplazar Roles

```
User.setRoles([2, 4])
    ↓
roles_ids = [1, 3, 5] → [2, 4]
    ↓
Guardado en BD
    ↓
Roles anteriores NO incluyen este usuario
    ↓
Nuevos roles incluyen este usuario
```

---

## 🎯 Casos de Uso

### Caso 1: Verificar Permisos

```php
$user = User::find(1); // Juan

// ¿Es admin?
if ($user->hasRole('admin')) {
    // Acceso a panel de administración
}

// ¿Es supervisor o admin?
if ($user->hasAnyRole(['admin', 'supervisor'])) {
    // Acceso a supervisión
}

// ¿Tiene TODOS estos roles?
if ($user->hasAllRoles(['admin', 'supervisor'])) {
    // Acceso especial
}
```

### Caso 2: Listar Usuarios por Rol

```php
$role = Role::find(1); // admin

// Todos los admins
$admins = $role->allUsers();

foreach ($admins as $admin) {
    echo $admin->name; // Juan, Ana
}
```

### Caso 3: Cambiar Roles de Usuario

```php
$user = User::find(1); // Juan

// Agregar supervisor
$user->addRole(3);

// Eliminar asesor
$user->removeRole(5);

// Reemplazar todos
$user->setRoles([1, 2, 3]);
```

### Caso 4: Reportes

```php
// Usuarios por rol
Role::all()->map(function ($role) {
    return [
        'role' => $role->name,
        'users' => $role->countAllUsers(),
    ];
});

// Usuarios con múltiples roles
User::all()->filter(function ($user) {
    return count($user->roles_ids) > 1;
});

// Roles de cada usuario
User::all()->map(function ($user) {
    return [
        'name' => $user->name,
        'roles' => $user->roles()->pluck('name'),
    ];
});
```

---

## 🔐 Integridad de Datos

### Validaciones

```php
// Validar que role_id existe en tabla roles
$user->role_id; // Debe existir en roles.id o ser NULL

// Validar que todos los IDs en roles_ids existen en tabla roles
$user->roles_ids; // Cada ID debe existir en roles.id

// Validar que no hay duplicados
$user->roles_ids; // [1, 3, 5] ✅ (sin duplicados)
$user->roles_ids; // [1, 3, 3, 5] ❌ (duplicados)
```

### Constraints

```sql
-- role_id debe existir en roles
ALTER TABLE users ADD CONSTRAINT fk_role_id
FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- roles_ids es JSON, validado en aplicación
-- (MySQL no soporta FK en JSON directamente)
```

---

## 📝 Métodos Disponibles

### En User Model

```php
$user->role()              // BelongsTo Role (role_id)
$user->roles()             // Collection de Roles (roles_ids)
$user->hasRole($role)      // bool
$user->hasAnyRole($roles)  // bool
$user->hasAllRoles($roles) // bool
$user->addRole($roleId)    // void
$user->removeRole($roleId) // void
$user->setRoles($roleIds)  // void
$user->syncRoles($roleIds) // void
```

### En Role Model

```php
$role->users()             // HasMany User (role_id)
$role->usersWithJsonRole() // Collection de Users (roles_ids)
$role->allUsers()          // Collection de Users (role_id + roles_ids)
$role->countAllUsers()     // int
```

---

## ✅ Resumen

- ✅ **Relación 1:N** vía `role_id` (rol principal)
- ✅ **Relación N:N** vía `roles_ids` JSON (múltiples roles)
- ✅ **Backward compatible** (mantiene `role_id`)
- ✅ **Bidireccional** (User ↔ Role)
- ✅ **Eficiente** (JSON queries en MySQL)
- ✅ **Flexible** (agregar/quitar roles fácilmente)

---

**Fecha:** 2 de Diciembre de 2025

**Versión:** 1.0
