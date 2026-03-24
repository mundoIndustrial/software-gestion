# ANÁLISIS PROFUNDO: tracking-modal-handler.js
**Fecha:** 24/03/2026 | **Versión:** Completa y Detallada

---

## 1. ⚠️ FUNCIONES DUPLICADAS Y REPETIDAS

### 1.1 **`formatDate()` - Duplicada en múltiples contextos**

| Ubicación | Líneas | Problema |
|-----------|--------|----------|
| `formatDate()` function | ~2930-2950 | Función principal |
| `selectorOrderStartDate` block | ~540-570 | Lógica de formateo manual duplicada |
| `selectorOrderEstimatedDate` block | ~575-615 | Lógica de formateo manual duplicada |
| `updateEstimatedDeliveryDate()` | ~903-930 | Lógica de formateo manual duplicada |

**Problema específico - LÍNEAS 540-570:**
```javascript
if (typeof fechaInicio === 'string') {
  try {
    const date = new Date(fechaInicio);
    if (!isNaN(date.getTime())) {
      fechaFormateada = date.toLocaleDateString('es-ES', {...});
    } else {
      fechaFormateada = fechaInicio;
    }
  } catch (e) {
    fechaFormateada = fechaInicio;
  }
} else if (fechaInicio instanceof Date) {
  fechaFormateada = fechaInicio.toLocaleDateString('es-ES', {...});
} else if (fechaInicio && fechaInicio.date) {
  fechaFormateada = new Date(fechaInicio.date).toLocaleDateString('es-ES', {...});
}
```

✗ Este mismo código se repite **3 veces más** (líneas 575-615, 903-930, etc.)

---

### 1.2 **Validación de fechas - `toDateObject()` vs conversiones manuales**

**El archivo tiene `toDateObject()` pero sigue convirtiendo manualmente:**

| Línea | Código Duplicado |
|-------|-----------------|
| 1640-1650 | Dentro de `createAreaCard()`: `const ini = toDateObject(data.fecha_inicio);` |
| 1680-1690 | `const fin = toDateObject(fechaFinRaw);` |
| 1700-1710 | `const diasHabiles = calcularDiasHabilesSync(ini, fin);` |
| 1720+ | Se repite el mismo patrón **8 veces más** |

**El problema:** La función `calcularDiasHabilesSync()` convierte que:
```javascript
// LÍNEA 3020
const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);
```

✗ Esta conversión se duplica en `createAreaCard()`, `renderSeguimientosPorArea()`, y otros lugares.

---

### 1.3 **Formateo de duración - Código triplicado**

**Patrón de formateo de duración se repite:**

1. **LÍNEA 1650:** en `createAreaCard()`
```javascript
const formatBadgeDuration = function(diffMs) {
  const ms = Math.max(0, Number(diffMs) || 0);
  const minutes = Math.floor(ms / 60000);
  const hours = Math.floor(ms / 3600000);
  const days = Math.floor(ms / 86400000);
  // ...
};
```

2. **LÍNEA 3000:** existe `formatDurationHuman()`
```javascript
function formatDurationHuman(diffMs) {
  const totalSeconds = Math.floor((diffMs || 0) / 1000);
  const days = Math.floor(totalSeconds / 86400);
  // ...
};
```

✗ **Dos implementaciones diferentes para lo mismo**, con lógica inconsistente

---

### 1.4 **Actualización de fecha estimada - Código duplicado**

| Función | Líneas | Propósito |
|---------|--------|----------|
| `updateOrderInfo()` | 530-630 | Actualiza fecha estimada en modal |
| `updateEstimatedDeliveryDate()` | 900-950 | Actualiza fecha estimada en selector |
| `saveDiaEntregaSelection()` | 260-310 | Actualiza ambos elementos |

**LÍNEAS 600-610 vs 930-950:** Código casi idéntico
```javascript
// updateOrderInfo - LÍNEA 610
if (selectorOrderEstimatedDate) {
  selectorOrderEstimatedDate.textContent = fechaFormateada || '-';
}

// updateEstimatedDeliveryDate - LÍNEA 945
if (fechaEstimadaElement) {
  fechaEstimadaElement.textContent = fechaFormateada;
}
```

---

### 1.5 **Búsqueda de recibos - Lógica triplicada**

**Se busca el recibo "principal" en 3 lugares diferentes con lógica casi idéntica:**

