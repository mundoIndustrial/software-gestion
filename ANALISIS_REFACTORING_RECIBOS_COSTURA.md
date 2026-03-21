# 📊 Análisis de Refactorización: recibos-costura.blade.php

**Archivo:** `resources/views/registros/recibos-costura.blade.php`  
**Tamaño:** ~1900 líneas  
**Complejidad:** ALTA ⚠️  
**Fecha de análisis:** 21/03/2026

---

## 🔴 PROBLEMAS CRÍTICOS

### 1. **Arquitectura: JavaScript Embebido en Blade**
- **Problema:** Toda la lógica JavaScript está incrustada en el template Blade (300+ líneas)
- **Impacto:** Difícil de mantener, testear y reutilizar
- **Solución:** Extraer a módulo JavaScript separado

### 2. **Duplicación de Variable Global (window.currentOrderData)**
```javascript
// Aparece en:
- cargarDatosParaAgregarProceso() → window.currentOrderData = orderData
- handleAgregarProcesoDesdeBadge() → usa window.currentOrderData
- verificarDatosAntesDeGuardar() → usa window.currentOrderData
- abrirModalAgregarProcesoDesdeArea() → usa window.currentOrderData
- abrirModalSeguimiento() → usa window.currentOrderData
```
- **Problema:** Variable global sin patrón de gestión
- **Riesgo:** Race conditions, contaminación de namespace
- **Solución:** Patrón Module o Store centralizado

### 3. **Llamadas a API Innecesarias y Duplicadas**
```javascript
// En carga inicial (DOMContentLoaded):
fetch(`/api/pedidos/${pedidoProduccionId}/prendas`) // ❌ Por cada recibo

// En verDetallesRecibo():
fetch(`/registros/${pedidoId}/recibos-datos`) // ❌ Nuevamente

// En cargarDatosParaAgregarProceso():
fetch(`/registros/${pedidoId}/recibos-datos`) // ❌ Otra vez

// En abrirModalSeguimientoDirecto():
fetch(`/registros/${pedidoId}/consecutivo-costura`) // ✓ OK, es diferente
```
- **Impacto:** Múltiples solicitudes al servidor por la misma información
- **Solución:** Cache de datos o cargar datos una sola vez

### 4. **Buggy: Selector de Filtro Incorrecto**
```javascript
function selectAllCheckboxFilters(filterType) {
    const checkboxes = document.querySelectorAll(
        `#filterOptions-${filterType} input[type="checkbox"]`  // ❌ INCORRECTO
    );
    // ...
}

// Debería ser:
const checkboxes = document.querySelectorAll(
    `#filterOptions input[type="checkbox"]`  // ✓ Correcto
);
```
- **Impacto:** "Seleccionar todas" no funciona
- **Severidad:** Media

### 5. **Error Manejo Inconsistente**
- Mezcla de `console.error()`, `alert()`, `showError()`
- No hay logging centralizado
- Algunos errores se pierden silenciosamente

---

## 🟡 PROBLEMAS DE RENDIMIENTO

### 1. **Loop de DOM N+1 en DOMContentLoaded**
```javascript
// Esto hace N llamadas fetch para N recibos
filasRecibos.forEach(fila => {
    const reciboId = fila.getAttribute('data-orden-id');
    fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)  // ❌ POR CADA FILA
        .then(datos => {
            descripcionElemento.textContent = nombrePrenda;
        });
});
```
- **Severidad:** CRÍTICA si hay >10 recibos
- **Solución:** Cargar datos en batch o server-side rendering

### 2. **Event Listeners Duplicados**
```javascript
// En verificarDatosAntesDeGuardar():
btnConfirm.removeEventListener('click', verificarDatosAntesDeGuardar);
btnConfirm.addEventListener('click', verificarDatosAntesDeGuardar);
```
- **Problema:** Se agrega listener cada vez que se abre modal
- **Solución:** Agregar listener solo una vez en DOMContentLoaded

### 3. **DOM Queries Repetidas**
```javascript
document.getElementById('toastContainer')  // Aparece ~5 veces
document.getElementById('filterModal')     // Aparece ~4 veces
document.getElementById('tablaRecibosBody') // Aparece ~6 veces
```
- **Solución:** Cachear referencias al DOM

### 4. **Reflow Forzados en Dropdowns**
```javascript
dropdown.style.display = 'block';
setTimeout(() => {
    const dropRect = dropdown.getBoundingClientRect();  // ❌ Fuerza reflow
    if (dropRect.right > window.innerWidth) {
        dropdown.style.left = (window.innerWidth - dropRect.width - 10) + 'px';
    }
}, 10);  // ❌ setTimeout puede no ser suficiente
```
- **Solución:** Usar `requestAnimationFrame` o calcular posición antes

---

## 🟢 DUPLICACIÓN DE CÓDIGO

### 1. **Títulos de Filtros Hardcodeados**
```javascript
// Línea ~329
const titles = {
    'descripcion': 'Filtrar por Descripción',
    'cliente': 'Filtrar por Cliente',
    // ... 9 más
};
```
Este objeto podría estar en el backend (`data-filters` o similar)

### 2. **Funciones de Mensaje Duplicadas**
```javascript
function showSuccess(message, title = 'Éxito')  // Linha ~1165
function showError(message, title = 'Error')    // Línea ~1171
// Ambas llaman a showToast()
```
Podrían ser una sola función paramétrica

### 3. **Lógica de Obtener Pedido ID Repetida 3 VECES**
```javascript
// En verDetallesRecibo() - Líneas ~655-695
if (enlacePedido) { ... }
if (!pedidoId) { const pedidoIdAttr = ... }
if (!pedidoId) { const dropdownDiaEntrega = ... }

