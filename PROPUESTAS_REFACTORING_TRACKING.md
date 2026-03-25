# PROPUESTAS DE REFACTORING - tracking-modal-handler.js
**Fecha:** 24/03/2026 | **Prioridad:** CRÍTICA

---

## 1. 🔧 REFACTORING FASE 1: CRÍTICO

### 1.1 **Centralizar formateo de fechas**

#### ❌ PROBLEMA ACTUAL (3 implementaciones diferentes)

```javascript
// VERSIÓN 1 - updateOrderInfo() LÍNEA 540-570
if (typeof fechaInicio === 'string') {
  try {
    const date = new Date(fechaInicio);
    if (!isNaN(date.getTime())) {
      fechaFormateada = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } else {
      fechaFormateada = fechaInicio;
    }
  } catch (e) {
    fechaFormateada = fechaInicio;
  }
} else if (fechaInicio instanceof Date) {
  fechaFormateada = fechaInicio.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
} else if (fechaInicio && fechaInicio.date) {
  fechaFormateada = new Date(fechaInicio.date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}
selectorOrderStartDate.textContent = fechaFormateada || '-';

// VERSIÓN 2 - updateEstimatedDeliveryDate() LÍNEA 903-930
if (typeof fechaEstimada === 'string') {
  try {
    const date = new Date(fechaEstimada);
    if (!isNaN(date.getTime())) {
      fechaFormateada = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } else {
      fechaFormateada = fechaEstimada;
    }
  } catch (e) {
    fechaFormateada = fechaEstimada;
  }
} else if (fechaEstimada instanceof Date) {
  fechaFormateada = fechaEstimada.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
} else if (fechaEstimada && fechaEstimada.date) {
  fechaFormateada = new Date(fechaEstimada.date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}
selectorOrderEstimatedDate.textContent = fechaFormateada;

// VERSIÓN 3 - createAreaCard() LÍNEA 1665
const fechaLlegada = formatDate(data.fecha_inicio) || '---';
const fechaFin = formatDate(fechaFinRaw) || (data.esta_activo ? '---' : '---');
```

✅ **CÓDIGO MEJORADO - UNA FUNCIÓN CENTRALIZADA**

```javascript
/**
 * Centralizado DateFormatter
 */
class DateFormatter {
  /**
   * Formatea cualquier tipo de fecha a string DD/MM/YYYY
   * @param {string|Date|Object|null} value - La fecha a formatear
   * @param {string} fallback - Valor por defecto ('---', '-', null)
   * @returns {string|null}
   */
  static formatDate(value, fallback = null) {
    try {
      // Extraer fecha si es objeto con propiedad 'date'
      const rawDate = (value && typeof value === 'object' && value.date) ? value.date : value;
      
      if (!rawDate) return fallback;
      
      // Convertir a Date si no lo es
      const date = rawDate instanceof Date ? rawDate : new Date(rawDate);
      
      // Validar fecha
      if (isNaN(date.getTime())) return fallback;
      
      // Formatear
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch (error) {
      console.warn('[DateFormatter] Error formatting date:', value, error);
      return fallback;
    }
  }

  /**
   * Formatea con hora
   */
  static formatDateTime(value, fallback = null) {
    try {
      const rawDate = (value && typeof value === 'object' && value.date) ? value.date : value;
      if (!rawDate) return fallback;
      
      const date = rawDate instanceof Date ? rawDate : new Date(rawDate);
      if (isNaN(date.getTime())) return fallback;
      
      return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      });
    } catch (error) {
      console.warn('[DateFormatter] Error formatting datetime:', value, error);
      return fallback;
    }
  }

  /**
   * Convierte a objeto Date
   */
  static toDate(value) {
    try {
      const rawDate = (value && typeof value === 'object' && value.date) ? value.date : value;
      if (!rawDate) return null;
      
      const date = rawDate instanceof Date ? rawDate : new Date(rawDate);
      return isNaN(date.getTime()) ? null : date;
    } catch (error) {
      console.warn('[DateFormatter] Error converting to date:', value, error);
      return null;
    }
  }
}

// REEMPLAZO EN updateOrderInfo() - LÍNEA 540-615
selectorOrderStartDate.textContent = DateFormatter.formatDate(fechaInicio, '-');
selectorOrderEstimatedDate.textContent = DateFormatter.formatDate(fechaEstimada, '-');

// REEMPLAZO EN updateEstimatedDeliveryDate() - LÍNEA 930
fechaEstimadaElement.textContent = DateFormatter.formatDate(fechaEstimada, 'No definida');

// REEMPLAZO EN createAreaCard() - LÍNEA 1665
const fechaLlegada = DateFormatter.formatDate(data.fecha_inicio, '---');
const fechaFin = DateFormatter.formatDate(fechaFinRaw, data.esta_activo ? '---' : '---');
```

