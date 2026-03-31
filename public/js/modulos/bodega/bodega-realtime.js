/**
 * Real-Time System for Bodega - FASE 5 v1.0
 * Maneja actualizaciones en tiempo real para gestión de bodega
 * Migración de Echo/Reverb (FASE 4) → window.shared.websocket (FASE 5)
 * 
 * Arquitectura: Clase Singleton que gestiona suscripciones dinámicas a canales
 * Canales: bodega-detalles-{numeroPedido}-{talla} (múltiples simultáneamente)
 */

class BodegaRealtimeRefresh {
    static instance = null;
    
    constructor(options = {}) {
        // Patrón Singleton
        if (BodegaRealtimeRefresh.instance) {
            return BodegaRealtimeRefresh.instance;
        }
        
        BodegaRealtimeRefresh.instance = this;
        
        // Configuración
        this.debug = options.debug || false;
        this.isRunning = false;
        this.channels = new Map(); // Guardar canales activos
        
        // Elementos DOM
        this.init();
    }

    init() {
        if (this.debug) console.log('[BodegaRealtime] Sistema inicializado');
        
        // Esperar a que Echo esté disponible
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEcho());
        } else {
            this.setupEcho();
        }
    }

    setupEcho() {
        try {
            if (typeof window.waitForEcho !== 'function') {
                console.log('[BodegaRealtime]  Esperando a que window.waitForEcho esté disponible...');
                setTimeout(() => this.setupEcho(), 200);
                return;
            }

            window.waitForEcho(() => {
                const ws = window.shared.websocket;
                
                if (!ws) {
                    console.warn('[BodegaRealtime] WebSocket no disponible');
                    return;
                }

                // Obtener user ID desde meta tags
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

                if (!userId) {
                    console.warn('[BodegaRealtime] User ID no encontrado');
                    return;
                }

                if (this.debug) {
                    console.log('[BodegaRealtime] 🔌 Configurando canales de bodega con WebSocket');
                    console.log('  - User ID:', userId);
                }
                
                // Suscribirse a todos los canales de detalles visibles
                this.subscribeToVisibleChannels();
                
                if (this.debug) console.log('[BodegaRealtime]  Sistema de tiempo real activo');
            });
        } catch (error) {
            console.error('[BodegaRealtime]  Error configurando WebSocket:', error);
        }
    }

    subscribeToVisibleChannels() {
        // Encontrar todos los inputs de observaciones para determinar los canales
        const observacionesInputs = document.querySelectorAll('.observaciones-input');
        
        if (this.debug) {
            console.log(`[BodegaRealtime] Encontrados ${observacionesInputs.length} inputs para suscribir`);
        }
        
        observacionesInputs.forEach((input, index) => {
            const numeroPedido = input.dataset.numeroPedido;
            const talla = input.dataset.talla;
            
            if (numeroPedido && talla) {
                this.subscribeToDetalleChannel(numeroPedido, talla, index);
            }
        });
    }

    subscribeToDetalleChannel(numeroPedido, talla, index) {
        const channelName = `bodega-detalles-${numeroPedido}-${talla}`;
        
        // Si ya estamos suscritos a este canal, no suscribir de nuevo
        if (this.channels.has(channelName)) {
            if (this.debug) console.log(`[BodegaRealtime] Canal ${channelName} ya suscrito`);
            return;
        }

        try {
            const ws = window.shared.websocket;
            if (!ws) {
                console.warn(`[BodegaRealtime] WebSocket no disponible, no se puede suscribir a ${channelName}`);
                return;
            }

            if (this.debug) {
                console.log(`🔌 [BodegaRealtime] Suscribiendo al canal: ${channelName}`);
            }

            // Suscribirse a evento .detalle.actualizado
            try {
                ws.subscribe(channelName, '.detalle.actualizado', (event) => {
                    if (this.debug) console.log('[BodegaRealtime]  Detalle actualizado:', event);
                    this.handleDetalleUpdate(event, numeroPedido, talla);
                });
                if (this.debug) console.log(`[BodegaRealtime]  Suscrito a ${channelName}/.detalle.actualizado`);
            } catch (error) {
                console.error(`[BodegaRealtime]  Error en evento .detalle.actualizado: ${channelName}:`, error);
            }

            // Suscribirse a evento .nota.guardada
            try {
                ws.subscribe(channelName, '.nota.guardada', (event) => {
                    if (this.debug) console.log('[BodegaRealtime] 📝 Nota guardada:', event);
                    this.handleNotaGuardada(event, numeroPedido, talla);
                });
                if (this.debug) console.log(`[BodegaRealtime]  Suscrito a ${channelName}/.nota.guardada`);
            } catch (error) {
                console.error(`[BodegaRealtime]  Error en evento .nota.guardada: ${channelName}:`, error);
            }

            // Guardar referencia al canal para cleanup
            this.channels.set(channelName, true);

        } catch (error) {
            console.error(`[BodegaRealtime]  Error suscribiendo a ${channelName}:`, error);
        }
    }

    handleDetalleUpdate(event, numeroPedido, talla) {
        if (!event.detalles) return;

        // Obtener prenda_id del evento si existe
        const prendaId = event.detalles.prenda_id || event.prenda_id || null;
        
        if (this.debug) {
            console.log(` [BodegaRealtime] Actualizando detalle ${numeroPedido}-${talla}${prendaId ? ` (prenda_id: ${prendaId})` : ''}`);
        }

        // Construir selector base
        const baseSelector = `[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`;
        const prendaSelector = prendaId ? `[data-prenda-id="${prendaId}"]` : '';
        
        // Si hay prenda_id, usar selector específico; sino usar querySelectorAll para actualizar todos
        if (prendaId) {
            // CASO 1: Actualizar solo el registro específico con prenda_id
            const fecha = document.querySelector(`.fecha-input${baseSelector}${prendaSelector}`);
            const fechaPedido = document.querySelector(`.fecha-pedido-input${baseSelector}${prendaSelector}`);
            const pendientes = document.querySelector(`.pendientes-input${baseSelector}${prendaSelector}`);
            const observaciones = document.querySelector(`.observaciones-input${baseSelector}${prendaSelector}`);
            const estadoSelect = document.querySelector(`.estado-select${baseSelector}${prendaSelector}`);

            // Actualizar elementos encontrados
            if (event.detalles.fecha_entrega && fecha && document.activeElement !== fecha) {
                fecha.value = event.detalles.fecha_entrega;
            }
            if (event.detalles.fecha_pedido && fechaPedido && document.activeElement !== fechaPedido) {
                fechaPedido.value = event.detalles.fecha_pedido;
            }
            if (event.detalles.pendientes !== undefined && pendientes && document.activeElement !== pendientes) {
                pendientes.value = event.detalles.pendientes || '';
            }
            if (event.detalles.observaciones_bodega !== undefined && observaciones && document.activeElement !== observaciones) {
                observaciones.value = event.detalles.observaciones_bodega || '';
            }
            if (event.detalles.estado_bodega && estadoSelect && document.activeElement !== estadoSelect) {
                estadoSelect.value = event.detalles.estado_bodega;
                estadoSelect.setAttribute('data-original-estado', event.detalles.estado_bodega);
                
                // Disparar evento change para actualizar colores
                estadoSelect.dispatchEvent(new Event('change'));
            }
        } else {
            // CASO 2: Sin prenda_id, actualizar todos los registros con esa talla (comportamiento legacy)
            const fechas = document.querySelectorAll(`.fecha-input${baseSelector}`);
            const fechasPedido = document.querySelectorAll(`.fecha-pedido-input${baseSelector}`);
            const pendientesAll = document.querySelectorAll(`.pendientes-input${baseSelector}`);
            const observacionesAll = document.querySelectorAll(`.observaciones-input${baseSelector}`);
            const estadosSelect = document.querySelectorAll(`.estado-select${baseSelector}`);

            // Actualizar todos los elementos encontrados
            if (event.detalles.fecha_entrega) {
                fechas.forEach(fecha => {
                    if (document.activeElement !== fecha) {
                        fecha.value = event.detalles.fecha_entrega;
                    }
                });
            }
            if (event.detalles.fecha_pedido) {
                fechasPedido.forEach(fechaPedido => {
                    if (document.activeElement !== fechaPedido) {
                        fechaPedido.value = event.detalles.fecha_pedido;
                    }
                });
            }
            if (event.detalles.pendientes !== undefined) {
                pendientesAll.forEach(pendientes => {
                    if (document.activeElement !== pendientes) {
                        pendientes.value = event.detalles.pendientes || '';
                    }
                });
            }
            if (event.detalles.observaciones_bodega !== undefined) {
                observacionesAll.forEach(observaciones => {
                    if (document.activeElement !== observaciones) {
                        observaciones.value = event.detalles.observaciones_bodega || '';
                    }
                });
            }
            if (event.detalles.estado_bodega) {
                estadosSelect.forEach(estadoSelect => {
                    if (document.activeElement !== estadoSelect) {
                        estadoSelect.value = event.detalles.estado_bodega;
                        estadoSelect.setAttribute('data-original-estado', event.detalles.estado_bodega);
                        
                        // Disparar evento change para actualizar colores
                        estadoSelect.dispatchEvent(new Event('change'));
                    }
                });
            }
        }

        if (this.debug) {
            console.log(` [BodegaRealtime] Actualizado detalle ${numeroPedido}-${talla}${prendaId ? ` (prenda_id: ${prendaId})` : ''}`);
        }
    }

    handleNotaGuardada(event, numeroPedido, talla) {
        // Recargar notas si el modal está abierto
        if (typeof window.cargarNotas === 'function') {
            window.cargarNotas(numeroPedido, talla);
        }
        
        if (this.debug) {
            console.log(`[BodegaRealtime] 📝 Nota recargada para ${numeroPedido}-${talla}`);
        }
    }

    // Método público para suscribirse a nuevos canales (útil para modales)
    subscribeToChannel(numeroPedido, talla) {
        this.subscribeToDetalleChannel(numeroPedido, talla);
    }

    // Método público para limpiar canales
    leaveChannel(channelName) {
        if (this.channels.has(channelName)) {
            // Nota: La desuscripción de ws.subscribe() se maneja internamente en window.shared.websocket
            // Solo limpiamos nuestra referencia local
            this.channels.delete(channelName);
            if (this.debug) console.log(`🔌 [BodegaRealtime] Abandonado canal local: ${channelName}`);
        }
    }

    // Limpiar todos los canales
    leaveAllChannels() {
        this.channels.forEach((value, channelName) => {
            // Nota: La desuscripción de ws.subscribe() se maneja internamente en window.shared.websocket
            // Solo limpiamos nuestra referencia local
            if (this.debug) console.log(`🔌 [BodegaRealtime] Abandonado canal local: ${channelName}`);
        });
        this.channels.clear();
        if (this.debug) console.log('[BodegaRealtime] 🧹 Todos los canales limpiados');
    }
}

// Inicializar automáticamente cuando se carga el script
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en la página de bodega
    if (window.location.pathname.includes('/gestion-bodega/pedidos')) {
        window.bodegaRealtime = new BodegaRealtimeRefresh({
            debug: true // Activar logs para debugging
        });
    }
});

// Hacer disponible globalmente
window.BodegaRealtimeRefresh = BodegaRealtimeRefresh;
