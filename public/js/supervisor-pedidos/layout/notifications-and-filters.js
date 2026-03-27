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

        // Función para cargar notificaciones (órdenes pendientes + novedades)
        function cargarNotificacionesPendientes() {
            fetch('/supervisor-pedidos/notificaciones')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('notificationBadge');
                        const list = document.getElementById('notificationList');

                        badge.textContent = data.totalGeneral;
                        badge.style.display = data.totalGeneral > 0 ? 'block' : 'none';

                        // Tabs
                        let html = `
                            <div style="display:flex; border-bottom:2px solid #e0e6ed;">
                                <button class="notif-tab ${notifTabActiva === 'ordenes' ? 'active' : ''}" data-tab="ordenes"
                                    style="flex:1; padding:0.6rem; border:none; background:${notifTabActiva === 'ordenes' ? '#f0f7ff' : '#fff'}; cursor:pointer; font-weight:600; font-size:0.82rem; color:${notifTabActiva === 'ordenes' ? '#2563eb' : '#7f8c8d'}; border-bottom:${notifTabActiva === 'ordenes' ? '2px solid #2563eb' : 'none'}; margin-bottom:-2px;">
                                    Órdenes (${data.totalPendientes})
                                </button>
                                <button class="notif-tab ${notifTabActiva === 'novedades' ? 'active' : ''}" data-tab="novedades"
                                    style="flex:1; padding:0.6rem; border:none; background:${notifTabActiva === 'novedades' ? '#f0f7ff' : '#fff'}; cursor:pointer; font-weight:600; font-size:0.82rem; color:${notifTabActiva === 'novedades' ? '#2563eb' : '#7f8c8d'}; border-bottom:${notifTabActiva === 'novedades' ? '2px solid #2563eb' : 'none'}; margin-bottom:-2px;">
                                    Novedades (${data.totalNovedades})
                                </button>
                            </div>
                        `;

                        // Contenido tab Órdenes
                        html += `<div class="notif-tab-content" data-content="ordenes" style="display:${notifTabActiva === 'ordenes' ? 'block' : 'none'}; max-height:350px; overflow-y:auto;">`;
                        if (data.notificaciones && data.notificaciones.length > 0) {
                            html += data.notificaciones.map(notif => `
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

                        // Contenido tab Novedades
                        html += `<div class="notif-tab-content" data-content="novedades" style="display:${notifTabActiva === 'novedades' ? 'block' : 'none'}; max-height:350px; overflow-y:auto;">`;
                        if (data.novedades && data.novedades.length > 0) {
                            html += data.novedades.map(nov => `
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

                        // Listeners para tabs
                        list.querySelectorAll('.notif-tab').forEach(tab => {
                            tab.addEventListener('click', function(e) {
                                e.stopPropagation();
                                notifTabActiva = this.dataset.tab;
                                cargarNotificacionesPendientes();
                            });
                        });

                        // Función genérica para toggle visto
                        function toggleVisto(checkbox, url) {
                            const item = checkbox.closest('.notification-item');
                            const checked = checkbox.checked;
                            item.style.opacity = checked ? '0.55' : '1';
                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(r => r.json())
                            .then(resp => {
                                if (resp.success) {
                                    const badge = document.getElementById('notificationBadge');
                                    let count = parseInt(badge.textContent) || 0;
                                    count = resp.visto ? Math.max(0, count - 1) : count + 1;
                                    badge.textContent = count;
                                    badge.style.display = count > 0 ? 'block' : 'none';
                                }
                            })
                            .catch(err => console.error('Error toggle visto:', err));
                        }

                        // Listeners checkboxes órdenes
                        list.querySelectorAll('.pedido-visto-check').forEach(chk => {
                            chk.addEventListener('change', function(e) {
                                e.stopPropagation();
                                toggleVisto(this, `/supervisor-pedidos/notificaciones/pedido/${this.dataset.pedidoId}/toggle-visto`);
                            });
                        });

                        // Listeners checkboxes novedades
                        list.querySelectorAll('.news-visto-check').forEach(chk => {
                            chk.addEventListener('change', function(e) {
                                e.stopPropagation();
                                const source = this.dataset.source;
                                let url;
                                if (source === 'anulada') {
                                    // Anuladas usan la tabla pedidos_vistos_supervisor
                                    const pedidoId = String(this.dataset.newsId).replace('anulada_', '');
                                    url = `/supervisor-pedidos/notificaciones/pedido/${pedidoId}/toggle-visto`;
                                } else {
                                    url = `/supervisor-pedidos/notificaciones/news/${this.dataset.newsId}/toggle-visto`;
                                }
                                toggleVisto(this, url);
                            });
                        });
                    }
                })
                .catch(error => {
                    document.getElementById('notificationList').innerHTML = `
                        <div style="padding:1rem; text-align:center; color:#e74c3c;">
                            <p>Error al cargar notificaciones</p>
                        </div>
                    `;
                });
        }

        // Exponer refresh global para que otras vistas (ej. supervisor-pedidos/index) puedan forzar
        // actualización de badge/lista en tiempo real.
        try {
            window.supervisorPedidosRefreshNotificaciones = function() {
                try {
                    cargarNotificacionesPendientes();
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
            fetch('/supervisor-pedidos/notificaciones/marcar-todas-leidas', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    cargarNotificacionesPendientes();
                }
            })
            .catch(err => console.error('Error al marcar notificaciones:', err));
        });

        // Función para ir a la orden
        function irAOrden(numeroPedido) {
            if (!numeroPedido) return;
            window.location.href = '/supervisor-pedidos?aprobacion=pendiente';
        }

        // Cargar notificaciones al iniciar página y auto-refresh cada 30s
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof isCartera === 'undefined' || !isCartera) {
                cargarNotificacionesPendientes();
                setInterval(cargarNotificacionesPendientes, 30000);
            }
        });

        /**
         * Cargar contador de órdenes pendientes de aprobación
         */
        // function cargarContadorOrdenesPendientes() {
        //     fetch('/supervisor-pedidos/ordenes-pendientes-count')
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

            fetch('/supervisor-pedidos/ordenes-pendientes-count')
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

            fetch('/supervisor-pedidos/pendientes-control-calidad-count')
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
                echo.channel('despacho.pedidos')
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