---

### 1.2 **Mover `calcularDiasHabilesSync()` al backend**

#### ❌ PROBLEMA: Lógica de negocio en frontend

```javascript
// LÍNEA 3020-3080 - Duplicado en frontend
function calcularDiasHabilesSync(fechaInicio, fechaFin) {
  // ... 60 líneas de lógica de negocio
  while (actual <= fin) {
    if (actual.getDay() !== 0 && actual.getDay() !== 6) {
      const fechaStr = actual.toISOString().slice(0, 10);
      if (!festivos.includes(fechaStr)) {
        diasHabiles++;
      }
    }
    actual.setDate(actual.getDate() + 1);
  }
}

// Se usa en MÚLTIPLES LUGARES
// LÍNEA 145 - actualizarContadoresDinamicos
const diasHabiles = calcularDiasHabilesSync(ini, new Date());

// LÍNEA 1690 - createAreaCard
const diasHabiles = calcularDiasHabilesSync(ini, fin);

// LÍNEA 1425 - renderSeguimientosPorArea
const diasHabiles = calcularDiasHabilesSync(fechaCreacionDate, reciboActDate);
```

✅ **SOLUCIÓN: Crear un servicio de backend**

```php
// App/Application/Services/CalculadorDiasService.php
class CalculadorDiasService {
  /**
   * Calcula días hábiles entre dos fechas
   * @param DateTime $fechaInicio
   * @param DateTime $fechaFin
   * @return int
   */
  public function calcularDiasHabiles(
    DateTime $fechaInicio,
    DateTime $fechaFin
  ): int {
    // Lógica centralizada
    $diasHabiles = 0;
    $actual = clone $fechaInicio;
    
    while ($actual <= $fechaFin) {
      // Skip fin de semana
      if ($actual->format('w') !== '0' && $actual->format('w') !== '6') {
        // Skip festivos
        if (!$this->isFestivo($actual)) {
          $diasHabiles++;
        }
      }
      $actual->modify('+1 day');
    }
    
    return max(0, $diasHabiles - 1); // Excluir día inicial
  }

  private function isFestivo(DateTime $date): bool {
    // Usar festivos del backend
    $festivos = $this->getFestivosDelAño($date->format('Y'));
    return in_array($date->format('Y-m-d'), $festivos);
  }
}
```

**Frontend cambio a:**

```javascript
// NO más calcularDiasHabilesSync local
// El backend devuelve los datos calculados en la respuesta

// En updateOrderInfo():
// El backend devuelve ya calculado
const totalDias = orderData.total_dias_habiles;

// En createAreaCard():
// El backend devuelve ya calculado
const diasHabiles = data.dias_habiles_calculados;

// Para dinámico, llamar a endpoint:
class DurationService {
  static async getDiasHabiles(fechaInicio, fechaFin) {
    const response = await fetch('/api/calcular-dias-habiles', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
      })
    });
    const data = await response.json();
    return data.dias_habiles;
  }
}
```

---

### 1.3 **Crear APIClient para abstraer endpoints**

#### ❌ PROBLEMA: Fetch hardcoded en múltiples lugares

