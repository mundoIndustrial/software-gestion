# FIX: Error "Swal is not defined" - Solución Implementada

## 🔴 Problema Identificado

**Error en consola:**
```
Uncaught (in promise) ReferenceError: Swal is not defined
    at abrirModalDescripcion (pedidos:5021:13)
```

**Causa:** 
- SweetAlert2 se carga de forma asincrónica con atributo `defer` en el `<script>` tag
- Las funciones JavaScript que usan `Swal` se ejecutan **antes** de que SweetAlert2 cargue
- Resultado: `Swal` no existe cuando se intenta usar

##  Solución Implementada

### 1. Actualización de `_ensureSwal()` en UIModalService

**Archivo:** [public/js/utilidades/ui-modal-service.js](public/js/utilidades/ui-modal-service.js) línea 21

**Cambio:**
```javascript
// ANTES: Solo ejecutaba callback sin Promise
function _ensureSwal(callback, maxWaitTime = 5000) {
    if (typeof Swal !== 'undefined') {
        callback();
        return;  // ❌ No retorna Promise, no puedo usar await
    }
    // ...
}

// DESPUÉS: Retorna Promise para async/await
function _ensureSwal(callback, maxWaitTime = 5000) {
    return new Promise((resolve) => {
        if (typeof Swal !== 'undefined') {
            if (callback) callback();
            resolve(true);  //  Permite await
            return;
        }
        // ...
        resolve(false);  // Fallback si Swal no carga
    });
}
```

**Beneficios:**
-  Ahora puedo usar `await _ensureSwal()` en funciones async
-  Espera a que Swal esté disponible antes de continuar
-  Timeout de 5 segundos con fallback a alert nativo
-  Compatible con callbacks y Promises

### 2. Fix en `abrirModalDescripcion()`

**Archivo:** [resources/views/asesores/pedidos/index.blade.php](resources/views/asesores/pedidos/index.blade.php) línea 99

**Cambio:**
```javascript
// ANTES: Usaba Swal directamente sin verificar disponibilidad
async function abrirModalDescripcion(pedidoId, tipo) {
    try {
        UI.cargando(...);  // Intenta usar Swal internamente
        Swal.close();      // ❌ Swal podría no estar cargado
        // ...
    }
}

// DESPUÉS: Espera a que Swal esté listo
async function abrirModalDescripcion(pedidoId, tipo) {
    try {
        // Esperar a que Swal cargue ANTES de mostrar modal
        await _ensureSwal(() => {
            UI.cargando('Cargando información...', 'Por favor espera');
        });
        
        const response = await fetch(`/api/pedidos/${pedidoId}`);
        const result = await response.json();
        
        // Cerrar modal usando _ensureSwal
        await _ensureSwal(() => {
            Swal.close();
        });
        
        // Mostrar contenido
        UI.contenido({...});
    } catch (error) {
        // Cerrar en caso de error
        await _ensureSwal(() => {
            Swal.close();
        });
    }
}
```

### 3. Fix en `editarPedido()`

**Archivo:** [resources/views/asesores/pedidos/index.blade.php](resources/views/asesores/pedidos/index.blade.php) línea 265

**Cambio:**
```javascript
// ANTES: Swal podría no estar disponible
function editarPedido(pedidoId) {
    UI.cargando(...);      // ❌ Swal podría faltar
    fetch(...)
        .then(() => {
            Swal.close();  // ❌ Error aquí
        });
}

// DESPUÉS: Espera a Swal antes de usarlo
function editarPedido(pedidoId) {
    _ensureSwal(() => {
        UI.cargando('Cargando datos del pedido...', 'Por favor espera');
    });
    
    fetch(...)
        .then(() => {
            _ensureSwal(() => {
                Swal.close();  //  Espera a que Swal esté listo
            });
        })
        .catch(() => {
            _ensureSwal(() => {
                Swal.close();
            });
        });
}
```

### 4. Fix en `guardarCambiosPedido()`

**Archivo:** [resources/views/asesores/pedidos/index.blade.php](resources/views/asesores/pedidos/index.blade.php) línea 335

**Cambio:**
```javascript
// ANTES: Múltiples llamadas a Swal sin verificar
function guardarCambiosPedido(pedidoId, datosActualizados) {
    UI.cargando(...);           // ❌ Swal podría faltar
    fetch(...)
        .then(() => {
            Swal.close();       // ❌ Podría fallar
            Swal.fire({...});   // ❌ Podría fallar
        })
        .catch(() => {
            Swal.close();       // ❌ Podría fallar
        });
}

// DESPUÉS: Todos los usos de Swal pasan por _ensureSwal
function guardarCambiosPedido(pedidoId, datosActualizados) {
    _ensureSwal(() => {
        UI.cargando('Guardando cambios...', 'Por favor espera');
    });
    
    fetch(...)
        .then(() => {
            _ensureSwal(() => {
                Swal.close();
            });
            
            _ensureSwal(() => {
                Swal.fire({...});  //  Espera a Swal
            });
        })
        .catch(() => {
            _ensureSwal(() => {
                Swal.close();      //  Espera a Swal
            });
        });
}
```