1. **LÍNEA 660-720** - en `updateOrderInfo()` dentro del bloque de búsqueda de recibo
2. **LÍNEA 1490-1510** - en `showPrendaTracking()` con `normalizeConsecutivos()`
3. **LÍNEA 1380-1400** - en `createPrendasTable()` procesos extracción

**Todos tienen esta lógica similar:**
```javascript
const prioridadRecibos = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];
for (const prioridad of prioridadRecibos) {
  // buscar recibo con esa prioridad
}
```

---

## 2. 🔴 VIOLACIONES SOLID

### 2.1 **SINGLE RESPONSIBILITY PRINCIPLE (SRP) - VIOLADO**

#### `updateOrderInfo()` - LÍNEAS 505-800 hace DEMASIADAS COSAS

**La función realiza:**
1. ✓ Actualizar información del pedido (número, cliente, estado)
2. ✓ Formatear fechas (3 formatos diferentes)
3. ✓ Actualizar múltiples elementos DOM (selector + modal)
4. ✓ Buscar recibo principal (lógica compleja de prioridades)
5. ✓ Normalizar datos de consecutivos
6. ✓ Renderizar estilos inline
7. ✓ Validar datos faltantes

**Responsabilidades que debería tener 3-4 funciones:**

```javascript
// DEBERÍA SER:
function updateOrderInfo(orderData) {
  updateModalOrderInfo(orderData);          // SRP: Modal updates
  updateSelectorOrderInfo(orderData);       // SRP: Selector updates
  updateReceiptNumber(orderData);           // SRP: Receipt logic
}
```

---

#### `createAreaCard()` - LÍNEAS 1620-1880 hace MÚLTIPLES COSAS

**La función realiza:**
1. ✓ Validar tipo de área (isCorte, isCostura, etc.)
2. ✓ Calcular duraciones (asignación, en área, total)
3. ✓ Formatear badgets dinámicamente
4. ✓ Generar HTML inline
5. ✓ Crear lógica de botones (editar/eliminar)
6. ✓ Determinar visibilidad de campos

**260+ líneas en UNA FUNCIÓN**

```javascript
// Debería haber:
function createAreaCard(area, data, readonly) {
  const config = getAreaConfiguration(area);
  const calculations = calculateAreaDurations(data, config);
  const html = renderAreaCardHTML(area, data, calculations, readonly);
  return createCardElement(html);
}
```

---

#### `showPrendaTracking()` - LÍNEAS 1370-1540 hace MÚLTIPLES COSAS

**Responsabilidades:**
1. ✓ Validar y hidratar datos de prenda
2. ✓ Cerrar overlay anterior
3. ✓ Manejo de estilos CSS inline (8+ líneas de `setProperty`)
4. ✓ Lógica de readonly mode
5. ✓ Actualizar múltiples elementos DOM
6. ✓ Iniciar timers
7. ✓ Renderizar timeline
8. ✓ Manejo de estado global (window.currentPrendaData)

---

### 2.2 **OPEN/CLOSED PRINCIPLE (OCP) - VIOLADO**

#### El archivo requiere modificación frecuente para nuevas áreas

**Problema LÍNEA 2010-2050:**
```javascript
const icons = {
  'Corte': '...',
  'Bordado': '...',
  'Estampado': '...',
  'Costura': '...',
  // ...
};
```

✗ Cada vez que se agrega un área, se debe modificar esta función
✗ No es extensible sin cambiar el código existente

**Problema LÍNEA 1605-1615:**
```javascript
const needsEncargado = isCorte || isCostura || isControlCalidad;
const isInsumos = String(area || '').toLowerCase() === 'insumos';
const isCorte = String(area || '').toLowerCase().includes('corte');
const isCostura = String(area || '').toLowerCase().includes('costura');
```

✗ Cada nueva regla de área requiere más condicionales

---

#### Validación de encargado - LÍNEA 2800

```javascript
const needsEncargado = ['corte', 'costura', 'control de calidad'];
const areaRequiresEncargado = needsEncargado.some(reqArea => areaLower.includes(reqArea));
```

✗ Si agregan un nuevo área que requiera encargado, hay que cambiar este array
✗ Este mismo array se duplica en varios lugares (línea 1610)

---

### 2.3 **LISKOV SUBSTITUTION PRINCIPLE (LSP) - VIOLADO**