```javascript
// LÍNEA 273 - loadOrderBasicData
const response = await fetch(`/registros/${orderId}/recibos-datos`);

// LÍNEA 465 - loadPrendasWithTracking
const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);

// LÍNEA 835 - saveDiaEntregaSelection
const response = await fetch(`/api/pedidos/${ordenId}/calcular-fecha-entrega`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  },
  body: JSON.stringify({
    dias_estimados: diasSeleccionados
  })
});

// LÍNEA 2750 - handleAgregarProceso
const response = await fetch('/seguimiento-proceso/guardar', {
  method: 'POST',
  headers: { ... },
  body: JSON.stringify({ ... })
});

// LÍNEA 2650 - handleActualizarProceso
const response = await fetch('/seguimiento-proceso/' + procesoId, {
  method: 'PUT',
  headers: { ... },
  body: JSON.stringify({ ... })
});

// LÍNEA 2600 - executeDeleteProcess
const response = await fetch('/seguimiento-proceso/' + procesoId, {
  method: 'DELETE',
  headers: { ... }
});

// LÍNEA 1970 - convertEncargadoToSelect
const response = await fetch(`/api/areas/${encodeURIComponent(area)}/encargados`);
```

✅ **SOLUCIÓN: Centralizar en APIClient**

```javascript
/**
 * APIClient centralizado para todas las llamadas
 */
class OrderTrackingAPI {
  static API_ROOT = '/api';
  
  static getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  static async request(method, endpoint, body = null) {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': this.getCsrfToken()
      }
    };

    if (body) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(endpoint, options);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response.json();
  }

  // ==== ORDEN ====
  static getOrderBasicData(orderId) {
    return this.request('GET', `/registros/${orderId}/recibos-datos`);
  }

  static getPrendasWithTracking(orderId) {
    return this.request('GET', `/registros/${orderId}/seguimiento-prenda`);
  }

  static calcularFechaEntrega(orderId, diasEstimados) {
    return this.request(
      'POST',
      `/api/pedidos/${orderId}/calcular-fecha-entrega`,
      { dias_estimados: diasEstimados }
    );
  }

  // ==== PROCESOS ====
  static crearProceso(payload) {
    return this.request('POST', '/seguimiento-proceso/guardar', payload);
  }

  static actualizarProceso(procesoId, payload) {
    return this.request('PUT', `/seguimiento-proceso/${procesoId}`, payload);
  }

  static eliminarProceso(procesoId) {
    return this.request('DELETE', `/seguimiento-proceso/${procesoId}`);
  }

  // ==== ÁREAS ====
  static getEncargadosPorArea(area) {
    return this.request('GET', `/api/areas/${encodeURIComponent(area)}/encargados`);
  }

  // ==== CÁLCULOS ====
  static calcularDiasHabiles(fechaInicio, fechaFin) {
    return this.request('POST', `${this.API_ROOT}/calcular-dias-habiles`, {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin
    });
  }
}

// REEMPLAZOS EN EL CÓDIGO:

// LÍNEA 273 - loadOrderBasicData
async function loadOrderBasicData(orderId) {
  try {
    const data = await OrderTrackingAPI.getOrderBasicData(orderId);
    window.currentOrderData = data.data || data;
    updateOrderInfo(data);
  } catch (error) {
    console.error('[loadOrderBasicData] Error:', error);
    throw error;
  }
}

// LÍNEA 465 - loadPrendasWithTracking
async function loadPrendasWithTracking(orderId) {
  try {
    const data = await OrderTrackingAPI.getPrendasWithTracking(orderId);
    renderPrendas(data.prendas || []);
  } catch (error) {
    console.error('[loadPrendasWithTracking] Error:', error);
    throw error;
  }
}

// LÍNEA 835 - saveDiaEntregaSelection
async function saveDiaEntregaSelection() {
  try {
    const diasSeleccionados = window.__trackingDiasSeleccionados;
    const ordenId = getOrdenId(); // Nueva función auxiliar
    
    if (!ordenId) {
      console.warn('[saveDiaEntregaSelection] No se encontró el ID de la orden');
      return;
    }

    const result = await OrderTrackingAPI.calcularFechaEntrega(ordenId, diasSeleccionados);
    
    if (result.fecha_estimada) {
      updateEstimatedDeliveryDateUI(result.fecha_estimada);
    }

    showSuccess(`Fecha de entrega calculada: ${diasSeleccionados} días`);
  } catch (error) {
    console.error('[saveDiaEntregaSelection] Error:', error);
    showError('Error al guardar el día de entrega');
  }
}

// LÍNEA 2750 - handleAgregarProceso (simplificado)
async function handleAgregarProceso() {
  try {
    const area = document.getElementById('procesoArea').value;
    const encargado = document.getElementById('procesoEncargado').value.toUpperCase();

    if (!area) {
      showError('Por favor selecciona un área/proceso');
      return;
    }

    const payload = {
      pedido_produccion_id: window.currentOrderData.numero_pedido,
      prenda_id: window.currentPrendaData.id,
      area: area,
      encargado: encargado,
      estado: 'Pendiente'
    };

    const result = await OrderTrackingAPI.crearProceso(payload);
    
    // Actualizar UI...
    showSuccess('Proceso creado correctamente');
  } catch (error) {
    console.error('[handleAgregarProceso] Error:', error);
    showError('Error al crear proceso: ' + error.message);
  }
}
```

