<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Asesores') - MundoIndustrial</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('mundo_icon.ico') }}">
    
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos-erp.css') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
    
    @stack('styles')
</head>
<body class="light-theme">
    <!-- Overlay para móviles -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Container principal -->
    <div class="container">
        <!-- Sidebar principal -->
    <aside class="sidebar collapsed" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('asesores.dashboard') }}" class="logo-link" aria-label="Ir al Dashboard">
                <img src="{{ asset('images/logo2.png') }}"
                     alt="Logo Mundo Industrial"
                     class="header-logo"
                     data-logo-light="{{ asset('images/logo2.png') }}"
                     data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
            </a>
            <h2 class="sidebar-title">Menú</h2>
            <button class="sidebar-toggle" aria-label="Colapsar menú">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>

        <div class="sidebar-content">
            <!-- Lista del menú principal -->
            <ul class="menu-list" role="navigation" aria-label="Menú de asesores">
                <li class="menu-item">
                    <a href="{{ route('asesores.dashboard') }}"
                       class="menu-link {{ request()->routeIs('asesores.dashboard') ? 'active' : '' }}"
                       aria-label="Ir al Dashboard">
                        <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
                        <span class="menu-label">Dashboard</span>
                    </a>
                </li>

                <li class="menu-item">
                    <button class="menu-link submenu-toggle {{ request()->routeIs('asesores.pedidos.*') ? 'active' : '' }}"
                            aria-label="Gestionar Pedidos">
                        <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
                        <span class="menu-label">Gestionar Pedido</span>
                        <span class="material-symbols-rounded submenu-arrow" aria-hidden="true">expand_more</span>
                    </button>
                    <ul class="submenu">
                        <li class="submenu-item">
                            <a href="{{ route('asesores.pedidos.index') }}"
                               class="menu-link {{ request()->routeIs('asesores.pedidos.index') && !request()->get('tipo') ? 'active' : '' }}"
                               aria-label="Ver mis pedidos">
                                <span class="menu-label">Mis Pedidos</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('asesores.pedidos.create') }}"
                               class="menu-link {{ request()->routeIs('asesores.pedidos.create') ? 'active' : '' }}"
                               aria-label="Crear nuevo pedido">
                                <span class="menu-label">Nuevo Pedido</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('asesores.pedidos.index', ['tipo' => 'borradores']) }}"
                               class="menu-link {{ request()->get('tipo') === 'borradores' ? 'active' : '' }}"
                               aria-label="Ver borradores">
                                <span class="menu-label">Borradores</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('asesores.pedidos.index', ['tipo' => 'historial']) }}"
                               class="menu-link {{ request()->get('tipo') === 'historial' ? 'active' : '' }}"
                               aria-label="Ver historial">
                                <span class="menu-label">Historial</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-item">
                    <a href="{{ route('asesores.inventario-telas.index') }}"
                       class="menu-link {{ request()->routeIs('asesores.inventario-telas.*') ? 'active' : '' }}"
                       aria-label="Ver inventario de telas">
                        <svg class="icon-thread" style="enable-background:new 0 0 64 64;" version="1.1" viewBox="0 0 64 64" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"><g id="yarn"><path d="M57,11.9C57,11.9,57,11.9,57,11.9C57,11.9,57,11.9,57,11.9C56.9,10.3,55.6,9,54,9h-6V4c0-0.6-0.4-1-1-1h-8   c-0.6,0-1,0.4-1,1v5h-6c-1.7,0-3,1.3-3,3v15.8c-1.6-0.5-3.3-0.8-5-0.8c-9.4,0-17,7.6-17,17c0,0.3,0,0.5,0,0.8c0,0.2,0,0.4,0,0.5   C7.7,54.1,15.1,61,24,61c5.2,0,9.8-2.3,12.9-6c0,0,0,0,0.1,0h1v5c0,0.6,0.4,1,1,1h8c0.6,0,1-0.4,1-1v-5h6c1.7,0,3-1.3,3-3L57,11.9   C57,12,57,11.9,57,11.9z M38.5,46.1L23,55.3v-2.7L35.3,46h3.6c0,0,0,0,0,0C38.7,46,38.6,46.1,38.5,46.1z M10.1,49.6L26.2,41h4   l-19,10.7C10.8,51.1,10.4,50.3,10.1,49.6z M9.3,40.9c0,0,0.1,0,0.1-0.1l20.4-10.7c0.9,0.4,1.8,0.9,2.6,1.4L9,43.4   C9.1,42.5,9.2,41.7,9.3,40.9z M40.9,42.7c-0.1-1.4-0.3-2.7-0.7-3.9L55,37.1v4L40.9,42.7z M23,47.4l2.6-1.5C25.7,46,25.9,46,26,46h5   l-8,4.3V47.4z M29,44l5.3-3h4.4c0.2,1,0.3,2,0.3,3H29z M22.2,39l4-2H37c0.1,0,0.2,0,0.2,0c0.3,0.7,0.6,1.3,0.9,2H22.2z M31,21.9   l24-2.8v4l-24,2.8V21.9z M55,25.1v4l-19.7,2.3c-1.3-1.2-2.7-2.1-4.3-2.9v-0.6L55,25.1z M37.1,33.2L55,31.1v4l-15.6,1.8   C38.8,35.6,38,34.3,37.1,33.2z M36,35h-5.7l4-2C34.8,33.6,35.4,34.3,36,35z M55,13.1v4l-24,2.8v-4L55,13.1z M9.1,45.6l9.2-4.6H22   L9.5,47.6C9.3,47,9.2,46.3,9.1,45.6z M21,48.5v10.2c-1-0.2-2-0.5-3-1v-7.5L21,48.5z M41,44.7l14-1.6v4l-14.7,1.7   C40.7,47.5,40.9,46.1,41,44.7z M40,5h6v4h-6V5z M32,11h7h8h7c0.2,0,0.4,0.1,0.5,0.2L31,13.9V12C31,11.4,31.4,11,32,11z M27.2,29.3   L19,33.6V30c0,0,0-0.1,0-0.1c1.6-0.6,3.3-0.9,5-0.9C25.1,29,26.2,29.1,27.2,29.3z M17,30.7v3.9l-6.9,3.6C11.5,35,13.9,32.4,17,30.7   z M12.3,53.4l3.7-2.1v5.3C14.6,55.8,13.4,54.7,12.3,53.4z M23,58.9v-1.4l7-4.1v4.3c-1.8,0.8-3.9,1.3-6,1.3C23.7,59,23.3,59,23,58.9   z M32,56.7v-4.4l6.3-3.7C37.2,52,34.9,54.8,32,56.7z M46,59h-6v-4h6V59z M54,53h-7h-8h-0.6c0.4-0.7,0.8-1.4,1.1-2.1L55,49.1V52   C55,52.6,54.6,53,54,53z"/></g></svg>
                        <span class="menu-label">Inventario Telas</span>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link" aria-label="Ver clientes">
                        <span class="material-symbols-rounded" aria-hidden="true">group</span>
                        <span class="menu-label">Clientes</span>
                    </a>
                </li>

                <li class="menu-item">
                    <a href="#" class="menu-link" aria-label="Ver reportes">
                        <span class="material-symbols-rounded" aria-hidden="true">bar_chart</span>
                        <span class="menu-label">Reportes</span>
                    </a>
                </li>
                <li class="menu-item">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="menu-link" 
                                style="border:none;background:none;cursor:pointer;width:100%;"
                                aria-label="Cerrar sesión">
                            <span class="material-symbols-rounded" aria-hidden="true">logout</span>
                            <span class="menu-label">Salir</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <!-- Footer con toggle de tema -->
        <div class="sidebar-footer">
            <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
                <div class="theme-label">
                    <span class="material-symbols-rounded" aria-hidden="true">light_mode</span>
                    <span class="theme-text">Modo Claro</span>
                </div>
                <div class="theme-toggle-track">
                    <div class="theme-toggle-indicator"></div>
                </div>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <header class="top-nav">
            <div class="nav-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
            </div>

            <div class="nav-right">
                <!-- Notificaciones -->
                <div class="notification-dropdown">
                    <button class="notification-btn" id="notificationBtn">
                        <span class="material-symbols-rounded">notifications</span>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="notification-menu" id="notificationMenu">
                        <div class="notification-header">
                            <h3>Notificaciones</h3>
                            <button class="mark-all-read">Marcar todas como leídas</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-empty">
                                <span class="material-symbols-rounded">notifications_off</span>
                                <p>No tienes notificaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Perfil de Usuario -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                            @else
                                <div class="avatar-placeholder">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ Auth::user()->name }}</span>
                            <span class="user-role">Asesor</span>
                        </div>
                        <span class="material-symbols-rounded">expand_more</span>
                    </button>
                    <div class="user-menu" id="userMenu">
                        <a href="{{ route('asesores.profile') }}" class="menu-item">
                            <span class="material-symbols-rounded">person</span>
                            <span>Mi Perfil</span>
                        </a>
                        <a href="{{ route('asesores.profile') }}" class="menu-item">
                            <span class="material-symbols-rounded">settings</span>
                            <span>Configuración</span>
                        </a>
                        <div class="menu-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="menu-item logout">
                                <span class="material-symbols-rounded">logout</span>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
    </div><!-- Cierre del container -->

    <!-- Scripts -->
    <script src="{{ asset('js/asesores/layout.js') }}"></script>
    <script src="{{ asset('js/asesores/notifications.js') }}"></script>
    @stack('scripts')
</body>
</html>
