#  Jerarquía de Roles - Sistema de Herencia

## Descripción

Un sistema **extensible** que permite que ciertos roles hereden automáticamente los permisos de otros roles, sin modificar el código existente ni las rutas.

**Ejemplo**: Un `supervisor_pedidos` ahora puede acceder a todas las rutas de `asesor` automáticamente.

---

##  Cómo Funciona

### 1. **Sin cambios en rutas**
Las rutas siguen usando el middleware exactamente igual:
```php
Route::get('asesores/pedidos/{id}/factura-datos', ...)->middleware('role:asesor');
```

### 2. **Jerarquía automática**
Si `supervisor_pedidos` está configurado para heredar de `asesor`:
- Usuario con `supervisor_pedidos` accede automáticamente a rutas de `asesor`
- **No hay cambios en el middleware**
- **No hay cambios en las rutas**

### 3. **Proceso interno**
```
1. Usuario intenta acceder → Middleware verifica
2. Se obtienen roles del usuario (ej: ['supervisor_pedidos'])
3. Se aplica jerarquía → Roles efectivos: ['supervisor_pedidos', 'asesor']
4. Se verifica contra rol requerido (asesor)
5.  Acceso permitido (porque tiene 'asesor' heredado)
```

---

##  Cómo Configurar la Jerarquía

### Archivo: `config/role-hierarchy.php`

```php
return [
    'hierarchy' => [
        // Rol hijo => [Roles padres]
        'supervisor_pedidos' => ['asesor'],  // supervisor hereda permisos de asesor
        'gerente' => ['supervisor_pedidos', 'asesor'],  // gerente hereda de dos roles
        'admin' => ['gerente', 'supervisor_pedidos', 'asesor'],  // admin hereda de todos
    ],
];
```

### Ejemplos de Configuración

**Caso 1: Jerarquía simple lineal**
```php
'supervisor_pedidos' => ['asesor'],
'gerente' => ['supervisor_pedidos'],
```
- `asesor` ← base
- `supervisor_pedidos` ← hereda de `asesor`
- `gerente` ← hereda de `supervisor_pedidos` (y transitivamente de `asesor`)

**Caso 2: Jerarquía múltiple**
```php
'supervisor_pedidos' => ['asesor', 'operador'],
'gerente' => ['supervisor_pedidos'],
```
- `supervisor_pedidos` hereda de DOS roles

**Caso 3: Admin accede a todo**
```php
'admin' => ['gerente', 'supervisor_pedidos', 'asesor'],
```

---

##  Ubicación de Archivos

```
config/
  └─ role-hierarchy.php          ← Configuración de jerarquía (crear)

app/Services/
  └─ RoleHierarchyService.php    ← Lógica de herencia (crear)

app/Http/Middleware/
  └─ CheckRole.php               ← Middleware existente (MODIFICADO SIN REEMPLAZAR)
```

---

##  Verificar que Funciona

### 1. Revisar configuración
```bash
php artisan tinker
>>> config('role-hierarchy')
```

### 2. Revisar logs
```bash
tail -f storage/logs/laravel.log
```

Buscar líneas con:
- `[MIDDLEWARE-CHECKROLE]  JERARQUÍA DE ROLES APLICADA` ← Herencia detectada
- `roles_heredados` ← Qué roles se heredaron
- `roles_efectivos_totales` ← Todos los roles con los que se verifica

---

##  Ejemplo de Logs

### Escenario: Usuario `supervisor_pedidos` accede a ruta de `asesor`