#### Inconsistencia en estructura de datos esperada

**LÍNEA 1430 vs LÍNEA 1340:**

```javascript
// En createAreaCard() - espera objeto con fecha_completado
if (isInsumos && Boolean(toDateObject(data.fecha_completado))) {
  // ...
}

// En updateOrderInfo() - espera fecha_estimada_de_entrega
const fechaEstimada = orderData.fecha_estimada_de_entrega;
```

✗ Los objetos de datos no tienen estructura consistente
✗ El mismo campo tiene nombres diferentes en contextos diferentes:
  - `fecha_completado`
  - `fecha_fin`
  - `fecha_estimada_de_entrega`
  - `fecha_estimada_entrega`
  - `fecha_de_creacion_de_orden`
  - `created_at`

---

#### Inconsistencia en cálculo de días hábiles

**LÍNEA 1680-1690 en createAreaCard():**
```javascript
const diasHabiles = calcularDiasHabilesSync(ini, fin);
```

**vs LÍNEA 1425 en renderSeguimientosPorArea():**
```javascript
const diasHabiles = calcularDiasHabilesSync(fechaCreacionDate, reciboActDate);
```

✗ A veces se pasa `fin` directamente, a veces se calcula primero
✗ La lógica de qué usar como "inicio" varía según el contexto

---

### 2.4 **INTERFACE SEGREGATION PRINCIPLE (ISP) - VIOLADO**

#### `saveD día EntregaSelection()` - LÍNEA 200+

```javascript
async function saveDiaEntregaSelection() {
  const diasSeleccionados = window.__trackingDiasSeleccionados;
  let ordenId = null;
  
  if (window.currentOrderData) {
    ordenId = window.currentOrderData.id;
  }
  
  if (!ordenId) {
    const ordenNumberEl = document.getElementById('trackingOrderNumber');
    // ...
  }
  // ...
}
```

✗ La función hace demasiadas cosas:
  - Obtener datos de estado global
  - Hacer fetch al backend
  - Actualizar múltiples elementos DOM
  - Mostrar notificaciones
  
**Debería segregarse:**
```javascript
function saveDiaEntregaSelection() {
  const dias = getDiasSeleccionados();      // Interface: obtener datos
  const ordenId = getOrdenId();             // Interface: obtener orden
  calcularFechaEntrega(ordenId, dias);      // Interface: calcular
  actualizarUI(result);                     // Interface: actualizar UI
  mostrarNotificacion(result);              // Interface: notificaciones
}
```

---

#### `createAreaCard()` tiene parámetros mal definidos

**LÍNEA 1620:**
```javascript
function createAreaCard(area, data, readonly = false) {
  // El parámetro 'data' es genérico pero espera estructura específica
  // No especifica qué campos son obligatorios
}
```

✗ No está claro qué estructura debe tener `data`
✗ El código dentro assume campos que podrían no existir:
  - `data.fecha_inicio`
  - `data.fecha_completado`
  - `data.fecha_de_asignacion_encargado`
  - `data.estado`
  - Etc.

---

### 2.5 **DEPENDENCY INVERSION PRINCIPLE (DIP) - VIOLADO**

#### Dependencias directas en lugar de abstracciones

**LÍNEA 805, 815, 835 en showPrendaTracking():**
```javascript
modal.style.setProperty('display', 'flex', 'important');
modal.style.setProperty('visibility', 'visible', 'important');
modal.style.setProperty('opacity', '1', 'important');
modal.style.setProperty('z-index', '9999999', 'important');
```

✗ Dependencia directa del API DOM
✗ Estilos hardcoded (z-index, colores, etc.)
✗ Si cambian los selectores, todo se rompe

**Debería ser:**
```javascript
showModalWithConfig(modal, {
  position: 'fixed-center',
  zIndex: 'high',
  background: 'dark-overlay'
});
```

---

#### Dependencia de estado global

**LÍNEA 1370, 1380, etc.:**
```javascript
window.currentPrendaData = prenda;
window.currentOrderData = data;
window.prendasData = prendas;
window.__trackingDiasSeleccionados = n;
```

✗ Múltiples variables globales acopladas
✗ No hay abstracción de estado
✗ Difícil de testear
✗ Fácil de crear bugs de estado

**Debería usar un StateManager:**
```javascript
StateManager.setPrendaData(prenda);
StateManager.setOrderData(data);
// etc.
```

