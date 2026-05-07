/**
 * Real-Time Table Refresh System - Laravel Echo + Reverb Integration
 * @version 2.1 (WebSocket directo sin abstracción)
 * 
 * Usa globalThis.waitForEcho() y globalThis.Echo directamente
 * Sin polling - solo WebSocket en tiempo real
 */

class PedidosRealtimeRefresh {
    static instance = null;
    
    constructor(options = {}) {
        // Patrón Singleton - evitar múltiples instancias
        if (PedidosRealtimeRefresh.instance) {
            return PedidosRealtimeRefresh.instance;
        }
        
        PedidosRealtimeRefresh.instance = this;
        
        // Configuración optimada
        this.checkInterval = options.checkInterval || 30000;
        this.autoStart = options.autoStart !== false;
        this.debug = options.debug || false;
        this.isRunning = false;
        this.lastChangeTime = null;
        this.pedidosAnterior = new Map();
        this.pedidoMovido = false;
        
        // Detección de página
        this.isCarteraPage = globalThis.location.pathname.includes('/cartera/pedidos');
        this.isAnyCarteraPage = globalThis.location.pathname.includes('/cartera/');
        this.isSupervisorPedidosPage = globalThis.location.pathname.includes('/supervisor-pedidos');
        this.isDespachoPage = globalThis.location.pathname.includes('/despacho');
        this.usingWebSockets = false;

        // No ejecutar realtime en páginas de cartera (excepto /cartera/pedidos)
        if (this.isAnyCarteraPage && !this.isCarteraPage) {
            console.log('[PedidosRealtime] Página de cartera detectada, desactivando realtime');
            this.isRunning = false;
            return;
        }
        
        this.init();
    }