```
[2026-01-28 14:35:00] local.INFO: [MIDDLEWARE-CHECKROLE]  PARÁMETRO ROLES RECIBIDO 
{
  "parametro_roles_string":"asesor",
  "roles_parseados":["asesor"],
  "ruta":"asesores/pedidos/3/factura-datos",
  "método_http":"GET"
}

[2026-01-28 14:35:00] local.INFO: [MIDDLEWARE-CHECKROLE] ===== VERIFICACIÓN DE AUTORIZACIÓN INICIADA =====
{
  "usuario_id":93,
  "usuario_nombre":"yus2",
  "roles_requeridos":["asesor"]
}

[2026-01-28 14:35:00] local.INFO: [MIDDLEWARE-CHECKROLE] Roles configurados del usuario en BD
{
  "roles_ids_array":[11],
  "role_id_principal":null
}

[2026-01-28 14:35:00] local.DEBUG: [MIDDLEWARE-CHECKROLE] Roles obtenidos desde tabla roles (roles_ids)
{
  "roles_nombres":["supervisor_pedidos"],
  "cantidad":1
}

[2026-01-28 14:35:00] local.INFO: [MIDDLEWARE-CHECKROLE] Roles finales del usuario
{
  "usuario_id":93,
  "roles_finales":["supervisor_pedidos"]
}

⭐ NUEVO LOG - JERARQUÍA APLICADA:

[2026-01-28 14:35:00] local.INFO: [MIDDLEWARE-CHECKROLE]  JERARQUÍA DE ROLES APLICADA
{
  "usuario_id":93,
  "roles_originales":["supervisor_pedidos"],
  "roles_heredados":["asesor"],
  "roles_efectivos_totales":["supervisor_pedidos", "asesor"],
  "jerarquía_detectada":["supervisor_pedidos → [asesor]"]
}

[2026-01-28 14:35:00] local.DEBUG: [MIDDLEWARE-CHECKROLE] Rol encontrado
{
  "rol_buscado":"asesor",
  "usuario_tiene_rol":true
}

[2026-01-28 14:35:00] local.INFO:  [MIDDLEWARE-CHECKROLE] ACCESO PERMITIDO - Autorización exitosa
```

**¡Acceso permitido! 🎉**

---

##  Garantías de Seguridad

 **No elimina código existente**
- El middleware original funciona exactamente igual
- Solo se extiende la lógica

 **Compatible hacia atrás**
- Si no hay jerarquía configurada, funciona como antes
- Las rutas no se modifican

 **Escalable**
- Agregar nuevas jerarquías es solo editar `config/role-hierarchy.php`
- Soporta jerarquías profundas y múltiples

 **Auditable**
- Logs detallados muestran qué roles se heredaron y cuándo
- Fácil debugging

---

## Casos de Uso

### Caso 1: Supervisor ve reportes de Asesor
```php
// config/role-hierarchy.php
'supervisor_pedidos' => ['asesor'],

// routes/web.php - SIN CAMBIOS
Route::get('reportes-asesor', ...)->middleware('role:asesor');

// Usuario supervisor_pedidos:
//  Accede porque hereda permisos de asesor
```

### Caso 2: Gerente ve todo del departamento
```php
// config/role-hierarchy.php
'gerente' => ['supervisor_pedidos', 'asesor', 'operador'],

// Cualquier ruta que pida supervisor_pedidos, asesor u operador:
//  Gerente accede automáticamente
```

### Caso 3: Admin accede a todo
```php
// El rol 'admin' ya tiene acceso especial en el middleware
// Pero puedes agregarlo a la jerarquía para mantener consistencia:
'admin' => ['gerente', 'supervisor_pedidos', 'asesor'],
```

---

##  Limitaciones Conocidas

- Las jerarquías **no deben tener ciclos** (asesor → supervisor → asesor)
  - El servicio previene loops infinitos, pero la configuración debe ser lógica
- El sistema se evalúa **en cada request** (pequeño overhead pero negligible)
- Los permisos se heredan, no se pueden "excluir" selectivamente

---

##  Cómo Escalar en el Futuro

### Para agregar nuevos roles heredados:

1. **Editar `config/role-hierarchy.php`**
   ```php
   'nuevo_rol' => ['rol_padre'],
   ```

2. **Listo**. No hay más cambios necesarios.

### Para auditar/debuggear:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep "JERARQUÍA"

# O en tinker
php artisan tinker
>>> Log::tail()
```

### Para deshabilitar temporalmente:

```php
// En config/role-hierarchy.php
'hierarchy' => [], // Vacío = sin herencia
```

---

##  Testing

Para verificar que la jerarquía funciona:

```php
// tests/Unit/RoleHierarchyTest.php
public function test_supervisor_hereda_permisos_de_asesor()
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::whereName('supervisor_pedidos')->first());
    
    $effective = \App\Services\RoleHierarchyService::getEffectiveRoles(['supervisor_pedidos']);
    
    $this->assertContains('asesor', $effective);
}
```

---

**Sistema creado: 28 de Enero de 2026**