---

## 2. 🔧 REFACTORING FASE 2: IMPORTANTE

### 2.1 **Refactorizar `updateOrderInfo()` - 300+ líneas**

#### ❌ PROBLEMA ACTUAL

```javascript
function updateOrderInfo(orderData) {
  // MEZCLA DE RESPONSABILIDADES:
  // 1. Validar datos
  // 2. Formatear fechas (3 veces)
  // 3. Buscar recibo principal (30 líneas)
  // 4. Actualizar modal
  // 5. Actualizar selector
  // 6. Aplicar estilos
  // ... 300+ líneas total
}
```

✅ **REFACTORING: Separar responsabilidades**

```javascript
/**
 * Actualizar información del pedido en el modal
 */
async function updateOrderInfo(orderData) {
  try {
    updateModalOrderInfo(orderData);
    updateSelectorOrderInfo(orderData);
  } catch (error) {
    console.error('[updateOrderInfo] Error:', error);
    throw error;
  }
}

/**
 * Actualizar solo la información del modal
 */
function updateModalOrderInfo(orderData) {
  const elements = {
    numero: 'trackingOrderNumber',
    cliente: 'trackingOrderClient',
    estado: 'trackingOrderStatus',
    fecha_estimada: 'trackingEstimatedDate',
    total_dias: 'trackingTotalDays'
  };

  document.getElementById(elements.numero).textContent = orderData.numero_pedido || '-';
  document.getElementById(elements.cliente).textContent = orderData.cliente || '-';
  document.getElementById(elements.estado).textContent = 
    normalizeStatus(orderData.estado);
  document.getElementById(elements.fecha_estimada).textContent = 
    DateFormatter.formatDate(orderData.fecha_estimada_entrega, '-');

  const totalDiasEl = document.getElementById(elements.total_dias);
  if (totalDiasEl) totalDiasEl.textContent = orderData.total_dias || '0';

  updateReceiptNumberDisplay(orderData);
}

/**
 * Actualizar solo la información del selector de prendas
 */
function updateSelectorOrderInfo(orderData) {
  const elements = {
    numero: 'selectorOrderNumber',
    cliente: 'selectorOrderClient',
    estado: 'selectorOrderStatus',
    inicio: 'selectorOrderStartDate',
    estimada: 'selectorOrderEstimatedDate'
  };

  Object.entries(elements).forEach(([key, id]) => {
    const el = document.getElementById(id);
    if (!el) return;

    switch (key) {
      case 'numero':
        el.textContent = orderData.numero_pedido || '-';
        break;
      case 'cliente':
        el.textContent = orderData.cliente || '-';
        break;
      case 'estado':
        el.textContent = normalizeStatus(orderData.estado);
        break;
      case 'inicio':
        el.textContent = DateFormatter.formatDate(
          orderData.fecha_creacion || orderData.created_at,
          '-'
        );
        break;
      case 'estimada':
        const fecha = DateFormatter.formatDate(orderData.fecha_estimada_de_entrega, '-');
        el.textContent = fecha;
        applyEstimatedDateStyles(el, fecha);
        break;
    }
  });
}

/**
 * Función auxiliar: Normalizar estado
 */
function normalizeStatus(status) {
  if (!status) return '-';
  return status.replace(/_/g, ' ').toUpperCase();
}

/**
 * Función auxiliar: Actualizar número de recibo
 */
function updateReceiptNumberDisplay(orderData) {
  const element = document.getElementById('trackingOrderRecibo');
  if (!element) return;

  const numeroRecibo = findMainReceiptNumber(orderData);
  element.textContent = numeroRecibo || 'Sin recibo';
}

/**
 * Extraer número de recibo principal (lógica centralizada)
 */
function findMainReceiptNumber(orderData) {
  if (!orderData.prendas || orderData.prendas.length === 0) {
    return '-';
  }

  const RECEIPT_PRIORITY = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];

  for (const prenda of orderData.prendas) {
    const recibos = normalizeConsecutivos(prenda.consecutivos);
    for (const prioridad of RECEIPT_PRIORITY) {
      const recibo = recibos.find(r =>
        String(r.tipo_recibo).toUpperCase() === prioridad && r.activo === 1
      );
      if (recibo) {
        return `${recibo.tipo_recibo} #${recibo.consecutivo_actual}`;
      }
    }
  }

  return '-';
}

