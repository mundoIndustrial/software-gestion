# 🚨 DIAGNÓSTICO CRÍTICO - Tarjeta NO se Renderiza (Actualizado)

**Versión:** 2.0 (Problema Reportado por Usuario)  
**Fecha:** 15 de enero, 2026

---

## 📋 SÍNTOMAS REPORTADOS

El usuario reporta que después del refactor:

1. ❌ **La tarjeta NO aparece en la UI** después de agregar prenda
2. ❌ Los procesos llegan **vacíos {}** 
3. ✅ La prenda SÍ se agrega al gestor (logs lo confirman)
4. ✅ Las imágenes y tallas se agregan correctamente
5. ❌ **UI muestra "No hay ítems agregados"** a pesar de que gestor tiene prendas

**Logs clave:**
```
✅ ➕ Prenda PRENDA agregada (índice: 0)
✅ [GestionItemsUI] Prenda agregada al gestor con datos
❌ Procesos configurables (antes): {}
❌ Procesos configurables (después): {}
❌ [RENDER] UI sigue mostrando estado vacío
```

---

## 🔍 ANÁLISIS DE LA CADENA COMPLETA

### Fase 1: Recopilación de Datos ✅
**Archivo:** `gestion-items-pedido.js` (línea 219)

```javascript
agregarPrendaNueva() {
    // Línea 260-265: Obtiene procesos
    let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
    console.log(`🎨 [GestionItemsUI] Procesos configurables (antes):`, procesosConfigurables);
    // ← AQUÍ ES DONDE DICE {}
}
```

**Problema Inicial:** `obtenerProcesosConfigurables()` retorna `{}` (vacío)

### Fase 2: Creación de Objeto ✅
**Archivo:** `gestion-items-pedido.js` (línea 295)

```javascript
const prendaNueva = {
    nombre_producto: nombrePrenda,
    // ... otros datos ...
    procesos: procesosConfigurables,  // ← Aquí va {} vacío
    // ...
};
```

### Fase 3: Guardado en Gestor ✅
**Archivo:** `gestion-items-pedido.js` (línea 316)

```javascript
if (window.gestorPrendaSinCotizacion?.agregarPrenda) {
    window.gestorPrendaSinCotizacion.agregarPrenda(prendaNueva);
    console.log('✅ [GestionItemsUI] Prenda agregada al gestor con datos');
    // ← Log confirma que se agregó
}
```

### Fase 4: Renderizado ❌ **PROBLEMA AQUÍ**
**Archivo:** `gestion-items-pedido.js` (línea 320)

```javascript
if (window.renderizarPrendasTipoPrendaSinCotizacion) {
    window.renderizarPrendasTipoPrendaSinCotizacion();
    console.log('✅ [GestionItemsUI] UI renderizada');
    // ← Dice que se renderizó, pero...
}
```

**PERO... en `renderizador-prenda-sin-cotizacion.js` (línea 510):**

```javascript
function renderizarPrendasTipoPrendaSinCotizacion() {
    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    console.log('🎯 [RENDER] Prendas activas encontradas:', prendas.length);
    
    if (prendas.length === 0) {
        console.warn('⚠️ [RENDER] Sin prendas activas. Mostrando estado vacío.');
        container.innerHTML = `<p>No hay prendas agregadas.</p>`;
        return;  // ← RETORNA SIN RENDERIZAR
    }
}
```

---

## 🎯 PROBLEMA RAÍZ IDENTIFICADO

### Problema 1: Procesos Llegan Vacíos `{}`

**Ubicación:** `gestion-items-pedido.js` línea 262

```javascript
let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
console.log(`🎨 Procesos configurables (antes):`, procesosConfigurables);
// Log dice: {} (vacío)
```

**Causas posibles:**

1. ❌ Usuario NO marca procesos en el modal (checkbox sin marcar)
2. ❌ `limpiarProcesosSeleccionados()` se ejecuta ANTES de obtener procesos
3. ❌ El storage de procesos se limpia demasiado pronto

**En tu código veo:**
```javascript
// Línea 322: Cerrar modal
cerrarModalPrendaNueva();

// En prendas-wrappers.js:
window.cerrarModalPrendaNueva = function() {
    // ... cierra el modal
    
    // ✅ Limpia procesos
    if (window.limpiarProcesosSeleccionados) {
        window.limpiarProcesosSeleccionados();
    }
};
```

**⚠️ PERO LA LIMPIEZA OCURRE DESPUÉS DE OBTENER PROCESOS, así que ese no es el problema.**

---

### Problema 2: La Tarjeta NO se Renderiza (CRÍTICO)

**Ubicación:** `renderizador-prenda-sin-cotizacion.js` línea 510

Hay UNA DE TRES posibilidades:

#### Posibilidad A: `obtenerActivas()` retorna array vacío
```javascript
const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
// Retorna [] (vacío) a pesar de que gestor.prendas.length = 1

// Razón: Si prendasEliminadas tiene el índice 0, filtra la única prenda
this.prendas.filter((_, index) => !this.prendasEliminadas.has(index))
// Si prendasEliminadas = Set(0), retorna []
```

#### Posibilidad B: El contenedor no existe
```javascript
const container = document.getElementById('prendas-container-editable');
if (!container) {
    console.error('❌ [RENDER] Container no disponibles');
    return;  // ← No renderiza
}
```

#### Posibilidad C: Error en la sincronización
```javascript
sincronizarDatosAntesDERenderizar();  // Línea 498
// Si esta función lanza error, el renderizado se detiene
```

---

## 🔧 SOLUCIÓN PASO A PASO