## 📊 Resumen de Cambios

| Ubicación | Función | Cambios |
|---|---|---|
| `ui-modal-service.js:21` | `_ensureSwal()` | Ahora retorna Promise para usar con await |
| `index.blade.php:99` | `abrirModalDescripcion()` | Envuelve Swal con _ensureSwal |
| `index.blade.php:265` | `editarPedido()` | Envuelve Swal con _ensureSwal |
| `index.blade.php:335` | `guardarCambiosPedido()` | Envuelve Swal con _ensureSwal |

## 🔍 Cómo Funciona

1. **Cuando carga la página:**
   - Script de SweetAlert2 carga con `defer` (asincrónico)
   - JavaScript de pedidos carga inmediatamente

2. **Cuando usuario hace clic en "editar" o abre modal:**
   - Función llama a `_ensureSwal(callback)`
   - `_ensureSwal` verifica si `typeof Swal !== 'undefined'`
   - Si no está disponible, espera cada 50ms (máximo 5 segundos)
   - Una vez disponible, ejecuta callback y resuelve Promise
   - Flujo continúa sin errores

3. **Diagrama:**
   ```
   Usuario hace clic
       ↓
   Llamar abrirModalDescripcion()
       ↓
   await _ensureSwal() → Esperar a Swal
       ↓
   Si Swal disponible → Ejecutar callback inmediatamente
   Si Swal no disponible → Esperar hasta 5 segundos
   Timeout → Fallback a alert nativo
       ↓
   Continuar con fetch y mostrar modal
   ```

##  Resultado Esperado

**En consola del navegador:**
- ❌ NO debería ver: `Uncaught (in promise) ReferenceError: Swal is not defined`
-  SÍ debería ver: `⚠️ [UIModalService] SweetAlert2 aún no está cargado. Esperando...` (solo si Swal tarda en cargar)
-  SÍ debería ver: Modales abiertos correctamente

**Modales:**
-  "Cargando información..." aparece correctamente
-  Contenido carga sin errores
-  Botones funcionan correctamente
-  Guardado de datos funciona

## 🧪 Testing

### Test 1: Abrir modal de edición

```bash
# En navegador
GET /asesores/pedidos
# Click en "editar" de un pedido
# Verificar que aparece modal sin errores
```

**Resultado esperado:**
-  Modal "Cargando..." aparece
-  Modal se reemplaza con contenido
-  Sin errores en consola

### Test 2: Guardar cambios

```bash
# En modal de edición
# Cambiar datos
# Click en "Guardar"
```

**Resultado esperado:**
-  Modal "Guardando..." aparece
-  Modal de confirmación aparece
-  Sin errores en consola

### Test 3: Abrir descripción de prendas

```bash
# En lista de pedidos
# Click en botón de descripción/detalles
```

**Resultado esperado:**
-  Modal "Cargando..." aparece
-  Contenido con prendas se muestra
-  Sin errores en consola

## Próximos Pasos

1.  Código actualizado
2. ⏳ Probar modales en navegador
3. ⏳ Verificar consola sin errores de Swal
4. ⏳ Monitorear logs de laravel.log

## 📝 Notas

- **Sin cambios en BD:** Las tablas no cambian
- **Sin cambios en API:** El endpoint `/api/pedidos/{id}` sigue igual
- **Backward compatible:** `_ensureSwal()` sigue funcionando con callbacks
- **Nuevo:** Ahora soporta `await _ensureSwal()`

## 📞 Si Persiste el Error

1. **Verificar que SweetAlert2 CDN está disponible:**
   ```bash
   # En consola del navegador
   fetch('https://cdn.jsdelivr.net/npm/sweetalert2@latest/dist/sweetalert2.min.js')
   ```

2. **Verificar tiempos de carga:**
   ```javascript
   // En consola
   console.log(typeof Swal);  // Debe ser 'function' después de 2 segundos
   ```

3. **Si sigue fallando después de 5 segundos:**
   - Verificar conexión a internet (CDN podría no estar disponible)
   - Fallback automático a `alert()` nativo

---

**Status:**  COMPLETADO
**Archivos modificados:** 2 (ui-modal-service.js, index.blade.php)
**Líneas de código:** ~30 líneas modificadas
**Testing recomendado:** 3 test cases (todos describos arriba)
