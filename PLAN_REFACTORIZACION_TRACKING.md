# PLAN DE REFACTORIZACIÓN: Arquitectura Modular sin Duplicación

## 🎯 Objetivo
Refactorizar `tracking-modal-handler.js` de **800+ líneas con 8+ responsabilidades** → **módulos especializados de máximo 200 líneas cada uno**.

---

## 📐 Nueva Arquitectura (Capas Limpias)

```
┌─────────────────────────────────────────────┐
│   tracking-modal-handler.js (180 líneas)    │ ← Orquestador principal
│   SOLO: coordinación de eventos             │
└──────────────┬──────────────────────────────┘
               │
    ┌──────────┼──────────┬────────────┬────────────┐
    │          │          │            │            │
    ▼          ▼          ▼            ▼            ▼
┌────────┐┌─────────┐┌────────┐┌──────────┐┌──────────┐
│ State  ││API      ││Renderer││DateFmt   ││DOMQuery  │
│Manager ││Service  ││Service ││Utility   ││Utility   │
└────────┘└─────────┘└────────┘└──────────┘└──────────┘
```

---

## 📦 Módulos a Crear

### 1. **OrderState.js** (Estado Centralizado)
**Responsabilidad:** Gestionar estado sin variables globales  
**Reemplaza:** `window.currentOrderData`, `window.currentPrendaData`, `window.__trackingDiasSeleccionados`

```javascript
class OrderState {
  constructor() {
    this.order = null;
    this.prendas = [];
    this.selectedDays = null;
  }
  
  setOrder(data) { this.order = data; }
  getOrder() { return this.order; }
  
  setPrendas(data) { this.prendas = data; }
  getPrendas() { return this.prendas; }
  
  setSelectedDays(days) { this.selectedDays = days; }
  getSelectedDays() { return this.selectedDays; }
  
  clear() {
    this.order = null;
    this.prendas = [];
    this.selectedDays = null;
  }
}

export const orderState = new OrderState();
```

**Ventajas:**
-  Testeable
-  Control centralizado
-  Sin efectos secundarios ocultos
-  Fácil debuggear

---

### 2. **DateFormatter.js** (Elimina 100% duplicación)
**Responsabilidad:** Formatear fechas en UN solo lugar  
**Reemplaza:** Los 3+ bloques duplicados de formateo

```javascript
class DateFormatter {
  static readonly FORMAT_OPTIONS = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  };
  
  /**
   * Convierte cualquier tipo de fecha a string formateado
   * Sin fallbacks, sin try-catch. Si falla, lanza error para debuggear.
   */
  static format(dateInput, format = 'es-ES') {
    if (!dateInput) return '-';
    
    let date = this.#toDateObject(dateInput);
    if (!date) return '-';
    
    return date.toLocaleDateString(format, this.FORMAT_OPTIONS);
  }
  
  /**
   * Convierte diversos tipos de entrada a Date
   * @throws Error si no puede convertir (NO FALLBACK)
   */
  static #toDateObject(input) {
    // Si ya es Date
    if (input instanceof Date) return input;
    
    // Si es string ISO
    if (typeof input === 'string') {
      const date = new Date(input);
      if (!isNaN(date.getTime())) return date;
      return null;
    }
    
    // Si es objeto Laravel/Carbon con propiedad .date
    if (input?.date) {
      const date = new Date(input.date);
      if (!isNaN(date.getTime())) return date;
      return null;
    }
    
    return null;
  }
  
  /**
   * Obtener fecha correcta del objeto orden
   * Elimina fallbacks de "cual campo usar"
   */
  static getOrderStartDate(orderData) {
    return orderData.fecha_creacion 
      || orderData.created_at 
      || orderData.created_at 
      || null;
  }
  
  static getOrderEstimatedDate(orderData) {
    return orderData.fecha_estimada_de_entrega 
      || orderData.fecha_estimada_entrega 
      || null;
  }
}

export default DateFormatter;
```

**Ventajas:**
-  Eliminadas 3+ líneas duplicadas de conversión
-  Un solo lugar donde debuggear formateo
-  Reutilizable en otras vistas
-  Fácil cambiar formato a futuro (ej: i18n)

---

### 3. **OrderApiService.js** (Unifica todas las llamadas API)
**Responsabilidad:** Comunicación con backend  
**Reemplaza:** `loadOrderBasicData()`, `loadPrendasWithTracking()`, `convertEncargadoToSelect()`, `saveDiaEntregaSelection()`