/**
 * Aplicar estilos a la fecha estimada
 */
function applyEstimatedDateStyles(element, fecha) {
  if (fecha && fecha !== '-') {
    element.style.color = '#1f2937';
    element.style.fontWeight = '600';
  } else {
    element.style.color = '#9ca3af';
    element.style.fontWeight = '400';
  }
}
```

---

### 2.2 **Extraer búsqueda de recibo a función reutilizable**

#### ❌ PROBLEMA: Mismo código en 3 lugares

```javascript
// LÍNEA 700-720 (updateOrderInfo)
// LÍNEA 1480-1510 (showPrendaTracking)
// LÍNEA maybe more places

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

✅ **CENTRALIZAR**

```javascript
/**
 * Utilidad para búsqueda de recibos
 */
class ReceiptFinder {
  static PRIORITY_ORDER = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];

  /**
   * Encuentra el recibo principal según prioridades
   */
  static findMainReceipt(recibos) {
    const normalized = normalizeConsecutivos(recibos);
    
    for (const prioridad of this.PRIORITY_ORDER) {
      const recibo = normalized.find(r =>
        String(r.tipo_recibo).toUpperCase() === prioridad &&
        (r.activo === 1 || r.activo === true)
      );
      if (recibo) return recibo;
    }

    return normalized[0] || null;
  }

  /**
   * Formatea el recibo para mostrar
   */
  static formatReceipt(recibo) {
    if (!recibo) return 'Sin recibo';
    return `${recibo.tipo_recibo} #${recibo.consecutivo_actual}`;
  }

  /**
   * Obtiene el recibo activo, sin importar prioridad
   */
  static findAnyActiveReceipt(recibos) {
    const normalized = normalizeConsecutivos(recibos);
    return normalized.find(r => r.activo === 1 || r.activo === true) || normalized[0] || null;
  }
}

// USO EN EL CÓDIGO:
const mainReceipt = ReceiptFinder.findMainReceipt(prenda.consecutivos);
const displayText = ReceiptFinder.formatReceipt(mainReceipt);
```

---

### 2.3 **Crear StateManager para variables globales**

#### ❌ PROBLEMA: Múltiples variables globales

```javascript
window.currentOrderData = data;
window.currentPrendaData = prenda;
window.prendasData = prendas;
window.__trackingDiasSeleccionados = n;
window.currentConsecutivoCosturaData = {...};
window.editingProcessId = procesoId;
window.processToDelete = { id, name };
```

✅ **CENTRALIZAR EN STATEMANAGER**

```javascript
/**
 * Gestor centralizado de estado
 */
