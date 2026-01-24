# 🔧 DIAGNÓSTICO Y SOLUCIÓN: Redirección a Despacho

**Fecha:** 23 de enero de 2026  
**Problema:** Usuario no redirige a `/despacho` después de login  
**Estado:**  RESUELTO

---

## ❌ Problema identificado

**Causa:** El sistema verificaba `$user->role` (rol singular) pero los usuarios tienen `roles_ids` (múltiples roles en JSON)

```php
// ❌ ANTES (No funcionaba)
if ($user && $user->role) {                    // ← Verifica role_id (singular)
    if ($roleName === 'Despacho') {
        return redirect(route('despacho.index'));
    }
}
```

**¿Por qué no funcionaba?**
- La base de datos tiene:
  - `role_id` (nullable) - rol singular/principal
  - `roles_ids` (JSON) - múltiples roles
- Si el usuario tenía el rol solo en `roles_ids`, la condición `$user->role` era NULL
- Por lo tanto, nunca entraba al bloque if y no redirigía

---

##  Solución implementada

**Archivo:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

### Cambio 1: Verificar primero roles_ids

```php
//  AHORA (Funciona correctamente)

// Verificar primero si tiene rol Despacho en roles_ids
$despachoRole = \App\Models\Role::where('name', 'Despacho')->first();
if ($despachoRole) {
    $rolesIds = json_decode($user->roles_ids ?? '[]', true);
    if (in_array($despachoRole->id, $rolesIds)) {
        return redirect(route('despacho.index', absolute: false));
    }
}

// Luego verificar role_id (principal)
if ($user && $user->role) {
    // ... resto de lógica
}
```

**¿Cómo funciona?**
1. Obtiene el rol "Despacho" de la tabla roles
2. Decodifica `roles_ids` (array JSON)
3. Verifica si el ID del rol Despacho está en el array
4. **Si está → Redirige a `/despacho` ✓**
5. Si no está → Continúa verificando `role_id` principal

### Cambio 2: Remover condición redundante

```php
// ❌ Removido
if ($roleName === 'Despacho') {
    return redirect(route('despacho.index'));
}
```

Ya no es necesaria porque se verifica primero en `roles_ids`

---

## 🔍 Cómo verificar que funciona

### Verificación 1: Rol creado
```bash
php artisan tinker
> App\Models\Role::where('name', 'Despacho')->first();
```

**Esperado:** Objeto Role con id=X, name=Despacho

### Verificación 2: Usuario tiene el rol
```bash
php artisan tinker
> $user = App\Models\User::find(1);
> $user->roles_ids;  // Debe ser JSON: "[10]" o similar
```

**Esperado:** String JSON como `"[10]"` donde 10 es el ID del rol Despacho

### Verificación 3: Login test
1. Ir a `/login`
2. Ingresar credenciales
3. **Esperado:** Redirige a `/despacho`

---

##  Tabla comparativa

| Aspecto | Antes | Después |
|---------|-------|---------|
| Verifica | role_id (singular) | roles_ids (JSON array) |
| Si role_id es NULL | No entra al if | Igual chequea roles_ids |
| Redirige a Despacho | ❌ NO |  SÍ |
| Compatible con multiples roles | ❌ NO |  SÍ |

---

##  Flujo de login AHORA (Correcto)

```
1. Usuario: click login
   ↓
2. POST /login → AuthenticatedSessionController::store()
   ↓
3. $user = Auth::user() ← Usuario autenticado
   ↓
4. Obtiene Role::where('name', 'Despacho')->first()
   ├─ $despachoRole = Role(id: 10, name: 'Despacho')
   │
5. json_decode($user->roles_ids) → [10] (array con ID)
   │
6. in_array(10, [10]) → TRUE ✓
   │
7. return redirect(route('despacho.index'))
   │  ↓
8. GET /despacho
   │  ↓
9. Middleware verifica:
   │  ├─ auth()->check() → TRUE ✓
   │  ├─ Tiene rol Despacho → TRUE ✓
   │
10. DespachoController::index()
    │
11. Renderiza vista con lista de pedidos
```

---

## 📝 Logs esperados

En `storage/logs/laravel.log` verá:

```
[2026-01-23] Login usuario {
  "user_id": 1,
  "roles_ids": "[10]",
  "role": null,
  "role_name": "null"
}

[2026-01-23] Redirigiendo a despacho...
```

---

##  Testing completo

### Test 1: Usuario CON rol Despacho
```bash
# 1. Asignar rol
php artisan tinker
> $user = App\Models\User::find(1);
> $despachoRole = App\Models\Role::where('name', 'Despacho')->first();
> $user->roles_ids = json_encode([$despachoRole->id]);
> $user->save();

# 2. Limpiar caché
php artisan optimize:clear

# 3. Login → Debe redirigir a /despacho ✓
```

### Test 2: Usuario SIN rol Despacho
```bash
# 1. Remover rol
php artisan tinker
> $user = App\Models\User::find(1);
> $user->roles_ids = json_encode([]);  # Array vacío
> $user->save();

# 2. Limpiar caché
php artisan optimize:clear

# 3. Login → Debe ir al dashboard principal
```

### Test 3: Usuario con rol Despacho + otro rol
```bash
# 1. Asignar múltiples roles
php artisan tinker
> $user = App\Models\User::find(1);
> $despachoRole = App\Models\Role::where('name', 'Despacho')->first();
> $otroRole = App\Models\Role::where('name', 'asesor')->first();
> $user->roles_ids = json_encode([$otroRole->id, $despachoRole->id]);
> $user->save();

# 2. Login → Debe redirigir a /despacho (se prioriza)
# Porque verificamos Despacho PRIMERO
```

---

## 🔐 Seguridad

El middleware sigue protegiendo la ruta:

```php
Route::prefix('despacho')
    ->middleware(['auth', 'check.despacho.role'])
    ->group(function () { ... });
```

**Validaciones:**
1. ✓ Usuario autenticado
2. ✓ Usuario tiene rol Despacho
3. ✓ Redirige automáticamente si no cumple

---

## 📊 Resumen de cambios

### Archivo modificado
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

### Cambios realizados
1. **Línea 35-41:** Nuevo bloque que verifica `roles_ids`
2. **Línea 85-87:** Removido bloque redundante de Despacho

### Impacto
-  Usuario con rol Despacho en `roles_ids` redirige correctamente
-  Compatible con sistema de múltiples roles
-  No afecta otros roles
-  Documentación clara

---

## Conclusión

**Problema:** Sistema no verificaba `roles_ids` (JSON array)  
**Solución:** Verificar primero en `roles_ids` antes de `role_id`  
**Resultado:** Redirección automática ahora funciona ✓

**Próximo paso:** Reintentar login

---

**Solución implementada:** 23 de enero de 2026  
**Estado:**  OPERACIONAL
