# Análisis y Solución: Duplicación de Prendas en Borradores

## 🔍 Problema Identificado

Cuando editas una prenda en un borrador (draft) pedido y cambias una talla, la prenda aparece duplicada en la vista antes de guardar. El sistema luego guarda una de ellas correctamente, pero la UX es confusa.

## 🎯 Raíz del Problema

El problema está en el flujo de re-renderización después de editar:

### 1. Archivo: `public/js/componentes/prenda-card-editar-simple.js` (Línea 617-658)

Función `reRenderizarTarjetaPrendaEditada()` tiene guards que causan retorno temprano:

```javascript
function reRenderizarTarjetaPrendaEditada(prendaIndex) {
    // ❌ Estos guards PREVIENEN que solo se actualice la tarjeta editada
    if (!globalThis.gestorPrendaSinCotizacion || !globalThis.generarTarjetaPrendaReadOnly) {
        return; // ← PROBLEMA: Retorna sin hacer nada
    }
    
    // ... rest of code
}
```

**Cuando estos guards retornan temprano:**
- La función no actualiza la tarjeta específica
- El fallback en `prenda-flow-service.js` (línea 334) se ejecuta
- Llama `_actualizarRenderItemsOrdenadosSinBloquear()` que re-renderiza TODO
- Esto puede causar que aparezca una tarjeta duplicada en la vista

### 2. Archivo: `public/js/modulos/crear-pedido/procesos/prenda-flow-service.js` (Línea 328-335)

```javascript
// FIX DUPLICADOS: No re-renderizar TODO, solo actualizar la tarjeta específica
// Para evitar duplicados al editar, solo re-renderizamos la tarjeta editada
if (typeof globalThis.reRenderizarTarjetaPrendaEditada === 'function') {
    globalThis.reRenderizarTarjetaPrendaEditada(this.ui.prendaEditIndex);
} else {
    // ❌ FALLBACK PROBLEMÁTICO: Re-renderiza TODAS las prendas
    this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
}
```

**Escenario de duplicación:**

1. Usuario abre un borrador con 1 prenda
2. Usuario edita la talla de esa prenda
3. Sistema llama `_procesarEditacionEnMemoria()` - actualiza la prenda en memoria ✓
4. Sistema intenta `reRenderizarTarjetaPrendaEditada()` - RETORNA TEMPRANO ✗
5. Sistema ejecuta fallback: `_actualizarRenderItemsOrdenadosSinBloquear()` ✗
6. Esto causa que la tarjeta se renderize 2 veces
7. Usuario ve la prenda duplicada temporalmente

## ✅ Solución: Opción A (Recomendada)

### Paso 1: Mejorar `reRenderizarTarjetaPrendaEditada()`

Archivo: `public/js/componentes/prenda-card-editar-simple.js`

Reemplaza líneas 617-658:

```javascript
function reRenderizarTarjetaPrendaEditada(prendaIndex) {
    console.log('[reRenderizarTarjetaPrendaEditada] Actualizando tarjeta de prenda:', prendaIndex);
    
    // Fallback si el gestor no existe: obtener prenda desde gestionItemsUI
    let prenda = null;
    
    if (globalThis.gestorPrendaSinCotizacion && typeof globalThis.gestorPrendaSinCotizacion.obtenerPorIndice === 'function') {
        prenda = globalThis.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
    } else if (globalThis.gestionItemsUI && globalThis.gestionItemsUI.prendas) {
        // FALLBACK: Obtener directamente desde gestionItemsUI.prendas
        prenda = globalThis.gestionItemsUI.prendas[prendaIndex];
    }
    
    if (!prenda) {
        console.warn('[reRenderizarTarjetaPrendaEditada] No se encontró prenda en índice:', prendaIndex);
        return false;
    }
    
    // Obtener función de generación de tarjeta
    if (typeof globalThis.generarTarjetaPrendaReadOnly !== 'function') {
        console.warn('[reRenderizarTarjetaPrendaEditada] generarTarjetaPrendaReadOnly no disponible');
        return false;
    }
    
    // Buscar TODAS las tarjetas DOM con este índice
    const tarjetas = document.querySelectorAll(`[data-prenda-index="${prendaIndex}"]`);
    if (tarjetas.length === 0) {
        console.warn('[reRenderizarTarjetaPrendaEditada] No hay tarjetas en DOM para índice:', prendaIndex);
        return false;
    }
    
    console.log('[reRenderizarTarjetaPrendaEditada] Encontradas', tarjetas.length, 'tarjeta(s)');
    
    // Re-generar HTML de la tarjeta
    const nuevoHTML = globalThis.generarTarjetaPrendaReadOnly(prenda, prendaIndex);
    const nuevoElemento = document.createElement('div');
    nuevoElemento.innerHTML = nuevoHTML;
    const nuevaTarjeta = nuevoElemento.firstElementChild;
    
    if (!nuevaTarjeta) {
        console.error('[reRenderizarTarjetaPrendaEditada] Error al generar HTML de tarjeta');
        return false;
    }
    
    // Reemplazar la PRIMERA tarjeta y eliminar duplicados
    let primeraReemplazada = false;
    tarjetas.forEach((tarjeta, idx) => {
        if (!primeraReemplazada) {
            // Reemplazar la primera tarjeta
            tarjeta.replaceWith(nuevaTarjeta);
            console.log('[reRenderizarTarjetaPrendaEditada] Tarjeta actualizada');
            primeraReemplazada = true;
        } else {
            // Eliminar los duplicados
            console.warn('[reRenderizarTarjetaPrendaEditada] DUPLICADO eliminado - índice:', idx);
            tarjeta.remove();
        }
    });
    
    return true;
}
```

