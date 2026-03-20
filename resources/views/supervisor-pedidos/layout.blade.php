<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Meta tags para Reverb WebSocket (valores públicos para el cliente) -->
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-app-id" content="{{ config('broadcasting.connections.reverb.app_id') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    @endauth
    
    <title>@yield('title', 'Supervisor de Pedidos') - MundoIndustrial</title>

    <!-- CSS (heredado de asesores) -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    
    <!-- Bootstrap 4 CSS (igual que asesores) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css">

    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Font Awesome para iconos (restaurado con CDN alterno para evitar bloqueos CORS) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Fallback: si CDN falla, intenta cargar jQuery desde un CDN alterno.
        // Nota: esto debe ejecutarse ANTES de cargar Bootstrap JS.
        (function() {
            function ensureJquery(callback) {
                if (typeof window.jQuery !== 'undefined') {
                    callback();
                    return;
                }

                console.warn('[Supervisor-Pedidos] jQuery no cargó desde CDN principal, intentando fallback...');
                var s = document.createElement('script');
                s.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
                s.onload = function() {
                    callback();
                };
                s.onerror = function() {
                    console.error('[Supervisor-Pedidos] No se pudo cargar jQuery desde fallback.');
                    callback();
                };
                document.head.appendChild(s);
            }

            // Exponer helper por si algún módulo quiere esperar a jQuery
            window.waitForJquery = function(cb) {
                ensureJquery(function() {
                    try { cb && cb(); } catch (e) { /* noop */ }
                });
            };
        })();
    </script>
    
    <!-- Bootstrap 4 JS (igual que asesores) -->
    <script>
        // Cargar Bootstrap sólo cuando jQuery esté disponible (Bootstrap 4 depende de jQuery)
        window.waitForJquery(function() {
            try {
                if (document.querySelector('script[data-bootstrap-bundle]')) return;
                var bs = document.createElement('script');
                bs.src = 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js';
                bs.setAttribute('data-bootstrap-bundle', 'true');
                document.head.appendChild(bs);
            } catch (e) {
                // noop
            }
        });
    </script>

    <!-- GLOBAL: Usuario autenticado disponible desde el inicio -->
    <script>
        window.usuarioAutenticado = {
            @if(auth()->check())
                id: {{ auth()->user()->id }},
                nombre: "{{ auth()->user()->name }}",
                email: "{{ auth()->user()->email }}",
                rol: "{{ auth()->user()->roles->first()?->name ?? 'Sin Rol' }}",
                roles: @json(auth()->user()->roles->pluck('name')->toArray())
            @else
                id: null,
                nombre: 'Usuario',
                email: '',
                rol: 'Sin Rol',
                roles: []
            @endif
        };
        console.log('[Supervisor-Pedidos Layout] 👤 Usuario autenticado:', window.usuarioAutenticado);
    </script>

    <style>
        /* Asegurar que todos los modales de Bootstrap estén ocultos por defecto */
        .modal {
            display: none !important;
        }
        
        .modal.show {
            display: block !important;
        }
        
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .nav-left {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-center {
            flex: 0 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
        }

        /*  PROTECCIÓN MÁXIMA: El nav NUNCA se puede ocultar en supervisor-pedidos */
        header.top-nav {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            min-height: 72px !important;
            position: relative !important;
            z-index: 100 !important;
            pointer-events: auto !important;
        }

        /* Estilos para notificaciones */
        .notification-dropdown {
            position: relative;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #2c3e50;
            padding: 0.5rem;
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            display: none;
        }

        .notification-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 350px;
            max-height: 500px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            margin-top: 0.5rem;
        }

        .notification-menu.active {
            display: block;
        }

        .notification-header {
            padding: 1rem;
            border-bottom: 1px solid #e0e6ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #2c3e50;
        }

        .mark-all-read {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: underline;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #e0e6ed;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-empty {
            padding: 2rem;
            text-align: center;
            color: #7f8c8d;
        }

        /* Badge Alert */
        .badge-alert {
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            min-width: 24px;
            margin-left: auto;
            flex-shrink: 0;
        }

        .floating-clear-filters {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transform: scale(0) translateY(20px);
        }

        .floating-clear-filters.visible {
            opacity: 1;
            visibility: visible;
            transform: scale(1) translateY(0);
        }

        .floating-clear-filters:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
            transform: scale(1.08) translateY(0);
        }

        .floating-clear-filters:active {
            transform: scale(0.95) translateY(0);
        }

        .floating-clear-filters-tooltip {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: #1f2937;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .floating-clear-filters:hover .floating-clear-filters-tooltip {
            opacity: 1;
        }
    </style>

    @stack('styles')

    @vite(['resources/js/app.js'])

    <!-- Infraestructura compartida (DDD Híbrido - Fase 1) -->
    <script src="{{ asset('js/shared/infrastructure/HttpClient.js') }}"></script>
    <script src="{{ asset('js/shared/infrastructure/NotificationService.js') }}"></script>
    <script src="{{ asset('js/shared/infrastructure/ModalManager.js') }}"></script>
    <script src="{{ asset('js/shared/bootstrap.js') }}"></script>

    <!-- Arquitectura DDD supervisor-pedidos (Fase 2) -->
    <script src="{{ asset('js/supervisor-pedidos/core/domain/PedidoRepository.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/core/infrastructure/PedidoApiRepository.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/core/application/FilterService.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/core/application/SelectionService.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/core/application/OrderEditService.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/core/bootstrap.js') }}"></script>

    <!-- Laravel Echo & WebSockets para supervisor-pedidos -->
    @auth
    <script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endauth

</head>
    <!-- Sidebar Supervisor Pedidos (Componente propio) -->
    @include('components.sidebars.sidebar-supervisor-pedidos')

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation Moderna -->
        <header class="top-nav">
            <div class="nav-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="nav-center">
                <!-- Barra de búsqueda -->
                <form method="GET" action="{{ route('supervisor-pedidos.index') }}" class="search-form" style="flex: 1; max-width: 500px; margin: 0 1rem;" onsubmit="limpiarParametrosVacios(event)">
                    @if(request('aprobacion'))
                        <input type="hidden" name="aprobacion" value="{{ request('aprobacion') }}">
                    @endif
                    @if(request('estado'))
                        <input type="hidden" name="estado" value="{{ request('estado') }}">
                    @endif
                    @if(request('asesora'))
                        <input type="hidden" name="asesora" value="{{ request('asesora') }}">
                    @endif
                    @if(request('forma_pago'))
                        <input type="hidden" name="forma_pago" value="{{ request('forma_pago') }}">
                    @endif
                    @if(request('fecha_desde'))
                        <input type="hidden" name="fecha_desde" value="{{ request('fecha_desde') }}">
                    @endif
                    @if(request('fecha_hasta'))
                        <input type="hidden" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                    @endif
                    <div style="display: flex; gap: 0.5rem; width: 100%;">
                        <input type="text" 
                               name="busqueda" 
                               id="busqueda" 
                               class="filtro-input" 
                               placeholder="Buscar por pedido o cliente..." 
                               value="{{ request('busqueda') }}"
                               style="flex: 1; padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 20px; font-size: 0.9rem; background: var(--bg-color);">
                    </div>
                </form>
            </div>

            <div class="nav-right">
                <!-- Notificaciones -->
                <div class="notification-dropdown">
                    <button class="notification-btn" id="notificationBtn" aria-label="Notificaciones">
                        <span class="material-symbols-rounded">notifications</span>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="notification-menu" id="notificationMenu">
                        <div class="notification-header">
                            <h3>Notificaciones</h3>
                            <button class="mark-all-read">Marcar todas</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-empty">
                                <span class="material-symbols-rounded">notifications_off</span>
                                <p>Sin notificaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Perfil de Usuario -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn" aria-label="Perfil de usuario">
                        <div class="user-avatar">
                            @if(auth()->user()->avatar)
                                <img src="{{ asset('storage/supervisores/' . auth()->user()->avatar) }}"
                                     alt="Avatar"
                                     class="user-avatar">
                            @else
                                <div class="avatar-placeholder" style="background: #3498db; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem; width: 40px; height: 40px; border-radius: 50%;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <span class="user-role">Supervisor</span>
                        </div>
                    </button>
                    <div class="user-menu" id="userMenu">
                        <div class="user-menu-header">
                            <div class="user-avatar-large">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/supervisores/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}">
                                @else
                                    <div class="avatar-placeholder" style="background: #3498db; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.8rem; width: 56px; height: 56px; border-radius: 50%;">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="user-menu-name">{{ auth()->user()->name }}</p>
                                <p class="user-menu-email">{{ auth()->user()->email }}</p>
                            </div>
                        </div>
                        <div class="menu-divider"></div>
                        <a href="{{ route('supervisor-pedidos.profile') }}" class="menu-item">
                            <span class="material-symbols-rounded">person</span>
                            <span>Mi Perfil</span>
                        </a>
                        <a href="#" class="menu-item">
                            <span class="material-symbols-rounded">settings</span>
                            <span>Configuración</span>
                        </a>
                        <div class="menu-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="menu-item logout" style="border: none; background: none; cursor: pointer; width: 100%; text-align: left; padding: 0.75rem 1rem;">
                                <span class="material-symbols-rounded">logout</span>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')

            <button id="clearFiltersBtn" class="floating-clear-filters" type="button" onclick="limpiarTodosLosFiltros()" title="Limpiar todos los filtros">
                <span class="material-symbols-rounded">filter_alt_off</span>
                <div class="floating-clear-filters-tooltip">Limpiar filtros</div>
            </button>
        </div>
    </div>

    <!-- Scripts are loaded via Vite in the main layout -->
    <script>
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
        //     fetch('{{ route("supervisor-pedidos.ordenes-pendientes-count") }}')
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

            fetch('{{ route("supervisor-pedidos.ordenes-pendientes-count") }}')
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
            if (isSupervisorPedidosIndexView()) return;
            cargarBadgeSidebarPedidos();
        }, 300);

        function iniciarRealtimeBadgeSidebarPedidos() {
            if (isCarteraRoute()) return;
            if (isSupervisorPedidosIndexView()) return;

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
            if (isSupervisorPedidosIndexView()) return;

            cargarBadgeSidebarPedidos();

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
    </script>

    @stack('scripts')

    <!--  PROTECCIÓN TOTAL: Prevenir que el nav se oculte por cualquier medio -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topNav = document.querySelector('.top-nav');
            
            if (!topNav) {
                console.error(' .top-nav no encontrado');
                return;
            }

            console.log(' TOP-NAV PROTECTOR ACTIVADO');

            // Función agresiva para restaurar el nav
            function forceNavVisible() {
                // Remover cualquier clase que pueda ocultarlo
                topNav.classList.remove('hidden', 'hide', 'invisible', 'd-none', 'opacity-0');
                
                // Forzar estilos CSS
                topNav.style.cssText = `
                    display: flex !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    height: auto !important;
                    min-height: 72px !important;
                    position: relative !important;
                    z-index: 100 !important;
                    pointer-events: auto !important;
                `;
            }

            // Ejecutar inmediatamente
            forceNavVisible();

            // MutationObserver para detectar cambios
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes') {
                        console.log(`[MUTACIÓN] Atributo ${mutation.attributeName} cambió en .top-nav`);
                        forceNavVisible();
                    }
                });
            });

            observer.observe(topNav, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                subtree: false
            });

            // También proteger cada 200ms como fallback
            setInterval(() => {
                const computed = window.getComputedStyle(topNav);
                if (computed.display === 'none' || computed.visibility === 'hidden') {
                    console.log(' NAV OCULTADO - RESTAURANDO');
                    forceNavVisible();
                }
            }, 200);

            console.log(' Protecciones instaladas');
        });

        // ===== FUNCIÓN PARA LIMPIAR PARÁMETROS VACÍOS =====
        function limpiarParametrosVacios(event) {
            event.preventDefault();
            const form = event.target;
            
            // Crear objeto con todos los campos del form
            const params = {};
            new FormData(form).forEach((value, key) => {
                // Solo incluir si tiene valor y no está vacío
                if (value && value.trim() !== '') {
                    params[key] = value;
                }
            });
            
            // Construir URL con solo parámetros no vacíos
            const baseUrl = form.getAttribute('action');
            const queryParams = new URLSearchParams(params).toString();
            const finalUrl = queryParams ? baseUrl + '?' + queryParams : baseUrl;
            
            console.log('[Search] URL final:', finalUrl);
            window.location.href = finalUrl;
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
    </script>

    <div id="cotizacionModal" class="modal fullscreen" style="display: none;">
        <div class="modal-content" style="background: white;">
            <div class="modal-header">
                <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo" width="150" height="60">
                <div style="display: flex; gap: 3rem; align-items: center; flex: 1; margin-left: 2rem; color: white; font-size: 0.85rem;">
                    <div>
                        <p style="margin: 0; opacity: 0.8;">Cotización #</p>
                        <p id="modalHeaderNumber" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                    </div>
                    <div>
                        <p style="margin: 0; opacity: 0.8;">Fecha</p>
                        <p id="modalHeaderDate" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                    </div>
                    <div>
                        <p style="margin: 0; opacity: 0.8;">Cliente</p>
                        <p id="modalHeaderClient" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                    </div>
                    <div>
                        <p style="margin: 0; opacity: 0.8;">Asesora</p>
                        <p id="modalHeaderAdvisor" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                    </div>
                </div>
                <button onclick="closeCotizacionModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">✕</button>
            </div>
            <div id="modalBody" style="padding: 2rem; overflow-y: auto; background: white;"></div>
        </div>
    </div>

    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>

</body>
</html>

