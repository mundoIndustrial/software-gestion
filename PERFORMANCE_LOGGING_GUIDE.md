# 📊 Performance Logging Guide - Supervisor Pedidos

## Descripción General

Se ha implementado un sistema completo de logging de performance para medir:
- **Backend**: Tiempo de procesamiento de requests, queries, y transformación de datos
- **Frontend**: Tiempo de módulos, API calls, rendering, navegación del navegador

---

## 🔍 CÓMO USAR

### 1️⃣ Backend Logging (Laravel)

Todos los logs se escriben en `storage/logs/laravel.log`

#### Ejemplo de output:
```
[2026-04-22 15:30:45] local.INFO: [PERF] 🚀 REQUEST STARTED {
    "request_id": "a1b2c3d4",
    "endpoint": "GET /api/cartera/pedidos",
    "timestamp": "2026-04-22T15:30:45.000000Z",
    "memory_initial_mb": 8.5
}

[2026-04-22 15:30:45] local.INFO: [PERF] ⏱️ PARAMS_PREPARED {
    "request_id": "a1b2c3d4",
    "total_ms": 1.23,
    "delta_ms": 1.23,
    "memory_mb": 8.6,
    "memory_peak_mb": 8.6
}

[2026-04-22 15:30:45] local.INFO: [PERF] ⏱️ USE_CASE_EXECUTED {
    "request_id": "a1b2c3d4",
    "total_ms": 145.67,
    "delta_ms": 144.44,
    "memory_mb": 12.3,
    "memory_peak_mb": 12.3,
    "success": true
}

[2026-04-22 15:30:45] local.INFO: [PERF] ✅ REQUEST FINISHED {
    "request_id": "a1b2c3d4",
    "status": 200,
    "total_time_ms": 156.78,
    "memory_mb": 10.2,
    "memory_peak_mb": 12.3,
    "markers_count": 4,
    "marker_1": "PARAMS_PREPARED (1.23ms)",
    "marker_2": "USE_CASE_EXECUTED (144.44ms)",
    "marker_3": "DATA_MAPPED (2.10ms)",
}
```

#### Ver logs en tiempo real:
```bash
tail -f storage/logs/laravel.log | grep "\[PERF\]"
```

#### Filtrar por request ID:
```bash
tail -f storage/logs/laravel.log | grep "a1b2c3d4"
```

---

### 2️⃣ Frontend Logging (Browser Console)

#### Abrir DevTools:
```
Windows/Linux: F12
Mac: Cmd + Option + I
```

#### Ver logs automáticos:
Cuando carga la página, verás en consola:

```
[PERF] Monitor iniciado

⏱️ PAGE_LOAD_START +0.00ms (total: 0.00ms)
⏱️ DOM_CONTENT_LOADED +2145.67ms (total: 2145.67ms)
⏱️ SUPERVISOR_PEDIDOS_INIT_START +245.00ms (total: 2390.67ms)
⏱️ SUPERVISOR_VIEW_DETECTED +0.50ms (total: 2391.17ms)
⏱️ STARTING_TABLE_AND_FILTERS +1.23ms (total: 2392.40ms)

🔌 API: /api/cartera/pedidos 200 147.23ms 
   (o con tamaño: 23.45 KB)

⏱️ TABLE_API_RESPONSE_RECEIVED +147.50ms (total: 2539.90ms)
⏱️ TABLE_RESPONSE_PARSED +5.20ms (total: 2545.10ms)
⏱️ TABLE_RENDER_START +0.89ms (total: 2546.00ms)
   rows: 15
⏱️ TABLE_RENDER_COMPLETE +45.30ms (total: 2591.30ms)

⏱️ TABLE_AND_FILTERS_READY +52.10ms (total: 2643.40ms)
⏱️ MODULE_FULLY_READY +0.23ms (total: 2643.63ms)

[PERF] Typing: perfMonitor.report() in console for details
```

#### Generar reporte detallado:
En la consola del navegador, escribe:
```javascript
perfMonitor.report()
```

Verás un reporte como:
```
📊 PERFORMANCE REPORT

Session ID: perf-1713795045000-abc123de
Total Time: 2643.63ms

Marks:
  PAGE_LOAD_START: +0.00ms
  DOM_CONTENT_LOADED: +2145.67ms
  TABLE_API_RESPONSE_RECEIVED: +147.50ms
  TABLE_RENDER_START: +45.30ms
  MODULE_FULLY_READY: +0.23ms

API Calls:
  /api/cartera/pedidos: 200 147.23ms 23.45 KB
  /api/cartera/opciones-filtro: 200 12.34ms 1.23 KB

Navigation Timing:
  DNS lookup: 15ms
  TCP connection: 24ms
  Request time: 45ms
  Response time: 67ms
  DOM Parse: 234ms
  DOM Interactive: 2145ms
  First Contentful Paint: 2156.12ms
```

