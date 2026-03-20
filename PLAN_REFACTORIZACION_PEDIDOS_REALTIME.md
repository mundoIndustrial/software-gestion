# Refactorización: pedidos-realtime.js (967 líneas)

**Archivo**: `public/js/modulos/asesores/pedidos-realtime.js`  
**Complejidad**: 🔴 ALTA  
**Prioridad**: ⭐ CRÍTICA  
**Impacto**: Afecta `/supervisor-pedidos` y `/cartera/pedidos` (2 páginas críticas)

---

## 📊 Estructura Actual (967 líneas)

### Sección 1: Definición de Clase (Líneas 1-50)
```
- Clase: PedidosRealtimeRefresh (Singleton)
- Propiedades: pedidoMovido, ultimoEstadoPedidos, refreshDebounce, ...
- Constructor: inicializa propiedades
- Getter: getInstance() - Patrón Singleton
```

**Acción de Refactor**: Minimal - solo comentarios, sin cambios de lógica

---

### Sección 2: Inicialización (Líneas 50-120)
```
- Método: init()
  - Detecta página actual (/supervisor-pedidos vs /cartera/pedidos vs /cartera/*)
  - Decide si activar realtime (NO para cartera excepto /cartera/pedidos)
  - Configura listeners de actividad
  - Configura listeners de visibilidad
  - Llama a setupEchoConnection()
```

**Acción de Refactor**: 
- Agregar validación `if (typeof window.waitForEcho !== 'function')` 
- Encapsular `setupEchoConnection()` call en try/catch

---

### Sección 3: Detección de Actividad (Líneas 120-200)
```
- Método: setupActivityDetection()
  - Listeners: mousedown, mousemove, keypress, scroll, click
  - Propósito: Detectar si el usuario está activo
  - Actualiza: this.usuarioActivo = true/false
```

**Acción de Refactor**: ✅ NO CAMBIAR - Es UI concern, no WebSocket

---

### Sección 4: Detección de Visibilidad (Líneas 200-250)
```
- Método: setupVisibilityDetection()
  - Listeners: visibilitychange, focus, blur
  - Propósito: Saber si tab está visible
  - Actualiza: this.paginaVisible = true/false
```

**Acción de Refactor**: ✅ NO CAMBIAR - Es UI concern, no WebSocket

---

### Sección 5: Conexión Echo (Líneas 250-450) ⚠️ CRÍTICA
```
- Método: setupEchoConnection()
  - Validación: window.EchoInstance debe existir
  - 3 suscripciones públicas:
    1. 'despacho.pedidos' -> '.pedido.actualizado' -> handlePedidoUpdate()
    2. 'supervisor-pedidos' -> '.pedido.estado' -> handlePedidoEstado()
    3. 'pedidos.creados' -> '.pedido.creado' -> handlePedidoCreado()
  - 1 suscripción privada:
    4. `private('orders.${userId}')` -> '.pedido.actualizado' -> handlePedidoUpdate()
  - Error handling: .on('error') callbacks para cada canal
```

**Acción de Refactor** 🔴 CRÍTICA:
```javascript
// ANTES (lines 250-350)
setupEchoConnection() {
    if (!window.EchoInstance) return;
    
    const userId = document.body.getAttribute('data-user-id');
    
    this.channel1 = window.EchoInstance.channel('despacho.pedidos')
        .listen('.pedido.actualizado', (data) => this.handlePedidoUpdate(data))
        .on('error', (error) => this.startPollingFallback());
    
    this.channel2 = window.EchoInstance.channel('supervisor-pedidos')
        .listen('.pedido.estado', (data) => this.handlePedidoEstado(data))
        .on('error', (error) => this.startPollingFallback());
    
    this.channel3 = window.EchoInstance.channel('pedidos.creados')
        .listen('.pedido.creado', (data) => this.handlePedidoCreado(data))
        .on('error', (error) => this.startPollingFallback());
    
    this.privateChannel = window.EchoInstance.private(`orders.${userId}`)
        .listen('.pedido.actualizado', (data) => this.handlePedidoUpdate(data))
        .on('error', (error) => this.startPollingFallback());
}

// DESPUÉS
setupEchoConnection() {
    if (typeof window.waitForEcho !== 'function') {
        setTimeout(() => this.setupEchoConnection(), 100);
        return;
    }
    
    window.waitForEcho(() => {
        try {
            const ws = window.shared.websocket;
            const userId = document.body.getAttribute('data-user-id');
            
            // Public channel subscriptions
            ws.subscribe('despacho.pedidos', '.pedido.actualizado', (data) => {
                this.handlePedidoUpdate(data);
            });
            
            ws.subscribe('supervisor-pedidos', '.pedido.estado', (data) => {
                this.handlePedidoEstado(data);
            });
            
            ws.subscribe('pedidos.creados', '.pedido.creado', (data) => {
                this.handlePedidoCreado(data);
            });
            
            // Private channel
            ws.private(`orders.${userId}`).listen('.pedido.actualizado', (data) => {
                this.handlePedidoUpdate(data);
            });
            
        } catch (error) {
            console.error('[PedidosRealtime] WebSocket setup failed:', error.message);
            this.startPollingFallback(); // Fallback a polling
        }
    });
}
```

