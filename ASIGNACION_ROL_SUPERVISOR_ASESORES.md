# Asignación del Rol supervisor_asesores a Usuarios

## Problema
Cuando un usuario intenta iniciar sesión con el rol `supervisor_asesores`, no es redireccionado al dashboard correspondiente.

## Solución

El rol `supervisor_asesores` fue insertado en la base de datos, pero los usuarios **no tienen asignado este rol**. 

### Paso 1: Verificar que el rol existe

```sql
SELECT * FROM roles WHERE name = 'supervisor_asesores';
```

**Resultado esperado:** 1 fila con el role_id (ejemplo: id = 7)

### Paso 2: Asignar el rol a un usuario (Opción A - SQL Directo)

```sql
UPDATE users 
SET roles_ids = JSON_ARRAY(7)  -- Cambiar 7 por el ID real del rol supervisor_asesores
WHERE id = 1;  -- Cambiar 1 por el ID del usuario
```

**Nota:** Si el usuario ya tiene otros roles, usar:
```sql
UPDATE users 
SET roles_ids = JSON_ARRAY(7, 2, 3)  -- Mantener otros roles + agregar supervisor_asesores
WHERE id = 1;
```

### Paso 3: Asignar el rol a un usuario (Opción B - Artisan Command)

Si existe un comando personalizado:
```bash
php artisan user:assign-role {user_id} {role_id}
```

### Paso 4: Verificar asignación

```sql
SELECT id, name, roles_ids FROM users WHERE id = 1;
```

**Resultado esperado:** `roles_ids` contiene el ID del rol supervisor_asesores

### Paso 5: Limpiar sesión y reintentar login

1. Cerrar todas las sesiones del navegador
2. Limpiar caché del navegador (Ctrl + Shift + Delete)
3. Ir a `/login` y volver a iniciar sesión

## Flujo de Redirección después del Login

Una vez asignado el rol, el flujo será:

1. ✅ Usuario ingresa credenciales en `/login`
2. ✅ `AuthenticatedSessionController@store()` verifica el rol
3. ✅ Detecta `supervisor_asesores`
4. ✅ Redirige a `/supervisor-asesores/dashboard`
5. ✅ El middleware `role:supervisor_asesores,admin` permite acceso

## Roles Implementados

| Rol | Nombre BD | Redirección |
|-----|-----------|-------------|
| Asesor | `asesor` | `/asesores/dashboard` |
| Contador | `contador` | `/contador/dashboard` |
| Supervisor | `supervisor` | `/registros` |
| Supervisor Planta | `supervisor_planta` | `/registros` |
| Insumos | `insumos` | `/insumos/materiales` |
| Patronista | `patronista` | `/insumos/materiales` |
| Aprobador de Cotizaciones | `aprobador_cotizaciones` | `/cotizaciones/pendientes` |
| Supervisor de Pedidos | `supervisor_pedidos` | `/supervisor-pedidos` |
| **Supervisor de Asesores** | **`supervisor_asesores`** | **/supervisor-asesores/dashboard** |
| Cortador | `cortador` | `/operario/dashboard` |
| Costurero | `costurero` | `/operario/dashboard` |
| Admin | `admin` | `/dashboard` |

## Archivos Modificados

1. **app/Http/Controllers/Auth/AuthenticatedSessionController.php**
   - Agregada condición para detectar rol `supervisor_asesores`
   - Redirige a `supervisor-asesores.dashboard`

## Verificación Final

Una vez completados los pasos:

1. ✅ El usuario puede iniciar sesión
2. ✅ Es redireccionado automáticamente a `/supervisor-asesores/dashboard`
3. ✅ Puede acceder a todas las rutas del supervisor de asesores
4. ✅ El middleware protege rutas no autorizadas

## Troubleshooting

**Problema:** "Acceso denegado" o "No autorizado"
- Solución: Verificar que `roles_ids` en la BD contiene el ID correcto del rol

**Problema:** "Ruta no encontrada" 
- Solución: Ejecutar `php artisan route:list` y verificar que exista `supervisor-asesores.dashboard`

**Problema:** Se redirige a otra página después del login
- Solución: Verificar que no hay middleware conflictivo en `routes/web.php`

## SQL Automatizado para Asignar Rol

```sql
-- 1. Obtener el ID del rol supervisor_asesores
SELECT @role_id := id FROM roles WHERE name = 'supervisor_asesores' LIMIT 1;

-- 2. Asignar el rol al usuario (reemplazar XXX con el ID del usuario)
UPDATE users 
SET roles_ids = JSON_ARRAY(@role_id)
WHERE id = XXX;

-- 3. Verificar asignación
SELECT id, name, roles_ids FROM users WHERE id = XXX;
```