### Paso 1: Verificar que Procesos Se Obtienen

**En consola F12 ANTES de agregar prenda:**
```javascript
console.log('Procesos antes:', window.procesosSeleccionados);
console.log('Función obtener:', window.obtenerProcesosConfigurables());
```

**En consola F12 DESPUÉS de marcar "Reflectivo":**
```javascript
console.log('Procesos después:', window.procesosSeleccionados);
// Debería mostrar: { reflectivo: { tipo: "reflectivo", datos: {...} } }
```

---

### Paso 2: Verificar que Prenda Se Agrega al Gestor

**Agregar este código en `agregarPrendaNueva()` ANTES de renderizar:**

```javascript
// Después de agregar al gestor (línea 316)
if (window.gestorPrendaSinCotizacion?.agregarPrenda) {
    window.gestorPrendaSinCotizacion.agregarPrenda(prendaNueva);
    
    // ✅ DEBUG: Verificar que se agregó realmente
    console.log('🔍 DEBUG - Verificación:');
    console.log('  Prendas en gestor.prendas:', window.gestorPrendaSinCotizacion.prendas.length);
    console.log('  Prendas activas:', window.gestorPrendaSinCotizacion.obtenerActivas().length);
    console.log('  Prendas eliminadas:', Array.from(window.gestorPrendaSinCotizacion.prendasEliminadas));
    console.log('  Contenido última prenda:', window.gestorPrendaSinCotizacion.prendas[window.gestorPrendaSinCotizacion.prendas.length - 1]);
}
```

---

### Paso 3: Verificar que Container Existe

**Agregar en `renderizarPrendasTipoPrendaSinCotizacion()`:**

```javascript
function renderizarPrendasTipoPrendaSinCotizacion() {
    const container = document.getElementById('prendas-container-editable');
    
    // ✅ DEBUG: Verificar container
    if (!container) {
        console.error('❌ PROBLEMA: Container no encontrado en DOM');
        console.error('   ID buscado: "prendas-container-editable"');
        console.log('   Elementos con "container" en el ID:');
        document.querySelectorAll('[id*="container"]').forEach(el => {
            console.log(`   - ${el.id}`);
        });
        return;
    }
    
    console.log('✅ Container encontrado:', container);
}
```

---

### Paso 4: Identificar Por Qué `obtenerActivas()` Retorna Vacío

**Agregar en `renderizarPrendasTipoPrendaSinCotizacion()`:**

```javascript
const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
console.log('🔍 DEBUG obtenerActivas():');
console.log('  this.prendas.length:', window.gestorPrendaSinCotizacion.prendas.length);
console.log('  prendasEliminadas:', Array.from(window.gestorPrendaSinCotizacion.prendasEliminadas));
console.log('  prendas activas:', prendas.length);

// Si prendas.length === 0 pero prendas.length !== 0, hay un problema
if (window.gestorPrendaSinCotizacion.prendas.length > 0 && prendas.length === 0) {
    console.error('❌ PROBLEMA: Todas las prendas están marcadas como eliminadas');
    console.error('   Revisa si algo está llamando a gestor.eliminar(0)');
}
```

---

## 🚨 CULPABLES POTENCIALES

### Culpable 1: `cerrarModalPrendaNueva()` Limpia Procesos Demasiado Pronto

**Archivo:** `prendas-wrappers.js` línea 43

```javascript
window.cerrarModalPrendaNueva = function() {
    // ... cierra modal ...
    
    if (window.limpiarProcesosSeleccionados) {
        window.limpiarProcesosSeleccionados();  // ← ¿Se ejecuta en el orden correcto?
    }
};
```

**Solución:** Verificar el orden de ejecución:
```javascript
agregarPrendaNueva()
  ↓
[Obtiene procesos] ← Aquí aún debería estar lleno
  ↓
[Agrega al gestor]
  ↓
[Renderiza UI]
  ↓
cerrarModalPrendaNueva()
  ↓
[Limpia procesos] ← Aquí ya no importa
```

---

### Culpable 2: Sincronización de Datos Falla

**Archivo:** `renderizador-prenda-sin-cotizacion.js` línea 498

```javascript
sincronizarDatosAntesDERenderizar();
// Si esta función lanza error, todo se detiene
```

---

### Culpable 3: El Container NO Existe

Posible que el HTML tenga ID diferente:
- ❌ `prendas-container-editable` (esperado)
- ✅ `lista-items-pedido` (posible alternativo)
- ✅ Otro ID diferente

---

## ✅ CHECKLIST DE DEBUGGING

- [ ] Marca un proceso en el modal ANTES de agregar prenda
- [ ] Verifica en consola que `procesosSeleccionados` tiene datos
- [ ] Ejecuta `debugVerificarUltimaPrenda()` después de agregar
- [ ] Revisa que `gestor.prendas.length` es mayor que 0
- [ ] Verifica que `gestor.obtenerActivas().length` también es mayor que 0
- [ ] Busca errores en rojo en consola F12
- [ ] Verifica que el container existe: `document.getElementById('prendas-container-editable')`
- [ ] Revisa el HTML de la página para ver el ID correcto del container

---

## 🎯 CONCLUSIÓN

**El problema REAL es:**

1. ✅ Procesos se obtienen vacíos porque usuario NO marca procesos en modal
2. ❌ **PERO** la tarjeta también debería renderizarse SIN procesos
3. ❌ Lo que significa que `obtenerActivas()` está retornando array vacío
4. ❌ O el container no existe
5. ❌ O hay un error que previene el renderizado

**Siguiente paso:** Ejecuta los comandos de debugging para identificar cuál es el problema exacto.
