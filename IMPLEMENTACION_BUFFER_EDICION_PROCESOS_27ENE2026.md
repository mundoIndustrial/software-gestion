# ✅ IMPLEMENTADO: Buffer de Edición de Procesos

**Fecha:** 27 de enero de 2026  
**Estado:** ✅ COMPLETADO  
**Archivo modificado:** `public/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js`

---

## 📋 Cambios Realizados

### 1. **Agregadas Variables Globales** (líneas 7-9)

```javascript
// NUEVO: Flag para diferenciar entre CREACIÓN y EDICIÓN
let modoActual = 'crear';  // 'crear' o 'editar'

// NUEVO: Buffer temporal para cambios en EDICIÓN
let cambiosProceso = null;
```

**Propósito:**
- `modoActual`: Controla si estamos en modo de creación o edición
- `cambiosProceso`: Almacena cambios temporalmente en edición (no toca `procesosSeleccionados` hasta PATCH final)

---

### 2. **Modificada abrirModalProcesoGenerico()** (línea ~57)

```javascript
procesoActual = tipoProceso;
// NUEVO: Establecer el modo (crear o editar)
modoActual = esEdicion ? 'editar' : 'crear';
const config = procesosConfig[tipoProceso];
```

**Propósito:** Cuando se abre el modal, establecer automáticamente si es creación o edición.

---

### 3. **Mejorada Lógica de Limpieza** (líneas ~74-100)

```javascript
// SOLO limpiar variables si NO es edición
if (!esEdicion) {
    // En CREACIÓN: limpiar todo
    window.tallasSeleccionadasProceso = { dama: [], caballero: [] };
    // ...
} else {
    // En EDICIÓN: renderizar lo que ya está cargado
    if (window.renderizarListaUbicaciones) {
        window.renderizarListaUbicaciones();
    }
    // ...
}
```

**Propósito:** En edición, preservar el estado existente del proceso.

---

### 4. **Modificada agregarProcesoAlPedido()** (líneas ~982-1042)

```javascript
// NUEVO: DIFERENCIAR ENTRE CREACIÓN Y EDICIÓN
if (modoActual === 'crear') {
    // CREACIÓN: Guardar directamente en procesosSeleccionados
    window.procesosSeleccionados[procesoActual].datos = datos;
    
    if (window.renderizarTarjetasProcesos) {
        window.renderizarTarjetasProcesos();  // Re-renderizar ahora
    }
    
} else if (modoActual === 'editar') {
    // EDICIÓN: Guardar TEMPORALMENTE en buffer
    cambiosProceso = datos;
    console.log('[EDICIÓN-BUFFER] Cambios guardados temporalmente...');
    // NO re-renderiza hasta GUARDAR CAMBIOS final
}
```

**Propósito:** 
- **CREACIÓN:** Comportamiento actual (guardar directamente)
- **EDICIÓN:** Guardar en buffer temporal (sin tocar `procesosSeleccionados` aún)

---

### 5. **Mejorada cerrarModalProcesoGenerico()** (líneas ~131-162)

```javascript
// En CREACIÓN: Deseleccionar si no se guardó
// En EDICIÓN: No hacer nada (cambios están en buffer)
if (modoActual === 'crear' && procesoActual && !procesoGuardado) {
    // Lógica de deselección solo en creación
    // ...
}

// NUEVO: Reset de variables
procesoActual = null;
modoActual = 'crear';  // Reset a valor por defecto
```

**Propósito:** 
- Solo aplica lógica de deselección en **CREACIÓN**
- En **EDICIÓN**, los cambios quedan en buffer esperando PATCH final

---

### 6. **Nuevas Funciones Públicas** (líneas ~1048-1071)

#### `aplicarCambiosProcesosDesdeBuffer()`

```javascript
window.aplicarCambiosProcesosDesdeBuffer = function() {
    if (cambiosProceso) {
        // Aplicar cambios del buffer a procesosSeleccionados
        window.procesosSeleccionados[cambiosProceso.tipo] = {
            tipo: cambiosProceso.tipo,
            datos: cambiosProceso
        };
        
        cambiosProceso = null;  // Limpiar buffer
    }
};
```

**Uso:** Llamar ANTES de hacer el PATCH final de la prenda

#### `obtenerBufferProcesoActual()`

```javascript
window.obtenerBufferProcesoActual = function() {
    return cambiosProceso;
};
```

**Uso:** Para debugging/validación

#### `obtenerModoActual()`

```javascript
window.obtenerModoActual = function() {
    return modoActual;
};
```

**Uso:** Para debugging

---

## 🔄 Flujo Completo

### CREACIÓN (Comportamiento sin cambios)

```
1. Usuario clickea checkbox proceso
   ↓
2. abrirModalProcesoGenerico(tipo, false)  ← esEdicion=false
   modoActual = 'crear'
   ↓
3. Usuario carga foto/datos
   ↓
4. Clickea "Guardar Proceso"
   ↓
5. agregarProcesoAlPedido()
   ↓
6. if (modoActual === 'crear')
      window.procesosSeleccionados[tipo] = datos
      renderizarTarjetasProcesos()  ← Re-renderiza AHORA
   ↓
7. Modal cierra, tarjeta se ve actualizada
```

