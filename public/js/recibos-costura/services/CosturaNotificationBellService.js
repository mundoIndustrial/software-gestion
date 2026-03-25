/**
 * CosturaNotificationBellService
 * Maneja el sistema de notificaciones de campana para recibos en costura
 * 
 * Responsabilidades:
 * - Cargar conteo de recibos en ejecución de corte
 * - Actualizar badge y lista de notificaciones
 * - Marcar recibos como vistos
 * - Configurar event listeners para la campana
 * - Polling automático cada 30 segundos
 * 
 * @class CosturaNotificationBellService
 * @example
 * const service = CosturaNotificationBellService.getInstance();
 * service.init(); // Inicializar con polling automático
 * service.loadCosturaCount(); // Cargar manualmente
 */

class CosturaNotificationBellService {
    constructor() {
        this.pollInterval = null;
        this.pollDuration = 30000; // 30 segundos
    }

    /**
     * Obtener instancia singleton del servicio
     * @static
     * @returns {CosturaNotificationBellService} Instancia única
     */
    static getInstance() {
        if (!window.costuraNotificationBellServiceInstance) {
            window.costuraNotificationBellServiceInstance = new CosturaNotificationBellService();
        }
        return window.costuraNotificationBellServiceInstance;
    }

    /**
     * Inicializar el servicio con polling automático
     * @public
     */
    init() {
        this._setupEventListeners();
        this.loadCosturaCount();
        
        // Polling automático cada 30 segundos
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        this.pollInterval = setInterval(() => {
            this.loadCosturaCount();
        }, this.pollDuration);

        console.log('[🔔 CosturaNotificationBellService]  Inicializado con polling cada 30s');
    }

    /**
     * Cargar conteo de recibos en ejecución de corte
     * @public
     * @returns {Promise<void>}
     */
    async loadCosturaCount() {
        try {
            const response = await fetch('/api/recibos-costura/ejecutando-corte', {
                headers: { 
                    'Accept': 'application/json', 
                    'X-Requested-With': 'XMLHttpRequest' 
                }
            });

            if (response.ok) {
                const data = await response.json();
                const total = data.total || 0;
                const recibos = data.recibos || [];

                console.log('[🔔 CAMPANA COSTURA] Total:', total);

                this._updateBadge(total);
                this._updateList(recibos);
            }
        } catch (error) {
            console.error('[🔔 CAMPANA COSTURA] Error cargando conteo:', error);
        }
    }

    /**
     * Marcar recibo como visto
     * @public
     * @param {number} reciboId - ID del recibo
     * @param {HTMLElement} itemElement - Elemento DOM del item de notificación
     * @returns {Promise<void>}
     */
    async markAsViewed(reciboId, itemElement) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await fetch(`/api/recibos-costura/${reciboId}/marcar-visto-corte`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken || ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Animar remover del DOM
                    itemElement.style.opacity = '0';
                    itemElement.style.transform = 'translateX(10px)';
                    
                    setTimeout(() => {
                        itemElement.remove();
                        // Recargar conteo
                        this.loadCosturaCount();
                    }, 200);

                    console.log('[✓ VISTO] Recibo marcado:', reciboId);
                }
            }
        } catch (error) {
            console.error('[✗ ERROR VISTO] No se pudo marcar el recibo:', error);
        }
    }

    /**
     * PRIVADO: Actualizar badge con el conteo
     * @private
     * @param {number} total - Cantidad total de recibos
     */
    _updateBadge(total) {
        const badge = document.getElementById('costuraBadge');
        if (badge) {
            badge.textContent = total;
            if (total > 0) {
                badge.classList.add('show');
            } else {
                badge.classList.remove('show');
            }
        }
    }

    /**
     * PRIVADO: Actualizar lista de notificaciones
     * @private
     * @param {Array} recibos - Array de recibos a mostrar
     */
    _updateList(recibos) {
        const list = document.getElementById('costuraNotifList');
        if (!list) return;

        if (recibos.length > 0) {
            list.innerHTML = '';
            
            recibos.forEach((recibo) => {
                const item = document.createElement('div');
                item.className = 'costura-notif-item';

                // Contenido de la notificación
                const content = document.createElement('div');
                content.className = 'costura-notif-content';
                content.innerHTML =
                    `<p class="costura-notif-number">Recibo #${recibo.numero_recibo}</p>` +
                    `<p class="costura-notif-cliente">${recibo.cliente}</p>` +
                    `<p class="costura-notif-fecha">${recibo.fecha}</p>`;

                // Botón "Visto"
                const viewedBtn = document.createElement('button');
                viewedBtn.className = 'costura-notif-visto-btn';
                viewedBtn.textContent = 'Visto';
                viewedBtn.dataset.reciboId = recibo.id;
                viewedBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    await this.markAsViewed(recibo.id, item);
                });

                item.appendChild(content);
                item.appendChild(viewedBtn);
                list.appendChild(item);
            });
        } else {
            list.innerHTML = '<div class="costura-notif-empty">Sin recibos en ejecución</div>';
        }
    }

    /**
     * PRIVADO: Configurar event listeners para la campana
     * @private
     */
    _setupEventListeners() {
        const bellBtn = document.getElementById('costuraBellBtn');
        const dropdown = document.getElementById('costuraDropdown');
        const clearBtn = document.getElementById('costuraClearBtn');

        if (!bellBtn || !dropdown) {
            console.warn('[🔔 CosturaNotificationBellService] Elementos del bell no encontrados');
            return;
        }

        // Toggle dropdown al hacer click en campana
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        // Cerrar dropdown al hacer click en botón "Limpiar"
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.remove('show');
            });
        }

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        console.log('[🔔 CosturaNotificationBellService]  Event listeners configurados');
    }

    /**
     * Detener polling automático
     * @public
     */
    stop() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        console.log('[🔔 CosturaNotificationBellService] ⏸️ Polling detenido');
    }
}
