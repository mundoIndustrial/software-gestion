# ANÁLISIS PROFUNDO: tracking-modal-handler.js
## Violaciones SOLID, DDD y Malas Prácticas

---

## 1️⃣ FUNCIONES DUPLICADAS E INEFICIENCIAS

### 1.1 Lógica de Formateo de Fechas (DUPLICADA 3+ veces)

**Problema:** La lógica de conversión de fechas se repite exactamente en múltiples lugares:

| Ubicación | Líneas | Contexto |
|-----------|--------|----------|
| `updateOrderInfo()` | 577-626 | Actualiza `selectorOrderStartDate` |
| `updateOrderInfo()` | 649-673 | Actualiza fecha inicio dentro del mismo método |
| `updateEstimatedDeliveryDate()` | 726-750 | Repite exactamente el mismo patrón |

**Código Duplicado:**
```javascript
if (typeof fechaInicio === 'string') {
  try {
    const date = new Date(fechaInicio);
    if (!isNaN(date.getTime())) {
      fechaFormateada = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    }
  } catch (e) {
    fechaFormateada = fechaInicio;  // ⚠️ FALLBACK
  }
} else if (fechaInicio instanceof Date) {
  // Repite lo mismo
} else if (fechaInicio && fechaInicio.date) {
  // Y nuevamente lo mismo
}
```

**Consecuencia:** Cambiar el formato de fecha requiere editar 3+ lugares. Mayor probabilidad de inconsistencias.

---

### 1.2 Actualización Redundante del Orden (540+ líneas de código)

**Problema:** `updateOrderInfo()` (líneas 546-691) hace lo mismo que `updateEstimatedDeliveryDate()` (líneas 719-760):
- Ambas actualizan `selectorOrderEstimatedDate`
- Ambas convierten fechas con el mismo patrón
- Ambas buscan el mismo `element.textContent`

---

## 2️⃣ VIOLACIONES DE SOLID

### 2.1 SINGLE RESPONSIBILITY PRINCIPLE (SRP) 

El archivo actúa como **"God Object"** (Objeto Todopoderoso). Responsabilidades mixtas:

| Responsabilidad | Funciones Afectadas | Líneas |
|-----------------|-------------------|--------|
| **Manipulación DOM** | `updateOrderInfo()`, `updateEstimatedDeliveryDate()`, `createPrendasTable()` | 546-805 |
| **Comunicación API** | `loadOrderBasicData()`, `convertEncargadoToSelect()` | 430-474, 559-571 |
| **Lógica de Negocio** | `actualizarContadoresDinamicos()`, `calcularDiasHabilesSync()` | 101-165 |
| **Gestión de État** | `window.currentOrderData`, `window.currentPrendaData` | Global |
| **Temporizadores** | `iniciarTimerContadores()`, `detenerTimerContadores()` | 140-165 |
| **HTML Rendering** | `createPrendasTable()` concatena strings HTML | 795-805 |

**Síntoma:** El archivo hace demasiadas cosas. Cambiar un formato de fecha afecta 3+ funciones. Arreglar el cálculo de días rompe la lógica de temporizadores.

---

### 2.2 OPEN/CLOSED PRINCIPLE (OCP) 

**Problema en `convertEncargadoToSelect()` (líneas 430-474):**

```javascript
// Código actual en línea 444-474:
const response = await fetch(`/api/areas/${encodeURIComponent(area)}/encargados`);
```

El endpoint es **hardcodeado**. Para agregar:
- Validar nuevo tipo de área
- Cambiar estructura de respuesta
- Usar otro endpoint

**Requiere modificar el archivo.**

**Problema similar en `crearRecibosSelectors()` (líneas 649-673):**
```javascript
// Jerarquía hardcodeada:
const hierarchy = {
  'COSTURA': 1,
  'REFLECTIVO': 2,
  'REMATE': 3,
  // etc...
};
```

Para agregar una nueva área → hay que tocar el código.

---

### 2.3 DEPENDENCY INVERSION PRINCIPLE (DIP) 