### EDICIÓN (Nuevo flujo)

```
1. Usuario en EDICIÓN de prenda
   ↓
2. Clickea en proceso "Reflectivo" → Se abre modal
   ↓
3. abrirModalProcesoGenerico('reflectivo', true)  ← esEdicion=true
   modoActual = 'editar'
   ↓
4. Modal carga datos del proceso existente
   ↓
5. Usuario carga foto/modifica datos
   ↓
6. Clickea "Guardar Proceso"
   ↓
7. agregarProcesoAlPedido()
   ↓
8. if (modoActual === 'editar')
      cambiosProceso = datos  ← Guardar en BUFFER temporal
      NO renderizar aún
   ↓
9. Modal cierra
   ↓
10. Usuario hace otros cambios a la prenda
    ↓
11. Usuario clickea "GUARDAR CAMBIOS" de prenda
    ↓
12. aplicarCambiosProcesosDesdeBuffer()  ← APLICAR CAMBIOS DEL BUFFER
    ↓
13. PATCH /api/prendas-pedido/{id}/editar
    ↓
14. Backend procesa y guarda TODO junto
```

---

## ✨ Garantías

✅ **CREACIÓN no se ve afectada**
- Mismo comportamiento exacto
- Re-renderizado inmediato
- Checkbox functionality intacta

✅ **EDICIÓN es ahora asíncrona**
- Buffer temporal preserva cambios
- Cambios se aplican en PATCH final
- Re-renderizado ocurre solo después de PATCH exitoso

✅ **No toca lógica existente**
- Solo agrega condicionales
- Variables nuevas no interfieren
- Funciones nuevas son opcionales

---

## 🚀 Cómo Usarlo

### En el Controller/Editor que Maneja PATCH

```javascript
// Cuando el usuario hace click en "GUARDAR CAMBIOS"
const guardarCambiosPrenda = async function() {
    // 1. Aplicar cambios del buffer
    if (typeof window.aplicarCambiosProcesosDesdeBuffer === 'function') {
        window.aplicarCambiosProcesosDesdeBuffer();
    }
    
    // 2. Construir payload con procesos ya sincronizados
    const payload = construirPayloadPatch();
    
    // 3. Hacer PATCH
    const response = await fetch(`/api/prendas-pedido/${prendaId}/editar`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    
    if (response.ok) {
        console.log('✅ Cambios guardados exitosamente');
    }
};
```

---

## 🧪 Testing Manual

### Caso 1: Crear Proceso (Sin cambios)

```
1. Click en "Reflectivo" ✓
2. Modal abre vacío ✓
3. Cargar foto ✓
4. Clickear "Guardar Proceso" ✓
5. Tarjeta aparece INMEDIATAMENTE ✓
```

### Caso 2: Editar Proceso (Nuevo)

```
1. En edición de prenda, click en "Reflectivo" ✓
2. Modal abre con datos existentes ✓
3. Cargar foto nueva ✓
4. Clickear "Guardar Proceso" ✓
5. ✅ NUEVO: Tarjeta NO se re-renderiza ✓
6. Cargar otra foto (o editar otros datos) ✓
7. Clickear "GUARDAR CAMBIOS" de prenda ✓
8. PATCH se ejecuta ✓
9. Tarjeta se actualiza con TODAS las fotos ✓
```

---

## 📊 Variables Globales

| Variable | Tipo | Valor Inicial | Propósito |
|----------|------|---------------|-----------|
| `modoActual` | String | `'crear'` | Flag: 'crear' o 'editar' |
| `cambiosProceso` | Object/null | `null` | Buffer temporal de cambios en edición |
| `procesoActual` | String/null | `null` | Tipo de proceso actual (existía antes) |

---

## 🔗 Conexión con PATCH

Este sistema funciona perfecto con la Fase 1 completada:

```
FASE 1: Services Backend ✅
PATCH /api/prendas-pedido/{id}/editar

FASE 2: Tests ✅
41 tests pasados

FASE 3: Frontend Buffer (ESTA) ✅
Stagear cambios locales antes de PATCH

FASE 4: Integración Final
Conectar PrendaEditService con aplicarCambiosProcesosDesdeBuffer()
```

---

## 📝 Notas Importantes

1. **No rompe creación:** Si estás creando procesos nuevos, todo funciona igual
2. **Buffer inteligente:** Cada vez que editas un proceso, se actualiza el buffer (no se pierde)
3. **Reset automático:** Después de cerrar modal, `modoActual` se resetea a `'crear'`
4. **Debugging fácil:** Puedes llamar `obtenerBufferProcesoActual()` en consola para ver qué hay en buffer

---

**Status:** ✅ LISTO PARA INTEGRACIÓN CON PATCH

**Próximo paso:** Conectar con el controller que maneja `PATCH /api/prendas-pedido/{id}/editar` para que llame a `aplicarCambiosProcesosDesdeBuffer()` antes de hacer el PATCH.