// En cargarDatosParaAgregarProceso() - No la repite pero sí falta validación
// En abrirModalSeguimiento() - Similar
```
**Solución:** Función helper `extractPedidoId(row)`

### 4. **Inicialización de Modal Duplicada**
```javascript
// Línea ~931 (abrirModalAgregarProcesoDesdeArea)
modal.setAttribute('data-pedido-id', pedidoId);
modal.setAttribute('data-prenda-id', prendaId || '');
modal.setAttribute('data-area', areaSeleccionada);
modal.style.display = 'flex';
modal.classList.add('show');

// Línea ~821 (abrirModalSeguimientoDirecto)
trackingModal.style.display = 'flex';
trackingModal.classList.add('show');
```

### 5. **Cierre de Modal Duplicado**
```javascript
// Línea ~1110
modal.classList.remove('show');
modal.style.display = 'none';

// Línea ~1401 (closeModalOverlay)
modal.style.display = 'none';
wrapper.style.display = 'none';
```

---

## 🔵 PROBLEMAS DE MANTENIBILIDAD

### 1. **Constantes Mágicas Esparcidas**
```javascript
z-index: 999999           // ❌ Múltiples valores
z-index: 9998, 9997       // ❌ Sin patrón
z-index: 10000000, 10000001
max-width: 672px          // ¿De dónde viene?
border-radius: 16px, 8px, 12px  // ¿Cuál es el estándar?
```

### 2. **Error Handling Ad-hoc**
```javascript
if (!response.ok) {
    throw new Error('Error al agregar proceso');  // ❌ No específico
}

if (!modal) {
    alert('Modal de agregar proceso no disponible');  // ❌ UX pobre
}

console.warn('[Filtros] No se encontró la tabla para generar opciones dinámicas');  // ❌ Sin acción
```

### 3. **Logging Verboso pero Sin Estructura**
```javascript
console.log('[Filtros] openFilterModal llamado con:', filterType);
console.log('[DIAGNÓSTICO] Verificando sistema...');
console.log('🔴 [ReciboAprobado] Inicializando listener...');
console.log('[CargarNombres] ✅ Prenda actualizada...');
```
Mezclado con `console.error()` sin patrón de severidad

### 4. **Validación Inconsistente**
```javascript
// Validación estricta
if (!prendaId || prendaId === 'null' || prendaId === null) {  // ✓ Buena
    throw new Error('CRÍTICO: No se proporcionó...');
}

// Validación laxa
if (!area) {
    showError('Por favor selecciona un área/proceso');  // ❌ Silencioso
    return;
}
```

---

## 🟣 PROBLEMAS DE ARQUITECTURA

### 1. **Falta de Separación de Responsabilidades**
```
recibos-costura.blade.php
├── HTML (estructura)
├── CSS (estilos)
├── JavaScript (lógica)
│   ├── Filtros
│   ├── Recibos (CRUD)
│   ├── Procesos
│   ├── Seguimiento
│   ├── Notificaciones
│   ├── Toasts
│   └── Dropdowns
└── WebSocket listeners
```
**Todo en un archivo de 1900 líneas** 🔥

### 2. **Dependencias Implícitas**
```javascript
// handleAgregarProcesoDesdeBadge() depende de:
- window.currentOrderData (variable global)
- window.currentPrendaData (variable global)
- document.getElementById('procesoArea')
- document.getElementById('procesoEncargado')
- fetch('/seguimiento-proceso/guardar')  // Acoplado a ruta
- window.location.reload()  // Recarga toda la página
```

### 3. **Falta de Validación Previa**
```javascript
// En abrirModalAgregarProcesoDesdeArea()
if (!pedidoId) {
    alert('No se puede identificar el pedido asociado');
    return;  // ✓
}

