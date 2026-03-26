/**
 * Notifications & Realtime System - Insumos
 */

function initNotificationsRealtimeInsumos() {
    let notificacionesInsumos = [];

    function playNotificationSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (_e) {
            // noop
        }
    }

    function showNotificationToast(notificacion) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg z-50 flex items-center gap-3 animate-pulse';
        toast.style.animation = 'slideInUp 0.5s ease-out';
        toast.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="font-bold">Nuevo Recibo Aprobado</p>
                <p class="text-sm">Numero: #${notificacion.pedido_numero} - ${notificacion.cliente}</p>
            </div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.5s ease-out';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    function verReciboDesdeCampana(ordenId) {
        const row = document.querySelector(`tr[data-pedido-produccion-id="${ordenId}"]`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.style.backgroundColor = '#fef3c7';
            setTimeout(() => {
                row.style.backgroundColor = '';
            }, 2000);
        }

        const dropdown = document.getElementById('insumosDropdown');
        if (dropdown) dropdown.style.display = 'none';
    }

    async function marcarReciboVisto(reciboId, itemElement) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch(`/insumos/api/recibo/${reciboId}/marcar-visto`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                },
            });

            if (!response.ok) {
                console.error('[CAMPANA] Error marcando visto:', response.status);
                alert('Error al marcar como visto');
                return;
            }

            itemElement.style.transition = 'all 0.3s ease';
            itemElement.style.opacity = '0';
            itemElement.style.maxHeight = '0';
            itemElement.style.overflow = 'hidden';
            itemElement.style.padding = '0';
            itemElement.style.margin = '0';

            setTimeout(() => {
                itemElement.remove();

                const badge = document.getElementById('insumosBadge');
                if (badge) {
                    let count = parseInt(badge.textContent) || 0;
                    count = Math.max(0, count - 1);
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-flex' : 'none';
                }

                const list = document.getElementById('insumosNotifList');
                if (list && list.querySelectorAll('[data-recibo-id]').length === 0) {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500"><p>Sin recibos pendientes</p></div>';
                }
            }, 300);
        } catch (error) {
            console.error('[CAMPANA] Error en marcarReciboVisto:', error);
            alert('Error al marcar como visto');
        }
    }

    async function cargarConteoInicial() {
        try {
            const response = await fetch('/insumos/api/contar-costura-pendiente', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                console.error('[CAMPANA] Error HTTP:', response.status);
                return;
            }

            const data = await response.json();
            const total = data.total || 0;
            const recibos = data.recibos || [];

            const badge = document.getElementById('insumosBadge');
            if (badge) {
                badge.textContent = total;
                badge.style.display = total > 0 ? 'inline-flex' : 'none';
            }

            const list = document.getElementById('insumosNotifList');
            if (!list) return;

            if (recibos.length === 0) {
                list.innerHTML = '<div class="p-4 text-center text-gray-500"><p>Sin recibos pendientes</p></div>';
                return;
            }

            list.innerHTML = '';
            recibos.forEach((recibo) => {
                const item = document.createElement('div');
                item.className = 'p-3 hover:bg-gray-50 transition border-b border-gray-100';
                item.setAttribute('data-recibo-id', recibo.id);
                item.innerHTML =
                    '<div class="flex justify-between items-center">' +
                        '<div class="flex-1 cursor-pointer" data-action="ver">' +
                            '<p class="font-bold text-blue-600">Recibo #' + recibo.numero_recibo + '</p>' +
                            '<p class="text-sm text-gray-600">' + recibo.cliente + '</p>' +
                            '<p class="text-xs text-gray-400 mt-1">' + recibo.fecha + '</p>' +
                        '</div>' +
                        '<button class="btn-marcar-visto ml-2 p-1.5 rounded-full hover:bg-green-100 transition" data-id="' + recibo.id + '" title="Marcar como visto">' +
                            '<svg class="w-5 h-5 text-gray-400 hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' +
                            '</svg>' +
                        '</button>' +
                    '</div>';

                item.querySelector('[data-action="ver"]').addEventListener('click', () => {
                    verReciboDesdeCampana(recibo.pedido_id);
                });

                item.querySelector('.btn-marcar-visto').addEventListener('click', (e) => {
                    e.stopPropagation();
                    marcarReciboVisto(recibo.id, item);
                });

                list.appendChild(item);
            });

            if (total > recibos.length) {
                const moreItem = document.createElement('div');
                moreItem.className = 'p-3 text-center text-gray-500 text-sm';
                moreItem.textContent = '... y ' + (total - recibos.length) + ' recibo(s) más';
                list.appendChild(moreItem);
            }
        } catch (error) {
            console.error('[CAMPANA] Error cargando datos:', error);
        }
    }

    function initializeRealtimeListener() {
        try {
            if (typeof window.waitForEcho !== 'function') {
                console.warn('[Realtime Insumos] Esperando a que waitForEcho este disponible...');
                setTimeout(() => initializeRealtimeListener(), 200);
                return;
            }

            window.waitForEcho(() => {
                const ws = window.shared.websocket;
                if (!ws) {
                    console.warn('[Realtime Insumos] WebSocket no disponible');
                    return;
                }

                const addNotification = (orden) => {
                    const notificacion = {
                        id: Math.random().toString(36).substr(2, 9),
                        pedido_numero: orden.numero_pedido || orden.pedido,
                        cliente: orden.cliente_nombre || 'Sin cliente',
                        timestamp: new Date().toLocaleTimeString(),
                        orden_id: orden.id,
                    };

                    notificacionesInsumos.push(notificacion);

                    const badge = document.getElementById('insumosBadge');
                    if (badge) {
                        const current = parseInt(badge.textContent || '0') + 1;
                        badge.textContent = current;
                        badge.style.display = 'inline-flex';
                    }

                    const notificationsList = document.getElementById('insumosNotifList');
                    if (notificationsList) {
                        if (
                            notificationsList.children.length === 1 &&
                            (notificationsList.children[0].textContent.includes('Sin notificaciones') ||
                                notificationsList.children[0].textContent.includes('Sin recibos'))
                        ) {
                            notificationsList.innerHTML = '';
                        }

                        const notifEl = document.createElement('div');
                        notifEl.className = 'p-4 hover:bg-gray-50 transition cursor-pointer border-b border-gray-100';
                        notifEl.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-bold text-blue-600">Recibo #${notificacion.pedido_numero}</p>
                                    <p class="text-sm text-gray-600">${notificacion.cliente}</p>
                                    <p class="text-xs text-gray-400 mt-1">${notificacion.timestamp}</p>
                                </div>
                                <button class="text-blue-600 hover:text-blue-800 font-medium text-sm px-3 py-1 rounded hover:bg-blue-50" data-insumos-action="notif-ver-recibo-campana" data-orden-id="${notificacion.orden_id}">
                                    Ver
                                </button>
                            </div>
                        `;
                        notificationsList.insertBefore(notifEl, notificationsList.firstChild);
                    }

                    playNotificationSound();
                    showNotificationToast(notificacion);
                };

                const debounce = (func, wait) => {
                    let timeout;
                    return (...args) => {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), wait);
                    };
                };

                const refreshMateriales = debounce(() => {
                    location.reload();
                }, 2000);

                const subscribeIfMatch = (data) => {
                    if (data.orden && data.orden.estado === 'PENDIENTE_INSUMOS') {
                        addNotification(data.orden);
                        refreshMateriales();
                    }
                };

                try { ws.subscribe('supervisor-pedidos', '.orden.updated', subscribeIfMatch); } catch (error) { console.error('[Realtime Insumos] subscribe supervisor-pedidos:', error); }
                try { ws.subscribe('ordenes', '.orden.updated', subscribeIfMatch); } catch (error) { console.error('[Realtime Insumos] subscribe ordenes/.orden.updated:', error); }
                try { ws.subscribe('ordenes', 'orden.updated', subscribeIfMatch); } catch (error) { console.error('[Realtime Insumos] subscribe ordenes/orden.updated:', error); }
                try { ws.subscribe('ordenes', 'OrdenUpdated', subscribeIfMatch); } catch (error) { console.error('[Realtime Insumos] subscribe ordenes/OrdenUpdated:', error); }
            });
        } catch (error) {
            console.error('[Realtime Insumos] Error inicializando listener:', error);
        }
    }

    function setupNotificationBellControls() {
        const bellBtn = document.getElementById('insumosBellBtn');
        const dropdown = document.getElementById('insumosDropdown');
        const clearBtn = document.getElementById('insumosClearBtn');

        if (bellBtn) {
            bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (dropdown) {
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                notificacionesInsumos = [];
                const badge = document.getElementById('insumosBadge');
                if (badge) {
                    badge.textContent = '0';
                    badge.style.display = 'none';
                }
                const notificationsList = document.getElementById('insumosNotifList');
                if (notificationsList) {
                    notificationsList.innerHTML = '<div class="p-4 text-center text-gray-500"><p>Sin notificaciones</p></div>';
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (dropdown && bellBtn && !dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    cargarConteoInicial();
    initializeRealtimeListener();
    setupNotificationBellControls();

    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.notificationsRealtime = {
        initializeRealtimeListener,
        setupNotificationBellControls,
        playNotificationSound,
        showNotificationToast,
        verReciboDesdeCampana,
        marcarReciboVisto,
        cargarConteoInicial,
    };

    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(100px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideOutDown {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(100px); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        #insumosBellBtn { position: relative; transition: all 0.2s ease-in-out; }
        #insumosBellBtn:hover { background-color: #dbeafe; }
        #insumosDropdown { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .notification-item { border-left: 4px solid #2563eb; transition: all 0.2s ease-in-out; }
        .notification-item:hover { background-color: #f3f4f6; }
    `;
    document.head.appendChild(style);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationsRealtimeInsumos);
} else {
    initNotificationsRealtimeInsumos();
}