class TrackingStateManager {
  static #state = {
    order: null,
    prenda: null,
    prendas: [],
    diasSeleccionados: null,
    consecutivoCostura: null,
    editingProcessId: null,
    processToDelete: null
  };

  static #listeners = new Map();

  /**
   * Obtener estado completo
   */
  static getState() {
    return { ...this.#state };
  }

  /**
   * Obtener valor específico
   */
  static get(key) {
    return this.#state[key];
  }

  /**
   * Establecer valor y notificar listeners
   */
  static set(key, value) {
    const oldValue = this.#state[key];
    this.#state[key] = value;

    // Notificar listeners
    if (this.#listeners.has(key)) {
      this.#listeners.get(key).forEach(callback => {
        callback(value, oldValue);
      });
    }

    console.log(`[TrackingState] ${key} actualizado:`, value);
  }

  /**
   * Escuchar cambios en un campo
   */
  static onChange(key, callback) {
    if (!this.#listeners.has(key)) {
      this.#listeners.set(key, []);
    }
    this.#listeners.get(key).push(callback);

    // Retornar función para desuscribirse
    return () => {
      const listeners = this.#listeners.get(key);
      const index = listeners.indexOf(callback);
      if (index > -1) listeners.splice(index, 1);
    };
  }

  /**
   * Limpiar estado
   */
  static reset() {
    this.#state = {
      order: null,
      prenda: null,
      prendas: [],
      diasSeleccionados: null,
      consecutivoCostura: null,
      editingProcessId: null,
      processToDelete: null
    };
  }

  // === SHORTCUTS ===
  static setOrder(data) { this.set('order', data); }
  static getOrder() { return this.get('order'); }

  static setPrenda(data) { this.set('prenda', data); }
  static getPrenda() { return this.get('prenda'); }

  static setPrendas(data) { this.set('prendas', data); }
  static getPrendas() { return this.get('prendas'); }

  static setDiasSeleccionados(dias) { this.set('diasSeleccionados', dias); }
  static getDiasSeleccionados() { return this.get('diasSeleccionados'); }
}

// REEMPLAZOS EN EL CÓDIGO:

// Antes:
window.currentOrderData = data;
window.currentPrendaData = prenda;
window.prendasData = prendas;

// Después:
TrackingStateManager.setOrder(data);
TrackingStateManager.setPrenda(prenda);
TrackingStateManager.setPrendas(prendas);

// Acceso desde cualquier lugar:
const prenda = TrackingStateManager.getPrenda();
const orden = TrackingStateManager.getOrder();

// Escuchar cambios:
TrackingStateManager.onChange('order', (newOrder, oldOrder) => {
  console.log('Orden cambió:', newOrder);
  updateUI();
});
```

---

## 3. 📊 Matriz de Prioridades

| Issue | Actual | Refactored | Líneas Ahorradas | Complejidad | Impacto |
|-------|--------|-----------|------------------|-------------|---------|
| formatDate duplicada | 3 impls | 1 centralizado | ~120 | Bajo | Alto |
| calcularDiasHabiles en frontend | 3020+ | Backend | ~100 | ALTO | CRÍTICO |
| APIClient | 7+ fetch calls | 1 APIClient | ~40 | Bajo | Alto |
| updateOrderInfo() | 300 líneas | 4 funciones de ~75 | N/A | Medio | Alto |
| Búsqueda recibo triplicada | 3 copias | 1 class | ~60 | Bajo | Medio |
| StateManager | 8 globals | 1 manager | N/A | Bajo | Medio |
| Formateo duración duplicado | 2 funciones | 1 función | ~20 | Bajo | Bajo |
| createAreaCard() | 260 líneas | 4 funciones | N/A | Alto | Medio |

---

**Fin de propuestas**
