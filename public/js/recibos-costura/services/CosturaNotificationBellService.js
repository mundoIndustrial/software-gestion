/**
 * CosturaNotificationBellService
 * Maneja el sistema de notificaciones de campana para recibos en costura.
 *
 * Responsabilidades:
 * - Cargar conteo de recibos en ejecucion de corte
 * - Actualizar badge y lista de notificaciones
 * - Marcar recibos como vistos
 * - Configurar event listeners para la campana
 * - Refrescar por eventos de Reverb/WebSocket
 *
 * @class CosturaNotificationBellService
 * @example
 * const service = CosturaNotificationBellService.getInstance();
 * service.init(); // Inicializar con carga inicial + realtime
 * service.loadCosturaCount(); // Cargar manualmente
 */

class CosturaNotificationBellService {
    constructor() {
        this.realtimeBound = false;
    }

    /**
     * Obtener instancia singleton del servicio
     * @static
     * @returns {CosturaNotificationBellService} Instancia unica
     */
    static getInstance() {
        if (!window.costuraNotificationBellServiceInstance) {
            window.costuraNotificationBellServiceInstance = new CosturaNotificationBellService();
        }
        return window.costuraNotificationBellServiceInstance;
    }

    /**
     * Inicializar el servicio con carga inicial y realtime
     * @public
     */
    init() {
        this._setupEventListeners();
        this._setupRealtimeListeners();
        this.loadCosturaCount();

        console.log('[CosturaNotificationBellService] Inicializado (realtime activo)');
    }

    /**
     * Cargar conteo de recibos en ejecucion de corte
     * @public
     * @returns {Promise<void>}
     */
    async loadCosturaCount() {
        try {
            const response = await fetch('/api/recibos-costura/ejecutando-corte', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                const total = data.total || 0;
                const recibos = data.recibos || [];

                this._updateBadge(total);
                this._updateList(recibos);
            }
        } catch (error) {
            console.error('[CosturaNotificationBellService] Error cargando conteo:', error);
        }
    }

    /**
     * Marcar recibo como visto
     * @public
     * @param {number} reciboId - ID del recibo
     * @param {HTMLElement} itemElement - Elemento DOM del item de notificacion
     * @returns {Promise<void>}
     */
    async markAsViewed(reciboId, itemElement) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/api/recibos-costura/${reciboId}/marcar-visto-corte`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken || ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    itemElement.style.opacity = '0';
                    itemElement.style.transform = 'translateX(10px)';

                    setTimeout(() => {
                        itemElement.remove();
                        this.loadCosturaCount();
                    }, 200);
                }
            }
        } catch (error) {
            console.error('[CosturaNotificationBellService] No se pudo marcar el recibo:', error);
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

                const content = document.createElement('div');
                content.className = 'costura-notif-content';
                content.innerHTML =
                    `<p class="costura-notif-number">Recibo #${recibo.numero_recibo}</p>` +
                    `<p class="costura-notif-cliente">${recibo.cliente}</p>` +
                    `<p class="costura-notif-fecha">${recibo.fecha}</p>`;

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
            list.innerHTML = '<div class="costura-notif-empty">Sin recibos en ejecucion</div>';
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
            console.warn('[CosturaNotificationBellService] Elementos del bell no encontrados');
            return;
        }

        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.remove('show');
            });
        }

        document.addEventListener('click', (e) => {
            if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        console.log('[CosturaNotificationBellService] Event listeners configurados');
    }

    /**
     * PRIVADO: Configurar listeners de tiempo real para la campana
     * @private
     */
    _setupRealtimeListeners() {
        if (this.realtimeBound) {
            return;
        }

        const onReciboAprobado = () => {
            this.loadCosturaCount();
        };

        try {
            const ws = window.shared?.websocket;
            if (ws && typeof ws.subscribe === 'function') {
                ws.subscribe('recibos-costura', '.recibo.aprobado', onReciboAprobado);
                this.realtimeBound = true;
                console.log('[CosturaNotificationBellService] Realtime configurado con window.shared.websocket');
                return;
            }
        } catch (error) {
            console.warn('[CosturaNotificationBellService] No se pudo usar window.shared.websocket:', error);
        }

        if (window.EchoInstance && typeof window.EchoInstance.channel === 'function') {
            window.EchoInstance
                .channel('recibos-costura')
                .listen('recibo.aprobado', onReciboAprobado);
            this.realtimeBound = true;
            console.log('[CosturaNotificationBellService] Realtime configurado con EchoInstance');
            return;
        }

        console.warn('[CosturaNotificationBellService] Realtime no disponible; solo carga inicial');
    }

    /**
     * Detener listeners activos
     * @public
     */
    stop() {
        this.realtimeBound = false;
        console.log('[CosturaNotificationBellService] stop() ejecutado');
    }
}
