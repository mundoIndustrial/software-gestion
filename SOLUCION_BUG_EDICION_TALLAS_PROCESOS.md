# 🔧 SOLUCIÓN: Bug en Edición de Tallas de Procesos

**Fecha de solución:** 27 Enero 2026  
**Versión:** 1.0  
**Estado:** ✅ COMPLETADO

---

## 🐞 Problema Identificado

Al editar las tallas de un proceso (reflectivo, bordado, estampado, etc.) en la vista `/asesores/pedidos-editable/crear-nuevo`, **se estaban modificando las tallas y cantidades de la PRENDA PRINCIPAL** en lugar de mantenerlas independientes.

### Ejemplo del Bug:
```
Prenda original: S = 20 unidades
Proceso (reflectivo): Asignar a 5 unidades de S

BUG: Al guardar, la prenda quedaba con S = 5 (sobrescrito)
ESPERADO: Prenda debe mantener S = 20, proceso solo guarda S = 5
```

---

## 🔍 Causa Raíz

El bug estaba en el archivo:
- **[public/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js](public/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js)**

**Función problemática:** `actualizarCantidadTallaProceso()`

```javascript
// ❌ INCORRECTO - Modificaba las tallas de la PRENDA
window.tallasRelacionales[generoMayuscula][talla] = cantidad;
```

### Problema:
1. La prenda usa `window.tallasRelacionales` para almacenar sus tallas
2. Los procesos estaban usando la MISMA estructura para guardar cantidades
3. Al editar un proceso, se sobrescribía directamente `tallasRelacionales`
4. Esto hacía que la prenda perdiera sus tallas originales

---

## ✅ Solución Implementada

### 1. **Estructura de Datos Independiente**

Se agregó una variable global separada para las tallas del PROCESO:

```javascript
// Cantidades de TALLAS DEL PROCESO (independiente de la prenda)
window.tallasCantidadesProceso = { dama: {}, caballero: {} };
```

**Separación clara:**
- `window.tallasRelacionales` → Tallas de la **PRENDA** (original)
- `window.tallasCantidadesProceso` → Tallas del **PROCESO** (específicas)

### 2. **Función Corregida: `actualizarCantidadTallaProceso()`**

```javascript
window.actualizarCantidadTallaProceso = function(input) {
    const genero = input.dataset.genero;
    const talla = input.dataset.talla;
    const cantidad = parseInt(input.value) || 0;
    
    // ✅ Obtener cantidad MÁXIMA desde la prenda
    const tallasPrenda = obtenerTallasDeLaPrenda();
    const cantidadMaxima = tallasPrenda[genero.toLowerCase()]?.[talla] || 0;
    
    // ✅ VALIDACIÓN: No permitir exceder cantidad disponible
    if (cantidad > cantidadMaxima) {
        input.value = cantidadMaxima;
        input.style.borderColor = '#dc2626'; // Marcar en rojo
        return;
    }
    
    // ✅ Actualizar SOLO en tallasCantidadesProceso (NO tocas tallasRelacionales)
    window.tallasCantidadesProceso[generoMayuscula][talla] = cantidad;
};
```

### 3. **Modal de Edición de Tallas - Cambios**

```javascript
window.abrirEditorTallasEspecificas = function() {
    const tallasPrenda = obtenerTallasDeLaPrenda(); // Original
    
    tallasDamaArray.forEach(talla => {
        const cantidadPrenda = tallasPrenda.dama[talla];      // De la prenda
        const cantidadProceso = window.tallasCantidadesProceso?.dama?.[talla]; // Del proceso
        
        // Campo muestra cantidad del proceso O cantidad de la prenda
        const cantidadMostrar = cantidadProceso || cantidadPrenda;
        
        // Con validación de máximo
        input.max = cantidadPrenda;
        input.placeholder = `Máx: ${cantidadPrenda}`;
    });
};
```

### 4. **Guardado de Tallas en el Proceso**

```javascript
window.guardarTallasSeleccionadas = function() {
    // ... recopilar tallas seleccionadas ...
    
    // ✅ IMPORTANTE: Guardar las tallas en el objeto del PROCESO
    if (procesoActual && window.procesosSeleccionados[procesoActual]?.datos) {
        window.procesosSeleccionados[procesoActual].datos.tallas = {
            dama: window.tallasCantidadesProceso.dama || {},
            caballero: window.tallasCantidadesProceso.caballero || {}
        };
    }
};
```

### 5. **Resumen Visual Correcto**

La función `actualizarResumenTallasProceso()` ahora muestra cantidades del PROCESO:

```javascript
const tallasProceso = window.tallasCantidadesProceso || {};
const cantidadMostrar = tallasProceso.dama?.[talla] || 0;
```

---

## 📋 Cambios por Archivo

### [gestor-modal-proceso-generico.js](public/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js)

