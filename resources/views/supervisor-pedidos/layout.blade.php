<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supervisor de Pedidos') - MundoIndustrial</title>

    <!-- CSS (heredado de asesores) -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">

    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
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
    </style>

    @stack('styles')

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
                <form method="GET" action="{{ route('supervisor-pedidos.index') }}" class="search-form" style="flex: 1; max-width: 500px; margin: 0 1rem;">
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
                        @if(request('busqueda'))
                            <a href="{{ route('supervisor-pedidos.index', array_merge(
                                request()->only(['aprobacion', 'estado', 'asesora', 'forma_pago', 'fecha_desde', 'fecha_hasta']),
                                ['busqueda' => '']
                            )) }}" class="btn-limpiar" style="padding: 0 1rem; border-radius: 20px; display: flex; align-items: center;">
                                <span class="material-symbols-rounded">close</span>
                            </a>
                        @endif
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
                        <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: none;">
                            @csrf
                        </form>
                        <button type="button" class="menu-item logout" onclick="document.getElementById('logout-form').submit();">
                            <span class="material-symbols-rounded">logout</span>
                            <span>Cerrar Sesión</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
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

        // Función para cargar notificaciones (órdenes pendientes de aprobación)
        function cargarNotificacionesPendientes() {
            fetch('/supervisor-pedidos/notificaciones')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('notificationBadge');
                        const list = document.getElementById('notificationList');
                        
                        // Actualizar badge con órdenes pendientes totales
                        // (mostrar todas las pendientes, no solo las no leídas)
                        badge.textContent = data.totalPendientes;
                        badge.style.display = data.totalPendientes > 0 ? 'block' : 'none';
                        
                        // Llenar lista de notificaciones
                        if (data.notificaciones && data.notificaciones.length > 0) {
                            list.innerHTML = data.notificaciones.map(notif => `
                                <div class="notification-item" style="padding: 1rem; border-bottom: 1px solid #e0e6ed; cursor: pointer;" onclick="irAOrden(${notif.numero_pedido})">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.95rem; color: #2c3e50;">
                                                <strong>Orden #${notif.numero_pedido}</strong>
                                            </h4>
                                            <p style="margin: 0.25rem 0; font-size: 0.85rem; color: #7f8c8d;">
                                                Cliente: <strong>${notif.cliente}</strong>
                                            </p>
                                            <p style="margin: 0.25rem 0; font-size: 0.85rem; color: #7f8c8d;">
                                                Asesor: ${notif.asesor}
                                            </p>
                                            <small style="color: #999;">${notif.fecha}</small>
                                        </div>
                                        <span style="background: #fff3cd; color: #f39c12; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; margin-left: 0.5rem;">
                                            PENDIENTE
                                        </span>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            list.innerHTML = `
                                <div class="notification-empty" style="padding: 2rem; text-align: center; color: #7f8c8d;">
                                    <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">verified</span>
                                    <p>¡Sin órdenes pendientes!</p>
                                    <small>Todas las órdenes han sido aprobadas o anuladas.</small>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    document.getElementById('notificationList').innerHTML = `
                        <div class="notification-empty" style="padding: 1rem; text-align: center; color: #e74c3c;">
                            <p>Error al cargar notificaciones</p>
                        </div>
                    `;
                });
        }

        // Función para ir a la orden
        function irAOrden(numeroPedido) {
            // Ir a la sección de órdenes pendientes
            window.location.href = '/supervisor-pedidos?aprobacion=pendiente';
        }

        // Cargar notificaciones al iniciar página
        document.addEventListener('DOMContentLoaded', function() {
            // No ejecutar en cartera (será sobrescrito)
            if (typeof isCartera === 'undefined' || !isCartera) {
                cargarNotificacionesPendientes();
                // cargarContadorOrdenesPendientes();
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

        // Recargar contador cada 30 segundos (solo en supervisores)
        if (typeof isCartera === 'undefined' || !isCartera) {
            // setInterval(cargarContadorOrdenesPendientes, 30000);
        }
    </script>

    @stack('scripts')

</body>
</html>

