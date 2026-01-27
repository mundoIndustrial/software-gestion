# 🎯 PROBLEMA: Guardar Automático en Edición de Procesos

**Fecha:** 27 de enero de 2026  
**Estado:** 🔍 ANÁLISIS  
**Problema:** Cuando se edita un proceso existente y se agrega una foto, se guarda inmediatamente en memoria sin esperar al botón final "GUARDAR CAMBIOS" de la prenda.

---

## 📋 Flujo Actual (Problema)

```
1. Usuario en EDICIÓN de Prenda
   ↓
2. Clickea en proceso "Reflectivo" → Se abre modal
   ↓
3. Modal carga datos del proceso existente
   ↓
4. Usuario carga una foto en el modal
   ↓
5. ❌ PROBLEMA: agregarProcesoAlPedido() se dispara automáticamente
   ↓
6. window.procesosSeleccionados[reflectivo] se actualiza INMEDIATAMENTE
   ↓
7. renderizarTarjetasProcesos() re-renderiza
   ↓
8. Usuario aún NO hizo click en "GUARDAR CAMBIOS"
```

---

## 🎯 Flujo Deseado (Solución)

```
1. Usuario en EDICIÓN de Prenda
   ↓
2. Clickea en proceso "Reflectivo" → Se abre modal
   ↓
3. Modal carga datos del proceso existente
   ↓
4. Usuario carga una foto en el modal
   ↓
5. ✅ Foto se guarda en BUFFER TEMPORAL (no en procesosSeleccionados)
   ↓
6. Usuario puede seguir editando (más fotos, ubicaciones, etc.)
   ↓
7. Usuario clickea "GUARDAR CAMBIOS" de la PRENDA (boton principal)
   ↓
8. PATCH /api/prendas-pedido/{id}/editar → Se envía todo junto
   ↓
9. Backend procesa y guarda cambios
```

---

## 🔍 Raíz del Problema

**Archivo:** `gestor-modal-proceso-generico.js` línea 973

```javascript
window.agregarProcesoAlPedido = function() {
    // ... código ...
    window.procesosSeleccionados[procesoActual].datos = datos;  // ← GUARDA AQUÍ
    
    if (window.renderizarTarjetasProcesos) {
        window.renderizarTarjetasProcesos();  // ← RE-RENDERIZA AQUÍ
    }
    
    cerrarModalProcesoGenerico(true);
    // ...
};
```

**Problema:** Esto se ejecuta INCLUSO cuando estamos editando. No hay forma de distinguir entre:
- Creación (DEBE guardar al cerrar modal)
- Edición (NO DEBE guardar hasta "GUARDAR CAMBIOS" final)

---

## ✅ SOLUCIÓN PROPUESTA

### 1. **Crear Flag Global para Diferenciar Contexto**

```javascript
// En gestor-modal-proceso-generico.js
let procesoActual = null;
let modoActual = 'crear';  // ← NUEVO: 'crear' o 'editar'

window.abrirModalProcesoGenerico = function(tipoProceso, esEdicion = false) {
    procesoActual = tipoProceso;
    modoActual = esEdicion ? 'editar' : 'crear';  // ← ESTABLECER MODO
    
    // ... resto del código ...
};
```

### 2. **Crear Buffer de Cambios Temporales**

```javascript
// En gestor-modal-proceso-generico.js
let cambiosProceso = null;  // ← Buffer temporal para cambios en edición

window.guardarCambiosProceso = function() {
    // Este es el buffer temporal (no toca procesosSeleccionados)
    cambiosProceso = {
        tipo: procesoActual,
        ubicaciones: ubicacionesProcesoSeleccionadas,
        observaciones: document.getElementById('proceso-observaciones')?.value || '',
        tallas: {
            dama: window.tallasCantidadesProceso?.dama || {},
            caballero: window.tallasCantidadesProceso?.caballero || {}
        },
        imagenes: imagenesProcesoActual.filter(img => img !== null)
    };
    
    console.log('[BUFFER] Cambios en proceso guardados temporalmente', cambiosProceso);
};
```

### 3. **Modificar agregarProcesoAlPedido() Para Distinguir**

