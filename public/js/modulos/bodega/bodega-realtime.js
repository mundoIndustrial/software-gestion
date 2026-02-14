/**
 * Real-Time System for Bodega - Laravel Echo + Reverb
 * Adaptado para los canales y eventos específicos de bodega
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
        if (this.debug) console.log(' [BodegaRealtime] Sistema inicializado');
        
        // Esperar a que Echo esté disponible
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEcho());
        } else {
            this.setupEcho();
        }
    }

    setupEcho() {
        if (typeof window.Echo === 'undefined') {
            console.warn(' [BodegaRealtime] Laravel Echo no está disponible');
            return;
        }

        // Obtener user ID desde meta tags
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        if (!userId) {
            console.warn(' [BodegaRealtime] User ID no encontrado');
            return;
        }

        try {
            if (this.debug) {
                console.log('🔌 [BodegaRealtime] Configurando canales de bodega');
                console.log('  - User ID:', userId);
            }
            
            // Suscribirse a todos los canales de detalles visibles
            this.subscribeToVisibleChannels();
            
            if (this.debug) console.log(' [BodegaRealtime] Sistema de tiempo real activo');

        } catch (error) {
            console.error(' [BodegaRealtime] Error configurando WebSocket:', error);
        }
    }

    subscribeToVisibleChannels() {
        // Encontrar todos los inputs de observaciones para determinar los canales
        const observacionesInputs = document.querySelectorAll('.observaciones-input');
        
        if (this.debug) {
            console.log(` [BodegaRealtime] Encontrados ${observacionesInputs.length} inputs para suscribir`);
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
            if (this.debug) console.log(` [BodegaRealtime] Canal ${channelName} ya suscrito`);
            return;
        }

        try {
            if (this.debug) {
                console.log(`🔌 [BodegaRealtime] Suscribiendo al canal: ${channelName}`);
            }

            const channel = window.Echo.private(channelName)
                .listen('detalle.actualizado', (event) => {
                    if (this.debug) console.log('📡 [BodegaRealtime] Detalle actualizado:', event);
                    this.handleDetalleUpdate(event, numeroPedido, talla);
                })
                .listen('nota.guardada', (event) => {
                    if (this.debug) console.log('📝 [BodegaRealtime] Nota guardada:', event);
                    this.handleNotaGuardada(event, numeroPedido, talla);
                })
                .error((error) => {
                    console.error(` [BodegaRealtime] Error en canal ${channelName}:`, error);
                })
                .subscribed(() => {
                    if (this.debug) console.log(` [BodegaRealtime] Suscrito a canal: ${channelName}`);
                });

            // Guardar referencia al canal
            this.channels.set(channelName, channel);

        } catch (error) {
            console.error(` [BodegaRealtime] Error suscribiendo a ${channelName}:`, error);
        }
    }

    handleDetalleUpdate(event, numeroPedido, talla) {
        if (!event.detalles) return;

        // Actualizar los campos del formulario
        const fecha = document.querySelector(`.fecha-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`);
        const fechaPedido = document.querySelector(`.fecha-pedido-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`);
        const pendientes = document.querySelector(`.pendientes-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`);
        const observaciones = document.querySelector(`.observaciones-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`);
        const estadoSelect = document.querySelector(`.estado-select[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`);

        // Evitar actualizar si el campo está siendo editado
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

        if (this.debug) {
            console.log(` [BodegaRealtime] Actualizado detalle ${numeroPedido}-${talla}`);
        }
    }

    handleNotaGuardada(event, numeroPedido, talla) {
        // Recargar notas si el modal está abierto
        if (typeof window.cargarNotas === 'function') {
            window.cargarNotas(numeroPedido, talla);
        }
        
        if (this.debug) {
            console.log(`📝 [BodegaRealtime] Nota recargada para ${numeroPedido}-${talla}`);
        }
    }

    // Método público para suscribirse a nuevos canales (útil para modales)
    subscribeToChannel(numeroPedido, talla) {
        this.subscribeToDetalleChannel(numeroPedido, talla);
    }

    // Método público para limpiar canales
    leaveChannel(channelName) {
        if (this.channels.has(channelName)) {
            window.Echo.leave(channelName);
            this.channels.delete(channelName);
            if (this.debug) console.log(`🔌 [BodegaRealtime] Abandonado canal: ${channelName}`);
        }
    }

    // Limpiar todos los canales
    leaveAllChannels() {
        this.channels.forEach((channel, channelName) => {
            window.Echo.leave(channelName);
        });
        this.channels.clear();
        if (this.debug) console.log('🧹 [BodegaRealtime] Todos los canales limpiados');
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
