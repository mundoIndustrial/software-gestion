# Phase 4 - Unified Services (WebSocketClient + CacheRepository)

## Status: ✅ COMPLETADO

## Cambios Realizados

### 1. Abstract Interfaces

#### WebSocketClient (`public/js/shared/WebSocketClient.js`)
- Abstract interface para todas las operaciones de WebSocket/real-time
- Métodos: subscribe(), unsubscribe(), private(), join(), isConnected(), whisper()

#### CacheRepository (`public/js/shared/CacheRepository.js`)
- Abstract interface para cacheo centralizado  
- Métodos: get(), set(), has(), remove(), clear(), getOrFetch()

###  2. Concrete Implementations

#### EchoReverbWebSocketClient (`public/js/shared/infrastructure/EchoReverbWebSocketClient.js`)
- Implementación de WebSocketClient usando Laravel Echo + Reverb
- Wrappea todas las operaciones de Echo en interfaz limpia
- Error handling: WebSocketError custom

#### SessionStorageCacheRepository (`public/js/shared/infrastructure/SessionStorageCacheRepository.js`)
- Implementación de CacheRepository usando sessionStorage
- Características:
  - TTL automático (expiry timestamps)
  - Garbage collection periódica (cada 5 min)
  - Fallback a localStorage si sessionStorage no disponible
  - Fallback a in-memory Map si ambos indisponibles
  - Cuota de almacenamiento (5MB default, configurable)

### 3. Shared Bootstrap Updated
- `public/js/shared/bootstrap.js` v1.1.0
- Ahora expone: `window.shared.cache` + `window.shared.websocket` (lazy)
- WebSocketClient inicializa bajo demanda en primer acceso (evita race conditions con Echo)

### 4. Build Script Updated
- Bundles ahora incluyen 7 archivos shared:
  - HttpClient, NotificationService, ModalManager
  - WebSocketClient, CacheRepository (interfaces)
  - EchoReverbWebSocketClient, SessionStorageCacheRepository (implementations)
  - Bootstrap

## Bundle Size
- `shared-core.js`: 14.1 KB → 34.4 KB (dev), 5.4 KB → 12.2 KB (min)
- Incremento debido a nuevas abstracciones, aún comprimido en 64%
- `sp-core.js`: Sin cambios (20.6 KB dev, 8.0 KB min)

## Uso - WebSocketClient (19 archivos realtime actualizables)

### Patrón Anterior (repetido en 19 archivos)
```js
if (!window.Echo) {
    console.error('Echo no disponible');
    return;
}
window.Echo.channel('supervisor-pedidos').listen('OrdenUpdated', (data) => {
    // ... handler ...
});
```

### Patrón Nuevo Recomendado
```js
// Opción 1: Esperar a que WebSocket esté listo
window.waitForEcho(() => {
    window.shared.websocket.subscribe('supervisor-pedidos', 'OrdenUpdated', (data) => {
        // ... handler ...
    });
});

// Opción 2: Directo (lanzará error si Echo no está listo)
try {
    const ws = window.shared.websocket;
    ws.subscribe('supervisor-pedidos', 'OrdenUpdated', (data) => {
        // ... handler ...
    });
} catch (error) {
    console.error('WebSocket no disponible:', error.message);
}

// Opción 3: Verificar conexión antes de usar
if (window.shared.websocket?.isConnected()) {
    window.shared.websocket.subscribe(...);
}
```

## Uso - CacheRepository

### Patrón Anterior (repeat en ~5 archivos insumos, órdenes)
```js
const ttl = 30 * 60 * 1000; // 30 min
const key = `insumos_${pedidoId}`;
const existingJson = sessionStorage.getItem(key);
let data = existingJson ? JSON.parse(existingJson) : null;
// ... check expiry, etc ...
```

### Patrón Nuevo
```js
const cache = window.shared.cache;

// Guardar
cache.set('insumos_' + pedidoId, { /* data */ }, 30 * 60 * 1000);

// Recuperar
const data = cache.get('insumos_' + pedidoId);

// Con fallback a API
const data = await cache.getOrFetch(
    'insumos_' + pedidoId,
    () => fetch(`/api/insumos/${pedidoId}`).then(r => r.json()),
    30 * 60 * 1000  // 30 min TTL
);

// Limpiar cuando apropiado
cache.remove('insumos_' + pedidoId);
```

## Archivos Listos para Refactor (19 realtime candidatos)

| Prioridad | Archivo | Razón |
|---|---|---|
| 🔴 ALTA | public/js/supervisor-pedidos/realtime-supervisor.js | Core, uso frecuente |
| 🔴 ALTA | public/js/modulos/asesores/pedidos-realtime.js | Complejo, PedidosRealtimeRefresh |
| 🟡 MEDIA | public/js/insumos/notifications-realtime-insumos.js | Notifications, usa window.initializeRealtimeListener() |
| 🟡 MEDIA | public/js/ordersjs/realtime-listeners.js | Orders updates |
| 🟢 BAJA  | public/js/realtime-cotizaciones.js | Quotes (lower traffic) |
| 🟢 BAJA  | public/js/cartera-pedidos/cartera_pedidos.js | Cartera (existing logic) |

## Próximos Pasos (Fase 5)

1. **Refactor realtime-supervisor.js**
   - Replace Echo.channel() with window.shared.websocket.subscribe()
   - Test notifications y table updates

2. **Refactor insumos notifications** 
   - Use WebSocketClient
   - Simplify initialization

3. **Refactor all 19 realtime files**
   - Gradual migration (1-2 files por semana)
   - Test cada refactor con QA

4. **Consider SessionStorageInsumoRepository deprecation**
   - Move insumos cache to generic SessionStorageCacheRepository
   - Simplify code, shared pattern

## Error Handling

```js
// WebSocketError
try {
    window.shared.websocket.subscribe('channel', 'event', handler);
} catch (error) {
    if (error instanceof WebSocketError) {
        console.error('WebSocket error:', error.message);
        console.error('Original cause:', error.originalError);
    }
}

// CacheError  
try {
    window.shared.cache.set('big_data', largeObject);
} catch (error) {
    if (error instanceof CacheError) {
        console.warn('Cache error:', error.message);
        // Fallback to direct API call
    }
}
```

## Summary

✅ **Shared infrastructure now unifies**:
- HTTP requests (SharedHttpClient - Phase 1)
- Notifications (SharedNotification - Phase 1)
- Modal management (SharedModal - Phase 1)
- **Real-time WebSocket (WebSocketClient - Phase 4)** ← NEW
- **Caching/Storage (CacheRepository - Phase 4)** ← NEW

All exposed via `window.shared`, no global pollution, single source of truth.