// Pero en handleAgregarProcesoDesdeBadge()
if (!window.currentOrderData || !window.currentPrendaData) {
    showError('No hay datos de la prenda o pedido');
    return;  // ❌ Debería no llegar aquí si verificarDatosAntesDeGuardar funciona
}
```

---

## ⚠️ BUGS DETECTADOS

### 1. **Bug: selectAllCheckboxFilters Selector Incorrecto**
```javascript
const checkboxes = document.querySelectorAll(
    `#filterOptions-${filterType} input[type="checkbox"]`  // ❌ No existe
);
```
Debería ser:
```javascript
const checkboxes = document.querySelectorAll(
    `#filterOptions input[type="checkbox"]`  // ✓
);
```

### 2. **Bug: filterCheckboxOptions Asume Clase Inexistente**
```javascript
const searchTerm = document.querySelector('.filter-search').value;  // ❌ ¿Existe?
```

### 3. **Bug: Fallback a Primera Prenda (Crítico)** 
```javascript
if (!prendaSeleccionada) {
    prendaSeleccionada = prendas[0];  // ❌ Abre recibo INCORRECTO
    console.log('[...] Usando primera prenda como fallback...');
}
```
Esto puede abrir el recibo de la prenda equivocada

### 4. **Bug: Modal de Seguimiento Sin Inicialización**
```javascript
// Si openOrderTracking falla, nunca se llama a abrirModalSeguimientoDirecto()
// Pero el modal se abre sin datos
```

---

## 📋 CHECKLIST DE REFACTORIZACIÓN

### FASE 1: Extracción de Código
- [ ] Extraer todos los estilos inline CSS a componente `<x-recibos.recibos-costura-extra-styles />`
- [ ] Separar JavaScript en módulos:
  - [ ] `js/modules/recibos-filtros.js`
  - [ ] `js/modules/recibos-procesos.js`
  - [ ] `js/modules/recibos-modales.js`
  - [ ] `js/modules/recibos-notificaciones.js`
  - [ ] `js/modules/recibos-toasts.js`

### FASE 2: Consolidación
- [ ] Crear `RecibosManager` class para gestionar estado
- [ ] Crear `DropdownHandler` para manejo de dropdowns
- [ ] Crear `FilterManager` para lógica de filtros
- [ ] Crear `ProcessManager` para procesos

### FASE 3: Optimización
- [ ] Cachear llamadas a `getElementById()` frecuentes
- [ ] Implementar lazy loading para datos de recibos
- [ ] Agregar debouncing a filtros
- [ ] Usar `requestAnimationFrame` para posicionamiento de dropdowns

### FASE 4: Bug Fixes
- [ ] Fix selector en `selectAllCheckboxFilters()`
- [ ] Fix validación de prenda específica en `abrirModalSeguimiento()`
- [ ] Fix inicialización de listeners de modal
- [ ] Validar elemento `.filter-search` existe

### FASE 5: Testing
- [ ] Tests unitarios para funciones de filtro
- [ ] Tests de integración para flujo de procesos
- [ ] Tests E2E para agregar procesos desde badge

---

## 💡 RECOMENDACIONES PRIORITARIAS

| Prioridad | Acción | Línea(s) | Impacto |
|-----------|--------|----------|---------|
| 🔴 ALTA | Cachear datos de recibos (evitar N+1) | ~625-645 | Rendimiento |
| 🔴 ALTA | Fix selector filterOptions | ~631 | Funcionalidad |
| 🔴 ALTA | Centralizar gestión de estado global | ~980-1050 | Mantenibilidad |
| 🟡 MEDIA | Extraer JavaScript a módulo | Todo | Mantenibilidad |
| 🟡 MEDIA | Unificar funciones datos (3 versiones) | ~655-695 | DRY |
| 🟡 MEDIA | Eliminar listeners duplicados | ~944 | Rendimiento |
| 🟢 BAJA | Reorganizar CSS (agregar variables) | ~35-300 | Mantenibilidad |

---

## 📊 Métricas

| Métrica | Valor | Referencia |
|---------|-------|-----------|
| Líneas totales | 1900+ | - |
| Líneas JavaScript | 1200+ | Debería ser <300 |
| Variables globales | 8+ | Debería ser 0 |
| Funciones | 25+ | OK pero muy acopladas |
| Fetch calls | 6+ | Debería ser 3-4 |
| Complejidad ciclomática promedio* | ~8 | Debería ser <5 |

*Estimado en funciones como `verDetallesRecibo()`, `abrirModalAgregarProcesoDesdeArea()`

---

## 🎯 Plan de Refactorización Sugerido

### Semana 1: Preparación
1. Crear estructura de módulos
2. Crear clase `RecibosDataManager` para cache
3. Crear archivo `recibos-costura-config.js` con constantes

### Semana 2: Extracción
1. Extraer filtros a `recibos-filtros.js`
2. Extraer procesos a `recibos-procesos.js`
3. Extraer modales a `recibos-modales.js`

### Semana 3: Consolidación
1. Consolidar funciones duplicadas
2. Implementar cache de datos
3. Agregar validaciones centralizadas

### Semana 4: Testing & Optimización
1. Escribir tests
2. Optimizar rendimiento
3. Documentar público API

---

## ✅ Conclusión

El archivo **requiere refactorización urgente**. Está en un estado "legacy" donde la funcionalidad es correcta pero el mantenimiento es cada vez más difícil. La recomendación es proceder por fases, empezando por los bugs críticos y luego extrayendo código a módulos separados.

**Riesgo actual:** Cualquier cambio pequeño puede romper funcionalidad no evidente debido al acoplamiento global.