```javascript
class OrderApiService {
  static async loadOrderData(orderId) {
    const response = await fetch(`/registros/${orderId}/recibos-datos`);
    if (!response.ok) throw new Error('Error cargando pedido');
    
    const result = await response.json();
    return result.data || result;
  }
  
  static async loadPrendasWithTracking(orderId) {
    const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);
    if (!response.ok) throw new Error('Error cargando prendas');
    
    const data = await response.json();
    return data.prendas || [];
  }
  
  static async loadEncargados(area) {
    const response = await fetch(
      `/api/areas/${encodeURIComponent(area)}/encargados`
    );
    const data = await response.json();
    
    if (!data.success || !data.encargados?.length) {
      throw new Error(`No hay encargados para: ${area}`);
    }
    
    return data.encargados;
  }
  
  static async calculateDeliveryDate(orderId, estimatedDays) {
    const response = await fetch(
      `/api/pedidos/${orderId}/calcular-fecha-entrega`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.#getCsrfToken()
        },
        body: JSON.stringify({ dias_estimados: estimatedDays })
      }
    );
    
    if (!response.ok) throw new Error('Error calculando fecha');
    return response.json();
  }
  
  static #getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content') || '';
  }
}

export default OrderApiService;
```

**Ventajas:**
-  Todas las APIs en UN lugar
-  Manejo centralizado de errores
-  Sin fallbacks en la API
-  Fácil agregar caché o retry logic

---

### 4. **DOMRenderer.js** (Separar presentación de lógica)
**Responsabilidad:** Renderizar HTML sin lógica de negocio  
**Reemplaza:** `createPrendasTable()`, `updateOrderInfo()`, fragmentos de HTML

```javascript
class DOMRenderer {
  /**
   * Renderiza tabla de prendas
   * @param {Array} prendas - Datos de prendas
   * @param {Element} container - Elemento donde renderizar
   */
  static renderPrendasTable(prendas, container) {
    if (!prendas?.length) {
      container.innerHTML = `
        <div class="tracking-no-prendas">
          <p>No hay prendas registradas</p>
        </div>
      `;
      return;
    }
    
    const rows = prendas
      .map((prenda, idx) => this.#createPrendaRow(prenda, idx))
      .join('');
    
    container.innerHTML = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Prenda</th>
              <th>Cantidad</th>
              <th>Procesos</th>
              <th>Área</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `;
  }
  
  static #createPrendaRow(prenda, index) {
    const nombre = prenda.nombre_prenda || `Prenda ${index + 1}`;
    const cantidad = prenda.cantidad || 0;
    const procesos = this.#formatProcesos(prenda);
    const area = prenda.ultimo_proceso_area || prenda.area || '-';
    
    return `
      <tr>
        <td>${nombre}</td>
        <td>${cantidad}</td>
        <td>${procesos}</td>
        <td>${area}</td>
        <td>${prenda.estado || '-'}</td>
        <td>
          <button onclick="viewPrendaTracking('${prenda.id}')">Ver</button>
        </td>
      </tr>
    `;
  }
  
  static #formatProcesos(prenda) {
    const tipos = prenda.tipos_recibo_procesos || [];
    if (!tipos.length) return '-';
    
    return tipos
      .map(t => `${t.nombre} (${t.estado})`)
      .join(', ');
  }
  
  /**
   * Actualizar campos del modal
   * Solo actualiza DOM, NO contiene lógica de negocio
   */
  static updateOrderDisplay(orderData) {
    const elements = {
      trackingOrderNumber: orderData.numero_pedido || '-',
      trackingOrderClient: orderData.cliente || '-',
      trackingOrderStatus: (orderData.estado || '-').replace(/_/g, ' ').toUpperCase(),
      trackingEstimatedDate: DateFormatter.format(
        DateFormatter.getOrderEstimatedDate(orderData)
      ),
      selectorOrderNumber: orderData.numero_pedido || '-',
      selectorOrderClient: orderData.cliente || '-',
      selectorOrderStatus: (orderData.estado || '-').replace(/_/g, ' ').toUpperCase(),
      selectorOrderStartDate: DateFormatter.format(
        DateFormatter.getOrderStartDate(orderData)
      ),
      selectorOrderEstimatedDate: DateFormatter.format(
        DateFormatter.getOrderEstimatedDate(orderData)
      )
    };
    
    for (const [id, text] of Object.entries(elements)) {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    }
  }
}

export default DOMRenderer;
```

**Ventajas:**
-  Lógica de presentación separada
-  Renders aislados, testeable
-  Elimina concatenación de HTML en funciones
-  Posibilidad de cambiar a Vue/React sin afectar lógica

---

### 5. **QueryUtils.js** (Helpers de DOM)
**Responsabilidad:** Encontrar elementos (mini utilidad)

```javascript
export const QueryUtils = {
  byId: (id) => document.getElementById(id),
  bySelector: (selector) => document.querySelector(selector),
  byClass: (className) => document.querySelector(`.${className}`),
  
  // Evitar null checks repetidos
  byIdSafe: (id) => document.getElementById(id) ?? null,
  
  // Para eventos delegados
  closest: (el, selector) => el.closest(selector)
};