    init() {
        // Validar que Echo esté disponible
        if (typeof globalThis.waitForEcho !== 'function') {
            if (this.debug) console.log('[PedidosRealtime] Esperando inicialización de Echo...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        if (this.debug) console.log('[PedidosRealtime] Sistema inicializado');
        
        // Configurar WebSocket
        this.setupWebSocket();
        
        if (this.autoStart) {
            this.start();
        }
    }

    /**
     * Configurar WebSocket usando Echo directamente
     */
    setupWebSocket() {
        if (typeof globalThis.waitForEcho !== 'function') {
            console.warn('[PedidosRealtime] Echo initializer not available, retrying');
            setTimeout(() => this.setupWebSocket(), 100);
            return;
        }

        console.log('[PedidosRealtime] Llamando a waitForEcho...');

        globalThis.waitForEcho((echo) => {
            console.log('[PedidosRealtime] ✅ Callback de waitForEcho ejecutado, echo:', typeof echo);
            
            try {
                if (!echo) {
                    throw new Error('Echo no disponible');
                }
                
                console.log('[PedidosRealtime] Echo está disponible, detectando página...');
                console.log('[PedidosRealtime] isDespachoPage:', this.isDespachoPage);
                console.log('[PedidosRealtime] isSupervisorPedidosPage:', this.isSupervisorPedidosPage);
                console.log('[PedidosRealtime] isCarteraPage:', this.isCarteraPage);
                
                // /supervisor-pedidos: escuchar eventos pero no recargar
                if (this.isSupervisorPedidosPage) {
                    console.log('[PedidosRealtime] 📍 Configurando supervisor-pedidos');

                    echo.channel('pedidos.general')
                        .listen('.pedido.actualizado', (event) => {
                            if (this.debug) console.log('[PedidosRealtime] Pedido actualizado (supervisor)');
                            try {
                                globalThis.dispatchEvent(new CustomEvent('supervisorPedidos:realtimePedidoActualizado', { 
                                    detail: { pedido: event?.pedido, source: 'pedidos.general' }
                                }));
                            } catch (e) {
                                console.warn('[PedidosRealtime] Event dispatch error:', e.message);
                            }
                        });

                    echo.channel('pedidos.creados')
                        .listen('.pedido.creado', (event) => {
                            if (this.debug) console.log('[PedidosRealtime] Pedido creado (supervisor)');
                            
                            // Filtrar: NO mostrar pedidos en estado pendiente_cartera al supervisor
                            if (event?.pedido?.estado === 'pendiente_cartera') {
                                console.log('[PedidosRealtime] ⏭️ Pedido omitido para supervisor (pendiente_cartera):', event?.pedido?.numero_pedido);
                                return;
                            }
                            
                            try {
                                globalThis.dispatchEvent(new CustomEvent('supervisorPedidos:realtimePedidoCreado', { 
                                    detail: { pedido: event?.pedido, source: 'pedidos.creados' }
                                }));
                            } catch (e) {
                                console.warn('[PedidosRealtime] Event dispatch error:', e.message);
                            }
                        });

                    this.usingWebSockets = true;
                    if (this.debug) console.log('[PedidosRealtime] ✅ WebSocket activo para supervisor-pedidos');
                    return;
                }

                // /cartera/pedidos: escuchar y recargar tabla
                if (this.isCarteraPage) {
                    console.log('[PedidosRealtime] 📍 Configurando cartera/pedidos');

                    echo.channel('pedidos.creados')
                        .listen('.pedido.creado', (event) => {
                            if (this.debug) console.log('[PedidosRealtime] Pedido creado (cartera)');
                            if (typeof globalThis.cargarPedidos === 'function') {
                                globalThis.cargarPedidos();
                            }
                        });

                    echo.channel('pedidos.general')
                        .listen('.pedido.actualizado', (event) => {
                            if (this.debug) console.log('[PedidosRealtime] Pedido actualizado (cartera)');
                            this.moverPedidoAlInicio(event?.pedido?.id);
                            if (typeof globalThis.cargarPedidos === 'function') {
                                setTimeout(() => {
                                    if (!this.pedidoMovido) {
                                        globalThis.cargarPedidos();
                                    }
                                    this.pedidoMovido = false;
                                }, 1000);
                            }
                        });

                    // Canal privado del usuario
                    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
                    if (userId) {
                        try {
                            echo.private(`pedidos.${userId}`)
                                .listen('.PedidoActualizado', (event) => {
                                    if (this.debug) console.log('[PedidosRealtime] PedidoActualizado privado (cartera)');
                                    if (typeof globalThis.cargarPedidos === 'function') {
                                        globalThis.cargarPedidos();
                                    }
                                });
                        } catch (error) {
                            console.error('[PedidosRealtime] Error en canal privado cartera:', error);
                        }
                    }

                    this.usingWebSockets = true;
                    if (this.debug) console.log('[PedidosRealtime] ✅ WebSocket activo para cartera/pedidos');
                    return;
                }
                
                // /despacho: Configuración especial para despacho
                if (this.isDespachoPage) {
                    console.log('[PedidosRealtime] 📍 Configurando despacho - INICIANDO SUSCRIPCIONES');
                    
                    // Escuchar cambios generales de pedidos
                    console.log('[PedidosRealtime] Suscribiendo a pedidos.general...');
                    echo.channel('pedidos.general')
                        .listen('.pedido.actualizado', (event) => {
                            console.log('[PedidosRealtime] 🎯 EVENTO RECIBIDO en pedidos.general (desde pedidos-realtime.js):', event?.pedido?.numero_pedido);
                            
                            // Disparar evento personalizado para despacho
                            try {
                                globalThis.dispatchEvent(new CustomEvent('despacho:pedidoActualizado', {
                                    detail: {
                                        pedido: event?.pedido,
                                        source: 'websocket',
                                        timestamp: new Date().toISOString()
                                    }
                                }));
                                console.log('[PedidosRealtime] ✅ CustomEvent despacho:pedidoActualizado disparado');
                            } catch (e) {
                                console.warn('[PedidosRealtime] Error despachando evento despacho:', e.message);
                            }
                        });
                    console.log('[PedidosRealtime] ✅ Suscrito a pedidos.general');
                    
                    // Escuchar nuevos pedidos
                    console.log('[PedidosRealtime] Suscribiendo a pedidos.creados...');
                    echo.channel('pedidos.creados')
                        .listen('.pedido.creado', (event) => {
                            console.log('[PedidosRealtime] 🎯 EVENTO RECIBIDO en pedidos.creados (desde pedidos-realtime.js):', event?.pedido?.numero_pedido);
                            
                            try {
                                globalThis.dispatchEvent(new CustomEvent('despacho:pedidoCreado', {
                                    detail: {
                                        pedido: event?.pedido,
                                        source: 'websocket',
                                        timestamp: new Date().toISOString()
                                    }
                                }));
                                console.log('[PedidosRealtime] ✅ CustomEvent despacho:pedidoCreado disparado');
                            } catch (e) {
                                console.warn('[PedidosRealtime] Error despachando evento despacho:', e.message);
                            }
                        });
                    console.log('[PedidosRealtime] ✅ Suscrito a pedidos.creados');
                    
                    this.usingWebSockets = true;
                    console.log('[PedidosRealtime] ✅✅✅ WebSocket ACTIVO para despacho');
                    return;
                }

                // Asesores - canal privado
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
                if (!userId) {
                    console.error('[PedidosRealtime] User ID no encontrado, WebSocket desactivado');
                    throw new Error('User ID no encontrado para suscripción privada');
                }

                console.log('[PedidosRealtime] 📍 Configurando asesores - canal privado');

                try {
                    echo.private(`pedidos.${userId}`)
                        .listen('.PedidoActualizado', (event) => {
                            if (this.debug) console.log('[PedidosRealtime] Actualización privada');
                            this.handlePedidoUpdate(event.pedido, 'pedido.actualizado');
                        })
                        .listen('.PedidoCreado', (event) => {
                            if (this.debug) console.log('[PedidosRealtime] Nuevo pedido privado');
                            this.handlePedidoUpdate(event.pedido, 'pedido.creado');
                        });

                    this.usingWebSockets = true;
                    if (this.debug) console.log('[PedidosRealtime] ✅ WebSocket activo para asesores');
                } catch (error) {
                    console.error('[PedidosRealtime] Error en suscripción privada:', error);
                    throw error;
                }

            } catch (error) {
                console.error('[PedidosRealtime] WebSocket setup failed:', error.message);
                this.usingWebSockets = false;
                
                // Log detallado del error para debugging
                console.error('[PedidosRealtime] Error details:', {
                    message: error.message,
                    stack: error.stack,
                    type: error.constructor.name,
                    timestamp: new Date().toISOString()
                });
                
                // Reintentar conexión después de 5 segundos
                console.log('[PedidosRealtime] Reintentando conexión WebSocket en 5 segundos...');
                setTimeout(() => {
                    console.log('[PedidosRealtime] Reintentando setupWebSocket...');
                    this.setupWebSocket();
                }, 5000);
            }
        });
    }

    /**
     * Manejar actualización de pedido desde WebSocket
     */
    handlePedidoUpdate(pedido, action) {
        if (this.debug) console.log('[PedidosRealtime] Actualización de pedido:', pedido?.id);
        this.actualizarPedidoIndividual(pedido);
        this.lastChangeTime = new Date();
    }

    /**
     * Actualizar pedido individual desde WebSocket
     */
    actualizarPedidoIndividual(pedido) {
        const selector = this.isCarteraPage ? 
            `[data-orden-id="${pedido.id}"]` : 
            `[data-pedido-id="${pedido.id}"]`;
        const fila = document.querySelector(selector);
        
        if (fila) {
            this.actualizarFila(fila, pedido);
        } else if (!this.isCarteraPage && this.debug) {
            console.log('[PedidosRealtime] Nuevo pedido:', pedido.id);
            this.agregarFilaNueva(pedido);
        }
        
        // Guardar estado
        this.pedidosAnterior.set(pedido.id, {
            estado: pedido.estado,
            novedades: pedido.novedades,
            forma_pago: pedido.forma_pago,
            fecha_estimada: pedido.fecha_estimada,
        });
    }

    start() {
        if (this.isRunning) {
            return;
        }
        
        if (this.debug) console.log('[PedidosRealtime] Iniciando monitoreo');
        this.isRunning = true;
        
        if (this.debug) console.log('[PedidosRealtime] Usando WebSockets exclusivamente (sin polling)');
    }
    
    pause() {
        if (!this.isRunning) return;
        
        if (this.debug) console.log('[PedidosRealtime] ⏸️ Pausado');
        this.isRunning = false;
    }

    stop() {
        if (!this.isRunning) return;
        
        if (this.debug) console.log('[PedidosRealtime] ⏹️ Detenido');
        this.isRunning = false;
        
        // Note: Channel unsubscription is handled by Echo
        // No direct cleanup needed here
    }

    /**
     * Destruir instancia completamente
     */
    destroy() {
        if (this.debug) console.log('[PedidosRealtime] 💥 Destruyendo instancia');
        
        this.stop();
        this.pedidosAnterior.clear();
        this.usingWebSockets = false;
        PedidosRealtimeRefresh.instance = null;
    }

    /**
     * Obtener estado del sistema
     */
    getStatus() {
        return {
            isRunning: this.isRunning,
            usingWebSockets: this.usingWebSockets,
            connectionType: this.usingWebSockets ? 'WebSocket (Real-time)' : 'Polling (Fallback)',
            pedidosCount: this.pedidosAnterior.size,
            lastChangeTime: this.lastChangeTime
        };
    }

    /**
     * Obtener URL de API según página
     */
    getApiUrl() {
        if (this.isCarteraPage) {
            return '/api/cartera/pedidos?estado=pendiente_cartera';
        }
        return '/api/asesores/realtime/pedidos';
    }
    
    /**
     * Verificar cambios en lista de pedidos
     */
    async checkForChanges(pedidosNuevos) {
        if (this.debug) console.log('[PedidosRealtime] Analizando', pedidosNuevos.length, 'pedidos');
        
        const hayCambios = this.detectarCambios(pedidosNuevos);
        
        if (hayCambios) {
            this.lastChangeTime = new Date();
            if (this.debug) console.log('[PedidosRealtime] Cambios detectados');
            
            if (typeof globalThis.cargarPedidos === 'function') {
                await globalThis.cargarPedidos();
            } else if (this.isCarteraPage && typeof globalThis.cargarPedidosCartera === 'function') {
                await globalThis.cargarPedidosCartera();
            }
        }
    }

    /**
     * Detectar si hay cambios en los pedidos
     */
    detectarCambios(pedidosNuevos) {
        if (this.pedidosAnterior.size === 0) {
            this.guardarEstadoPedidos(pedidosNuevos);
            return false;
        }
        
        let hayCambios = false;
        
        // Nueva cantidad
        if (pedidosNuevos.length !== this.pedidosAnterior.size) {
            if (this.debug) console.log('[PedidosRealtime] Cantidad cambió:', this.pedidosAnterior.size, '->', pedidosNuevos.length);
            hayCambios = true;
        }
        
        // Cambios en pedidos existentes
        for (const pedido of pedidosNuevos) {
            const anterior = this.pedidosAnterior.get(pedido.id);
            
            if (!anterior) {
                if (this.debug) console.log('[PedidosRealtime] Nuevo pedido:', pedido.id);
                hayCambios = true;
                continue;
            }
            
            if (anterior.estado !== pedido.estado || anterior.novedades !== pedido.novedades) {
                if (this.debug) console.log('[PedidosRealtime] Cambio en pedido:', pedido.id);
                hayCambios = true;
            }
        }
        
        this.guardarEstadoPedidos(pedidosNuevos);
        return hayCambios;
    }

    /**
     * Guardar estado actual de pedidos
     */
    guardarEstadoPedidos(pedidos) {
        this.pedidosAnterior.clear();
        for (const pedido of pedidos) {
            this.pedidosAnterior.set(pedido.id, {
                estado: pedido.estado,
                novedades: pedido.novedades,
                forma_pago: pedido.forma_pago,
                fecha_estimada: pedido.fecha_estimada,
            });
        }
    }

    /**
     * Actualizar fila individual
     */
    actualizarFila(fila, pedido) {
        const celdas = fila.querySelectorAll('[style*="display: flex"]');
        if (celdas.length >= 2) {
            // Actualizar estado
            const celdaEstado = celdas[1];
            if (celdaEstado.textContent.trim() !== pedido.estado) {
                celdaEstado.textContent = pedido.estado;
                fila.style.background = '#fef3c7';
                setTimeout(() => {
                    fila.style.background = '';
                    fila.style.transition = 'background-color 0.5s ease-out';
                }, 2000);
            }
        }
    }

    /**
     * Agregar nueva fila a la tabla
     */
    agregarFilaNueva(pedido) {
        // Placeholder para agregar nueva fila si es necesario
        if (this.debug) console.log('[PedidosRealtime] Nueva fila para pedido:', pedido.id);
    }

    moverPedidoAlInicio(pedidoId) {
        if (!pedidoId) return;
        
        try {
            // Buscar el contenedor principal
            const contenedorTabla = document.querySelector('.table-scroll-container');
            if (!contenedorTabla) {
                console.log('[PedidosRealtime] Contenedor de tabla no encontrado');
                this.pedidoMovido = false;
                return;
            }
            
            // Buscar el header
            const header = contenedorTabla.querySelector('[style*="grid-template-columns: 200px"]');
            if (!header) {
                console.log('[PedidosRealtime] Header de tabla no encontrado');
                this.pedidoMovido = false;
                return;
            }
            
            // Buscar todas las filas completas (divs con grid-template-columns)
            const filas = Array.from(contenedorTabla.querySelectorAll('div[style*="grid-template-columns: 200px"]'))
                .filter(fila => fila !== header); // Excluir el header
            
            if (filas.length === 0) {
                console.log('[PedidosRealtime] No se encontraron filas de pedidos');
                this.pedidoMovido = false;
                return;
            }
            
            // Convertir NodeList a array y extraer números de pedido
            const filasConNumero = filas.map(fila => {
                // Buscar el span con el número de pedido usando varios selectores posibles
                let numeroElement = fila.querySelector('span[style*="font-weight: 600; color: #1e5ba8;"]');
                
                // Si no encuentra con ese estilo, intentar otros selectores
                if (!numeroElement) {
                    numeroElement = fila.querySelector('span[style*="color: #1e5ba8"]');
                }
                
                // Último intento: buscar cualquier span que contenga #
                if (!numeroElement) {
                    const spans = fila.querySelectorAll('span');
                    for (const span of spans) {
                        if (span.textContent.includes('#')) {
                            numeroElement = span;
                            break;
                        }
                    }
                }
                
                const numeroTexto = numeroElement ? numeroElement.textContent.replace('#', '').trim() : '0';
                const numero = parseInt(numeroTexto) || 0;
                const id = fila.getAttribute('data-pedido-id');
                
                console.log('[PedidosRealtime] Pedido encontrado:', { id, numero, texto: numeroElement?.textContent });
                
                return { fila, numero, id };
            });
            
            // Ordenar por número de pedido en orden ascendente (más antiguo primero)
            filasConNumero.sort((a, b) => a.numero - b.numero);
            
            console.log('[PedidosRealtime] Pedidos antes de reordenar:', 
                filasConNumero.map(item => ({ id: item.id, numero: item.numero }))
            );
            console.log('[PedidosRealtime] Pedidos después de ordenar (ascendente):', 
                filasConNumero.map(item => ({ id: item.id, numero: item.numero }))
            );
            
            // Reordenar las filas manteniendo la estructura completa
            filasConNumero.forEach((item, index) => {
                // Mover cada fila completa a su nueva posición
                if (index === 0) {
                    // Primera fila: insertar después del header
                    contenedorTabla.insertBefore(item.fila, header.nextSibling);
                } else {
                    // Siguientes filas: insertar después de la fila anterior
                    const filaAnterior = filasConNumero[index - 1].fila;
                    const siguienteElemento = filaAnterior.nextSibling;
                    contenedorTabla.insertBefore(item.fila, siguienteElemento);
                }
                
                // Resaltar la fila que se actualizó
                if (item.id == pedidoId) {
                    // DESMARCAR CHECKBOX cuando se reordena el pedido
                    // La deselección se guarda en el servidor automáticamente cuando se actualiza una prenda
                    const checkbox = item.fila.querySelector(`input.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                        if (this.debug) console.log('[PedidosRealtime] Checkbox desmarcado para pedido:', pedidoId);
                    }
                    
                    // Actualizar atributo data-seleccionado a false
                    item.fila.setAttribute('data-seleccionado', 'false');
                    
                    item.fila.style.background = '#fef3c7';
                    item.fila.style.transition = 'all 0.3s ease';
                    
                    // Quitar el resaltado después de la animación
                    setTimeout(() => {
                        item.fila.style.background = '';
                        item.fila.style.transition = 'background-color 0.5s ease-out';
                    }, 2000);
                }
            });
            
            this.pedidoMovido = true;
            console.log('[PedidosRealtime] Tabla reordenada por número de pedido (ascendente)');
            
        } catch (error) {
            console.error('[PedidosRealtime] Error al reordenar tabla:', error);
            this.pedidoMovido = false;
        }
    }
}

// Inicializar cuando el DOM esté listo con patrón singleton
document.addEventListener('DOMContentLoaded', () => {
    const realtimeDebug = (
        globalThis.location.search.includes('realtimeDebug=1') ||
        globalThis.localStorage?.getItem('realtimeDebug') === '1'
    );

    // Destruir instancia existente si la hay
    if (globalThis.pedidosRealtimeRefresh) {
        globalThis.pedidosRealtimeRefresh.destroy();
        globalThis.pedidosRealtimeRefresh = null;
    }
    
    // Crear nueva instancia solo si no existe
    if (!globalThis.pedidosRealtimeRefresh) {
        globalThis.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
            checkInterval: 30000, // 30 segundos
            autoStart: true,
            debug: realtimeDebug // Activable por querystring/localStorage
        });
    }
});

// También inicializar si el DOM ya está cargado
if (document.readyState !== 'loading') {
    // DOM ya cargado, crear la instancia
    const realtimeDebug = (
        globalThis.location.search.includes('realtimeDebug=1') ||
        globalThis.localStorage?.getItem('realtimeDebug') === '1'
    );

    if (!globalThis.pedidosRealtimeRefresh) {
        globalThis.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
            checkInterval: 30000,
            autoStart: true,
            debug: realtimeDebug
        });
    }
}