---

#### Fetch directo acoplado

**LÍNEA 273, 465, 835, 2750, etc.:**
```javascript
const response = await fetch(`/registros/${orderId}/recibos-datos`);
const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);
const response = await fetch('/seguimiento-proceso/guardar', { method: 'POST' });
```

✗ Las URLs están hardcoded en varios lugares
✗ Los endpoints no están centralizados
✗ No hay abstracción de API

**Debería usar un APIClient:**
```javascript
const data = await OrderAPI.loadReciboDatos(orderId);
const prendas = await OrderAPI.loadSeguimientoPrendas(orderId);
```

---

## 3. 🔴 VIOLACIONES DDD (Domain-Driven Design)

### 3.1 **Lógica de Negocio en la UI - CRÍTICO**

#### Cálculo de días hábiles - LÍNEA 3020-3080

```javascript
function calcularDiasHabilesSync(fechaInicio, fechaFin) {
  // CALCULATE business logic
  while (actual <= fin) {
    if (actual.getDay() !== 0 && actual.getDay() !== 6) {
      const fechaStr = actual.toISOString().slice(0, 10);
      if (!festivos.includes(fechaStr)) {
        diasHabiles++;
      }
    }
    actual.setDate(actual.getDate() + 1);
  }
  return Math.max(0, diasHabiles - (inicioEsDiaHabil ? 1 : 0));
}
```

✗ **Este es cálculo de negocio, debe estar en el backend**
✗ Se usa dinámicamente en `actualizarContadoresDinamicos()` (LÍNEA 130+)
✗ También se calcula en `createAreaCard()` (LÍNEA 1690)
✗ Y en `renderSeguimientosPorArea()` (LÍNEA 1425)

**Problema:** Si cambia la regla de negocio (ej: nuevos festivos, cambio en fin de semana), hay que actualizar 3+ lugares

---

#### Determinación de estado de proceso - LÍNEA 1430-1440

```javascript
const hasFechaCompletado = !isInsumos && Boolean(toDateObject(data.fecha_completado));
const estadoDisplay = isInsumos 
  ? (data.estado || 'Pendiente') 
  : (hasFechaCompletado ? 'Completado' : 'Pendiente');
```

✗ La lógica de negocio "un proceso está completado si tiene fecha_completado" está hardcoded en la UI
✗ Si cambian la regla (ej: necesitar más validaciones), hay que cambiar aquí
✗ No existe una entidad `ProcessStatus` o `ProcessState` con esta lógica

---

#### Definición de áreas y sus características - LÍNEA 1605-1615

```javascript
const needsEncargado = isCorte || isCostura || isControlCalidad;
const isInsumos = String(area || '').toLowerCase() === 'insumos';
const isCorte = String(area || '').toLowerCase().includes('corte');
```

✗ **Configuración de áreas (que necesitan encargado, qué iconos usan, etc.) está en la UI**
✗ Debería estar en el backend como **Area Value Objects** o **Area Entities**
✗ Si agregan una nueva área que requiere encargado, hay que cambiar la UI

**Debería existir en el backend:**
```php
// App/Domain/Area/AreaConfiguration.php
class AreaConfiguration {
  const AREAS_THAT_NEED_MANAGER = ['Corte', 'Costura', 'Control de Calidad'];
  const AREA_ICONS = ['Corte' => 'scissors', ...];
}
```

---

#### Prioridad de recibos - LÍNEA 700-710

```javascript
const prioridadRecibos = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];

for (const prioridad of prioridadRecibos) {
  for (const recibo of recibosArray) {
    if (recibo.activo === 1 && recibo.tipo_recibo === prioridad) {
      reciboPrincipalEncontrado = recibo;
      break;
    }
  }
}
```

✗ **La prioridad de recibos es lógica de negocio que cambia**
✗ Está duplicada en LÍNEA 700-710 AND LÍNEA 1480-1490

---

### 3.2 **Transformación de datos que debe estar en DTOs - CRÍTICO**

#### Normalización de consecutivos - LÍNEA 2895

```javascript
function normalizeConsecutivos(consecutivos) {
  if (!consecutivos) return [];
  if (Array.isArray(consecutivos)) return consecutivos;

  if (typeof consecutivos === 'object') {
    try {
      return Object.values(consecutivos).filter(Boolean);
    } catch (e) {
      return [];
    }
  }

  return [];
}
```

