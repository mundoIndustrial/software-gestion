# Fase 5: Refactorización de Archivos Real-time

## Status: 🔄 EN PROGRESO

### ✅ Completado (1 archivo)

**1. realtime-supervisor.js** ✅
- Refactorización: `window.EchoInstance.channel()` → `window.shared.websocket.subscribe()`
- Líneas antes: ~370
- Cambios principales:
  - Extrae `const ws = window.shared.websocket` en lugar de `window.EchoInstance`
  - Sustituye 3 `echo.channel().listen()` por 3 `ws.subscribe()` calls
  - Mejor separación: `_subscribeToChannels()`, `_setupCustomEventHandlers()`, `_refreshTablaConDelay()`
  - Error handling per-channel integrado

**Patrón Aplicado:**
```javascript
// ANTES
window.EchoInstance.channel('despacho.pedidos')
    .listen('.pedido.actualizado', (data) => refreshTabla(data, 'despacho.pedidos:.pedido.actualizado'))
    .on('pusher:subscription_succeeded', () => {
        console.log('✅ Subscripción exitosa al canal despacho.pedidos');
    })
    .on('pusher:subscription_error', (error) => {
        console.error('❌ Error en subscripción despacho.pedidos:', error);
    });

// DESPUÉS
ws.subscribe('despacho.pedidos', '.pedido.actualizado', (data) => {
    _refreshTablaConDelay(data, 'despacho.pedidos:.pedido.actualizado');
});
// Error handling simplificado con try/catch
```

---

## 📋 Archivos Pendientes (18 restantes)

| # | Archivo | Líneas | Complejidad | Prioridad | Patrón |
|---|---------|--------|-------------|-----------|--------|
| 1 | pedidos-realtime.js | 967 | 🔴 ALTA | ALTA | Singleton + múltiples canales |
| 2 | insumos/notifications-realtime-insumos.js | ~400 | 🟡 MEDIA | MEDIA | Simple subscribe + notify |
| 3 | ordersjs/realtime-listeners.js | ~300 | 🟡 MEDIA | MEDIA | Event listeners |
| 4 | realtime-cotizaciones.js | ~150 | 🟢 BAJA | BAJA | Simple channel |
| 5 | cartera-pedidos/cartera_pedidos.js | ~200 | 🟡 MEDIA | MEDIA | Mixed logic |
| 6 | despacho/realtime-packings.js | ~180 | 🟢 BAJA | BAJA | Table refresh |
| 7 | bodega/realtime-bodega.js | ~220 | 🟡 MEDIA | MEDIA | State + table |
| 8 | ... | ... | ... | ... | ... |

---

## 🔧 Guía de Refactorización: Patrones Clave

### Patrón 1: Simple Subscribe (para archivos pequeños)

**Antes:**
```javascript
if (!window.Echo) return;
window.Echo.channel('mi-canal')
    .listen('.mi-evento', (data) => {
        // handler
    });
```

**Después:**
```javascript
if (typeof window.waitForEcho === 'function') {
    window.waitForEcho(() => {
        try {
            const ws = window.shared.websocket;
            ws.subscribe('mi-canal', '.mi-evento', (data) => {
                // handler
            });
        } catch (error) {
            console.error('WebSocket error:', error.message);
        }
    });
}
```

### Patrón 2: Múltiples Canales (como pedidos-realtime.js)

**Antes:**
```javascript
class MyRealtime {
    setupEchoConnection() {
        this.channel1 = window.EchoInstance.channel('ch1').listen('ev1', h1);
        this.channel2 = window.EchoInstance.channel('ch2').listen('ev2', h2);
        this.channel3 = window.EchoInstance.private('ch3').listen('ev3', h3);
    }
}
```

**Después:**
```javascript
class MyRealtime {
    setupEchoConnection() {
        if (typeof window.waitForEcho !== 'function') {
            setTimeout(() => this.setupEchoConnection(), 100);
            return;
        }

        window.waitForEcho(() => {
            try {
                const ws = window.shared.websocket;
                
                // Public channels
                ws.subscribe('ch1', 'ev1', (data) => this.handle1(data));
                ws.subscribe('ch2', 'ev2', (data) => this.handle2(data));
                
                // Private channel
                ws.private('ch3').listen('ev3', (data) => this.handle3(data));
                
            } catch (error) {
                console.error('WebSocket setup error:', error.message);
            }
        });
    }
}
```

### Patrón 3: Presence Channels (si aplica)

**Antes:**
```javascript
window.EchoInstance.join('presence-team')
    .here(users => console.log('Users:', users))
    .joining(user => console.log('Joined:', user))
    .leaving(user => console.log('Left:', user));
```

**Después:**
```javascript
const presenceChannel = window.shared.websocket.join('presence-team');
presenceChannel.listen('.Joined', (user) => {
    console.log('User joined:', user);
});
// O: presenceChannel.here(users => {...})
```

---

## 📌 Notas Importantes

1. **Validación Estricta**
   - Siempre validar `window.waitForEcho` y `window.shared.websocket`
   - NO usar fallbacks - los errores deben ser claros
   
2. **Error Handling**
   - Cada `subscribe()` puede lanzar `WebSocketError`
   - Usar try/catch para capturar

3. **Lazy Initialization**
   - `window.shared.websocket` se inicializa en el primer acceso
   - `window.waitForEcho()` asegura que Echo esté listo

4. **Backwards Compatibility**
   - Los archivos refactorizados siguen soportando `window.Echo` si es necesario
   - Gradual migration: refactor 2-3 archivos por sesión

---

## ✅ Checklist de Validación Post-Refactor

Para cada archivo refactorizado:

- [ ] ✅ Todas las suscripciones usan `window.shared.websocket.subscribe()`
- [ ] ✅ No quedan `window.EchoInstance` o `window.Echo.channel()` directo
- [ ] ✅ Error handling con try/catch
- [ ] ✅ Validación `window.waitForEcho` al inicio
- [ ] ✅ No hay `window.Echo` fallbacks
- [ ] ✅ Funciona en dev + prod (bundles minificados)
- [ ] ✅ QA test: real-time eventos llegan correctamente
- [ ] ✅ QA test: fallos de WebSocket se manejan gracefully

---

## 🚀 Plan de Ejecución (Siguientes Sesiones)

**Sesión actual (Fase 5 - Parte 1)**:
- ✅ Refactorizado: realtime-supervisor.js
- 📝 Documentado: Esta guía
- ⏭️ Siguiente: pedidos-realtime.js (si hay tiempo)

**Sesión V2 (Fase 5 - Parte 2)**:
- Refactorizar: pedidos-realtime.js (secciones 1-5)
- Refactorizar: insumos/notifications-realtime-insumos.js
- Test integración

**Sesión V3 (Fase 5 - Parte 3)**:
- Refactorizar: ordersjs/realtime-listeners.js
- Refactorizar: realtime-cotizaciones.js
- Refactorizar: cartera-pedidos/

**Sesión V4 (Fase 5 - Final)**:
- Refactorizar: bodega/, despacho/, asistencia/, visualizador/
- Test completo de toda la arquitectura
- QA validación

---

## 📊 Métricas Post-Refactor

Esperados:
- **Código duplicado reducido**: 40-50% en manejo de WebSocket
- **Líneas de código removidas**: ~200-300 (fallbacks, error handlers duplicados)
- **Mantenibilidad**: +60% (patrón centralizado)
- **Performance: Sin cambios (mismo Echo por debajo)
