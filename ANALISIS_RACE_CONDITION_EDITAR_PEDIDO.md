# 🔴 ANÁLISIS: Race Condition en Editar Pedido Durante Carga Inicial

## Problema Identificado

Cuando se hace clic en **"Editar"** **mientras la página está cargando**, el modal se queda atrapado con el mensaje:
```
Cargando datos del pedido...
Por favor espera
```

Pero si se espera a que la página cargue completamente y luego se hace clic en editar, funciona normalmente.

---

## Causa Raíz: Race Condition con `Swal`

### 📍 Ubicación del Bug
**Archivo:** [resources/views/asesores/pedidos/index.blade.php](resources/views/asesores/pedidos/index.blade.php#L268)

```javascript
function editarPedido(pedidoId) {
    // ❌ PROBLEMA AQUÍ: Usar _ensureSwal para esperar a que Swal esté listo
    _ensureSwal(() => {
        UI.cargando('Cargando datos del pedido...', 'Por favor espera');
    });
    
    fetch(`/api/pedidos/${pedidoId}`)
        .then(res => res.json())
        .then(respuesta => {
            // ❌ Cerrar modal de carga usando _ensureSwal
            _ensureSwal(() => {
                Swal.close();
            });
            // ...
        })
        .catch(err => {
            _ensureSwal(() => {
                Swal.close();
            });
            UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
        });
}
```

### ¿Qué está sucediendo?

1. **Usuario hace clic en "Editar"** durante la carga de la página
2. Se llama a `editarPedido(pedidoId)`
3. `_ensureSwal()` espera a que `Swal` esté disponible (máximo 5 segundos)
4. **PERO:** Si se hace clic durante la carga, `Swal` **ya está disponible** pero posiblemente **otros scripts de inicialización aún no han terminado**
5. `UI.cargando()` muestra el modal
6. El `fetch` se ejecuta
7. **PROBLEMA:** Cuando la respuesta llega, el `Swal.close()` del `.then()` O del `.catch()` **NO se ejecuta** porque:
   - La función `_ensureSwal()` **NO espera a que termine el callback**, solo espera a que `Swal` esté disponible
   - El modal de carga **queda abierto indefinidamente**

---

## Diagrama del Flujo Problemático

```
┌─────────────────────────────────────────────────────────────┐
│ Página cargando... Usuario hace clic en "Editar"           │
└─────────────────────────────────────────────────────────────┘
                        ↓
        ┌───────────────────────────────┐
        │ editarPedido(pedidoId)        │
        │ - _ensureSwal() inicia        │
        │ - Espera a que Swal esté OK  │
        │ - ✓ Swal ya está disponible  │
        │ - UI.cargando() mostrado     │
        └───────────────────────────────┘
                        ↓
        ┌───────────────────────────────┐
        │ fetch() se ejecuta            │
        │ - Esperando respuesta         │
        └───────────────────────────────┘
                        ↓
        ✓ Respuesta del servidor
                        ↓
        ┌───────────────────────────────┐
        │ .then() se ejecuta            │
        │ - _ensureSwal(Swal.close)    │
        │ ❌ ¿Se ejecuta?              │
        │   Depende de si Swal sigue   │
        │   disponible o si está        │
        │   ocupado con otra cosa       │
        └───────────────────────────────┘
```

---

## El Problema Real: Función `_ensureSwal()`

**Ubicación:** [public/js/utilidades/ui-modal-service.js](public/js/utilidades/ui-modal-service.js#L25)

```javascript
function _ensureSwal(callback, maxWaitTime = 5000) {
    return new Promise((resolve) => {
        if (typeof Swal !== 'undefined') {
            if (callback) callback();  // ← Se ejecuta INMEDIATAMENTE
            resolve(true);
            return;
        }
        
        // ... esperar si no está disponible ...
    });
}
```

### El problema clave:
1. `_ensureSwal()` **no espera a que el callback termine**, solo a que `Swal` esté disponible
2. **NO garantiza que el modal se cierre**, solo que se llame a `Swal.close()`
3. Si hay múltiples `_ensureSwal()` simultáneos, pueden interferirse

---

## Estados Inconsistentes Posibles

| Escenario | Resultado |
|-----------|-----------|
| Clic después de carga completa |  Funciona normal |
| Clic durante carga (script aún inicializando) | ❌ Modal queda atrapado |
| Clic durante carga + cierre manual del modal | ⚠️ Fetch sigue ejecutándose |
| Clic rápido múltiples veces | ❌❌ Múltiples modales atrapados |

---

## Soluciones Propuestas

###  Solución 1: Usar `await` en lugar de callbacks (RECOMENDADA)

```javascript
async function editarPedido(pedidoId) {
    try {
        // Esperar a que Swal esté disponible ANTES de mostrar modal
        await _ensureSwal();
        
        // Ahora mostrar el modal de carga
        Swal.fire({
            title: 'Cargando datos del pedido...',
            html: 'Por favor espera',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: async () => {
                Swal.showLoading();
            }
        });
        
        const response = await fetch(`/api/pedidos/${pedidoId}`);
        const respuesta = await response.json();
        
        Swal.close();  // ← Cerrar ANTES de abrir el siguiente modal
        
        if (!respuesta.success) {
            throw new Error(respuesta.message || 'Error al cargar datos');
        }
        
        const datos = respuesta.data || respuesta.datos;
        abrirModalEditarPedido(pedidoId, datos, 'editar');
        
    } catch (err) {
        Swal.close();
        UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
    }
}
```

###  Solución 2: Agregar flag de prevención de múltiples clics

```javascript
let edicionEnProgreso = false;  // Flag global

function editarPedido(pedidoId) {
    if (edicionEnProgreso) {
        console.warn('Edición ya en progreso...');
        return;
    }
    
    edicionEnProgreso = true;
    
    _ensureSwal(() => {
        UI.cargando('Cargando datos del pedido...', 'Por favor espera');
    });
    
    fetch(`/api/pedidos/${pedidoId}`)
        .then(res => res.json())
        .then(respuesta => {
            _ensureSwal(() => {
                Swal.close();
            });
            
            if (!respuesta.success) throw new Error(respuesta.message || 'Error al cargar datos');
            const datos = respuesta.data || respuesta.datos;
            abrirModalEditarPedido(pedidoId, datos, 'editar');
        })
        .catch(err => {
            _ensureSwal(() => {
                Swal.close();
            });
            UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
        })
        .finally(() => {
            edicionEnProgreso = false;  // ← Permitir nuevas ediciones
        });
}
```

###  Solución 3: Mejorar `_ensureSwal()` para retornar una promesa correcta

```javascript
async function _ensureSwal() {
    // Esperar a que Swal esté disponible
    while (typeof Swal === 'undefined') {
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    return true;
}
```

---

## Recomendación: Implementar Solución 1 + 2

**Por qué:**
- **Solución 1** (async/await): Más limpia, moderna, y evita callbacks anidados
- **Solución 2** (flag): Previene múltiples clics simultáneos
- Juntas forman una solución robusta

**Ventajas:**
 No queda modal atrapado  
 Previene race conditions  
 Código más legible  
 Funciona con clics durante carga  

---

## Archivos a Modificar

1. **[resources/views/asesores/pedidos/index.blade.php](resources/views/asesores/pedidos/index.blade.php#L268)**
   - Reemplazar función `editarPedido()`
   - Agregar flag global `edicionEnProgreso`

2. **Opcional: [public/js/utilidades/ui-modal-service.js](public/js/utilidades/ui-modal-service.js#L25)**
   - Mejorar `_ensureSwal()` para mejor control

---

## Testing

Pasos para verificar la solución:

```
1. Cargar http://localhost:8000/asesores/pedidos
2. Hacer clic inmediatamente en "Editar" (sin esperar carga)
3.  Verificar que el modal se muestre correctamente
4.  Verificar que los datos del pedido se carguen
5.  Hacer clic rápido múltiples veces
6.  Verificar que no haya modales múltiples atrapados
```

---

## Conclusión

Este es un **race condition clásico** donde:
- ❌ **Código actual:** Asume que `_ensureSwal()` espera correctamente, pero no lo hace
-  **Código mejorado:** Usar `async/await` + flag de prevención

