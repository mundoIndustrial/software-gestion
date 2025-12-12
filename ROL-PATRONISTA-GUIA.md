# 📋 ROL PATRONISTA - GUÍA COMPLETA

## 🎯 Objetivo
Crear un rol **Patronista** que tenga acceso de **solo lectura** al módulo de **Insumos**, permitiendo visualizar información sin poder editar, crear o eliminar registros.

---

## ✅ COMPONENTES IMPLEMENTADOS

### 1. **Rol en Base de Datos**
- **Archivo**: `database/seeders/RolesSeeder.php`
- **Nombre**: `patronista`
- **Descripción**: "Patronista - Visualización de insumos (solo lectura)"
- **requires_credentials**: `true`

### 2. **Middleware de Control de Acceso**
- **Archivo**: `app/Http/Middleware/PatronistaReadOnly.php`
- **Funcionalidad**:
  - Permite solicitudes GET (lectura)
  - Bloquea solicitudes POST, PATCH, PUT, DELETE (escritura)
  - Retorna error 403 con mensaje descriptivo

### 3. **Registro del Middleware**
- **Archivo**: `bootstrap/app.php`
- **Alias**: `patronista-readonly`
- **Aplicado a**: Rutas de Insumos

### 4. **Actualización de Middleware de Insumos**
- **Archivo**: `app/Http/Middleware/InsumosAccess.php`
- **Cambio**: Agregado soporte para rol `patronista`
- **Permite acceso a**: admin, supervisor-admin, supervisor_planta, patronista

### 5. **Rutas Configuradas**
- **Archivo**: `modules/insumos/backend/Routes/web.php`
- **Middleware aplicado**: `auth`, `insumos-access`, `patronista-readonly`
- **Rutas permitidas**:
  - GET `/insumos/dashboard` ✅
  - GET `/insumos/materiales` ✅
  - GET `/insumos/api/materiales/{numeroPedido}` ✅
  - GET `/insumos/api/filtros/{column}` ✅
  - POST `/insumos/materiales/{numeroPedido}` ❌ (bloqueado)
  - POST `/insumos/materiales/{numeroPedido}/eliminar` ❌ (bloqueado)

### 6. **Vista Modificada**
- **Archivo**: `resources/views/insumos/materiales/index.blade.php`
- **Cambio**: Condición `@if(auth()->user()->role !== 'patronista')`
- **Resultado**: 
  - Patronista ve solo botón "Ver" (ojo azul)
  - Otros roles ven todos los botones (Ver, Insumos, Enviar a producción)

---

## 🔐 FLUJO DE SEGURIDAD

```
Usuario Patronista intenta acceder a Insumos
    ↓
Middleware InsumosAccess verifica rol
    ↓
✅ Rol patronista permitido → Acceso a Insumos
    ↓
Usuario ve lista de órdenes (solo lectura)
    ↓
Usuario intenta hacer clic en botón "Enviar a producción"
    ↓
Middleware PatronistaReadOnly bloquea POST
    ↓
❌ Error 403: "No tienes permiso para realizar esta acción"
```

---

## 📊 PERMISOS POR ACCIÓN

| Acción | Patronista | Admin | Supervisor Admin | Supervisor Planta |
|--------|-----------|-------|------------------|-------------------|
| Ver Dashboard | ✅ | ✅ | ✅ | ✅ |
| Ver Materiales | ✅ | ✅ | ✅ | ✅ |
| Ver Orden | ✅ | ✅ | ✅ | ✅ |
| Ver Insumos | ✅ | ✅ | ✅ | ✅ |
| Crear Material | ❌ | ✅ | ✅ | ✅ |
| Editar Material | ❌ | ✅ | ✅ | ✅ |
| Eliminar Material | ❌ | ✅ | ✅ | ✅ |
| Enviar a Producción | ❌ | ✅ | ✅ | ✅ |

---

## 👤 CÓMO CREAR UN USUARIO PATRONISTA

### 1. Ejecutar el Seeder
```bash
php artisan db:seed --class=RolesSeeder
```

### 2. Crear Usuario en la Aplicación
1. Ir a **Usuarios** (módulo de administración)
2. Hacer clic en **Crear Usuario**
3. Completar datos:
   - **Nombre**: Ej. "Juan Patronista"
   - **Email**: Ej. "juan@patronista.com"
   - **Contraseña**: Contraseña segura
   - **Rol**: Seleccionar **"Patronista"**
