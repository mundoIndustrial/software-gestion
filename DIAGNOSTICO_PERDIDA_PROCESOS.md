# DIAGNÓSTICO: Pérdida de Procesos en Modal de Recibos

**FECHA:** 2024-01-25
**ESTADO:** ⚠️ EN DIAGNÓSTICO - Logging agregado, necesita ejecución

## Síntomas

- ❌ Modal "Recibo del Pedido" solo muestra "RECIBO DE COSTURA"
- ❌ `prenda.procesos` llega undefined/vacío en receipt-manager.js
- ✅ Backend retorna procesos correctamente
- ✅ No hay errores en consola

## Cadena de Flujo Auditada

```
1. Backend (/asesores/pedidos/{id}/recibos-datos)
   ↓ retorna JSON con prendas[] incluyendo procesos[]
   
2. fetch() en invoice-from-list.js línea ~530
   ↓ const datos = await response.json()
   
3. crearModalRecibosDesdeListaPedidos(datos, prendasIndex)
   ↓ pasa datos completo a cargarComponenteOrderDetailModal()
   
4. cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex)
   ↓ crea HTML + inyecta datos en window global
   
5. ReceiptManager(datos, prendasIndex)
   ↓ en constructor recibe datosFactura
   ↓ llama generarRecibos(datosFactura)
   
6. generarRecibos(datosFactura)
   ↓ itera datosFactura.prendas[]
   ↓ intenta acceder prenda.procesos
   ✗ prenda.procesos === undefined | []
```

## Puntos de Pérdida Potenciales

### 1. **Backend → Fetch (LOW RISK)**
```javascript
// invoice-from-list.js línea 530
const datos = await response.json();
```
**Riesgo:** BAJO
**Razón:** Si el backend retorna procesos, llegarían aquí intactos
**Debug:** Logs agregados en línea ~535-560

### 2. **crearModalRecibosDesdeListaPedidos() (MEDIUM RISK)**
```javascript
// invoice-from-list.js línea ~590
function crearModalRecibosDesdeListaPedidos(datos, prendasIndex = null) {
    // ← Datos se pasan como parámetro
    cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex);
}
```
**Riesgo:** MEDIO
**Razón:** Aquí se recibe datos, pero se pasa intacto a cargarComponente()
**Debug:** Logs agregados en línea ~590-605

### 3. **cargarComponenteOrderDetailModal() (HIGH RISK) ⚠️**
```javascript
// invoice-from-list.js línea ~700
function cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex = null) {
    // ← Aquí se genera el HTML de la modal
    // ← Después se crea window.receiptManager
    
    setTimeout(() => {
        if (typeof ReceiptManager === 'undefined') {
            cargarReceiptManager(() => {
                window.receiptManager = new ReceiptManager(datos, prendasIndex);
            });
        } else {
            window.receiptManager = new ReceiptManager(datos, prendasIndex);
        }
    }, 100);
}
```
**Riesgo:** ALTO
**Razón:** Aquí se pasa `datos` al ReceiptManager. Si hubo transformación en el HTML, podría perderse.
**Sospecha:** ¿Se modifica `datos` antes de pasar al ReceiptManager?

### 4. **ReceiptManager Constructor (VERIFY)**
```javascript
// receipt-manager.js línea 4
class ReceiptManager {
    constructor(datosFactura, prendasIndex = null, contenedorId = null) {
        this.datosFactura = datosFactura;  // ← Recibe datos
        const todosRecibos = this.generarRecibos(datosFactura);  // ← Los pasa
    }
}
```
**Riesgo:** BAJO
**Razón:** Solo pasa el parámetro sin modificarlo
**Debug:** Logs agregados en línea ~7-30

### 5. **generarRecibos() (VERIFY)**
```javascript
// receipt-manager.js línea 63
generarRecibos(datosFactura) {
    datosFactura.prendas.forEach((prenda, prendaIdx) => {
        if (prenda.procesos && Array.isArray(prenda.procesos)) {  // ← Aquí falla?
            prenda.procesos.forEach(...)
        }
    });
}
```
**Riesgo:** BAJO (pero es donde vemos el síntoma)
**Debug:** Logs agregados en línea ~63-110

## Teorías Principales

### Teoría 1: Transformación en cargarComponenteOrderDetailModal()
**Hipótesis:** Los datos se modifican o reemplazan antes de pasar a ReceiptManager

