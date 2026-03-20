# FASE 5: REFACTORIZACIÓN ARCHIVOS REAL-TIME
## Estado de Progreso - Sesión Actual

**Inicialización**: Sistema listo para refactorizar 11 archivos real-time  
**Objetivo**: Migrar todos los archivos de `window.EchoInstance` → `window.shared.websocket`  
**Meta**: 61% reducción de código duplicado en WebSocket handling  

---

## 📊 INVENTARIO COMPLETO: 11 Archivos Real-time

### ✅ COMPLETADO (1 archivo)

| # | Archivo | Líneas | Estado | Cambios |
|---|---------|--------|--------|---------|
| 1 | **realtime-supervisor.js** | 370 | ✅ DONE | Echo.channel → shared.websocket.subscribe |

**Ubicación**: `public/js/supervisor-pedidos/realtime-supervisor.js`  
**Refactorización**: Completada sesión anterior  
**Validación**: ✅ Bundle reconstruido, sin breaking changes  

---

### 🔄 EN ANÁLISIS (1 archivo)

| # | Archivo | Líneas | Complejidad | Prioridad | Bloqueante |
|---|---------|--------|-------------|-----------|-----------|
| 2 | **pedidos-realtime.js** | 967 | 🔴 CRÍTICA | ⭐ MÁXIMA | Sí (2 páginas) |

**Ubicación**: `public/js/modulos/asesores/pedidos-realtime.js`  
**Impacto**: Afecta `/supervisor-pedidos` + `/cartera/pedidos` (2 páginas críticas)  
**Estado Análisis**: 
- ✅ Líneas 1-100: Clase + constructor + init()
- ✅ Líneas 100-300: setupActivityDetection + setupVisibilityDetection + setupEchoConnection
- ✅ Líneas 300-500: handlePedidoUpdate + hashandlePedidoEstado + polling start
- ✅ Líneas 500-800: pause/stop/destroy + detect changes + polling loop
- ✅ Líneas 800-967: Table updates (actualizarTabla, actualizarFila, etc.)

**Cambios Requeridos**:
1. Líneas 250-450: setupEchoConnection() - Refactorización CRÍTICA
2. Líneas 600-800: Polling fallback - Refactorización IMPORTANTE
3. Líneas 830-850: destroy() - Refactorización MENOR

**Documentación**: [`PLAN_REFACTORIZACION_PEDIDOS_REALTIME.md`](./PLAN_REFACTORIZACION_PEDIDOS_REALTIME.md) (guía completa)

---

### 📋 PENDIENTE (9 archivos)

| # | Archivo | Líneas | Complejidad | Prioridad | Estado |
|---|---------|--------|-------------|-----------|--------|
| 3 | notifications-realtime-insumos.js | ~400 | 🟡 MEDIA | ⭐⭐ | `📋 PENDING` |
| 4 | realtime-listeners.js | ~300 | 🟡 MEDIA | ⭐⭐ | `📋 PENDING` |
| 5 | realtime-cotizaciones.js | ~150 | 🟢 BAJA | ⭐ | `📋 PENDING` |
| 6 | realtime-cotizaciones-simple.js | ~100 | 🟢 BAJA | ⭐ | `📋 PENDING` |
| 7 | bodega-realtime.js | ~220 | 🟡 MEDIA | ⭐⭐ | `📋 PENDING` |
| 8 | notifications-realtime.js | ~150 | 🟢 BAJA | ⭐ | `📋 PENDING` |
| 9 | registros-por-orden-realtime.js | ~200 | 🟡 MEDIA | ⭐⭐ | `📋 PENDING` |
| 10 | realtime.js (operario/dashboard) | ~250 | 🟡 MEDIA | ⭐⭐ | `📋 PENDING` |
| 11 | realtime-debug.js | ~180 | 🟢 BAJA | ⭐ | `📋 PENDING` |

**Total Líneas Pendientes**: ~1,950 líneas  
**Código Duplicado Esperado**: ~500-600 líneas (reducibles con refactor)  

---

## 🎯 PATRÓN REFACTORIZACIÓN ESTÁNDAR

Todos los archivos siguen este patrón de transformación:

### Patrón 1: Validación de Disponibilidad
```javascript
// ANTES
if (!window.EchoInstance) {
    console.warn('Echo no disponible');
    return;
}

// DESPUÉS
if (typeof window.waitForEcho !== 'function') {
    console.warn('Echo no disponible aún, reintentando...');
    setTimeout(() => this.init(), 100);
    return;
}

window.waitForEcho(() => {
    // Setup con garantía de que Echo está listo
});
```

### Patrón 2: Suscripción Simple
```javascript
// ANTES
window.EchoInstance.channel('mi-canal')
    .listen('.mi-evento', handler)
    .on('error', fallbackHandler);

// DESPUÉS
const ws = window.shared.websocket;
ws.subscribe('mi-canal', '.mi-evento', handler);
// Error handling: try/catch o startPollingFallback()
```

### Patrón 3: Múltiples Canales
```javascript
// ANTES
this.channel1 = window.EchoInstance.channel('ch1').listen('ev1', h1);
this.channel2 = window.EchoInstance.channel('ch2').listen('ev2', h2);
this.channels = [this.channel1, this.channel2];

// DESPUÉS
const ws = window.shared.websocket;
ws.subscribe('ch1', 'ev1', h1);
ws.subscribe('ch2', 'ev2', h2);
// No necesita almacenarlas - abstraído en ws
```

