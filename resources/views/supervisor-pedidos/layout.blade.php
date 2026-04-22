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
    <script src="{{ asset('js/supervisor-pedidos/layout/jquery-bootstrap-loader.js') }}"></script>
    
    <!-- Bootstrap 4 JS (igual que asesores) -->

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
        
        // Compatibilidad: Alias USUARIO_ACTUAL para scripts que lo buscan
        window.USUARIO_ACTUAL = window.usuarioAutenticado;
        
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

        .top-nav .search-form {
            flex: 1 !important;
            max-width: 500px !important;
            margin: 0 1rem !important;
            min-width: 0;
        }

        .top-nav .search-form > div {
            width: 100%;
        }

        .top-nav .filtro-input {
            width: 100%;
            min-width: 0;
        }

        .top-nav .nav-left,
        .top-nav .nav-center,
        .top-nav .nav-right {
            min-width: 0;
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

        @media (max-width: 1200px) {
            .top-nav {
                gap: 1rem;
            }

            .top-nav .search-form {
                max-width: 380px !important;
                margin: 0 0.5rem !important;
            }
        }

        @media (max-width: 992px) {
            header.top-nav {
                flex-wrap: wrap !important;
                row-gap: 0.75rem;
                column-gap: 0.75rem;
                align-items: center !important;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }

            .nav-left {
                flex: 1 1 auto;
                order: 1;
            }

            .nav-right {
                flex: 0 0 auto;
                order: 2;
                margin-left: auto;
                gap: 0.5rem;
            }

            .nav-center {
                order: 3;
                flex: 1 1 100%;
                width: 100%;
                justify-content: stretch;
            }

            .top-nav .search-form {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
            }
        }

        @media (max-width: 768px) {
            .top-nav {
                gap: 0.5rem;
            }

            .breadcrumb-section {
                display: block !important;
                min-width: 0;
            }

            .page-title {
                font-size: 0.72rem;
                font-weight: 600;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 120px;
                margin: 0;
            }

            .user-info {
                display: none;
            }

            .notification-menu,
            .user-menu {
                right: 0;
                left: auto;
                width: min(92vw, 350px);
                max-width: 92vw;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 0.65rem;
                max-width: 88px;
            }
        }

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

        .submenu {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.25s ease, opacity 0.2s ease;
        }

        .submenu.open {
            max-height: 420px;
            opacity: 1;
        }

        .submenu-item {
            margin-top: 4px;
        }

        .submenu-item .menu-link {
            font-size: 0.82rem;
            min-height: 38px;
            padding: 8px 12px 8px 30px;
        }

        .submenu-toggle {
            justify-content: space-between;
        }

        .submenu-arrow {
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        .submenu-toggle.active .submenu-arrow {
            transform: rotate(180deg);
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

    <!-- CRÍTICO: Definir window.waitForEcho ANTES del resto de scripts -->
    <!-- resources/js/bootstrap.js lo llamará vía notifyEchoReady() cuando esté listo -->
    <script src="{{ asset('js/supervisor-pedidos/layout/echo-ready.js') }}"></script>

    @vite(['resources/js/app.js', 'resources/js/supervisor-pedidos/index.js'])

    <!-- DDD Core Bundles (Fase 3: 10 scripts → 2 bundles minificados) -->
    @if(app()->environment('production'))
        <script src="{{ asset('js/bundles/shared-core.min.js') }}"></script>
        <script src="{{ asset('js/bundles/sp-core.min.js') }}"></script>
    @else
        <script src="{{ asset('js/bundles/shared-core.js') }}"></script>
        <script src="{{ asset('js/bundles/sp-core.js') }}"></script>
    @endif

    <!-- Laravel Echo & WebSockets para supervisor-pedidos -->
    @auth
    <script src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endauth

</head>
<body data-module="supervisor-pedidos">
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
                @php
                    $searchAction = trim($__env->yieldContent('search-action'));
                @endphp
                <form method="GET" action="{{ $searchAction !== '' ? $searchAction : route('supervisor-pedidos.index') }}" class="search-form" style="flex: 1; max-width: 500px; margin: 0 1rem;" onsubmit="limpiarParametrosVacios(event)">
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
    <script src="{{ asset('js/shared/echo-ready-utils.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/layout/notifications-and-filters.js') }}"></script>

    @stack('scripts')
    <script src="{{ asset('js/supervisor-pedidos/layout/nav-protector.js') }}"></script>

    <!--  PROTECCIÓN TOTAL: Prevenir que el nav se oculte por cualquier medio -->

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