```javascript
// ¿Podría estarse reemplazando datos aquí?
contenedor.innerHTML = `...`  // ← No debería afectar datos
```

**Acción:** Verificar si `datos` se modifica entre crearModal() y ReceiptManager()

### Teoría 2: Filtrado en crearModalRecibosDesdeListaPedidos()
**Hipótesis:** Se extrae solo ciertos campos de prendas, perdiendo procesos

**Acción:** Revisar qué estructura se extrae de datos en crearModalRecibosDesdeListaPedidos()

### Teoría 3: Serialización JSON
**Hipótesis:** Al serializar/deserializar, se pierden propiedades

**Acción:** Los logs mostrarán si procesos existe en cada punto

## Archivos con Logging Agregado

✅ **invoice-from-list.js**
- Línea ~535-560: DEBUG tras fetch()
- Línea ~590-605: DEBUG en crearModalRecibosDesdeListaPedidos()

✅ **receipt-manager.js**
- Línea ~7-30: DEBUG en constructor
- Línea ~63-110: DEBUG en generarRecibos()

## Cómo Ejecutar el Diagnóstico

1. **Abrir navegador:**
   - Chrome / Firefox: F12 para abrir consola
   - Ir a `/asesores/pedidos`

2. **Ejecutar la acción:**
   - Hacer clic en un pedido que tenga procesos
   - Buscar button "Ver Recibos" o similar
   - Se abre modal

3. **Observar consola:**
   ```
   [DEBUG] Datos recibidos del backend - /asesores/pedidos/{id}/recibos-datos
   [crearModalRecibosDesdeListaPedidos] Datos recibidos en función
   [ReceiptManager] Constructor - Datos recibidos
   [ReceiptManager.generarRecibos] Procesando prendas
   ```

4. **Buscar indicadores:**
   - ✅ `procesos existe?` → `true` significa se transmite
   - ❌ `procesos existe?` → `false` significa se pierde antes
   - `procesos_count` → cuántos procesos tiene

## Puntos de Verificación Esperados

### ESCENARIO 1: Procesos llegan completos
```
✅ Backend: procesos_count: 2
✅ invoice-from-list fetch: procesos: [{...}, {...}]
✅ crearModal: procesos_existe: true
✅ ReceiptManager constructor: procesos: [{...}, {...}]
✅ generarRecibos: Procesando 2 procesos

RESULTADO: ✅ Modal muestra todos los recibos
```

### ESCENARIO 2: Procesos se pierden en fetch
```
❌ Backend: procesos_count: 2
❌ invoice-from-list fetch: procesos: undefined
❌ crearModal: procesos_existe: false

RESULTADO: ❌ Problema en respuesta del endpoint o transformación JSON
```

### ESCENARIO 3: Procesos se pierden entre crearModal y ReceiptManager
```
✅ Backend: procesos_count: 2
✅ invoice-from-list fetch: procesos: [{...}, {...}]
✅ crearModal: procesos_existe: true
❌ ReceiptManager constructor: procesos: undefined
❌ generarRecibos: Sin procesos o no es array

RESULTADO: ❌ Problema en transformación de datos dentro de crearModalRecibosDesdeListaPedidos()
```

## Próximos Pasos Después del Diagnóstico

1. **Si procesos llegan pero se pierden en crearModal():**
   - Revisar `cargarComponenteOrderDetailModal()` línea ~700
   - Buscar si se modifica `datos` antes de ReceiptManager

2. **Si procesos se pierden en ReceiptManager:**
   - Verificar si constructor modifica datosFactura
   - Revisar si generarRecibos() hace transformación

3. **Si procesos nunca llegan desde backend:**
   - Problema en endpoint `/asesores/pedidos/{id}/recibos-datos`
   - Requeriría revisión backend (fuera de alcance actual)

## Notas Técnicas

- El código actual ya intenta usar `proceso.nombre_processo || proceso.tipo_proceso`
- Esto sugiere que el desarrollador anterior sabía que hay transformación
- El problema es que `prenda.procesos` es undefined antes de que se acceda a campos internos

## Archivos Auditados

- ✅ invoice-from-list.js (líneas 520-610)
- ✅ receipt-manager.js (líneas 1-110)
- ✅ PedidoProduccionRepository.php (línea 817) - Backend retorna correctamente

## Estado de Ejecución

**PRÓXIMO:** Ejecutar en navegador y capturar logs de consola para identificar punto exacto de pérdida.