✗ **Esto es transformación de DTO, no debería estar en la UI**
✗ El backend está devolviendo datos en formato inconsistente (a veces array, a veces objeto)
✗ La UI tiene que hacer "trabajo sucio" de normalización

**Debería estar en el backend:**
```php
// App/Application/DTOs/ConsecutivoRecepDTO.php
class ConsecutivoRecepDTO {
  public function normalize($data) {
    if (is_array($data)) return $data;
    return array_values((array)$data);
  }
}
```

---

#### Conversión de fechas - LÍNEA 2865

```javascript
function toDateObject(value) {
  if (!value) return null;
  try {
    const raw = (value && typeof value === 'object' && value.date)
      ? value.date
      : value;
    const date = raw instanceof Date ? raw : new Date(raw);
    if (isNaN(date.getTime())) return null;
    return date;
  } catch (e) {
    return null;
  }
}
```

✗ Múltiples formatos de fechas llegando del backend
✗ La UI tiene que "arreglarlo"
✗ Debería haber un DTO consistente

**Campos de fecha que vienen en diferentes formatos:**
- `fecha_creacion` (LÍNEA 510)
- `fecha_de_creacion_de_orden` (LÍNEA 510)
- `created_at` (LÍNEA 510)
- `fecha_estimada_entrega` (LÍNEA 512)
- `fecha_estimada_de_entrega` (LÍNEA 930)

---

#### Determinación de "Bodega vs Confecciona" - LÍNEA 1340

```javascript
let badgeHtml = '';
if (prenda.de_bodega) {
  badgeHtml = '<span class="bodega-badge">SE SACA DE BODEGA</span>';
} else {
  badgeHtml = '<span class="confeciona-badge">SE CONFECCIONA</span>';
}
```

✗ **Lógica "una prenda es de bodega si tiene flag de_bodega" está en la UI**
✗ Debería haber un **Value Object `PrendaSource`** que encapsule esta lógica
✗ Si luego necesitan "prenda de bodega + confeccionada" u otro estado, hay que cambiar la UI

```php
// Backend debería devolver un DTO con:
class PrendaDTO {
  public string $source;  // 'bodega' | 'confecciona' | 'hibrida'
  
  public function getDisplayLabel(): string {
    return match($this->source) {
      'bodega' => 'SE SACA DE BODEGA',
      'confecciona' => 'SE CONFECCIONA',
      default => 'MIXTO'
    };
  }
}
```

---

### 3.3 **Validación que debe estar en Value Objects/Entities**

#### Validación de área - LÍNEA 2790

```javascript
if (!area) {
  showError('Por favor selecciona un área/proceso');
  return;
}
```

✗ Validación trivial pero no encapsulada
✗ Debería haber una clase de negocio

#### Validación de encargado - LÍNEA 2800-2810

```javascript
const needsEncargado = ['corte', 'costura', 'control de calidad'];
const areaRequiresEncargado = needsEncargado.some(reqArea => areaLower.includes(reqArea));

if (areaRequiresEncargado && !encargado.trim()) {
  showError('Por favor ingresa el nombre del encargado');
  return;
}
```

✗ Lógica de validación está mezclada con código de UI
✗ Debería estar en una Entidad/Value Object:

```php
class Proceso {
  public function validate() {
    if ($this->requiresManager() && !$this->manager) {
      throw new InvalidProceso('Area requiere encargado');
    }
  }
}
```

---

## 4. 🔴 PROBLEMAS ESPECÍFICOS Y DUPLICACIÓN

### 4.1 **Formateo de fechas - 3 implementaciones inconsistentes**

| Función | Líneas | Problema |
|---------|--------|----------|
| `formatDate()` | 2930-2950 | Global, chequea `window.formatDate` |
| `formatDateTime()` | 2955-2985 | Existe pero poco usada |
| Manual en updateOrderInfo | 540-570 | Duplicado |
| Manual en updateOrderInfo | 575-615 | Duplicado |
| Manual en updateEstimatedDeliveryDate | 903-930 | Duplicado |

**RESULTADO:** formatDate() está llamada en LÍNEA 512, 695, 929, 1325, pero también hay formateo manual.

---

### 4.2 **Cálculo de duración - 2 funciones incompatibles**