| Línea | Función | Cambio |
|-------|---------|--------|
| 12 | Variables globales | ✅ Agregada `window.tallasCantidadesProceso` |
| 85 | `abrirModalProcesoGenerico()` | ✅ Inicializar `tallasCantidadesProceso` en modo nuevo |
| 144 | `cerrarModalProcesoGenerico()` | ✅ Limpiar `tallasCantidadesProceso` al cerrar |
| 540 | `actualizarCantidadTallaProceso()` | ✅ **REESCRITA COMPLETAMENTE** para validar y guardar correctamente |
| 494 | `abrirEditorTallasEspecificas()` | ✅ Cargar cantidades del proceso y valores máximos |
| 600 | `guardarTallasSeleccionadas()` | ✅ Guardar tallas en el objeto del proceso |
| 625 | `actualizarResumenTallasProceso()` | ✅ Usar `tallasCantidadesProceso` no `tallasRelacionales` |
| 720 | `agregarProcesoAlPedido()` | ✅ Usar `tallasCantidadesProceso` al guardar |

### [renderizador-tarjetas-procesos.js](public/js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js)

| Línea | Cambio |
|-------|--------|
| 427 | ✅ Cargar tallas en `tallasCantidadesProceso` (no `tallasRelacionales`) |
| 333 | ✅ Logging mejorado en `cargarDatosProcesoEnModal()` |

---

## 🧪 Flujo de Validación

### ✅ Restricción 1: No superar cantidad disponible
```javascript
if (cantidad > cantidadMaxima) {
    input.value = cantidadMaxima; // Revertir al máximo
    input.style.borderColor = '#dc2626'; // Indicador visual
}
```

### ✅ Restricción 2: Solo tallas existentes en la prenda
```javascript
const tallasPrenda = obtenerTallasDeLaPrenda();
// Solo se muestran tallas que existen en tallasPrenda
tallasDamaArray.forEach(talla => {
    // talla ya viene de tallasPrenda.dama (garantizado que existe)
});
```

### ✅ Restricción 3: Independencia de datos
- Prenda: `window.tallasRelacionales` → **NO modificable desde proceso**
- Proceso: `window.tallasCantidadesProceso` → **Estructura independiente**
- Guardado: Cada uno guarda en su propio objeto

---

## 🎯 Resultados Esperados

### Antes (❌ BUG)
```javascript
// Prenda original
window.tallasRelacionales = {
    DAMA: { S: 20, M: 20 }
}

// Editar reflectivo con S = 5
// ❌ Después de guardar:
window.tallasRelacionales = {
    DAMA: { S: 5, M: 20 }  // PRENDA MODIFICADA (MALO)
}
```

### Después (✅ CORRECTO)
```javascript
// Prenda original (INTACTA)
window.tallasRelacionales = {
    DAMA: { S: 20, M: 20 }
}

// Editar reflectivo con S = 5
window.procesosSeleccionados['reflectivo'].datos.tallas = {
    DAMA: { S: 5 }  // Solo tallas del proceso
}
window.tallasCantidadesProceso = {
    dama: { S: 5 }  // Estructura temporal del modal
}

// ✅ Resultado: Prenda mantiene S: 20, proceso solo guarda S: 5
```

---

## 📝 Ejemplo de Uso Correcto

1. **Crear Prenda:**
   - Talla S: 20 unidades
   - Talla M: 20 unidades

2. **Agregar Proceso (Reflectivo):**
   - Marcar checkbox "Reflectivo"
   - Click en "Editar tallas"

3. **Modal de Edición:**
   ```
   ☑ S [5] (Máx: 20)
   ☐ M [ ] (Máx: 20)
   ```
   - Ingresa 5 para S
   - Deja M sin seleccionar

4. **Guardar Proceso:**
   - Prenda mantiene: S=20, M=20 ✅
   - Reflectivo tiene: S=5 ✅

---

## 🔐 Garantías de Seguridad

| Garantía | Implementación |
|----------|----------------|
| Independencia | `tallasCantidadesProceso` separada de `tallasRelacionales` |
| Validación | Máximo = cantidad de prenda, sin excepciones |
| Persistencia | Datos guardados en `window.procesosSeleccionados[tipo].datos.tallas` |
| Edición | Al cargar proceso, se restauran sus datos sin afectar prenda |
| Límites | Campo `input.max` y validación JS doble garantía |

---

## 📌 Notas Importantes

### Para desarrolladores futuros:

1. **NUNCA modificar directamente `window.tallasRelacionales` desde funciones de PROCESO**
   - Es la fuente de verdad de la PRENDA

2. **Usar `window.tallasCantidadesProceso` para operaciones de PROCESO**
   - Es una estructura temporal que se sincroniza con `procesosSeleccionados[tipo].datos.tallas`

3. **Validación siempre contra `obtenerTallasDeLaPrenda()`**
   - Garantiza que los límites sean respetados

4. **Al cerrar modal sin guardar, limpiar `tallasCantidadesProceso`**
   - Ya está implementado en `cerrarModalProcesoGenerico()`

---

## 🚀 Próximas Mejoras (Opcional)

- [ ] Agregar animación cuando se supera el límite
- [ ] Mostrar tooltip con "Máximos disponibles"
- [ ] Agregar estadísticas de cobertura (X/Y unidades del total)
- [ ] Historial de cambios en procesos

---

**Fin del documento**