```javascript
window.agregarProcesoAlPedido = function() {
    if (!procesoActual) {
        alert('Error: no hay proceso seleccionado');
        return;
    }
    
    try {
        const imagenesValidas = imagenesProcesoActual.filter(img => img !== null);
        
        const datos = {
            tipo: procesoActual,
            ubicaciones: ubicacionesProcesoSeleccionadas,
            observaciones: document.getElementById('proceso-observaciones')?.value || '',
            tallas: {
                dama: window.tallasCantidadesProceso?.dama || {},
                caballero: window.tallasCantidadesProceso?.caballero || {}
            },
            imagenes: imagenesValidas
        };
        
        // DIFERENCIACIÓN
        if (modoActual === 'crear') {
            // CREACIÓN: Guardar directamente en procesosSeleccionados
            if (!window.procesosSeleccionados) {
                window.procesosSeleccionados = {};
            }
            
            if (!window.procesosSeleccionados[procesoActual]) {
                window.procesosSeleccionados[procesoActual] = {
                    tipo: procesoActual,
                    datos: null
                };
            }
            
            window.procesosSeleccionados[procesoActual].datos = datos;
            
            if (window.renderizarTarjetasProcesos) {
                window.renderizarTarjetasProcesos();
            }
            
        } else if (modoActual === 'editar') {
            // EDICIÓN: Guardar en BUFFER temporal, NO en procesosSeleccionados
            cambiosProceso = datos;
            console.log('[EDICIÓN] Cambios guardados en buffer (no se sincronizarán hasta GUARDAR CAMBIOS)', cambiosProceso);
        }
        
        cerrarModalProcesoGenerico(true);
        
        if (window.actualizarResumenProcesos) {
            window.actualizarResumenProcesos();
        }
        
    } catch (error) {
        console.error('[agregarProcesoAlPedido] Error:', error);
    }
};
```

### 4. **Al Cerrar Modal: Solo en Creación se Re-renderiza**

```javascript
window.cerrarModalProcesoGenerico = function(procesoGuardado = false) {
    const modal = document.getElementById('modal-proceso-generico');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // En EDICIÓN: No hacer nada especial (cambios están en buffer)
    // En CREACIÓN: Deseleccionar si no se guardó
    if (modoActual === 'crear' && procesoActual && !procesoGuardado) {
        // Deseleccionar checkbox...
        // Limpiar estructura...
    }
    
    procesoActual = null;
    modoActual = 'crear';  // Reset
};
```

### 5. **Al Hacer PATCH: Aplicar Cambios del Buffer**

En el controller/servicio que maneja `PATCH /api/prendas-pedido/{id}/editar`:

```javascript
// Cuando el usuario hace click en "GUARDAR CAMBIOS" de la PRENDA
const guardarCambiosPrenda = function() {
    // Si hay cambios en proceso (edición):
    if (modoActual === 'editar' && cambiosProceso) {
        window.procesosSeleccionados[cambiosProceso.tipo] = {
            tipo: cambiosProceso.tipo,
            datos: cambiosProceso
        };
        cambiosProceso = null;  // Vaciar buffer
    }
    
    // Ahora sí, hacer el PATCH con los procesos finales
    const payload = construirPayloadPatch();
    fetch('/api/prendas-pedido/' + prendaId + '/editar', {
        method: 'PATCH',
        body: JSON.stringify(payload)
    });
};
```

---

## 📊 Tabla Comparativa

| Aspecto | Creación | Edición |
|--------|----------|--------|
| Modal abierto | Vacío | Con datos existentes |
| agregarProcesoAlPedido() | Guarda en procesosSeleccionados | Guarda en buffer temporal |
| renderizarTarjetasProcesos() | Se llama inmediatamente | Se omite |
| "GUARDAR CAMBIOS" de prenda | N/A (no existe en crear) | Aplica buffer y hace PATCH |
| Re-renderizado | Inmediato | Retrasado hasta PATCH |

---

## 🎯 Implementación

### Archivos a Modificar:

1. **`gestor-modal-proceso-generico.js`**
   - Agregar `modoActual` flag
   - Agregar `cambiosProceso` buffer
   - Modificar `abrirModalProcesoGenerico()` para set modo
   - Modificar `agregarProcesoAlPedido()` para diferenciar
   - Modificar `cerrarModalProcesoGenerico()` para no re-renderizar en edición

2. **`renderizador-tarjetas-procesos.js` (si existe)**
   - No requiere cambios si la lógica de diferenciación está en el gestor

3. **`prenda-editor.js` (servicio que maneja edición)**
   - Cuando se hace PATCH: aplicar cambios del buffer

---

## ✨ Ventajas

✅ Separa claramente creación vs edición  
✅ No rompe flujo existente de CREACIÓN  
✅ Edición solo guarda al hacer PATCH final  
✅ Buffer temporal mantiene cambios sincronizados  
✅ Compatible con foto cargada en proceso

---

## 🚀 Siguiente Paso

Confirmar que quieres proceder con esta implementación, y entonces:

1. Actualizar `gestor-modal-proceso-generico.js`
2. Verificar que `prenda-editor.js` aplica el buffer
3. Testear flujo completo: crear, editar, guardar

---

**Status:** 🔍 PROPUESTA - Esperando confirmación para implementar
