/**
 * WebSocketChannelConfigurator - DDD Service Layer
 * Configura suscripciones de WebSocket según el contexto de página
 * Responsabilidad única: Mapear páginas → canales y eventos
 * 
 * Uso: const config = new WebSocketChannelConfigurator(ws, callbacks)
 *      config.configureForCurrentPage()
 */

class WebSocketChannelConfigurator {
    constructor(webSocket, callbacks = {}) {
        this.ws = webSocket;
        this.debug = callbacks.debug || false;
        this.onUpdate = callbacks.onUpdate || (() => {});
        this.onPollRequired = callbacks.onPollRequired || (() => {});
        
        // Detección de página
        this.pathname = window.location.pathname;
        this.isCarteraPage = this.pathname.includes('/cartera/pedidos');
        this.isAnyCarteraPage = this.pathname.includes('/cartera/');
        this.isSupervisorPedidosPage = this.pathname.includes('/supervisor-pedidos');
    }

    /**
     * Configurar canales según página actual
     */
    configureForCurrentPage() {
        if (this.debug) {
            console.log('[WebSocketChannelConfigurator] Configurando para:', {
                isCarteraPage: this.isCarteraPage,
                isSupervisorPage: this.isSupervisorPedidosPage
            });
        }

        // Desactivar realtime en páginas de cartera (excepto /cartera/pedidos)
        if (this.isAnyCarteraPage && !this.isCarteraPage) {
            if (this.debug) console.log('[WebSocketChannelConfigurator] Página de cartera, desactivando');
            return false;
        }

        if (this.isSupervisorPedidosPage) {
            this.configureSupervisorPage();
        } else if (this.isCarteraPage) {
            this.configureCarteraPage();
        } else {
            this.configureAsesorPage();
        }

        return true;
    }

    /**
     * Configurar para /supervisor-pedidos
     */
    configureSupervisorPage() {
        if (this.debug) console.log('[WebSocketChannelConfigurator] Configurando supervisor-pedidos');

        try {
            this.ws.subscribe('despacho.pedidos', '.pedido.actualizado', (event) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] 🔄 Pedido actualizado:', event?.pedido?.id);
                this.onUpdate('pedido.actualizado', event?.pedido);
            });

            this.ws.subscribe('pedidos.creados', '.pedido.creado', (event) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] ➕ Pedido creado:', event?.pedido?.id);
                this.onUpdate('pedido.creado', event?.pedido);
            });

            return true;
        } catch (error) {
            console.error('[WebSocketChannelConfigurator] Error en supervisor-pedidos:', error);
            return false;
        }
    }

    /**
     * Configurar para /cartera/pedidos
     */
    configureCarteraPage() {
        if (this.debug) console.log('[WebSocketChannelConfigurator] Configurando cartera/pedidos');

        try {
            // Nuevos pedidos
            this.ws.subscribe('pedidos.creados', '.pedido.creado', (event) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] ➕ Nuevo pedido (cartera):', event?.pedido?.id);
                this.onUpdate('pedido.creado', event?.pedido);
            });

            // Actualizaciones
            this.ws.subscribe('despacho.pedidos', '.pedido.actualizado', (event) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] 🔄 Actualizado (cartera):', event?.pedido?.id);
                this.onUpdate('pedido.actualizado', event?.pedido);
            });

            // Cambios de orden
            this.ws.subscribe('supervisor-pedidos', 'OrdenUpdated', (data) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] 📨 OrdenUpdated (cartera):', data?.orden?.id);
                this.onUpdate('orden.actualizada', data?.orden);
            });

            // Canal privado del usuario
            if (window.usuarioAutenticado && window.usuarioAutenticado.id) {
                const userId = window.usuarioAutenticado.id;
                this.ws.subscribe(`pedidos.${userId}`, '.PedidoActualizado', (event) => {
                    if (this.debug) console.log('[WebSocketChannelConfigurator] 📡 Privado (cartera):', event?.pedido?.id);
                    this.onUpdate('pedido.actualizado', event?.pedido);
                });
            }

            return true;
        } catch (error) {
            console.error('[WebSocketChannelConfigurator] Error en cartera-pedidos:', error);
            return false;
        }
    }

    /**
     * Configurar para asesores (canales privados)
     */
    configureAsesorPage() {
        if (this.debug) console.log('[WebSocketChannelConfigurator] Configurando página de asesor');

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (!userId) {
            console.warn('[WebSocketChannelConfigurator] User ID no encontrado, usando polling fallback');
            this.onPollRequired();
            return false;
        }

        try {
            this.ws.subscribe(`pedidos.${userId}`, '.PedidoActualizado', (event) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] 📡 Actualizado privado:', event?.pedido?.id);
                this.onUpdate('pedido.actualizado', event?.pedido);
            });

            this.ws.subscribe(`pedidos.${userId}`, '.PedidoCreado', (event) => {
                if (this.debug) console.log('[WebSocketChannelConfigurator] ➕ Creado privado:', event?.pedido?.id);
                this.onUpdate('pedido.creado', event?.pedido);
            });

            return true;
        } catch (error) {
            console.error('[WebSocketChannelConfigurator] Error en asesor:', error);
            return false;
        }
    }

    /**
     * Obtener URL de API según contexto
     */
    getApiUrl() {
        if (this.isCarteraPage) {
            return '/api/cartera/pedidos?estado=pendiente_cartera';
        }
        return '/asesores/realtime/pedidos';
    }
}

// Exportar como módulo
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketChannelConfigurator;
}
