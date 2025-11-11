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
            <img src="{{ asset('images/logo2.png') }}"
                 alt="Logo Mundo Industrial"
                 class="header-logo"
                 data-logo-light="{{ asset('images/logo2.png') }}"
                 data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
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
