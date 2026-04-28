        // Toggle sidebar en móvil
        document.getElementById('mobileToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar?.classList.toggle('collapsed');
        });

        // Toggle sidebar con botón de collapse
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar?.classList.toggle('collapsed');
        });

        // Submenús del sidebar (patrón similar a asesoras)
        const submenuToggles = document.querySelectorAll('.submenu-toggle');
        submenuToggles.forEach((toggle) => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = toggle.nextElementSibling;
                if (!submenu || !submenu.classList.contains('submenu')) return;
                submenu.classList.toggle('open');
                toggle.classList.toggle('active');
            });
        });

        // Toggle user menu
        document.getElementById('userBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('userMenu');
            menu?.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('userMenu');
            if (!e.target.closest('.user-dropdown')) {
                userMenu?.classList.remove('show');
            }
        });

        // Toggle notification menu y cargar notificaciones
        document.getElementById('notificationBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('notificationMenu');
            menu?.classList.toggle('active');
            
            // Cargar notificaciones al abrir
            if (menu?.classList.contains('active')) {
                cargarNotificacionesPendientes();
            }
        });

        document.addEventListener('click', function(e) {
            const notificationMenu = document.getElementById('notificationMenu');
            if (!e.target.closest('.notification-dropdown')) {
                notificationMenu?.classList.remove('active');
            }
        });

        // Notificaciones del menú: solo novedades
        const notifState = {
            novedades: new Map(),
        };
        let notifRealtimeSubscribed = false;
        let badgesRealtimeSubscribed = false;

        function onEchoReady(callback) {
            if (window.SharedEchoReady && typeof window.SharedEchoReady.wait === 'function') {
                window.SharedEchoReady.wait(callback);
                return;
            }

            if (typeof callback === 'function') {
                callback();
            }
        }

        function formatFecha(fechaRaw) {
            if (!fechaRaw) return '';
            const d = new Date(fechaRaw);
            if (Number.isNaN(d.getTime())) return String(fechaRaw);
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            const hh = String(d.getHours()).padStart(2, '0');
            const min = String(d.getMinutes()).padStart(2, '0');
            return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
        }

        function iconoPorTipo(tipo = '') {
            const t = String(tipo);
            if (t.includes('pedido_creado') || t.includes('order_created')) return ['add_circle', '#10b981'];
            if (t.includes('prenda') || t.includes('epp')) return ['inventory_2', '#2563eb'];
            if (t.includes('status')) return ['sync_alt', '#f59e0b'];
            return ['notifications', '#6b7280'];
        }

        function normalizarNovedadDesdePayload(payload = {}) {
            const id = payload.id;
            if (!id) return null;
            const [icono, color] = iconoPorTipo(payload.event_type);
            return {
                id,
                source: 'news',
                pedido: payload.pedido || 0,
                descripcion: payload.description || 'Nueva novedad',
                fecha: formatFecha(payload.created_at || new Date().toISOString()),
                timestamp: payload.created_at || new Date().toISOString(),
                icono,
                color,
                visto: payload.status === 'read',
            };
        }

        function renderNotificacionesDesdeEstado() {
            const badge = document.getElementById('notificationBadge');
            const list = document.getElementById('notificationList');
            if (!badge || !list) return;

            const novedades = Array.from(notifState.novedades.values()).sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
            const totalNovedades = novedades.filter(x => !x.visto).length;

            badge.textContent = String(totalNovedades);
            badge.style.display = totalNovedades > 0 ? 'block' : 'none';

            let html = '';

            html += `<div class="notif-tab-content" data-content="novedades" style="display:block; max-height:350px; overflow-y:auto;">`;
            if (novedades.length > 0) {
                html += novedades.map(nov => `
                    <div class="notification-item" style="padding:0.7rem 1rem; border-bottom:1px solid #f0f0f0; ${nov.visto ? 'opacity:0.55;' : ''}">
                        <div style="display:flex; gap:0.6rem; align-items:start;">
                            <label style="display:flex; align-items:center; cursor:pointer; margin-top:2px; flex-shrink:0;" onclick="event.stopPropagation()">
                                <input type="checkbox" class="news-visto-check" data-news-id="${nov.id}" data-source="${nov.source || 'news'}" ${nov.visto ? 'checked' : ''}
                                    style="width:16px; height:16px; accent-color:#10b981; cursor:pointer;">
                            </label>
                            <span class="material-symbols-rounded" style="color:${nov.color}; font-size:1.3rem; margin-top:2px; flex-shrink:0;">${nov.icono}</span>
                            <div style="flex:1; min-width:0; cursor:pointer;" onclick="irAOrden(${nov.pedido || 0})">
                                <p style="margin:0 0 0.2rem 0; font-size:0.83rem; color:#2c3e50; line-height:1.3; word-break:break-word;">${nov.descripcion}</p>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    ${nov.pedido ? `<small style="color:#2563eb; font-weight:600;">Orden #${nov.pedido}</small>` : ''}
                                    <small style="color:#999;">${nov.fecha}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                html += `
                    <div style="padding:2rem; text-align:center; color:#7f8c8d;">
                        <span class="material-symbols-rounded" style="font-size:2rem; display:block; margin-bottom:0.5rem;">notifications_off</span>
                        <p>Sin novedades recientes</p>
                    </div>`;
            }
            html += `</div>`;

            list.innerHTML = html;

            list.querySelectorAll('.news-visto-check').forEach(chk => {
                chk.addEventListener('change', function(e) {
                    e.stopPropagation();
                    const newsId = this.dataset.newsId;
                    const source = this.dataset.source;
                    const notif = notifState.novedades.get(newsId);
                    if (!notif) return;
                    notif.visto = this.checked;
                    notifState.novedades.set(newsId, notif);
                    renderNotificacionesDesdeEstado();

                    let url;
                    if (source === 'anulada') {
                        const pedidoId = String(newsId).replace('anulada_', '');
                        url = `/api/supervisor-pedidos/notificaciones/pedido/${pedidoId}/toggle-visto`;
                    } else {
                        url = `/api/supervisor-pedidos/notificaciones/news/${newsId}/toggle-visto`;
                    }

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                });
            });
        }
        function cargarNotificacionesPendientes() {
            return fetch('/api/supervisor-pedidos/notificaciones', {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.success) {
                    renderNotificacionesDesdeEstado();
                    return;
                }

                const novedades = Array.isArray(data.novedades) ? data.novedades : [];
                notifState.novedades.clear();

                novedades.forEach((nov) => {
                    if (!nov || !nov.id) return;
                    notifState.novedades.set(String(nov.id), {
                        id: nov.id,
                        source: nov.source || 'news',
                        pedido: nov.pedido || 0,
                        descripcion: nov.descripcion || nov.description || 'Nueva novedad',
                        fecha: nov.fecha || formatFecha(nov.created_at || new Date().toISOString()),
                        timestamp: nov.timestamp || nov.created_at || new Date().toISOString(),
                        icono: nov.icono || iconoPorTipo(nov.tipo || nov.event_type || '')[0],
                        color: nov.color || iconoPorTipo(nov.tipo || nov.event_type || '')[1],
                        visto: Boolean(nov.visto || nov.status === 'read'),
                    });
                });

                renderNotificacionesDesdeEstado();
            })
            .catch(() => {
                renderNotificacionesDesdeEstado();
            });
        }

        function suscribirNotificacionesRealtime() {
            const echo = window.EchoInstance || window.Echo;
            if (!echo || typeof echo.channel !== 'function') return;
            if (notifRealtimeSubscribed) return;

            try {
                echo.channel('notifications')
                    .listen('.new-notification', (data) => {
                        const notif = normalizarNovedadDesdePayload(data || {});
                        if (!notif) return;
                        notifState.novedades.set(String(notif.id), { ...notif, visto: false });
                        renderNotificacionesDesdeEstado();
                    });

                if (echo.connector?.pusher?.connection?.bind) {
                    echo.connector.pusher.connection.bind('connected', () => {
                        cargarNotificacionesPendientes();
                    });
                }

                notifRealtimeSubscribed = true;
            } catch (e) {
                // noop
            }
        }

        try {
            window.supervisorPedidosRefreshNotificaciones = function() {
                try {
                    renderNotificacionesDesdeEstado();
                } catch (e) {
                    // noop
                }
            };

            window.addEventListener('supervisorPedidos:notificacionesRefresh', function() {
                try {
                    cargarNotificacionesPendientes();
                } catch (e) {
                    // noop
                }
            });
        } catch (e) {
            // noop
        }

        // Marcar todas como leídas
        document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
            e.stopPropagation();
            fetch('/api/supervisor-pedidos/notificaciones/marcar-todas-leidas', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notifState.novedades.forEach((value, key) => notifState.novedades.set(key, { ...value, visto: true }));
                    renderNotificacionesDesdeEstado();
                }
            })
            .catch(err => console.error('Error al marcar notificaciones:', err));
        });

        // Función para ir a la orden
        function irAOrden(numeroPedido) {
            if (!numeroPedido) return;
            window.location.href = '/supervisor-pedidos?aprobacion=pendiente';
        }

        function runWhenIdle(callback, timeout = 1500) {
            if (typeof callback !== 'function') return;
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(() => callback(), { timeout });
                return;
            }
            setTimeout(callback, 350);
        }

        // Inicialización liviana: suscripción realtime en idle, sin fetch pesado inmediato.
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof isCartera === 'undefined' || !isCartera) {
                runWhenIdle(() => onEchoReady(() => suscribirNotificacionesRealtime()));
            }
        });

        /**
         * Cargar contador de órdenes pendientes de aprobación
         */
        document.addEventListener('visibilitychange', function() {
            if (typeof isCartera !== 'undefined' && isCartera) return;
            if (document.visibilityState === 'visible') {
                renderNotificacionesDesdeEstado();
            }
        });

        // function cargarContadorOrdenesPendientes() {
        //     fetch('/api/supervisor-pedidos/ordenes-pendientes-count')
        //         .then(response => response.json())
        //         .then(data => {
        //             // Actualizar contador de órdenes pendientes regulares
        //             const badgePendientes = document.getElementById('ordenesPendientesCount');
        //             if (badgePendientes) {
        //                 if (data.success && data.count > 0) {
        //                     // Restar las órdenes de logo para obtener solo las regulares
        //                     const countRegulares = data.count - (data.pendientesLogo || 0);
        //                     if (countRegulares > 0) {
        //                         badgePendientes.textContent = countRegulares;
        //                         badgePendientes.style.display = 'inline-flex';
        //                     } else {
        //                         badgePendientes.style.display = 'none';
        //                     }
        //                 } else {
        //                     badgePendientes.style.display = 'none';
        //                 }
        //             }

        //             // Actualizar contador de órdenes pendientes de logo
        //             const badgeLogo = document.getElementById('ordenesPendientesLogoCount');
        //             if (badgeLogo) {
        //                 if (data.success && data.pendientesLogo > 0) {
        //                     badgeLogo.textContent = data.pendientesLogo;
        //                     badgeLogo.style.display = 'inline-flex';
        //                 } else {
        //                     badgeLogo.style.display = 'none';
        //                 }
        //             }
        //         })
        //         .catch(error => console.error('Error al cargar contador:', error));
        // }

        // Cargar contador al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof isCartera === 'undefined' || !isCartera) {
                // cargarContadorOrdenesPendientes();
            }
        });

        function cargarBadgeSidebarPedidos() {
            const badgePendientes = document.getElementById('ordenesPendientesCount');
            if (!badgePendientes) return;

            fetch('/api/supervisor-pedidos/ordenes-pendientes-count')
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.success) {
                        badgePendientes.style.display = 'none';
                        return;
                    }

                    const countRegulares = (data.count || 0) - (data.pendientesLogo || 0);
                    if (countRegulares > 0) {
                        badgePendientes.textContent = countRegulares;
                        badgePendientes.style.display = 'inline-flex';
                    } else {
                        badgePendientes.style.display = 'none';
                    }
                })
                .catch(() => {
                    badgePendientes.style.display = 'none';
                });
        }

        function cargarBadgeSidebarControlCalidad() {
            const badgesControlCalidad = document.querySelectorAll('[data-control-calidad-badge]');
            if (!badgesControlCalidad.length) return;

            fetch('/api/supervisor-pedidos/recibos/pendientes-control-calidad-count')
                .then(response => response.json())
                .then(data => {
                    const rawCount = (!data || !data.success)
                        ? 0
                        : (data.count ?? data?.data?.count ?? 0);
                    const count = parseInt(rawCount || 0, 10) || 0;
                    badgesControlCalidad.forEach((badge) => {
                        badge.textContent = String(count);
                        badge.style.display = 'inline-flex';
                    });
                })
                .catch(() => {
                    badgesControlCalidad.forEach((badge) => {
                        badge.textContent = '0';
                        badge.style.display = 'inline-flex';
                    });
                });
        }

        function isSupervisorPedidosIndexView() {
            const path = (window.location.pathname || '').replace(/\/+$/, '');
            const search = window.location.search || '';
            return path === '/supervisor-pedidos' && search === '';
        }

        function isCarteraRoute() {
            const path = (window.location.pathname || '');
            return path.startsWith('/cartera');
        }

        function debounce(fn, wait) {
            let t;
            return function(...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        const refreshBadgeDebounced = debounce(() => {
            if (isCarteraRoute()) return;
            cargarBadgeSidebarPedidos();
            cargarBadgeSidebarControlCalidad();
        }, 300);

        function iniciarRealtimeBadgeSidebarPedidos() {
            if (isCarteraRoute()) return;
            if (badgesRealtimeSubscribed) return;

            const echo = window.EchoInstance || window.Echo;
            if (!echo || typeof echo.channel !== 'function') return;

            try {
                echo.channel('pedidos.general')
                    .listen('.pedido.actualizado', () => refreshBadgeDebounced());

                echo.channel('pedidos.creados')
                    .listen('.pedido.creado', () => refreshBadgeDebounced());

                echo.channel('recibos-costura')
                    .listen('.recibo.pasado.control.calidad', () => refreshBadgeDebounced());

                badgesRealtimeSubscribed = true;
            } catch (e) {
                // noop
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (isCarteraRoute()) return;

            runWhenIdle(() => {
                cargarBadgeSidebarPedidos();
                cargarBadgeSidebarControlCalidad();
                onEchoReady(() => iniciarRealtimeBadgeSidebarPedidos());
            }, 2200);
        });

        // Recargar contador cada 30 segundos (solo en supervisores)
        if (typeof isCartera === 'undefined' || !isCartera) {
            // setInterval(cargarContadorOrdenesPendientes, 30000);
        }

        // ===== FUNCIÓN PARA LIMPIAR TODOS LOS FILTROS =====
        function limpiarTodosLosFiltros() {
            const baseUrl = window.location.origin + window.location.pathname;

            if (typeof window.navegarSupervisorPedidos === 'function') {
                window.navegarSupervisorPedidos(baseUrl);
                return;
            }

            if (typeof window.navegarPendientesCostura === 'function') {
                window.navegarPendientesCostura(baseUrl);
                return;
            }

            window.location.href = baseUrl;
        }

        function supervisorPedidosHayFiltrosActivos() {
            try {
                const url = new URL(window.location.href);
                const keys = ['busqueda', 'numero', 'cliente', 'asesora', 'forma_pago', 'estado', 'fecha', 'fecha_desde', 'fecha_hasta', 'numero_recibo', 'asesor', 'prendas', 'fecha_creacion'];
                return keys.some(k => {
                    const v = url.searchParams.get(k);
                    return v !== null && String(v).trim() !== '';
                });
            } catch (e) {
                return false;
            }
        }

        function updateClearButtonVisibility() {
            const btn = document.getElementById('clearFiltersBtn');
            if (!btn) return;
            const visible = supervisorPedidosHayFiltrosActivos();
            btn.classList.toggle('visible', visible);
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateClearButtonVisibility();
        });

        window.addEventListener('popstate', function() {
            updateClearButtonVisibility();
        });

        window.addEventListener('supervisorPedidos:filtersUpdated', function() {
            updateClearButtonVisibility();
        });
