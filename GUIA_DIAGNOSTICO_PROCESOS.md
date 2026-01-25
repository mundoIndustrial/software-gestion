# GUÍA PRÁCTICA: Ejecutar Diagnóstico de Pérdida de Procesos

## 🎯 OBJETIVO

Identificar exactamente en qué punto se pierde `prenda.procesos` al abrir recibos.

## 📋 PASOS A SEGUIR

### PASO 1: Preparar el navegador

```bash
# Abrir Chrome o Firefox
# Presionar F12 para abrir DevTools
# Ir a pestaña "Console"
```

### PASO 2: Limpiar cachés

```bash
# En la URL del navegador escribir:
javascript:sessionStorage.clear(); localStorage.clear(); location.reload();

# O simplemente hacer:
Ctrl + Shift + Delete (limpiar datos de navegación)
```

### PASO 3: Navegar a la página de recibos

```
Ir a: http://localhost:8000/asesores/pedidos
(o tu URL local)
```

### PASO 4: Abrir la consola

```
Presionar F12
Ir a Console tab
Limpiar console: Ctrl + L
```

### PASO 5: Hacer clic en "Ver Recibos"

1. En la tabla de pedidos, encontrar un pedido que tenga procesos
2. Buscar botón "Ver Recibos" o similar
3. Hacer clic

### PASO 6: Observar la consola

Deberías ver estos logs en orden:

```
[DEBUG] Datos recibidos del backend - /asesores/pedidos/{id}/recibos-datos
├─ Estructura completa: {...}
├─ Número de prendas: N
├─ Prenda 0: NOMBRE_PRENDA
│  ├─ Campos disponibles: ["id", "nombre", ..., "procesos"]
│  ├─ procesos existe? true/false ← CLAVE 1
│  ├─ procesos es array? true/false ← CLAVE 2
│  └─ procesos count: N ← CLAVE 3

[crearModalRecibosDesdeListaPedidos] Datos recibidos en función
├─ datos completo: {...}
├─ prendas count: N
└─ Primera prenda estructura:
   ├─ procesos_existe: true/false ← CLAVE 4
   ├─ procesos_valor: [...] ← CLAVE 5
   └─ procesos_tipo: object ← CLAVE 6

[cargarComponenteOrderDetailModal] Antes de crear ReceiptManager
├─ datos parámetro: {...}
├─ datos.prendas.length: N
└─ Primera prenda en datos:
   ├─ procesos_existe: true/false ← CLAVE 7
   ├─ procesos_valor: [...] ← CLAVE 8
   └─ procesos_length: N ← CLAVE 9

[ReceiptManager] Constructor - Datos recibidos
├─ datosFactura: {...}
├─ datosFactura.prendas: [...]
├─ Número de prendas: N
└─ Primera prenda - Análisis detallado:
   ├─ Campos disponibles: [...] ← CLAVE 10
   ├─ Tiene "procesos"? true/false ← CLAVE 11
   ├─ procesos valor: [...] ← CLAVE 12
   ├─ procesos es array? true/false ← CLAVE 13
   └─ procesos length: N ← CLAVE 14

[ReceiptManager.generarRecibos] Procesando prendas
├─ Total de prendas a procesar: N
├─ Procesando Prenda 0: NOMBRE_PRENDA
│  ├─ ✓ Agregado: "RECIBO DE COSTURA"
│  ├─ Verificando procesos:
│  │  ├─ prenda.procesos existe? true/false ← CLAVE 15
│  │  ├─ prenda.procesos valor: [...] ← CLAVE 16
│  │  ├─ Es array? true/false ← CLAVE 17
│  │  └─ Procesando N procesos
│  │     └─ Proceso 0: "NOMBRE_PROCESO"
│  └─ ⚠️ Sin procesos o no es array
└─ Total de recibos generados: N
   └─ Recibos: [...]
```

## 🔍 MATRIZ DE DIAGNÓSTICO

| Paso | Clave | Debe ser | Si es ❌ |
|------|-------|----------|---------|
| 1 | Backend retorna procesos | `procesos_count: > 0` | Problema en Backend |
| 2 | Fetch recibe procesos | `procesos existe? true` | Problema en fetch/response |
| 3 | crearModal recibe procesos | `procesos_existe: true` | Problema en transformación |
| 4 | Antes de ReceiptManager | `procesos_existe: true` | Problema en cargarComponente |
| 5 | ReceiptManager recibe | `Tiene "procesos"? true` | Problema en paso de parámetro |
| 6 | generarRecibos ve procesos | `prenda.procesos existe? true` | Problema interno |