export default QueryUtils;
```

---

## 🔧 Antes vs Después: Refactorización de updateOrderInfo()

###  ANTES (540 líneas, duplicado, múltiples responsabilidades)
```javascript
function updateOrderInfo(orderData) {
  // Líneas 577-626: Formateo de fecha inicio (1ª vez)
  if (typeof fechaInicio === 'string') {
    try {
      const date = new Date(fechaInicio);
      if (!isNaN(date.getTime())) {
        fechaFormateada = date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
      }
    } catch (e) {
      fechaFormateada = fechaInicio; // FALLBACK
    }
  } else if (fechaInicio instanceof Date) { /* repetir */ }
  else if (fechaInicio && fechaInicio.date) { /* repetir */ }
  
  // ... 200+ líneas más ...
  
  // Líneas 719-750: EXACTO MISMO formateo de fecha (2ª vez)
  // DUPLICADO TOTAL
}
```

###  DESPUÉS (15 líneas, claro, sin duplicación)
```javascript
function updateOrderInfo(orderData) {
  DOMRenderer.updateOrderDisplay(orderData);
  // FIN. Todo en una línea.
  // DOMRenderer.updateOrderDisplay internamente:
  // - Usa DateFormatter.format() para fechas
  // - No duplica lógica
  // - Solo renderiza, no calcula
}
```

---

## 🔄 Refactorización de saveDiaEntregaSelection()

###  ANTES (múltiples responsabilidades)
```javascript
async function saveDiaEntregaSelection() {
  // Línea 193: Obtener ID (fallback)
  let ordenId = null;
  if (window.currentOrderData) {
    ordenId = window.currentOrderData.id;
  }
  if (!ordenId) {
    const text = document.getElementById('trackingOrderNumber').textContent;
    if (/^\d+$/.test(text)) {  // ⚠️ FALLBACK FRÁGIL
      ordenId = parseInt(text);
    }
  }
  
  // Línea 227: Llamada API
  const response = await fetch(`/api/pedidos/${ordenId}/calcular-fecha-entrega`, ...);
  
  // Línea 240: Llamadas a formatDate 2 veces
  // Línea 245: Actualización manual del DOM (2 elementos)
  // Línea 255: Notificaciones
}
```

###  DESPUÉS (responsabilidades claras)
```javascript
async function handleDaysSelection(selectedDays) {
  // El estado YA tiene el orden
  const order = orderState.getOrder();
  if (!order) throw new Error('Orden no cargada');
  
  // Una responsabilidad: guardar días + calcular
  const result = await OrderApiService.calculateDeliveryDate(
    order.id, 
    selectedDays
  );
  
  // Actualizar estado
  orderState.setSelectedDays(selectedDays);
  
  // Actualizar UI (una línea)
  DOMRenderer.updateDeliveryDate(result.fecha_estimada);
  
  // Notificación
  showSuccess(`Entrega calculada: ${selectedDays} día${selectedDays !== 1 ? 's' : ''}`);
}
```

**Ventajas:**
-  Sin fallbacks
-  Error se lanza, no se oculta
-  Una responsabilidad por función
-  Testeable

---

## 📋 Resumen de Cambios

| Archivo Original | Líneas | Responsabilidades | Problemas |
|------------------|--------|-------------------|-----------|
| `tracking-modal-handler.js` | 800+ | 8+ | Duplicación, fallbacks, globales |

| Nuevo | Líneas | Responsabilidad | SOLID |
|--------|--------|-----------------|--------|
| `OrderState.js` | 30 | Estado centralizado |  SRP, DIP |
| `DateFormatter.js` | 50 | Formateo de fechas |  SRP |
| `OrderApiService.js` | 80 | APIs |  SRP |
| `DOMRenderer.js` | 100 | Presentación HTML |  SRP |
| `QueryUtils.js` | 15 | Helpers DOM |  SRP |
| `tracking-modal-handler.js` | **180** | Orquestación |  Todos |

**Total:** 455 líneas bien organizadas vs 800+ líneas caóticas.

---

## 🚀 Siguiente Paso

¿Quieres que empecemos con crear estos módulos? Propongo:

1. **Primero:** `OrderState.js` + `DateFormatter.js` (eliminan globales y duplicación)
2. **Luego:** `OrderApiService.js` (centraliza APIs)
3. **Finalmente:** `DOMRenderer.js` (refactoriza presentación)
4. **Cierre:** `tracking-modal-handler.js` se convierte en orquestador limpio

¿Vamos con esta estrategia?
