# 📊 Guía de Lectura de Logs de Performance

## Dónde ver los logs

### Backend (Laravel)
**Archivo:** `storage/logs/laravel.log`

```bash
# Ver los logs en tiempo real
tail -f storage/logs/laravel.log

# Filtrar solo los logs de insumos
tail -f storage/logs/laravel.log | grep -i "RecibosQueryService\|FIN RecibibosQueryService"
```

### Frontend (Browser)
**Consola del navegador:** `F12` → pestaña **Console**

---

## 📈 Estructura de Logs

### BACKEND - RecibosQueryService

```
═══ INICIO RecibosQueryService ═══
timestamp: 2026-04-22T15:30:45.123Z
url: http://localhost:8000/insumos/materiales?page=1
page: 1

✓ Query BD completada
  duration_ms: 1234.56
  total_recibos: 145
  items_pagina: 10

✓ Maps obtenidos
  duration_ms: 45.23
  parciales_count: 3
  materiales_count: 8

✓ Transformación completada
  duration_ms: 56.78

═══ FIN RecibosQueryService ═══
duration_total_ms: 1336.57
breakdown:
  query_ms: 1234.56
  maps_ms: 45.23
  transform_ms: 56.78
  other_ms: 0.00
total_recibos: 145
```

**Qué significa:**
- `duration_total_ms`: Tiempo TOTAL que el backend tardó (idealmente < 2000ms después de optimizar)
- `query_ms`: Tiempo de la query BD (crítico - debe bajar de 500ms)
- `maps_ms`: Tiempo para obtener mapas de relaciones
- `transform_ms`: Tiempo de transformación de datos en PHP

---

### FRONTEND - Materiales Page Loader

```
╔════════════════════════════════════════╗
║  INICIO CARGA PÁGINA - INSUMOS         ║
╚════════════════════════════════════════╝
Timestamp: 3:30:45 PM
DOM Ready: interactive

Cargando 15 scripts...

  ✓ pagination.js (45.23ms)
  ✓ index.js (78.56ms)
  ✓ modal-handlers-insumos.js (23.45ms)
  ✓ filter-manager-no-url.js (156.78ms)
  ✓ material-operations-insumos.js (34.12ms)
  ... [10 scripts más]

✓ Todos los scripts cargados en 1245.67ms

✓ Scripts cargados: 1245.67ms
✓ Festivos inicializados: 12.34ms
✓ Event handlers vinculados: 5.67ms

╔════════════════════════════════════════╗
║  CARGA COMPLETADA - RESUMEN            ║
╚════════════════════════════════════════╝
Scripts:           1245.67ms
Festivos:          12.34ms
Event Handlers:    5.67ms
───────────────────────────────────────
TOTAL JS FRONTEND: 1263.68ms
```

**Qué significa:**
- `TOTAL JS FRONTEND`: Tiempo que tarda JavaScript en cargar (idealmente < 500ms después de parallelizar)
- Scripts individuales: Si alguno es > 200ms, investigar

---

## 🎯 Métricas Esperadas

### ANTES de optimizaciones
```
Backend (RecibosQueryService): 3000-5000ms
  - Query BD: 2500-4500ms  ← LOS 5 JOINs SUBQUERIES
  - Maps: 100-200ms
  - Transform: 100-150ms

Frontend (JS Loading): 1500-2000ms
  - Scripts cargados secuencialmente: 1500-2000ms

TOTAL PÁGINA: 5-7 segundos
```

### DESPUÉS de optimizaciones
```
Backend (RecibosQueryService): 800-1500ms
  - Query BD: 500-1000ms  ← SIN los 5 JOINs
  - Maps: 100-200ms
  - Transform: 100-150ms

Frontend (JS Loading): 300-500ms
  - Scripts cargados en paralelo: 300-500ms

TOTAL PÁGINA: 1-2 segundos
```

---

## 📋 Checklist de Lectura

Cuando veas los logs, verifica:

- [ ] `duration_total_ms` del backend
  - ✅ Bueno: < 1500ms
  - ⚠️ Aceptable: 1500-2500ms
  - ❌ Malo: > 2500ms

- [ ] `query_ms` del backend (la más importante)
  - ✅ Bueno: < 1000ms
  - ⚠️ Aceptable: 1000-2000ms
  - ❌ Malo: > 2000ms

- [ ] `TOTAL JS FRONTEND`
  - ✅ Bueno: < 500ms
  - ⚠️ Aceptable: 500-1000ms
  - ❌ Malo: > 1000ms

- [ ] Scripts individuales más lentos
  - Buscar los que tarden > 200ms
  - Son candidatos para optimize/lazy-load

---

## 🔍 Ejemplos de Análisis

### Caso 1: Backend lento (query BD)
```
backend query_ms: 3500ms  ← MALO
Posible causa: Los 5 JOINs subqueries siguen ahí
Solución: Verificar que se aplicó la optimización #1
```

### Caso 2: Frontend lento (scripts)
```
filter-manager-no-url.js: 450ms  ← Lento
form-handlers-insumos.js: 380ms  ← Lento
Posible causa: Scripts cargados secuencialmente
Solución: Implementar optimización #2 (parallelizar)
```

### Caso 3: Transformación lenta
```
transform_ms: 250ms  ← Lento para solo 10 recibos
Posible causa: Regex complejas o cálculos innecesarios
Solución: Optimización #5
```

---

## 💾 Guardar Logs para Comparación

### Backend
```bash
# Copiar antes de optimizar
tail -100 storage/logs/laravel.log > logs_antes.txt

# Después de optimizar
tail -100 storage/logs/laravel.log > logs_despues.txt

# Comparar
diff logs_antes.txt logs_despues.txt
```

### Frontend
```javascript
// En consola, copiar el output completo de los logs
// Guardar en archivo: logs_js_antes.txt

// Después de optimizar
// Copiar nuevamente: logs_js_despues.txt
```

---

## 🚀 Interpretar Mejoras

Si ves esto después de las optimizaciones:

```
ANTES:
  Backend: 4500ms
  Frontend: 1800ms
  Total: 6300ms

DESPUÉS:
  Backend: 1000ms (78% mejor ✅)
  Frontend: 400ms (78% mejor ✅)
  Total: 1400ms (78% mejor ✅)
```

¡Éxito! La página está 4.5x más rápida.

---

## 🛠️ Troubleshooting

### Los logs del backend no aparecen
```
Verificar:
1. APP_DEBUG=true en .env
2. storage/logs/ existe y es escribible
3. Tail está viendo el archivo correcto
```

### Los logs del frontend son muy lentos
```
1. Abrir DevTools (F12)
2. Ir a Console
3. Buscar los scripts individuales lentos
4. Investigar si hay loops/recursion
```

### Los logs muestran optimización pero la UI sigue lenta
```
Podría ser:
1. Network lenta (verificar Network tab en DevTools)
2. Rendering lento (verificar Performance tab)
3. CSS muy complejo
4. Imágenes sin optimizar
```