## 🎯 ESCENARIOS

### ✅ ESCENARIO 1: Todo funciona (procesos se muestran)

```
DEBUG - CLAVE 1,3: procesos existe? true | procesos_count: 2
crearModal - CLAVE 4,5: procesos_existe: true | procesos_valor: [...]
ReceiptManager - CLAVE 11,14: Tiene "procesos"? true | procesos_length: 2
generarRecibos - CLAVE 15,17: prenda.procesos existe? true | Es array? true

RESULTADO: ✅ Modal muestra 3 recibos (1 costura + 2 procesos)
```

### ❌ ESCENARIO 2: Procesos se pierden en BACKEND

```
DEBUG - CLAVE 1,3: procesos existe? false | procesos_count: 0
(No aparecen en logs posteriores)

RESULTADO: ❌ Problema en endpoint /asesores/pedidos/{id}/recibos-datos
SOLUCIÓN: Revisar PedidoProduccionRepository.php línea 817
```

### ❌ ESCENARIO 3: Procesos se pierden en FETCH/JSON

```
DEBUG - CLAVE 1,3: procesos existe? true | procesos_count: 2
(Siguiente log:)
crearModal - CLAVE 4,5: procesos_existe: false | procesos_valor: undefined

RESULTADO: ❌ Problema en JSON.parse() o fetch()
SOLUCIÓN: Ver si hay validación/filtro que quita procesos
```

### ❌ ESCENARIO 4: Procesos se pierden en crearModal

```
crearModal - CLAVE 4,5: procesos_existe: true | procesos_valor: [...]
(Siguiente log:)
cargarComponente - CLAVE 7,8: procesos_existe: false | procesos_valor: undefined

RESULTADO: ❌ Problema dentro cargarComponenteOrderDetailModal()
SOLUCIÓN: Revisar código entre líneas 630-730 de invoice-from-list.js
```

### ❌ ESCENARIO 5: Procesos se pierden antes de ReceiptManager

```
cargarComponente - CLAVE 7,8: procesos_existe: true | procesos_valor: [...]
(Siguiente log:)
ReceiptManager Constructor - CLAVE 11,12: Tiene "procesos"? false | procesos_valor: undefined

RESULTADO: ❌ Problema en setTimeout() o cargarReceiptManager()
SOLUCIÓN: Revisar tiempo de delay (100ms) o carga de script
```

### ❌ ESCENARIO 6: Procesos se pierden en generarRecibos

```
ReceiptManager - CLAVE 11,14: Tiene "procesos"? true | procesos_length: 2
(Siguiente log:)
generarRecibos - CLAVE 15,16: prenda.procesos existe? false | prenda.procesos valor: undefined

RESULTADO: ❌ Problema en copia/clonación de objeto prenda
SOLUCIÓN: Problema con this.datosFactura vs datosFactura
```

## 📸 CÓMO COMPARTIR LOGS

1. **Copiar TODA la consola:**
   - Click derecho en consola → "Save as..."
   - O seleccionar todo (Ctrl+A) y copiar (Ctrl+C)

2. **Pegarlo aquí:**
   ```
   Aquí van los logs...
   ```

3. **Describir qué se vio:**
   - ¿Aparecen los recibos?
   - ¿Cuántos recibos muestra? (debe ser 3 si hay 1 costura + 2 procesos)
   - ¿Qué mensaje muestra en consola?

## 🔧 PRUEBA RÁPIDA

Si quieres verificar manualmente en la consola ahora:

```javascript
// Copiar y pegar esto en la consola:
console.log('Datos en window.receiptManager:', window.receiptManager);
console.log('Prendas:', window.receiptManager.datosFactura.prendas);
console.log('Primera prenda procesos:', window.receiptManager.datosFactura.prendas[0].procesos);
console.log('Recibos generados:', window.receiptManager.recibos);
console.log('Recibos count:', window.receiptManager.recibos.length);
```

Esto mostrará exactamente qué tiene el ReceiptManager.

## 📝 CHECKLIST

- [ ] Abierto DevTools (F12)
- [ ] En pestaña Console
- [ ] Navegado a /asesores/pedidos
- [ ] Hice clic en "Ver Recibos"
- [ ] Veo los logs [DEBUG], [crearModal], [ReceiptManager], [generarRecibos]
- [ ] Copié todos los logs
- [ ] Identifiqué en qué CLAVE fallan los procesos

Una vez completes esto y me traigas los logs, podré identificar el punto exacto de pérdida y darte la solución específica.
