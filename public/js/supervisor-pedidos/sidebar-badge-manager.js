/**
 * =====================================================
 * SIDEBAR BADGE MANAGER - Control de Calidad
 * =====================================================
 * Gestiona el badge de "Pendiente C.C" en el sidebar
 * - Actualiza el contador desde la API
 * - Se suscribe a cambios en tiempo real
 * - Muestra/oculta el badge según corresponda
 */

(function() {
    'use strict';

    // Referencias del DOM
    const badgeElement = document.getElementById('controlCalidadPendientesCount');
    const contentContainer = document.getElementById('supervisorPendientesControlCalidadContent');
    
    if (!badgeElement) {
        console.warn('[SidebarBadgeManager] Badge element not found');
        return;
    }

    /**
     * Cuenta los recibos actuales en la tabla (fallback)
     */
    function countReceiptsInTable() {
        if (!contentContainer) return 0;
        
        const rows = contentContainer.querySelectorAll('[data-row="processo"]');
        return rows.length;
    }

    /**
     * Obtiene el contador desde la API
     */
    async function fetchCountFromApi() {
        try {
            const response = await fetch('/api/supervisor-pedidos/recibos/pendientes-control-calidad-count', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            return data?.data?.count ?? 0;
        } catch (err) {
            console.warn('[SidebarBadgeManager] Error fetching count from API:', err);
            // Fallback a contar en la tabla
            return countReceiptsInTable();
        }
    }

    /**
     * Actualiza el badge con el contador
     */
    async function updateBadge() {
        const count = await fetchCountFromApi();
        
        if (count > 0) {
            badgeElement.textContent = count;
            badgeElement.style.display = 'flex';
        } else {
            badgeElement.style.display = 'none';
        }
    }

    /**
     * Actualiza el badge cuando la tabla cambia
     */
    function onTableUpdate() {
        updateBadge();
    }

    /**
     * Escucha eventos de actualización de filtros
     */
    function setupEventListeners() {
        // Cuando se aplican filtros o se navega
        document.addEventListener('supervisorPedidos:filtersUpdated', onTableUpdate);
        
        // Cuando se actualiza la tabla (desde navegarPendientesControlCalidad)
        window.addEventListener('supervisorPedidos:filtersUpdated', onTableUpdate);
    }

    /**
     * Escucha eventos de WebSocket para actualizaciones en tiempo real
     */
    function setupRealtimeListeners() {
        // Esperar a que el sistema de WebSocket esté listo
        if (typeof window.waitForEcho === 'function') {
            window.waitForEcho().then(() => {
                setupWebSocketListeners();
            }).catch(err => {
                console.warn('[SidebarBadgeManager] WebSocket not available', err);
            });
        } else {
            // Fallback: verificar disponibilidad después de un tiempo
            setTimeout(() => {
                if (window.Echo) {
                    setupWebSocketListeners();
                }
            }, 1000);
        }
    }

    /**
     * Configura listeners de WebSocket
     */
    function setupWebSocketListeners() {
        if (!window.Echo) return;

        try {
            // Escuchar el evento específico cuando un recibo pasa a control de calidad
            window.Echo.channel('recibos-costura').listen('.recibo.pasado.control.calidad', (event) => {
                console.log('[SidebarBadgeManager] Recibo pasado a control de calidad:', event);
                // Actualizar badge inmediatamente
                setTimeout(updateBadge, 300);
            });

            console.log('[SidebarBadgeManager] WebSocket listeners configurados');
        } catch (err) {
            console.warn('[SidebarBadgeManager] Error configurando WebSocket listeners:', err);
        }
    }

    /**
     * Inicializa el gestor del badge
     */
    function init() {
        console.log('[SidebarBadgeManager] Inicializando...');
        
        // Actualizar badge al cargar
        updateBadge();
        
        // Configurar listeners de eventos
        setupEventListeners();
        
        // Configurar listeners de WebSocket
        setupRealtimeListeners();
        
        console.log('[SidebarBadgeManager] Inicializado correctamente');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