### Paso 2: Mejorar fallback en `prenda-flow-service.js`

Archivo: `public/js/modulos/crear-pedido/procesos/prenda-flow-service.js`

Reemplaza líneas 328-335:

```javascript
// FIX DUPLICADOS: Intentar actualizar solo la tarjeta editada
const actualizacionExitosa = typeof globalThis.reRenderizarTarjetaPrendaEditada === 'function' 
    ? globalThis.reRenderizarTarjetaPrendaEditada(this.ui.prendaEditIndex)
    : false;

// SOLO si la actualización específica falló, re-renderizar todo
if (!actualizacionExitosa) {
    console.warn('[guardarPrenda] Actualización específica falló, re-renderizando todo');
    this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
}
```

## ✅ Solución: Opción B (Alternativa más simple)

Si prefieres una solución más directa sin cambios grandes, solo mejora el fallback:

Archivo: `public/js/modulos/crear-pedido/procesos/prenda-flow-service.js` (Línea 328-335)

```javascript
// FIX DUPLICADOS: No re-renderizar TODO después de editar, solo la tarjeta editada
if (typeof globalThis.reRenderizarTarjetaPrendaEditada === 'function') {
    try {
        const exito = globalThis.reRenderizarTarjetaPrendaEditada(this.ui.prendaEditIndex);
        if (exito === false) {
            // Si la función retorna false, significa que falló
            console.warn('[guardarPrenda] reRenderizarTarjetaPrendaEditada falló, intentando fallback');
            this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
        }
    } catch (error) {
        console.error('[guardarPrenda] Error en reRenderizarTarjetaPrendaEditada:', error);
        this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
    }
} else {
    // Si la función no existe, solo re-renderizar
    this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
}
```

## 🧪 Cómo Probar la Solución

1. **En el navegador (borrador nuevo):**
   - Crea un nuevo pedido en borrador
   - Agrega una prenda (ej: "CAMISA")
   - Haz clic en el botón de editar (⋮) de la prenda
   - Abre el modal de edición
   - Cambia la cantidad de una talla (ej: aumenta XS de 5 a 10)
   - Haz clic en "Guardar Cambios"
   - ✓ La prenda NO debe duplicarse (debe actualizarse en su lugar)

2. **En el navegador (borrador existente):**
   - Abre un borrador que ya tenga prendas
   - Edita la cantidad de una talla
   - ✓ No debe haber duplicados

3. **En consola (debugging):**
   ```javascript
   // Ver si los servicios están disponibles
   console.log('gestorPrendaSinCotizacion:', !!globalThis.gestorPrendaSinCotizacion);
   console.log('generarTarjetaPrendaReadOnly:', !!globalThis.generarTarjetaPrendaReadOnly);
   console.log('gestionItemsUI:', !!globalThis.gestionItemsUI);
   ```

## 📊 Comparación de Soluciones

| Aspecto | Opción A | Opción B |
|---------|----------|----------|
| Cambios | Mas líneas (mejora robustez) | Menos líneas (fallback mejorado) |
| Robustez | Muy alta (fallbacks múltiples) | Media (confía en servicios) |
| Mantenibilidad | Buena (claro qué hace) | Buena (simple) |
| Recomendación | ✅ Sí (mejor) | Alternativa si A es muy grande |

## 🔗 Archivos Afectados

```
public/js/componentes/prenda-card-editar-simple.js
  └─ Función: reRenderizarTarjetaPrendaEditada() [Línea 617-658]
  
public/js/modulos/crear-pedido/procesos/prenda-flow-service.js
  └─ Sección: _procesarEditacionEnMemoria() [Línea 328-335]
```

## 📝 Notas Adicionales

- Las líneas exactas pueden variar según la versión del código
- La solución es retrocompatible (no rompe nada)
- Se recomienda probar en borrador primero antes de aplicar a pedidos existentes
- Los console.log ayudan con debugging si ocurren problemas