---

### Sección 6: Manejo de Eventos de Actualización (Líneas 450-600)
```
- Método: handlePedidoUpdate(data)
  - Lee: data.pedido_id, data.estado, data.proceso
  - Llama: actualizarPedidoIndividual()
- Método: handlePedidoEstado(data)
  - Lee: data.id, data.estado
  - Llama: actualizarPedidoIndividual()
- Método: handlePedidoCreado(data)
  - Llama: agregarFilaNueva()
  - Llama: moverPedidoAlInicio()
```

**Acción de Refactor**: ✅ NO CAMBIAR - Lógica de business, no WebSocket

---

### Sección 7: Polling Fallback (Líneas 600-800) ⚠️ IMPORTANTE
```
- Método: startPollingFallback()
  - Inicia polling cada 5 segundos
  - Usa: checkForChanges()
- Método: checkForChanges()
  - Fetch: GET /api/pedidos-estado
  - Compara estado actual vs anterior
  - Si hay cambios: actualizarTabla()
- Método: detectarCambios(datosPrevios, datosActuales)
  - Compara arrays
  - Retorna array de pedidos modificados
- Método: guardarEstadoPedidos(pedidos)
  - Guarda en this.ultimoEstadoPedidos
```

**Acción de Refactor** 🟡 IMPORTANTE:
- Reemplazar custom polling fetch loop por `window.shared.cache.getOrFetch()`
- TTL: 5 segundos (mismo que polling actual)
- Mantener lógica de detectarCambios

```javascript
// ANTES (líneas ~650-750)
startPollingFallback() {
    console.log('[PedidosRealtime] ⚠️ Iniciando polling fallback cada 5 segundos');
    this.pollInterval = setInterval(() => {
        this.checkForChanges();
    }, 5000);
}

checkForChanges() {
    if (this.pausedSuspend) return;
    
    fetch('/api/pedidos-estado')
        .then(response => response.json())
        .then(data => {
            const cambios = this.detectarCambios(this.ultimoEstadoPedidos, data.pedidos);
            if (cambios.length > 0) {
                this.guardarEstadoPedidos(data.pedidos);
                this.actualizarTabla(cambios);
            }
        })
        .catch(error => console.error('[PedidosRealtime] Polling error:', error));
}

// DESPUÉS
startPollingFallback() {
    console.log('[PedidosRealtime] ⚠️ Iniciando polling fallback cada 5 segundos');
    this.pollInterval = setInterval(() => {
        this.checkForChanges();
    }, 5000);
}

checkForChanges() {
    if (this.pausedSuspend) return;
    
    // Use shared cache with 5-second TTL
    const cache = window.shared.cache;
    cache.getOrFetch('pedidos-estado', async () => {
        const response = await fetch('/api/pedidos-estado');
        if (!response.ok) throw new Error('API error');
        return response.json();
    }, 5000) // 5 segundos TTL para polling
        .then(data => {
            const cambios = this.detectarCambios(this.ultimoEstadoPedidos, data.pedidos || []);
            if (cambios.length > 0) {
                this.guardarEstadoPedidos(data.pedidos);
                this.actualizarTabla(cambios);
            }
        })
        .catch(error => console.error('[PedidosRealtime] Polling error:', error.message));
}
```

---

### Sección 8: Métodos de Control (Líneas 800-850)
```
- Método: pause()
  - Detiene polling sin destruir
  - Actualiza: this.pausedSuspend = true
- Método: stop()
  - Desuscribe Echo channels
  - Detiene polling
- Método: destroy()
  - Limpia esto
  - Elimina listeners de actividad
  - Elimina listeners de visibilidad
- Método: getStatus()
  - Retorna objeto con estado actual
```

**Acción de Refactor**: 
- Reemplazar `this.channel1.unsubscribe()` por cleanup en destructor
- Mantener lógica de stop/pause

---

### Sección 9: Actualización de Tabla (Líneas 850-967)
```
- Método: actualizarTabla(pedidosModificados)
  - Itera cada pedido modificado
  - Llama: actualizarFila()
- Método: actualizarFila(pedidoId, nuevoData)
  - Encuentra DOM element
  - Actualiza contenido HTML
  - Resalta con color
- Método: agregarFilaNueva(pedidoData)
  - Crea nuevo row HTML
  - Inserta en tabla
- Método: moverPedidoAlInicio(pedidoId)
  - Busca fila actual
  - Mueve después del header
```