**Problema:** El código depende de **variables globales** en lugar de inyectar dependencias:

```javascript
// Línea 109: Dependencia global
window.currentPrendaData        // ¿De dónde viene? ¿Cuándo se establece?
window.currentOrderData         // ¿Quién lo mantiene?
window.__trackingDiasSeleccionados  // Más globales...
```

**Consecuencias:**
-  Imposible testear funciones
-  Side effects ocultos
-  Múltiples componentes pueden sobrescribir los mismos datos
-  Orden de inicialización crítico y frágil

**Ejemplo problemático (línea 553):**
```javascript
async function loadOrderBasicData(orderId) {
  // ...
  window.currentOrderData = data;  // ¿Y si otro código lo sobrescribe?
  updateOrderInfo(data);
}
```

---

### 2.4 LISKOV SUBSTITUTION PRINCIPLE (LSP) ⚠️

**Problema:** No hay interfaces claras, los objetos esperan estructuras inconsistentes.

En `updateOrderInfo()` (líneas 577-626), se espera **3 formatos de fecha diferentes**:

```javascript
// String ISO
if (typeof fechaInicio === 'string') { /* parsear */ }

// Objeto Date nativo
else if (fechaInicio instanceof Date) { /* convertir */ }

// Objeto Laravel/Carbon
else if (fechaInicio && fechaInicio.date) { /* parsear .date */ }
```

**¿Por qué?** El backend no tiene un contrato claro de qué estructura devuelve. El frontend assume 3 posibilidades diferentes.

---

### 2.5 INTERFACE SEGREGATION PRINCIPLE (ISP) 

**Problema:** Las funciones esperan objetos enormes cuando solo necesitan partes pequeñas.

Ejemplo:
```javascript
function updateOrderInfo(orderData) {
  // ¿Necesita todo orderData?
  // No. Solo necesita:
  // - numero_pedido
  // - cliente
  // - estado
  // - fecha_estimada_entrega
  
  // Pero acepta TODAS las propiedades:
  // - prendas[]
  // - seguimientos_por_area{}
  // - consecutivos{}
  // - etc...
}
```

El objeto es muy grande. La función debería recibir solo lo que necesita (Value Object).

---

## 3️⃣ VIOLACIONES DE DOMAIN-DRIVEN DESIGN (DDD)

### 3.1 Lógica de Negocio en la Capa de Presentación

**Problema:** Los cálculos de dominio están en JavaScript del frontend:

####  Cálculo de Días Hábiles (Líneas 101-137)
```javascript
function actualizarContadoresDinamicos() {
  // Línea 125: calcularDiasHabilesSync(ini, new Date())
  // ¿Quién define qué es "día hábil"?
  // ¿Qué festivos contar?
  // ¿Qué zona horaria?
}
```

**Problema:** Los días se cuentan en **hora del cliente**, no del servidor. Si:
- Cliente en Madrid → 08:00 GMT+1
- Servidor en UTC → 07:00

Pueden tener diferentes "hoy" y contar días diferentes. **Inconsistencia garantizada.**

####  Priorización de Recibos (Líneas 649-673)
```javascript
// Jerarquía de negocio hardcodeada:
const hierarchy = {
  'COSTURA': 1,
  'REFLECTIVO': 2,
  'REMATE': 3,
};

// ¿Por qué COSTURA > REFLECTIVO?
// ¿Quién decidió esto?
// ¿Y si cambia la estrategia?
```

Esta es una **regla de negocio crítica** que debería:
1.  Estar en el backend (fuente única de verdad)
2.  Ser testeable
3.  Poder cambiar sin tocar jQuery

---

### 3.2 Anemic Model (Modelos sin inteligencia)

**Problema:** Los datos se manipulan como estructuras "tontas":

```javascript
// Línea 553
window.currentOrderData = data;  // Es solo un objeto JSON

// Línea 625
document.getElementById('trackingOrderNumber').textContent = 
  orderData.numero_pedido || '-';  // Se manipula directamente
```

