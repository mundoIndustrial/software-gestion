# 📋 IMPLEMENTACIÓN: Edición y Eliminación de Procesos para Producción

**Fecha:** Diciembre 3, 2025  
**Objetivo:** Permitir que usuarios con rol "producción" puedan editar, asignar personas y borrar procesos desde el modal de seguimiento de órdenes.

---

## ✅ Cambios Realizados

### 1. **Backend: Actualizar Permisos en OrdenController** 
**Archivo:** `app/Http/Controllers/OrdenController.php`

#### Cambio 1: Método `editarProceso()` (línea ~480)
```php
// ANTES:
if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
    return response()->json([
        'success' => false,
        'message' => 'Solo administradores pueden editar procesos'
    ], 403);
}

// AHORA:
$userRole = auth()->user()->role?->name;
$isAllowed = in_array($userRole, ['admin', 'produccion']);

if (!$isAllowed) {
    return response()->json([
        'success' => false,
        'message' => 'No tienes permiso para editar procesos'
    ], 403);
}
```

#### Cambio 2: Método `eliminarProceso()` (línea ~550)
```php
// ANTES:
if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
    return response()->json([
        'success' => false,
        'message' => 'Solo administradores pueden eliminar procesos'
    ], 403);
}

// AHORA:
$userRole = auth()->user()->role?->name;
$isAllowed = in_array($userRole, ['admin', 'produccion']);

if (!$isAllowed) {
    return response()->json([
        'success' => false,
        'message' => 'No tienes permiso para eliminar procesos'
    ], 403);
}
```

**Razón:** Permitir que ambos roles puedan modificar procesos.

---

### 2. **Frontend: Actualizar Lógica de Visualización de Botones**
**Archivo:** `public/js/order-tracking/modules/trackingUI.js`

#### Cambio: Función `createProcessCard()` (línea ~88)
```javascript
// ANTES:
const isAdmin = document.body.getAttribute('data-is-admin') === 'true';

let topRightButtons = '';
if (isAdmin) {
    topRightButtons = createAdminButtons(proceso, orderData);
}

// AHORA:
// Verificar si el usuario puede editar procesos (admin o produccion)
const userRole = document.body.getAttribute('data-user-role');
const canEditProcess = userRole === 'admin' || userRole === 'produccion';

let topRightButtons = '';
if (canEditProcess) {
    topRightButtons = createAdminButtons(proceso, orderData);
}
```

**Razón:** Usar el atributo `data-user-role` del layout base que ya contiene el rol actual del usuario.

---

### 3. **Frontend: Implementar Event Listeners para Botones**
**Archivo:** `public/js/order-tracking/orderTracking-v2.js`

#### Cambio: Función `attachProcessButtonListeners()` (línea ~104)
Implementada completamente la función que antes solo tenía un comentario:

```javascript
function attachProcessButtonListeners(procesos) {
    // Agregar event listeners a botones de editar
    const editButtons = document.querySelectorAll('.btn-editar-proceso');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Encontrar el proceso asociado al botón
            const card = this.closest('.tracking-area-card');
            const areaNameElement = card.querySelector('.tracking-area-name span:last-child');
            const processName = areaNameElement ? areaNameElement.textContent.trim() : '';
            
            // Buscar el proceso en la lista
            const proceso = procesos.find(p => p.proceso === processName);
            if (proceso) {
                editarProceso(JSON.stringify(proceso));
            }
        });
    });
    
    // Agregar event listeners a botones de eliminar
    const deleteButtons = document.querySelectorAll('.btn-eliminar-proceso');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Encontrar el proceso asociado al botón
            const card = this.closest('.tracking-area-card');
            const areaNameElement = card.querySelector('.tracking-area-name span:last-child');
            const processName = areaNameElement ? areaNameElement.textContent.trim() : '';
            
            // Buscar el proceso en la lista
            const proceso = procesos.find(p => p.proceso === processName);
            if (proceso) {
                eliminarProceso(JSON.stringify(proceso));
            }
        });
    });
}
```

**Razón:** Vincular los eventos click de los botones con las funciones globales `editarProceso()` y `eliminarProceso()`.

---

## 🎯 Funcionalidades Habilitadas para "Producción"

Ahora los usuarios con rol **"producción"** pueden:

1. ✅ **Editar Procesos**
   - Cambiar el nombre del proceso
   - Modificar la fecha de inicio
   - Asignar o cambiar el encargado
   - Cambiar el estado (Pendiente, En Progreso, Completado, Pausado)

2. ✅ **Eliminar Procesos**
   - Borrar procesos (con confirmación)
   - No puede borrar el último proceso de una orden

3. ✅ **Visualizar Cambios**
   - Los cambios se reflejan inmediatamente en el timeline
   - El modal se recarga automáticamente después de guardar

---

## 🔒 Seguridad

- ✅ Backend valida que el usuario sea "admin" o "produccion"
- ✅ Frontend solo muestra botones si el usuario tiene uno de estos roles
- ✅ El rol se valida en el atributo `data-user-role` del `<body>`
- ✅ Protección contra eliminación del último proceso
- ✅ Confirmación de SweetAlert2 antes de eliminar

---

## 🧪 Cómo Probar

1. **Iniciar sesión** con un usuario que tenga rol "producción"
2. **Ir a la tabla de órdenes** (Registro de Órdenes)
3. **Hacer clic** en el botón "Ver → Seguimiento" de cualquier orden
4. **En el modal** deberías ver los botones "✏️ Editar" y "🗑️ Eliminar" en cada proceso
5. **Hacer clic** en "Editar" para cambiar detalles del proceso
6. **Hacer clic** en "Eliminar" para borrar el proceso (con confirmación)

---

## 📝 Archivos Modificados

| Archivo | Línea | Tipo Cambio |
|---------|-------|-----------|
| `app/Http/Controllers/OrdenController.php` | ~480 | Actualizar validación en `editarProceso()` |
| `app/Http/Controllers/OrdenController.php` | ~550 | Actualizar validación en `eliminarProceso()` |
| `public/js/order-tracking/modules/trackingUI.js` | ~88 | Cambiar lógica de visualización de botones |
| `public/js/order-tracking/orderTracking-v2.js` | ~104 | Implementar event listeners |

---

## 🔗 Relaciones

- **Controlador de API:** `app/Http/Controllers/OrdenController.php`
- **Modelo de Proceso:** `app/Models/ProcesosPrenda.php`
- **Modal Blade:** `resources/views/components/orders-components/order-tracking-modal.blade.php`
- **Scripts de Tracking:** `/public/js/order-tracking/`

---

## 🔧 FIX: Error "Swal is not defined"

**Problema:** Al hacer clic en eliminar un proceso, se mostraba el error `Uncaught (in promise) ReferenceError: Swal is not defined` en `processManager.js` línea 157.

**Causa:** SweetAlert2 CSS estaba cargado pero el script JS no.

**Solución:** Agregué el script de SweetAlert2 en `resources/views/layouts/base.blade.php`

```php
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

**Archivo Modificado:** `resources/views/layouts/base.blade.php` (línea ~46)

---

## ✨ Notas Importantes

- Los permisos se verifican **en el backend** (OrdenController)
- La visualización de botones se controla **en el frontend** (TrackingUI)
- Se mantiene toda la arquitectura SOLID ya implementada
- Compatible con los módulos: `ApiClient`, `ProcessManager`, `TrackingUI`
- Las funciones globales `editarProceso()` y `eliminarProceso()` siguen funcionando igual
- **SweetAlert2** debe estar cargado antes de usar `processManager.js`