**`formatBadgeDuration()` (LÍNEA 1650):**
```javascript
const formatBadgeDuration = function(diffMs) {
  const ms = Math.max(0, Number(diffMs) || 0);
  const minutes = Math.floor(ms / 60000);
  const hours = Math.floor(ms / 3600000);
  const days = Math.floor(ms / 86400000);
  
  if (days >= 1) return `${days} ${days === 1 ? 'Día' : 'Días'}`;
  else if (hours >= 1) return `${hours}h`;
  else if (minutes >= 1) return `${minutes}min`;
  else return '< 1min';
};
```

**`formatDurationHuman()` (LÍNEA 3000):**
```javascript
function formatDurationHuman(diffMs) {
  const totalSeconds = Math.floor((diffMs || 0) / 1000);
  const days = Math.floor(totalSeconds / 86400);
  // ...
  const parts = [];
  if (days > 0) parts.push(`${days} ${days === 1 ? 'día' : 'días'}`);
  // ...
  return parts.join(' ');
}
```

✗ `formatBadgeDuration` retorna "Día", `formatDurationHuman` retorna "día"
✗ Diferentes lógicas de formateo
✗ Sistema complicado de cuál usar

---

### 4.3 **Actualización de modal - Código repetido LÍNEA 505-630**

La función `updateOrderInfo()` actualiza los mismos campos **DOS VECES**:

```javascript
// Primera vez - elementos del modal
document.getElementById('trackingOrderNumber').textContent = orderData.numero_pedido || '-';
document.getElementById('trackingOrderClient').textContent = orderData.cliente || '-';

// Segunda vez - elementos del selector
if (selectorOrderNumber) {
  selectorOrderNumber.textContent = orderData.numero_pedido || '-';
}
if (selectorOrderClient) {
  selectorOrderClient.textContent = orderData.cliente || '-';
}
```

✗ Código casi idéntico (~60 líneas) se repite
✗ Si hay que cambiar la lógica, hay que cambiar en múltiples lugares

---

## 5. ⚠️ LÓGICA DE NEGOCIO EN FRONTEND

### 5.1 **Cálculo de días hábiles - CRÍTICO**

**UBICACIONES:**
- LÍNEA 3020-3080: `calcularDiasHabilesSync()` - implementación completa
- LÍNEA 130-160: `actualizarContadoresDinamicos()` - lo usa
- LÍNEA 1690-1700: `createAreaCard()` - lo usa múltiples veces
- LÍNEA 1425: `renderSeguimientosPorArea()` - lo usa

**PROBLEMA:**
```javascript
// LÍNEA 3050
while (actual <= fin) {
  if (actual.getDay() !== 0 && actual.getDay() !== 6) {
    const fechaStr = actual.toISOString().slice(0, 10);
    if (!festivos.includes(fechaStr)) {
      diasHabiles++;
    }
  }
  actual.setDate(actual.getDate() + 1);
}
```

✗ Toda la lógica de cálculo de negocio está aquí
✗ Si cambia la definición de "día hábil", hay que cambiar código frontend
✗ Los festivos se cargan desde `festivosCache` que no existe en este archivo (debe venir de otra parte)
✗ Duplicado en código backend seguramente

---

### 5.2 **Manejo de festivos - Fallback a hardcoded**

**LÍNEA 3055-3065:**
```javascript
let festivos = festivosCache.get(anio);

if (!festivos) {
  // Fallback: festivos fijos colombianos
  festivos = [
    `${anio}-01-01`, // Año Nuevo
    `${anio}-05-01`, // Día del Trabajo
    // ...
  ];
}
```

✗ Los festivos están hardcoded en el frontend
✗ Si agregan un festivo, hay que cambiar el código
✗ **Esto DEBE estar en el backend**, no en el cliente

---

### 5.3 **Transformación de prendas - LÍNEA 1365+**

```javascript
const nombrePrenda = prenda.nombre_prenda || `Prenda ${index + 1}`;
const cantidad = prenda.cantidad || 0;
const totalProcesos = prenda.total_procesos || 0;

// Extraer tipos de recibo que son procesos
let procesosInfo = '-';
if (prenda.tipos_recibo_procesos && prenda.tipos_recibo_procesos.length > 0) {
  procesosInfo = prenda.tipos_recibo_procesos.map(p => {
    const nombre = p.nombre || 'Proceso';
    const estado = (p.estado || 'PENDIENTE').replace(/_/g, ' ');
    return `${nombre} (${estado})`;
  }).join(', ');
}
```

