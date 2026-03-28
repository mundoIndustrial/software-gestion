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

        // Tab activa para notificaciones
        let notifTabActiva = 'ordenes';
        const notifState = {
            ordenes: new Map(),
            novedades: new Map(),
        };

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

        function normalizarOrdenDesdePayload(payload = {}) {
            const id = Number(payload.id || payload.pedido_id || payload.record_id || 0);
            if (!id) return null;
            return {
                id,
                numero_pedido: payload.numero_pedido || payload.pedido || id,
                cliente: payload.cliente || payload.cliente_nombre || 'Sin cliente',
                asesor: payload.asesor || payload.asesora || payload.asesora_nombre || 'N/A',
                fecha: formatFecha(payload.created_at || payload.updated_at || new Date().toISOString()),
                timestamp: payload.created_at || payload.updated_at || new Date().toISOString(),
                visto: Boolean(payload.visto),
            };
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

            const ordenes = Array.from(notifState.ordenes.values()).sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
            const novedades = Array.from(notifState.novedades.values()).sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
            const totalPendientes = ordenes.filter(x => !x.visto).length;
            const totalNovedades = novedades.filter(x => !x.visto).length;
            const totalGeneral = totalPendientes + totalNovedades;

            badge.textContent = String(totalGeneral);
            badge.style.display = totalGeneral > 0 ? 'block' : 'none';

            let html = `
                <div style="display:flex; border-bottom:2px solid #e0e6ed;">
                    <button class="notif-tab ${notifTabActiva === 'ordenes' ? 'active' : ''}" data-tab="ordenes"
                        style="flex:1; padding:0.6rem; border:none; background:${notifTabActiva === 'ordenes' ? '#f0f7ff' : '#fff'}; cursor:pointer; font-weight:600; font-size:0.82rem; color:${notifTabActiva === 'ordenes' ? '#2563eb' : '#7f8c8d'}; border-bottom:${notifTabActiva === 'ordenes' ? '2px solid #2563eb' : 'none'}; margin-bottom:-2px;">
                        Órdenes (${totalPendientes})
                    </button>
                    <button class="notif-tab ${notifTabActiva === 'novedades' ? 'active' : ''}" data-tab="novedades"
                        style="flex:1; padding:0.6rem; border:none; background:${notifTabActiva === 'novedades' ? '#f0f7ff' : '#fff'}; cursor:pointer; font-weight:600; font-size:0.82rem; color:${notifTabActiva === 'novedades' ? '#2563eb' : '#7f8c8d'}; border-bottom:${notifTabActiva === 'novedades' ? '2px solid #2563eb' : 'none'}; margin-bottom:-2px;">
                        Novedades (${totalNovedades})
                    </button>
                </div>
            `;

            html += `<div class="notif-tab-content" data-content="ordenes" style="display:${notifTabActiva === 'ordenes' ? 'block' : 'none'}; max-height:350px; overflow-y:auto;">`;
            if (ordenes.length > 0) {
                html += ordenes.map(notif => `
                    <div class="notification-item" style="padding:0.7rem 1rem; border-bottom:1px solid #e0e6ed; ${notif.visto ? 'opacity:0.55;' : ''}">
                        <div style="display:flex; gap:0.6rem; align-items:start;">
                            <label style="display:flex; align-items:center; cursor:pointer; margin-top:2px; flex-shrink:0;" onclick="event.stopPropagation()">
                                <input type="checkbox" class="pedido-visto-check" data-pedido-id="${notif.id}" ${notif.visto ? 'checked' : ''}
                                    style="width:16px; height:16px; accent-color:#10b981; cursor:pointer;">
                            </label>
                            <div style="flex:1; min-width:0;">
                                <h4 style="margin:0 0 0.3rem 0; font-size:0.9rem; color:#2c3e50;">
                                    <strong>Orden #${notif.numero_pedido}</strong>
                                </h4>
                                <p style="margin:0.15rem 0; font-size:0.82rem; color:#7f8c8d;">
                                    Cliente: <strong>${notif.cliente}</strong>
                                </p>
                                <p style="margin:0.15rem 0; font-size:0.82rem; color:#7f8c8d;">
                                    Asesor: ${notif.asesor}
                                </p>
                                <small style="color:#999;">${notif.fecha}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                html += `
                    <div style="padding:2rem; text-align:center; color:#7f8c8d;">
                        <span class="material-symbols-rounded" style="font-size:2rem; display:block; margin-bottom:0.5rem;">verified</span>
                        <p>¡Sin órdenes pendientes!</p>
                    </div>`;
            }
            html += `</div>`;

            html += `<div class="notif-tab-content" data-content="novedades" style="display:${notifTabActiva === 'novedades' ? 'block' : 'none'}; max-height:350px; overflow-y:auto;">`;
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

            list.querySelectorAll('.notif-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifTabActiva = this.dataset.tab;
                    renderNotificacionesDesdeEstado();
                });
            });

            list.querySelectorAll('.pedido-visto-check').forEach(chk => {
                chk.addEventListener('change', function(e) {
                    e.stopPropagation();
                    const pedidoId = Number(this.dataset.pedidoId || 0);
                    const notif = notifState.ordenes.get(pedidoId);
                    if (!notif) return;
                    notif.visto = this.checked;
                    notifState.ordenes.set(pedidoId, notif);
                    renderNotificacionesDesdeEstado();
                    fetch(`/api/supervisor-pedidos/notificaciones/pedido/${pedidoId}/toggle-visto`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                });
            });

            list.querySelectorAll('.news-visto-check').forEach(chk => {
                chk.addEventListener('change', function(e) {
                    e.stopPropagation();
                    const newsId = this.dataset.newsId;
                    const notif = notifState.novedades.get(newsId);
                    if (!notif) return;
                    notif.visto = this.checked;
                    notifState.novedades.set(newsId, notif);
                    renderNotificacionesDesdeEstado();
                    fetch(`/api/supervisor-pedidos/notificaciones/news/${newsId}/toggle-visto`, {
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
            renderNotificacionesDesdeEstado();
            return Promise.resolve();
        }

        function hidratarNotificacionesDesdeTabla() {
            const filas = document.querySelectorAll('[data-pedido-row="true"]');
            filas.forEach((fila) => {
                const pedidoId = Number(fila.getAttribute('data-pedido-id') || 0);
                if (!pedidoId) return;
                if (notifState.ordenes.has(pedidoId)) return;

                const celdas = fila.children;
                const fecha = celdas[2]?.textContent?.trim() || '';
                const numeroRaw = celdas[3]?.textContent?.trim() || String(pedidoId);
                const numero = numeroRaw.replace('#', '').trim();
                const cliente = celdas[4]?.textContent?.trim() || 'Sin cliente';
                const asesor = celdas[7]?.textContent?.trim() || 'N/A';

                notifState.ordenes.set(pedidoId, {
                    id: pedidoId,
                    numero_pedido: numero,
                    cliente,
                    asesor,
                    fecha,
                    timestamp: new Date().toISOString(),
                    visto: false,
                });
            });
        }

        function suscribirNotificacionesRealtime() {
            const echo = window.EchoInstance;
            if (!echo || typeof echo.channel !== 'function') return;

            try {
                echo.channel('pedidos.general')
                    .listen('.pedido.actualizado', (data) => {
                        const payload = data?.pedido || data?.orden || data || {};
                        const notif = normalizarOrdenDesdePayload(payload);
                        if (!notif) return;
                        notifState.ordenes.set(notif.id, { ...notif, visto: false });
                        renderNotificacionesDesdeEstado();
                    });

                echo.channel('pedidos.creados')
                    .listen('.pedido.creado', (data) => {
                        const payload = data?.pedido || data?.orden || data || {};
                        const notif = normalizarOrdenDesdePayload(payload);
                        if (!notif) return;
                        notifState.ordenes.set(notif.id, { ...notif, visto: false });
                        renderNotificacionesDesdeEstado();
                    });

                echo.channel('notifications')
                    .listen('.new-notification', (data) => {
                        const notif = normalizarNovedadDesdePayload(data || {});
                        if (!notif) return;
                        notifState.novedades.set(String(notif.id), { ...notif, visto: false });
                        renderNotificacionesDesdeEstado();
                    });
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

            window.addEventListener('supervisorPedidos:notificacionesRefresh', function(evt) {
                try {
                    const payload = evt?.detail?.pedido || evt?.detail?.raw?.pedido || evt?.detail?.raw?.orden || null;
                    const notif = normalizarOrdenDesdePayload(payload || {});
                    if (notif) {
                        notifState.ordenes.set(notif.id, { ...notif, visto: false });
                    }
                    renderNotificacionesDesdeEstado();
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
                    notifState.ordenes.forEach((value, key) => notifState.ordenes.set(key, { ...value, visto: true }));
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

        // Cargar notificaciones locales al iniciar (sin GET de notificaciones)
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof isCartera === 'undefined' || !isCartera) {
                hidratarNotificacionesDesdeTabla();
                renderNotificacionesDesdeEstado();

                let triesNotif = 0;
                const maxTriesNotif = 100;
                const timerNotif = setInterval(() => {
                    triesNotif++;
                    if (window.EchoInstance && typeof window.EchoInstance.channel === 'function') {
                        clearInterval(timerNotif);
                        suscribirNotificacionesRealtime();
                    } else if (triesNotif >= maxTriesNotif) {
                        clearInterval(timerNotif);
                    }
                }, 200);
            }
        });

        /**
         * Cargar contador de órdenes pendientes de aprobación
         */
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
            const badgeControlCalidad = document.getElementById('controlCalidadPendientesCount');
            if (!badgeControlCalidad) return;

            fetch('/api/supervisor-pedidos/recibos/pendientes-control-calidad-count')
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.success || (data.count || 0) <= 0) {
                        badgeControlCalidad.style.display = 'none';
                        return;
                    }

                    badgeControlCalidad.textContent = data.count;
                    badgeControlCalidad.style.display = 'inline-flex';
                })
                .catch(() => {
                    badgeControlCalidad.style.display = 'none';
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

            const echo = window.EchoInstance;
            if (!echo || typeof echo.channel !== 'function') return;

            try {
                echo.channel('pedidos.general')
                    .listen('.pedido.actualizado', () => refreshBadgeDebounced());

                echo.channel('pedidos.creados')
                    .listen('.pedido.creado', () => refreshBadgeDebounced());
            } catch (e) {
                // noop
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (isCarteraRoute()) return;

            cargarBadgeSidebarPedidos();
            cargarBadgeSidebarControlCalidad();

            let tries = 0;
            const maxTries = 100;
            const timer = setInterval(() => {
                tries++;
                if (window.EchoInstance && typeof window.EchoInstance.channel === 'function') {
                    clearInterval(timer);
                    iniciarRealtimeBadgeSidebarPedidos();
                } else if (tries >= maxTries) {
                    clearInterval(timer);
                }
            }, 200);
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
                const keys = ['busqueda', 'numero', 'cliente', 'asesora', 'forma_pago', 'estado', 'fecha_desde', 'fecha_hasta', 'numero_recibo', 'asesor', 'prendas', 'fecha_creacion'];
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