4. Guardar

### 3. Acceder a Insumos
1. Iniciar sesión con el usuario Patronista
2. Ir a `/insumos/materiales`
3. Ver lista de órdenes (solo lectura)

---

## 🧪 CÓMO PROBAR

### Test 1: Acceso a Lectura
1. Iniciar sesión como Patronista
2. Acceder a `/insumos/materiales`
3. ✅ Debe mostrar lista de órdenes
4. ✅ Debe mostrar solo botón "Ver"

### Test 2: Bloqueo de Escritura
1. Iniciar sesión como Patronista
2. Intentar hacer clic en "Enviar a producción"
3. ❌ Debe mostrar error 403
4. Verificar en consola del navegador (F12):
   ```
   POST /insumos/materiales/... 403 Forbidden
   ```

### Test 3: Bloqueo de Eliminación
1. Iniciar sesión como Patronista
2. Intentar eliminar un material
3. ❌ Debe mostrar error 403

### Test 4: Comparación de Botones
1. Iniciar sesión como Admin
2. Ver `/insumos/materiales`
3. ✅ Debe mostrar 3 botones (Ver, Insumos, Enviar a producción)
4. Cambiar a usuario Patronista
5. ✅ Debe mostrar solo 1 botón (Ver)

---

## 📁 ARCHIVOS CREADOS/MODIFICADOS

### Creados
- ✅ `app/Http/Middleware/PatronistaReadOnly.php` (nuevo)
- ✅ `ROL-PATRONISTA-GUIA.md` (este archivo)

### Modificados
- ✅ `database/seeders/RolesSeeder.php` (agregado rol patronista)
- ✅ `bootstrap/app.php` (registrado middleware)
- ✅ `app/Http/Middleware/InsumosAccess.php` (agregado soporte patronista)
- ✅ `modules/insumos/backend/Routes/web.php` (aplicado middleware)
- ✅ `resources/views/insumos/materiales/index.blade.php` (condición de botones)

---

## 🔍 VERIFICACIÓN TÉCNICA

### Middleware PatronistaReadOnly
```php
// Permite GET
GET /insumos/materiales → 200 OK ✅

// Bloquea POST
POST /insumos/materiales/123 → 403 Forbidden ❌

// Bloquea DELETE
DELETE /insumos/materiales/123 → 403 Forbidden ❌
```

### Vista de Insumos
```blade
@if(auth()->user()->role !== 'patronista')
    <!-- Botones de edición/eliminación -->
@endif
```

---

## 🎯 GARANTÍAS

✅ Patronista solo puede ver (GET)
✅ Patronista no puede crear (POST bloqueado)
✅ Patronista no puede editar (PATCH bloqueado)
✅ Patronista no puede eliminar (DELETE bloqueado)
✅ Interfaz adaptada (solo botón "Ver")
✅ Mensajes de error claros
✅ Seguridad en backend + frontend
✅ Compatible con otros roles

---

## 🚀 PRÓXIMOS PASOS (Opcionales)

1. **Agregar más módulos de solo lectura**
   - Cotizaciones (solo ver)
   - Pedidos (solo ver)
   - Reportes (solo ver)

2. **Crear dashboard personalizado para Patronista**
   - Estadísticas de insumos
   - Gráficos de consumo
   - Alertas de stock bajo

3. **Agregar auditoría**
   - Registrar qué vio el Patronista
   - Logs de acceso
   - Reportes de actividad

4. **Integrar con otros sistemas**
   - Sincronización con ERP
   - Exportación de reportes
   - Notificaciones por email

---

## 📞 SOPORTE

Si tienes problemas:

1. **Verificar que el rol existe**
   ```bash
   php artisan tinker
   >>> App\Models\Role::where('name', 'patronista')->first()
   ```

2. **Verificar que el usuario tiene el rol**
   ```bash
   php artisan tinker
   >>> $user = App\Models\User::find(1);
   >>> $user->roles
   ```

3. **Verificar logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Limpiar caché**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

---

## ✨ ESTADO FINAL

**✅ COMPLETADO Y FUNCIONAL**

El rol Patronista está completamente implementado y listo para usar. Los usuarios con este rol pueden:
- ✅ Ver dashboard de insumos
- ✅ Ver lista de órdenes
- ✅ Ver detalles de órdenes
- ✅ Ver insumos de cada orden
- ❌ NO pueden crear, editar o eliminar nada