### Patrón 4: Polling Fallback
```javascript
// ANTES
setInterval(() => {
    fetch('/api/data').then(r => r.json()).then(doSomething);
}, 5000);

// DESPUÉS
setInterval(() => {
    window.shared.cache.getOrFetch('api-data', async () => {
        const r = await fetch('/api/data');
        return r.json();
    }, 5000).then(doSomething);
}, 5000);
```

---

## 📈 MÉTRICAS ESPERADAS POST-REFACTOR

### Reducción de Código Duplicado
- **Antes**: 11 archivos × ~150 líneas promedio de Echo setup = ~1,650 líneas repetidas
- **Después**: 11 archivos × ~30 líneas promedio (centralizado en abstracciones) = ~330 líneas
- **Reducción**: 80% en código duplicado de WebSocket

### Mantenibilidad
- **Antes**: 11 lugares diferentes donde cambiar si cambia Echo
- **Después**: 1 lugar (EchoReverbWebSocketClient.js)
- **Mejora**: 11x más fácil de mantener cambios

### Performance
- **Mismo** (Echo sigue siendo el transport subyacente)
- Bundling + minificación: 2.3 KB guardados por archivo (~25 KB totales)

---

## 📝 DOCUMENTACIÓN CREADA ESTA SESIÓN

### 1. **FASE_5_REFACTORING_REALTIME.md** (Este Documento General)
- Resumen de todos los 11 archivos
- Patrones de refactorización
- Matriz de prioridades
- Validación post-refactor

### 2. **PLAN_REFACTORIZACION_PEDIDOS_REALTIME.md** (Guía Específica)
- Análisis línea por línea de pedidos-realtime.js (967 líneas)
- 9 secciones identificadas
- 4 cambios críticos documentados
- Plan de 5 pasos: Validación → setupEchoConnection → Polling → destroy → Testing

---

## 🔗 DEPENDENCIAS INTERNAS

Todos estos archivos dependen de:

```javascript
// Phase 4 Abstractions (Ya implementadas)
window.shared.websocket        // EchoReverbWebSocketClient - para subscribe/listen
window.shared.cache            // SessionStorageCacheRepository - para polling fallback
window.shared.notify           // SharedNotification - para toasts
window.shared.http             // SharedHttpClient - para AJAX

// Helpers
window.waitForEcho()           // Espera a que Echo esté inicializado
```

---

## ✅ CHECKLIST DE LA SESIÓN

- [x] ✅ Analizar pedidos-realtime.js (967 líneas)
- [x] ✅ Identificar todos los 11 archivos real-time en workspace
- [x] ✅ Crear documento FASE_5_REFACTORING_REALTIME.md (información general)
- [x] ✅ Crear documento PLAN_REFACTORIZACION_PEDIDOS_REALTIME.md (guía específica)
- [x] ✅ Documentar 4 patrones de refactorización estándar
- [x] ✅ Crear matriz de prioridades y timeline
- [ ] ⏳ Refactorizar pedidos-realtime.js (SIGUIENTE)
- [ ] ⏳ Refactorizar notifications-realtime-insumos.js
- [ ] ⏳ Refactorizar remaining 9 archivos

---

## 🚀 PLAN PARA PRÓXIMA SESIÓN

### Parte 1: Refactorizar pedidos-realtime.js (45 min)
1. Backup actual
2. Refactorizar setupEchoConnection() (líneas 250-450)
3. Refactorizar polling fallback (líneas 600-800)
4. Refactorizar destroy() method (líneas 830-850)
5. Test en dev y prod

### Parte 2: Refactorizar 2-3 archivos MEDIA (45 min)
1. notifications-realtime-insumos.js (400 líneas)
2. realtime-listeners.js (300 líneas)
3. bodega-realtime.js (220 líneas)

### Parte 3: Refactorizar archivos BAJA (30 min)
1. realtime-cotizaciones*.js (150+100 líneas)
2. registros-por-orden-realtime.js (200 líneas)

---

## 📊 PROGRESO ACUMULATIVO

```
Fase 1-2: Infrastructure + DDD Core    ████████░░  85% ✅ DONE
Fase 3:   Bundling + Minification      ██████████  100% ✅ DONE
Fase 4:   WebSocket + Cache Abstracts  ██████████  100% ✅ DONE
Fase 5:   Real-time Files Refactor     █░░░░░░░░   10% (1/11 DONE)
Fase 6:   Simplify Insumos Cache       ░░░░░░░░░░  0%  (PENDING)
Fase 7:   TypeScript Migration         ░░░░░░░░░░  0%  (OPTIONAL)
```

**Estimación Total**: 120-150 horas trabajo (25-30% completado hasta ahora)

---

## 💾 COMANDOS ÚTILES

```bash
# Ver líneas en un archivo
(Get-Content "ruta/archivo.js" | Measure-Object -Line).Lines

# Grep para encontrar patrones
grep -r "window.EchoInstance\|window.Echo.channel" public/js/

# Build bundles después de refactor
npm run build:core

# Watch para dev
npm run build:core:watch

# Verificar bundles incluyen cambios
npm run build:core && stat public/js/bundles/shared-core.min.js
```

---

**Documento Generado**: 2026-Mar-15  
**Estado**: 📋 DOCUMENTACIÓN COMPLETA  
**Próximo Estado**: 🔄 Refactorización pedidos-realtime.js  