#### Acceder al monitor globalmente:
```javascript
// Ver el monitor
console.log(supervisorPedidosMonitor)

// Ver resumen actual
console.log(supervisorPedidosMonitor.getSummary())

// Enviar reporte al servidor
supervisorPedidosMonitor.reportToServer()

// Marcar punto personalizado
supervisorPedidosMonitor.mark('MI_EVENTO_CUSTOM', { 
  datos: 'adicionales' 
})
```

---

## 📈 MÉTRICAS CLAVE A REVISAR

### Backend
```
USE_CASE_EXECUTED: < 200ms es bueno
                   > 500ms necesita optimización

DATA_MAPPED:       < 50ms es bueno
                   > 200ms hay problema
```

### Frontend
```
DOM_CONTENT_LOADED: < 3000ms es excelente
                    < 5000ms es aceptable
                    > 8000ms necesita optimización

First Contentful Paint: < 2500ms es excelente
                        < 4000ms es aceptable

TABLE_RENDER_COMPLETE: < 100ms es excelente
                       < 500ms es aceptable
```

### API Calls
```
/api/cartera/pedidos: < 200ms es excelente
                      < 500ms es aceptable
                      > 1000ms necesita optimización
```

---

## 🔧 ANALIZAR PERFORMANCE

### Caso 1: Carga lenta de API
```
Si TABLE_API_RESPONSE_RECEIVED toma > 500ms:

1. Ver logs backend: grep "USE_CASE_EXECUTED" laravel.log
2. ¿Cuánto tarda la query?
3. ¿Hay mucha transformación de datos?
4. ¿Se está cargando demasiadas relaciones?

Solución:
- Optimizar query (indexes, eager loading)
- Reducir datos retornados
- Caché de resultados
```

### Caso 2: Rendering lento
```
Si TABLE_RENDER_COMPLETE - TABLE_RENDER_START > 200ms:

1. Cuántas rows se están renderizando?
   perfMonitor.getSummary().marks
   → buscar "TABLE_RENDER_START"

2. ¿Es porque hay muchas rows?
   Si rows > 50, considerar paginación o virtualización

3. ¿Es JavaScript lento?
   Abrir DevTools → Performance → grabar → reproducir

Solución:
- Limitar rows por página
- Virtualización de tabla
- Optimizar renderizado
```

### Caso 3: Demora general
```
Si DOM_CONTENT_LOADED > 5000ms:

Desglose típico:
DNS: ~15ms (no se puede mejorar mucho)
TCP: ~24ms (depende del servidor)
Request: ~45ms (esperar respuesta del servidor)
Response: ~67ms (tiempo que tarda el servidor)
DOM Parse: ~234ms (parsing del HTML)
Resource Load: puede ser grande si hay muchos assets

Acciones:
1. Verificar qué toma más tiempo en Navigation Timing
2. Si "Response" es muy alto → problema backend
3. Si "Resource Load" es alto → hay assets sin lazy-load
```

---

## 📊 DASHBOARD RÁPIDO

Copia esto en la consola para un dashboard:

```javascript
function perfDashboard() {
    const perf = supervisorPedidosMonitor.getSummary();
    
    console.table({
        'Total Time': perf.totalTime,
        'Session': perf.sessionId,
        'Marks': perf.marks.length,
        'API Calls': perf.apiCalls.length,
    });
    
    console.group('Navigation Timing');
    console.table(perf.navigationTiming);
    console.groupEnd();
    
    console.group('Slowest Marks');
    const slowest = perf.marks
        .sort((a, b) => parseFloat(b.delta) - parseFloat(a.delta))
        .slice(0, 5);
    console.table(slowest);
    console.groupEnd();
}

perfDashboard()
```

---

## 🚀 PRÓXIMOS PASOS

### Para Mejorar Performance:

1. **Identificar cuellos de botella**
   - Ejecutar `perfMonitor.report()` en la consola
   - Ver qué toma más tiempo

2. **Optimizar backend**
   - Si USE_CASE_EXECUTED > 500ms:
     - Agregar índices a BD
     - Usar eager loading
     - Caché resultados

3. **Optimizar frontend**
   - Si TABLE_RENDER_COMPLETE > 200ms:
     - Limitar rows visibles
     - Virtualización de tabla
     - Lazy-load de datos

4. **Monitorear en producción**
   - Los reportes se envían a `/api/performance/log`
   - Crear endpoint para guardar reportes
   - Analizar tendencias

---

## 📝 NOTAS

- Los logs van en color para fácil identificación
- 🟢 Verde: < 500ms
- 🟠 Naranja: 500-2000ms
- 🔴 Rojo: > 2000ms

- El monitor NO está habilitado en producción por defecto
- Para habilitarlo, cambiar `config('app.debug')` en el controlador
