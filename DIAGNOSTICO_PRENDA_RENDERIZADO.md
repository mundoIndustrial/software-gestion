# 🔍 DIAGNÓSTICO: Problema de Renderizado de Tarjetas de Prendas

**Fecha de Análisis:** 15 de enero, 2026  
**Estado:** Problema Identificado + Soluciones Propuestas

---

## 📋 RESUMEN EJECUTIVO

Tras analizar el código después del refactor, he identificado **TRES PROBLEMAS CRÍTICOS** en la cadena de agregar prenda → renderizar tarjeta:

1. **❌ Procesos no se guardan en el objeto de prenda**
2. **❌ El renderizado de la tarjeta no incluye la sección de procesos**
3. **⚠️ Falta sincronización entre procesos seleccionados en el modal y la prenda guardada**

---

## 🔴 PROBLEMAS IDENTIFICADOS

### Problema 1: Procesos Configurables No Se Persisten

**Ubicación:** [`public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`](public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js#L263)

```javascript
// Línea 263: Obtener procesos configurados
const procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
console.log(`🎨 [GestionItemsUI] Procesos configurables:`, procesosConfigurables);

// Línea 282: Se asignan a prendaNueva
const prendaNueva = {
    nombre_producto: nombrePrenda,
    // ... otros datos ...
    procesos: procesosConfigurables,  // ✅ Se incluye en objeto
    cantidadesPorTalla: {}
};
```

**EL PROBLEMA:**
- ✅ Los procesos SE OBTIENEN correctamente de `obtenerProcesosConfigurables()`
- ✅ SE INCLUYEN en el objeto `prendaNueva`
- ✅ SE PASAN al gestor con `gestorPrendaSinCotizacion.agregarPrenda(prendaNueva)`
- ❌ **PERO:** El objeto procesosConfigurables contiene la estructura de STATE completo, **no el formato esperado por renderizado**

**Estructura actual enviada:**
```javascript
{
  "reflectivo": { "tipo": "reflectivo", "datos": null },
  "bordado": { "tipo": "bordado", "datos": null }
}
```

**Estructura esperada en tarjeta renderizada:**
Debería ser un array o lista simple como: `["reflectivo", "bordado"]`

---

### Problema 2: Renderizado NO Incluye Sección de Procesos

**Ubicación:** [`public/js/modulos/crear-pedido/prendas/renderizador-prenda-sin-cotizacion.js`](public/js/modulos/crear-pedido/prendas/renderizador-prenda-sin-cotizacion.js#L560)

**HALLAZGO CRÍTICO:**

La función `renderizarPrendaTipoPrenda()` que genera el HTML de cada tarjeta:
- ✅ Renderiza fotos
- ✅ Renderiza tallas
- ✅ Renderiza variaciones (manga, broche, bolsillos)
- ✅ Renderiza telas
- **❌ NO RENDERIZA PROCESOS**

```javascript
// Línea 552-560: Dentro de renderizarPrendaTipoPrenda()
let variacionesHtml = renderizarVariacionesPrendaTipo(prenda, index);
let telasHtml = renderizarTelasPrendaTipo(prenda, index);

return `
    <div class="prenda-card-editable" data-prenda-index="${index}">
        <!-- ... Fotos, Tallas, Variaciones, Telas ... -->
        <!-- ❌ FALTA AQUÍ: Sección de Procesos -->
    </div>
`;
```

---

### Problema 3: Falta Sincronización Entre Checkbox y Prenda Guardada

**Ubicación:** [`public/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js`](public/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js)

**EL FLUJO ACTUAL:**

1. Usuario marca checkbox de "Reflectivo" en el modal
2. Función `manejarCheckboxProceso('reflectivo', true)` se ejecuta
3. Proceso se registra en `window.procesosSeleccionados`
4. Se abre el modal genérico para configurar detalles
5. Usuario hace click en "Agregar Prenda"
6. Los procesos se obtienen pero **NO HAY VALIDACIÓN** de que realmente estén configurados

**PROBLEMA:** Si el usuario marca un checkbox pero **no llena los detalles en el modal genérico**, el proceso aparece en `procesosSeleccionados` como:
```javascript
{
  "reflectivo": {
    "tipo": "reflectivo",
    "datos": null  // ❌ NULL - Sin configuración real
  }
}
```

---

## ✅ SOLUCIONES PROPUESTAS

### Solución 1: Normalizar Estructura de Procesos

**Archivo:** `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js` (línea 263)

**Cambio:**
```javascript
// ANTES - Obtenemos el estado actual
const procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};

// DESPUÉS - Normalizamos a array de nombres
const procesosConfigurables = Object.keys(window.obtenerProcesosConfigurables?.() || {});
// O si necesitamos los detalles, transformar:
const procesosConfigurables = window.obtenerProcesosConfigurables?.() || [];
```

**Justificación:** El renderizador espera un array o estructura simple, no un objeto con keys.

---

### Solución 2: Agregar Sección de Procesos al Renderizado

**Archivo:** `public/js/modulos/crear-pedido/prendas/renderizador-prenda-sin-cotizacion.js` (alrededor de línea 650)

**Agregar nueva función:**
```javascript
/**
 * Renderizar procesos configurados de una prenda
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de procesos
 */
function renderizarProcesosPrendaTipo(prenda, index) {
    if (!prenda.procesos || Object.keys(prenda.procesos).length === 0) {
        return ''; // No hay procesos
    }

    const procesosNombres = {
        reflectivo: 'Reflectivo',
        bordado: 'Bordado',
        estampado: 'Estampado',
        dtf: 'DTF',
        sublimado: 'Sublimado'
    };

    const procesosIconos = {
        reflectivo: 'light_mode',
        bordado: 'auto_awesome',
        estampado: 'format_paint',
        dtf: 'print',
        sublimado: 'palette'
    };

    let html = `
        <div class="form-section" style="background: #f0f7ff; border-left: 4px solid #0066cc; padding: 1rem; border-radius: 6px;">
            <label class="form-label-primary" style="margin-bottom: 0.75rem;">
                <span class="material-symbols-rounded">settings</span>PROCESOS CONFIGURADOS
            </label>
            <ul style="margin: 0; padding-left: 1.5rem; list-style: disc;">
    `;

    Object.keys(prenda.procesos).forEach(tipoProceso => {
        const nombre = procesosNombres[tipoProceso] || tipoProceso;
        const icon = procesosIconos[tipoProceso] || 'settings';
        html += `
            <li style="margin: 0.5rem 0; color: #374151; font-size: 0.9rem;">
                <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle; margin-right: 0.5rem;">${icon}</span>
                ${nombre}
            </li>
        `;
    });

    html += `
            </ul>
        </div>
    `;

    return html;
}
```

**Integrar en renderizarPrendaTipoPrenda():**
```javascript
// Alrededor de línea 650, después de telasHtml
let telasHtml = renderizarTelasPrendaTipo(prenda, index);
let procesosHtml = renderizarProcesosPrendaTipo(prenda, index);  // ✅ AGREGAR

return `
    <div class="prenda-card-editable" data-prenda-index="${index}">
        <!-- ... código existente ... -->
        ${variacionesHtml}
        ${telasHtml}
        ${procesosHtml}  <!-- ✅ INSERTAR AQUÍ -->
    </div>
`;
```

---

### Solución 3: Validar Procesos Antes de Guardar

**Archivo:** `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js` (línea 263)

**Agregar validación:**
```javascript
// Obtener procesos configurados (reflectivo, bordado, estampado, etc.)
let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};
console.log(`🎨 [GestionItemsUI] Procesos configurables (antes):`, procesosConfigurables);

// ✅ FILTRAR: Solo incluir procesos que realmente tienen datos
procesosConfigurables = Object.keys(procesosConfigurables).reduce((acc, tipoProceso) => {
    const proceso = procesosConfigurables[tipoProceso];
    // ✅ Solo incluir si tiene datos o configuración real
    if (proceso && (proceso.datos !== null || proceso.tipo)) {
        acc[tipoProceso] = proceso;
    }
    return acc;
}, {});

console.log(`🎨 [GestionItemsUI] Procesos configurables (después):`, procesosConfigurables);
```

---

## 🛠️ PASOS DE DEBUG CONCRETOS

### Debug en Navegador - Consola

**Paso 1: Verificar que los procesos se obtienen correctamente**
```javascript
// En consola del navegador (F12 → Console)
console.log('Procesos seleccionados:', window.procesosSeleccionados);
console.log('Función obtener:', window.obtenerProcesosConfigurables());
```

**Paso 2: Verificar que el gestor recibe los procesos**
```javascript
// Después de agregar una prenda
const ultimaPrenda = window.gestorPrendaSinCotizacion.prendas[window.gestorPrendaSinCotizacion.prendas.length - 1];
console.log('Prenda agregada:', ultimaPrenda);
console.log('Procesos en prenda:', ultimaPrenda.procesos);
```

**Paso 3: Verificar que el renderizado incluye procesos**
```javascript
// Revisar el HTML generado
const prendaCard = document.querySelector('[data-prenda-index="0"]');
const tieneProcesos = prendaCard.innerHTML.includes('PROCESOS');
console.log('¿Tarjeta incluye procesos?', tieneProcesos);
```

---

### Debug en Backend - Laravel

**Paso 1: Agregar logs en el controlador**
```php
// En el endpoint que procesa la prenda
\Log::info('Prenda recibida:', [
    'nombre' => $request->nombre_producto,
    'procesos' => $request->procesos,
    'procesos_keys' => array_keys((array)$request->procesos ?? [])
]);
```

**Paso 2: Verificar que procesos se persisten**
```php
// Después de guardar
$prenda = Prenda::find($id);
\Log::info('Prenda guardada:', ['procesos' => $prenda->procesos]);
```

---

## 📊 MATRIZ DE DIAGNÓSTICO

| Componente | Estado | Evidencia | Severidad |
|------------|--------|-----------|-----------|
| `obtenerProcesosConfigurables()` | ✅ Funciona | Se llama en línea 263 | - |
| Procesos se asignan a prendaNueva | ✅ Funciona | Se incluye en línea 282 | - |
| Procesos se pasan al gestor | ✅ Funciona | agregarPrenda() recibe datos | - |
| **Renderizado de procesos** | ❌ FALTA | No hay función renderizarProcesosPrendaTipo() | 🔴 CRÍTICO |
| **Sincronización de estado** | ⚠️ Parcial | Procesos en state pero no validados | 🟡 ALTO |
| **Visualización en tarjeta** | ❌ FALTA | Sección de procesos no aparece | 🔴 CRÍTICO |

---

## 🚀 PLAN DE IMPLEMENTACIÓN

### Fase 1: Verificación (5 minutos)
1. Abre consola F12
2. Agrega una prenda marcando "Reflectivo"
3. Ejecuta los comandos de debug de arriba
4. Verifica si `ultimaPrenda.procesos` tiene el objeto

### Fase 2: Implementación (20 minutos)
1. Copia la nueva función `renderizarProcesosPrendaTipo()`
2. Agrégala a `renderizador-prenda-sin-cotizacion.js`
3. Integra la llamada en `renderizarPrendaTipoPrenda()`
4. Prueba con una prenda nueva

### Fase 3: Validación (10 minutos)
1. Agrega una prenda CON procesos
2. Verifica que aparezca la sección en la tarjeta
3. Cierra sesión y verifica que se persista en BD

### Fase 4: Optimización (opcional)
1. Filtrar procesos vacíos (solución 3)
2. Agregar íconos y estilos mejorados
3. Agregar opción de editar procesos desde tarjeta

---

## 📝 ARCHIVOS AFECTADOS

### Archivos a MODIFICAR:
1. **`public/js/modulos/crear-pedido/prendas/renderizador-prenda-sin-cotizacion.js`**
   - Agregar función `renderizarProcesosPrendaTipo()`
   - Integrar en `renderizarPrendaTipoPrenda()`

2. **`public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`** (opcional)
   - Agregar validación de procesos línea 263

### Archivos a REVISAR (sin cambios necesarios):
1. `public/js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js` ✅
2. `public/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js` ✅
3. `resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php` ✅

---

## ✨ CHECKLIST FINAL

- [ ] Función `renderizarProcesosPrendaTipo()` implementada
- [ ] Procesos aparecen en tarjeta después de agregar
- [ ] Procesos se persisten al recargar página
- [ ] Backend recibe procesos correctamente
- [ ] Procesos filtrados (no mostrar vacios)
- [ ] Estilos consistentes con otras secciones
- [ ] Consola sin errores relacionados
- [ ] Tested en múltiples géneros (dama, caballero)

---

## 🔗 REFERENCIAS

- [Modal agregar prenda](resources/views/asesores/pedidos/modals/modal-agregar-prenda-nueva.blade.php) (línea 241-296)
- [Manejadores procesos](public/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js) (línea 91)
- [Renderizador](public/js/modulos/crear-pedido/prendas/renderizador-prenda-sin-cotizacion.js) (línea 471+)

---

**Conclusión:** El problema principal es que **el renderizado NO incluye una sección visual de procesos**. Los procesos se guardan correctamente en el objeto de prenda, pero no se muestran en la tarjeta. La solución es implementar la función de renderizado de procesos e integrarla en el flujo actual.