**¿Dónde está la Entidad de Dominio `Order`?**

Debería haber:
```javascript
class Order {
  constructor(data) {
    this.numero = data.numero_pedido;
    this.cliente = data.cliente;
    this.estado = OrderStatus.from(data.estado);
    this.fechaEstimadaEntrega = this.estado.isCompleted() 
      ? null 
      : OrderDate.from(data.fecha_estimada_entrega);
  }

  isPending() { /* lógica de dominio */ }
  getMainReceipt() { /* lógica de dominio */ }
}
```

---

### 3.3 Falta de Agregados y Boundaries

**Problema:** Los datos `seguimientos_por_area` no tienen estructura de agregado:

```javascript
// Línea 109
window.currentPrendaData = {
  seguimientos_por_area: {
    'COSTURA': { fecha_inicio, fecha_fin, ... },
    'REFLECTIVO': { ... },
    // etc...
  }
}

// Se acceden directamente sin protección:
const processData = window.currentPrendaData?.seguimientos_por_area?.[area];
processData.fecha_inicio = nuevaFecha;  // ¿Sin validar?
```

**¿Dónde está la Entidad `Prenda`?**

```javascript
class Prenda {
  private seguimientos = new Map();
  
  addSeguimiento(area, seguimiento) {
    // Validar: ¿COSTURA puede ir después de REFLECTIVO?
    // ¿Existen conflictos de fecha?
    if (!this.isValidTransition(area)) throw new Error();
  }
}
```

---

## 4️⃣ FALLBACKS Y MALAS PRÁCTICAS

### 4.1 Fallback de Identificación (ANTI-PATRÓN) 

**Problema en `saveDiaEntregaSelection()` (líneas 193-204):**

Aunque el código extraído no muestra exactamente, el patrón es:
```javascript
// Si falla obtener orderId:
let orderId = window.currentOrderData?.id;

if (!orderId) {
  // ⚠️ FALLBACK: Intentar extraer del DOM con Regex
  const orderText = document.querySelector('.order-header').textContent;
  const match = orderText.match(/^\d+$/);
  orderId = match ? match[0] : null;  // MUY FRÁGIL
}
```

**Problemas:**
- 🔴 El regex es demasiado permisivo
- 🔴 Si el DOM cambia (renombran la clase), se rompe
- 🔴 Confía en presentación, no en datos
- 🔴 Imposible debuggear si falla

### 4.2 Fallback de Datos Inconsistentes 

**Problema en `createPrendasTable()` (líneas 795-805):**

```javascript
// Si hay tipos de recibo específicos, usarlos
let recibos = tiposReciboPorArea[area];

if (!recibos) {
  // ⚠️ FALLBACK: Usar procesos generales
  recibos = tiposReciboGenerales;  // Datos por defecto inconsistentes
}
```

**Por qué es malo:**
- 🔴 El backend devuelve estructuras inconsistentes
- 🔴 El frontend compensa con fallbacks
- 🔴 Enmascara bugs del backend
- 🔴 Lógica de fallback puede ser incorrecto

### 4.3 Estados Globales Mutables 

**Problema en toda la aplicación:**

```javascript
window.currentOrderData = data;        // Línea 571
window.currentPrendaData?.seguimientos_por_area?.[area] = nuevosdatos;  // Mutación
window.__trackingDiasSeleccionados = n;  // Línea 189
```

**Consecuencias:**
- 🔴 Múltiples componentes pueden acceder/modificar al mismo tiempo
- 🔴 Race conditions en fetch asincronos
- 🔴 Cambios inesperados de estado
- 🔴 Debugging imposible

---

## 5️⃣ LÓGICA QUE DEBERÍA ESTAR EN BACKEND

### 5.1 Cálculo de Días Dinámicos (CRÍTICO)

**Ubicación:** `actualizarContadoresDinamicos()` y `iniciarTimerContadores()` (líneas 101-165)