**Acción de Refactor**: ✅ NO CAMBIAR - Lógica de DOM, no WebSocket

---

## 🎯 Plan de Refactorización Fase 5 - pedidos-realtime.js

### Paso 1: Validación Inicial (5 min)
```javascript
// Línea 1: Agregar comentario de versión
/**
 * PedidosRealtimeRefresh - Singleton para real-time updates
 * @version 2.0 (Phase 5: WebSocket abstraction)
 * @deprecated Reemplaza direct Echo.channel() por window.shared.websocket
 */

// Línea ~50 en init(): Agregar validación
if (typeof window.waitForEcho !== 'function') {
    console.warn('[PedidosRealtime] Esperando inicialización de Echo...');
    setTimeout(() => this.init(), 100);
    return;
}
```

### Paso 2: Refactorizar setupEchoConnection() (15 min)
**Cambio crítico**: Líneas 250-450
- Remover validación manual de window.EchoInstance
- Usar window.shared.websocket.subscribe()
- Envolver en try/catch
- Mantener error callback → this.startPollingFallback()

### Paso 3: Refactorizar Polling (10 min)
**Cambio importante**: Líneas 600-800
- Reemplazar fetch loop por window.shared.cache.getOrFetch()
- Mantener TTL de 5 segundos
- Mantener detectarCambios() lógica

### Paso 4: Actualizar destroy() (5 min)
**Cambio menor**: Líneas 830-850
- Remover this.channel1.unsubscribe() (ya no existe)
- Limpiar propiedades WebSocket si las hay

### Paso 5: Testing (15 min)
- [ ] ✅ Realtime updates en /supervisor-pedidos
- [ ] ✅ Realtime updates en /cartera/pedidos
- [ ] ✅ Polling fallback si WebSocket falla
- [ ] ✅ No ha warnings en console

---

## 📋 Cambios Resumen

| Aspecto | Antes | Después | Líneas |
|--------|-------|---------|--------|
| Suscripción | `window.EchoInstance.channel()` | `window.shared.websocket.subscribe()` | 250-450 |
| Error Handling | `.on('error')` callbacks | try/catch + startPollingFallback() | 250-450 |
| Polling Fetch | Raw fetch() loop | `window.shared.cache.getOrFetch()` | 600-800 |
| Validación Echo | Manual `if (!window.EchoInstance)` | `window.waitForEcho()` | 50, 250 |
| Total Líneas Cambiadas | ~250 líneas afectadas | ~250 líneas refactorizadas | |
| Líneas Eliminadas | ~50 (error handlers duplicados) | | |

---

## ✅ Validación Post-Refactor

**Antes de Confirmar**:
```javascript
// Verify no direct Echo usage remains
grep -n "window.EchoInstance\|window.Echo.channel\|\.listen(" pedidos-realtime.js
// Should return: 0 results (except in ws.subscribe/ws.private)

// Verify new patterns
grep -n "window.shared.websocket\|window.waitForEcho" pedidos-realtime.js
// Should return: 4-5 results

// Verify error handling
grep -n "catch\|try\|startPollingFallback" pedidos-realtime.js
// Should return: 2-3 try/catch blocks

// Verify cache usage
grep -n "window.shared.cache" pedidos-realtime.js
// Should return: 1-2 results in getOrFetch()
```

---

## 🔗 Dependencias

Este archivo depende de:
- ✅ `window.shared.websocket` - Phase 4 abstraction (será inicializado por primer acceso)
- ✅ `window.shared.cache` - Phase 4 abstraction (SessionStorageCacheRepository)
- ✅ `window.waitForEcho()` - Helper para esperar inicialización
- ✅ `window.EchoInstance` - Se mantiene como fallback (compatibility)

---

## 🚀 Próximos Pasos Después

Una vez completada refactorización:

1. **Test en Dev**
   - Abrir `/supervisor-pedidos`
   - Crear pedido desde otra ventana
   - Verificar que se actualiza en realtime (WebSocket)
   - Desactivar WebSocket (DevTools) → verificar polling fallback

2. **Test en Producción**
   - Compilar bundle: `npm run build:core`
   - Verificar realtime en cartera-pedidos también

3. **Phase 5 - Siguientes Archivos**
   - insumos/notifications-realtime-insumos.js (400 líneas, MEDIA)
   - ordersjs/realtime-listeners.js (300 líneas, MEDIA)

---

**Versión**: 2.0-Phase5  
**Fecha**: 2026-Mar  
**Estado**: 📋 Plan documentado, listo para implementación
