/**
 * Real-Time Table Refresh System - Laravel Echo + Reverb Integration
 * @version 2.0 (Phase 5: DDD WebSocket Abstraction)
 * 
 * Uses window.shared.websocket (EchoReverbWebSocketClient) abstraction instead of direct Echo access
 * Polling fallback uses window.shared.cache (SessionStorageCacheRepository) for centralized cache handling
 * 
 * Removed all direct window.Echo.channel() calls - replaced with ws.subscribe() pattern
 * Polling uses cache.getOrFetch() with TTL instead of raw fetch loops
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
        this.isCarteraPage = window.location.pathname.includes('/cartera/pedidos');
        this.isAnyCarteraPage = window.location.pathname.includes('/cartera/');
        this.isSupervisorPedidosPage = window.location.pathname.includes('/supervisor-pedidos');
        this.usingWebSockets = false;

        // No ejecutar realtime en páginas de cartera (excepto /cartera/pedidos)
        if (this.isAnyCarteraPage && !this.isCarteraPage) {
            console.log('[PedidosRealtime] Página de cartera detectada, desactivando realtime');
            this.isRunning = false;
            return;
        }
        
        // Service injection from window.shared (DDD pattern)
        // NOTA: No acceder a window.shared aquí, esperar a initializeServices()
        this.uiUpdate = null;
        this.activityDetector = null;
        this.channelConfigurator = null;
        
        this.init();
    }

    init() {
        // CRÍTICO: Esperar a que window.shared esté disponible (race condition fix)
        if (!window.shared?.isReady) {
            if (this.debug) console.log(' [PedidosRealtime] Esperando window.shared.isReady en init...');
            setTimeout(() => this.init(), 50);
            return;
        }
        
        if (this.debug) console.log(' [PedidosRealtime] Sistema inicializado');
        
        // Inyectar y configurar servicios (DDD pattern)
        this.initializeServices();
        
        // Validar que Echo esté disponible
        if (typeof window.waitForEcho !== 'function') {
            if (this.debug) console.log(' [PedidosRealtime] Esperando inicialización de Echo...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        // Configurar WebSocket mediante abstracción
        this.setupWebSocket();
        
        if (this.autoStart) {
            this.start();
        }
    }

    /**
     * Inicializar servicios inyectados
     */
    initializeServices() {
        // Esperar a que window.shared esté disponible (CRÍTICO para evitar race condition)
        if (!window.shared?.isReady) {
            if (this.debug) console.log(' [PedidosRealtime] Esperando window.shared.isReady...');
            setTimeout(() => this.initializeServices(), 50);
            return;
        }
        
        // UIUpdateService para UI updates
        if (!this.uiUpdate && window.shared?.uiUpdate) {
            this.uiUpdate = window.shared.uiUpdate;
            if (this.debug) console.log(' UIUpdateService inyectado');
        }
        
        // ActivityDetectionService para detectar actividad
        if (!this.activityDetector && window.shared?.activityDetector) {
            this.activityDetector = window.shared.activityDetector;
            // Configurar callbacks de actividad
            if (this.activityDetector && typeof this.activityDetector.setupActivityDetection === 'function') {
                this.activityDetector.setupActivityDetection();
                if (this.debug) console.log(' ActivityDetectionService inyectado y configurado');
            }
        }
        
        // WebSocketChannelConfigurator para mapeo de canales
        if (!this.channelConfigurator && window.shared?.channelConfigurator) {
            this.channelConfigurator = window.shared.channelConfigurator;
            if (this.debug) console.log(' WebSocketChannelConfigurator inyectado');
        }
    }

    /**
     * Configurar WebSocket usando abstracción centralizada
     */
    setupWebSocket() {
        // CRÍTICO: Esperar a que window.shared esté disponible (race condition fix)
        if (!window.shared?.isReady) {
            if (this.debug) console.log(' [PedidosRealtime] Esperando window.shared.isReady en setupWebSocket...');
            setTimeout(() => this.setupWebSocket(), 50);
            return;
        }
        
        if (typeof window.waitForEcho !== 'function') {
            console.warn('[PedidosRealtime] Echo initializer not available, retrying');
            setTimeout(() => this.setupWebSocket(), 100);
            return;
        }

        window.waitForEcho(() => {
            try {
                const ws = window.shared.websocket;
                if (!ws) {
                    throw new Error('WebSocket abstraction not available');
                }
                
                // /supervisor-pedidos: escuchar eventos pero no recargar
                if (this.isSupervisorPedidosPage) {
                    if (this.debug) console.log('🔌 [PedidosRealtime] Configurando supervisor-pedidos');

                    ws.subscribe('pedidos.general', '.pedido.actualizado', (event) => {
                        if (this.debug) console.log('🔄 Pedido actualizado (supervisor)');
                        try {
                            window.dispatchEvent(new CustomEvent('supervisorPedidos:realtimePedidoActualizado', { 
                                detail: { pedido: event?.pedido, source: 'pedidos.general' }
                            }));
                        } catch (e) {
                            console.warn('[PedidosRealtime] Event dispatch error:', e.message);
                        }
                    });

                    ws.subscribe('pedidos.creados', '.pedido.creado', (event) => {
                        if (this.debug) console.log('➕ Pedido creado (supervisor)');
                        try {
                            window.dispatchEvent(new CustomEvent('supervisorPedidos:realtimePedidoCreado', { 
                                detail: { pedido: event?.pedido, source: 'pedidos.creados' }
                            }));
                        } catch (e) {
                            console.warn('[PedidosRealtime] Event dispatch error:', e.message);
                        }
                    });

                    this.usingWebSockets = true;
                    if (this.debug) console.log(' WebSocket activo para supervisor-pedidos');
                    return;
                }

                // /cartera/pedidos: escuchar y recargar tabla
                if (this.isCarteraPage) {
                    if (this.debug) console.log('🔌 [PedidosRealtime] Configurando cartera/pedidos');

                    ws.subscribe('pedidos.creados', '.pedido.creado', (event) => {
                        if (this.debug) console.log('➕ Pedido creado (cartera)');
                        if (this.uiUpdate) {
                            this.uiUpdate.showRealtimeToast(`Nuevo pedido recibido`, 'success');
                        }
                        if (typeof window.cargarPedidos === 'function') {
                            window.cargarPedidos();
                        }
                    });

                    ws.subscribe('pedidos.general', '.pedido.actualizado', (event) => {
                        if (this.debug) console.log('🔄 Pedido actualizado (cartera)');
                        this.moverPedidoAlInicio(event?.pedido?.id);
                        if (typeof window.cargarPedidos === 'function') {
                            setTimeout(() => {
                                if (!this.pedidoMovido) {
                                    window.cargarPedidos();
                                }
                                this.pedidoMovido = false;
                            }, 1000);
                        }
                    });

                    ws.subscribe('supervisor-pedidos', 'OrdenUpdated', (data) => {
                        if (this.debug) console.log('📨 OrdenUpdated (cartera)');
                        if (typeof window.cargarPedidos === 'function') {
                            window.cargarPedidos();
                        }
                    });

                    // Canal privado del usuario
                    if (window.usuarioAutenticado?.id) {
                        const userId = window.usuarioAutenticado.id;
                        try {
                            ws.subscribe(`pedidos.${userId}`, '.PedidoActualizado', (event) => {
                                if (this.debug) console.log('📡 PedidoActualizado privado (cartera)');
                                if (typeof window.cargarPedidos === 'function') {
                                    window.cargarPedidos();
                                }
                            });
                        } catch (error) {
                            console.error('[PedidosRealtime] Error en canal privado cartera:', error);
                        }
                    }

                    this.usingWebSockets = true;
                    if (this.debug) console.log(' WebSocket activo para cartera/pedidos');
                    return;
                }

                // Asesores: usar canal privado
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
                if (!userId) {
                    console.error('[PedidosRealtime] User ID no encontrado, WebSocket desactivado');
                    throw new Error('User ID no encontrado para suscripción privada');
                }

                if (this.debug) console.log('🔌 [PedidosRealtime] Configurando asesores - canal privado');

                try {
                    ws.subscribe(`pedidos.${userId}`, '.PedidoActualizado', (event) => {
                        if (this.debug) console.log('📡 Actualización privada');
                        this.handlePedidoUpdate(event.pedido, 'pedido.actualizado');
                    });

                    ws.subscribe(`pedidos.${userId}`, '.PedidoCreado', (event) => {
                        if (this.debug) console.log('➕ Nuevo pedido privado');
                        this.handlePedidoUpdate(event.pedido, 'pedido.creado');
                    });

                    this.usingWebSockets = true;
                    if (this.debug) console.log(' WebSocket activo para asesores');
                } catch (error) {
                    console.error('[PedidosRealtime] Error en suscripción privada:', error);
                    throw error;
                }

            } catch (error) {
                console.error('[PedidosRealtime] WebSocket setup failed - CRITICO:', error.message);
                this.usingWebSockets = false;
                if (this.uiUpdate) {
                    this.uiUpdate.showConnectionIndicator('WebSocket Error - Realtime no disponible', 'error');
                }
                throw error; // Propagar el error, no permitir fallback
            }
        });
    }

    /**
     * Manejar actualización de pedido desde WebSocket
     */
    handlePedidoUpdate(pedido, action) {
        if (this.debug) console.log('📡 Actualización de pedido:', pedido?.id);
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
            console.log('➕ Nuevo pedido:', pedido.id);
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
        
        if (this.debug) console.log(` [PedidosRealtime]  Iniciando monitoreo`);
        this.isRunning = true;
        
        if (this.debug) console.log(' [PedidosRealtime] Usando WebSockets exclusivamente (sin polling)');
    }
    


    pause() {
        if (!this.isRunning) return;
        
        if (this.debug) console.log(' [PedidosRealtime] ⏸️ Pausado');
        this.isRunning = false;
    }

    stop() {
        if (!this.isRunning) return;
        
        if (this.debug) console.log('⏹️ [PedidosRealtime] Detenido');
        this.isRunning = false;
        clearTimeout(this.userActivityTimeout);
        
        // Note: Channel unsubscription is handled by the WebSocket abstraction (window.shared.websocket)
        // No direct cleanup needed here
    }

    /**
     * Destruir instancia completamente
     */
    destroy() {
        if (this.debug) console.log('💥 [PedidosRealtime] Destruyendo instancia');
        
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
        if (this.debug) console.log(' Analizando', pedidosNuevos.length, 'pedidos');
        
        const hayCambios = this.detectarCambios(pedidosNuevos);
        
        if (hayCambios) {
            this.lastChangeTime = new Date();
            if (this.debug) console.log(' Cambios detectados');
            
            if (typeof window.cargarPedidos === 'function') {
                await window.cargarPedidos();
            } else if (this.isCarteraPage && typeof window.cargarPedidosCartera === 'function') {
                await window.cargarPedidosCartera();
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
            if (this.debug) console.log('Cantidad cambió:', this.pedidosAnterior.size, '->', pedidosNuevos.length);
            hayCambios = true;
        }
        
        // Cambios en pedidos existentes
        for (const pedido of pedidosNuevos) {
            const anterior = this.pedidosAnterior.get(pedido.id);
            
            if (!anterior) {
                if (this.debug) console.log('➕ Nuevo pedido:', pedido.id);
                hayCambios = true;
                continue;
            }
            
            if (anterior.estado !== pedido.estado || anterior.novedades !== pedido.novedades) {
                if (this.debug) console.log('Cambio en pedido:', pedido.id);
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
        if (this.debug) console.log('Nueva fila para pedido:', pedido.id);
    }

    moverPedidoAlInicio(pedidoId) {
        if (!pedidoId) return;
        
        try {
            // Buscar el contenedor principal
            const contenedorTabla = document.querySelector('.table-scroll-container');
            if (!contenedorTabla) {
                console.log('[PedidosRealtime]  Contenedor de tabla no encontrado');
                this.pedidoMovido = false;
                return;
            }
            
            // Buscar el header
            const header = contenedorTabla.querySelector('[style*="grid-template-columns: 200px"]');
            if (!header) {
                console.log('[PedidosRealtime]  Header de tabla no encontrado');
                this.pedidoMovido = false;
                return;
            }
            
            // Buscar todas las filas completas (divs con grid-template-columns)
            const filas = Array.from(contenedorTabla.querySelectorAll('div[style*="grid-template-columns: 200px"]'))
                .filter(fila => fila !== header); // Excluir el header
            
            if (filas.length === 0) {
                console.log('[PedidosRealtime]  No se encontraron filas de pedidos');
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
                
                console.log('[PedidosRealtime]  Pedido encontrado:', { id, numero, texto: numeroElement?.textContent });
                
                return { fila, numero, id };
            });
            
            // Ordenar por número de pedido en orden ascendente (más antiguo primero)
            filasConNumero.sort((a, b) => a.numero - b.numero);
            
            console.log('[PedidosRealtime]  Pedidos antes de reordenar:', 
                filasConNumero.map(item => ({ id: item.id, numero: item.numero }))
            );
            console.log('[PedidosRealtime]  Pedidos después de ordenar (ascendente):', 
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
            console.log('[PedidosRealtime]  Tabla reordenada por número de pedido (ascendente)');
            
        } catch (error) {
            console.error('[PedidosRealtime]  Error al reordenar tabla:', error);
            this.pedidoMovido = false;
        }
    }
}

// Inicializar cuando el DOM esté listo con patrón singleton
document.addEventListener('DOMContentLoaded', () => {
    const realtimeDebug = (
        window.location.search.includes('realtimeDebug=1') ||
        window.localStorage?.getItem('realtimeDebug') === '1'
    );

    // Destruir instancia existente si la hay
    if (window.pedidosRealtimeRefresh) {
        window.pedidosRealtimeRefresh.destroy();
        window.pedidosRealtimeRefresh = null;
    }
    
    // Crear nueva instancia solo si no existe
    if (!window.pedidosRealtimeRefresh) {
        window.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
            checkInterval: 30000, // 30 segundos
            autoStart: true,
            debug: realtimeDebug // Activable por querystring/localStorage
        });
    }
});

// También inicializar si el DOM ya está cargado
if (document.readyState === 'loading') {
    // DOM todavía cargando, esperar evento
} else {
    // DOM ya cargado, pero esperar a que window.shared esté disponible
    function initializePedidosRealtimeIfReady() {
        if (!window.shared?.isReady) {
            // window.shared aún no disponible, reintentar
            setTimeout(initializePedidosRealtimeIfReady, 50);
            return;
        }
        
        // Ahora sí, crear la instancia
        if (!window.pedidosRealtimeRefresh) {
            const realtimeDebug = (
                window.location.search.includes('realtimeDebug=1') ||
                window.localStorage?.getItem('realtimeDebug') === '1'
            );

            window.pedidosRealtimeRefresh = new PedidosRealtimeRefresh({
                checkInterval: 30000,
                autoStart: true,
                debug: realtimeDebug // Activable por querystring/localStorage
            });
        }
    }
    
    // Ejecutar solo cuando window.shared esté listo
    initializePedidosRealtimeIfReady();
}