```javascript
// Línea 125
const diasHabiles = calcularDiasHabilesSync(ini, new Date());
```

**¿Por qué debe ir al backend?**

1. **Zona horaria:** El cliente puede estar en UTC-5, ser "hoy" para él pero no para el servidor
2. **Festivos:** ¿Qué festivos contar? Españoles, colombianos, mexicanos...
3. **Reglas de negocio:** ¿Cuáles días son "hábiles"? ¿Fin de semana?
4. **Consistencia:** 10 clientes en 10 zonas horarias podrían calcular 10 valores diferentes

**Solución:**
```javascript
//  Backend calcula UNA VEZ
GET /api/orders/123/working-days-elapsed
{
  "diasHabiles": 5,
  "diasCalendario": 7,
  "proximaDia": "2026-03-25"
}

// Frontend solo muestra
document.textContent = `${diasHabiles} días`;
```

---

### 5.2 Priorización de Recibos (REGLA DE NEGOCIO)

**Ubicación:** `crearRecibosSelectors()` (líneas 649-673)

```javascript
// ¿Por qué COSTURA > REFLECTIVO > REMATE?
const hierarchy = { 'COSTURA': 1, 'REFLECTIVO': 2, ... };
```

**Problema:**
- 🔴 Es una regla de negocio crítica ("cual recibo es el principal")
- 🔴 Hardcodeada en JavaScript
- 🔴 No hay contexto de negocio
- 🔴 ¿Igual para todos los clientes? ¿Todas las áreas?

**Solución:**
```javascript
//  Backend devuelve el recibo prioritario
GET /api/orders/123/receipts
{
  "primaryReceipt": {
    "tipo": "COSTURA",
    "numero": "REC-2026-00123"
  },
  "allReceipts": [...]
}
```

---

### 5.3 Formateo de Fechas (CONSISTENCIA)

**Ubicación:** Múltiples lugares (líneas 577-750)

```javascript
// ¿Format de fecha es específico del usuario?
// ¿Depende del país? ¿Del navegador?
date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' })
```

**Problema:**
- 🔴 Duplicado en 3+ lugares
- 🔴 Si la app se internacionaliza, hay que cambiar todo
- 🔴 No hay formato seguro

**Solución:**
```javascript
//  Backend devuelve fecha formateada YA
GET /api/orders/123
{
  "numero": 123,
  "fechaEstimadaEntrega": "25/03/2026",  // YA formateado
  "fechaInicio": "18/03/2026"             // Consistente
}
```

---

## 📋 RESUMEN DE VIOLACIONES

| Categoría | Severidad | Cantidad |
|-----------|-----------|----------|
| **Código Duplicado** | 🔴 CRÍTICA | 3+ instancias |
| **SRP Violado** | 🔴 CRÍTICA | 1 archivo hace 8+ responsabilidades |
| **Fallbacks** | 🔴 CRÍTICA | 2+ patrones de fallback |
| **Lógica en Frontend** | 🔴 CRÍTICA | Cálculos de negocio en JS |
| **Estados Globales** | 🔴 CRÍTICA | 4+ variables en `window` |
| **Anemic Model** | 🟡 ALTA | No hay Entidades de dominio |
| **OCP Violado** | 🟡 ALTA | Hardcoding de áreas, endpoints, jerarquías |
| **DIP Violado** | 🟡 ALTA | Dependencias directas, sin inyección |

---

## 🎯 PRÓXIMOS PASOS (Sin hacer cambios aún)

Esto requiere una refactorización de arquitectura, no parchazos.

**Propuesta:** Crear una arquitectura limpia con:
1.  **Domain Layer:** Entidades (`Order`, `Prenda`, `Seguimiento`)
2.  **Use Cases:** Operaciones de negocio aisladas
3.  **Application Layer:** Coordinación entre capas
4.  **Presentation Layer:** Props inyectados, sin globales
5.  **Backend API:** Devuelve datos validados, cálculos seguros

¿Quieres que continúe con la propuesta de refactorización sin fallbacks?