✗ Transformación de datos que debería estar en un DTO
✗ Fallbacks y transformaciones scatter en la UI
✗ Si la estructura de datos cambia, el frontend se rompe

---

### 5.4 **Normalización de estado - LÍNEA 512, 523, 1435, etc.**

```javascript
// LÍNEA 512
document.getElementById('trackingOrderStatus').textContent = 
  (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();

// LÍNEA 1435
const estadoDisplay = isInsumos 
  ? (data.estado || 'Pendiente') 
  : (hasFechaCompletado ? 'Completado' : 'Pendiente');
```

✗ Transformación de estado (replace, toUpperCase) en la UI
✗ Se repite en múltiples lugares
✗ Si cambian la regla (ej: normalizar a PENDING en lugar de Pendiente), hay cambios en la UI

---

### 5.5 **Determinación de prioridad de recibos - LÍNEA 700-710 Y 1480-1490**

```javascript
const prioridadRecibos = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];

for (const prioridad of prioridadRecibos) {
  for (const recibo of recibosArray) {
    if (recibo.activo === 1 && recibo.tipo_recibo === prioridad) {
      reciboPrincipalEncontrado = recibo;
      break;
    }
  }
}
```

✗ **La lista de prioridad de recibos es lógica de negocio**
✗ Está hardcoded en el frontend
✗ Si cambian la prioridad, hay que updatear el código frontend
✗ **SE DUPLICA** en dos lugares diferentes

---

## 6. 🎯 RESUMEN DE ISSUES CRÍTICOS

| Issue | Severidad | Lineas | Impacto |
|-------|-----------|--------|--------|
| `formatDate()` duplicada | 🔴 ALTA | 540-950 | 3 implementaciones del mismo código |
| `calcularDiasHabilesSync()` en frontend | 🔴 CRÍTICA | 3020-3080 | Lógica de negocio en UI |
| `updateOrderInfo()` hace 7 cosas | 🔴 ALTA | 505-800 | SRP violado, 300+ líneas |
| `createAreaCard()` hace 5 cosas | 🔴 ALTA | 1620-1880 | SRP violado, 260+ líneas |
| `showPrendaTracking()` hace 6 cosas | 🔴 ALTA | 1370-1540 | SRP violado, 170+ líneas |
| Búsqueda de recibo triplicada | 🔴 ALTA | 700-720, 1480-1510, 1380-1400 | 3 copias del mismo código |
| Estado global sin control | 🔴 ALTA | Líneas 26, 30, 1370, etc. | Multiple `window.` variables |
| Fetch directo sin abstraer | 🔴 MEDIA | 273, 465, 835, 2750 | No hay APIClient |
| Iconos hardcoded | 🔴 MEDIA | 2005-2050 | OCP violado |
| Áreas hardcoded en UI | 🔴 MEDIA | 1605-1615 | OCP violado |
| Formateo de duración inconsistente | 🟡 MEDIA | 1650, 3000 | Dos funciones diferentes |
| Festivos hardcoded | 🔴 ALTA | 3055-3065 | Debe estar en backend |

---

## 7. 📋 RECOMENDACIONES DE REFACTORING (PRIORIDAD)

### FASE 1 (CRÍTICO - ESTA SEMANA):
1. ✅ Mover `calcularDiasHabilesSync()` al backend
2. ✅ Centralizar todas las transformaciones de fechas en `formatDate()`
3. ✅ Crear APIClient para abstraer endpoints

### FASE 2 (IMPORTANTE - PRÓXIMA SEMANA):
4. ✅ Refactorizar `updateOrderInfo()` en 3 funciones
5. ✅ Extraer búsqueda de recibo a función reutilizable
6. ✅ Crear StateManager para variables globales

### FASE 3 (MEJORA - SIGUIENTE SEMANA):
7. ✅ Refactorizar `createAreaCard()` en 4 funciones
8. ✅ Mover configuración de áreas al backend (DTOs)
9. ✅ Centralizar estilos (no inline)

### FASE 4 (OPTIMIZACIÓN):
10. ✅ Crear Value Objects para datos de negocio
11. ✅ Implementar DTO consistentes desde backend
12. ✅ Agregar validación con schema (ej: Zod, Yup)

---

**Fin del análisis**
