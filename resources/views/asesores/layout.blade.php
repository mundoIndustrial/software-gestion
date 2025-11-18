<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Asesores') - MundoIndustrial</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    @stack('styles')
</head>
<body class="light-theme">
    <!-- Sidebar Moderno -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-wrapper">
                <img src="{{ asset('images/logo2.png') }}" 
                     alt="Logo" 
                     class="header-logo"
                     data-logo-light="{{ asset('images/logo2.png') }}"
                     data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>

        <div class="sidebar-content">
            <div class="menu-section">
                <span class="menu-section-title">Gestión</span>
                <ul class="menu-list" role="navigation">
                    <li class="menu-item">
                        <a href="{{ route('asesores.dashboard') }}" 
                           class="menu-link {{ request()->routeIs('asesores.dashboard') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">dashboard</span>
                            <span class="menu-label">Dashboard</span>
                            <span class="menu-badge">New</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.pedidos.index') }}" 
                           class="menu-link {{ request()->routeIs('asesores.pedidos.index') || request()->routeIs('asesores.pedidos.show') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">assignment</span>
                            <span class="menu-label">Mis Pedidos</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.borradores.index') }}" 
                           class="menu-link {{ request()->routeIs('asesores.borradores.*') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">edit_note</span>
                            <span class="menu-label">Mis Borradores</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Información</span>
                <ul class="menu-list">
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <span class="material-symbols-rounded">group</span>
                            <span class="menu-label">Clientes</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <span class="material-symbols-rounded">bar_chart</span>
                            <span class="menu-label">Reportes</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
                <span class="material-symbols-rounded">light_mode</span>
                <span class="theme-text">Tema</span>
            </button>
        </div>
    </aside>

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

            <div class="nav-right">
                <!-- Search Bar -->
                <div class="search-bar">
                    <span class="material-symbols-rounded">search</span>
                    <input type="text" placeholder="Buscar pedidos...">
                </div>

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
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
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
                    </button>
                    <div class="user-menu" id="userMenu">
                        <div class="user-menu-header">
                            <div class="user-avatar-large">
                                @if(Auth::user()->avatar)
                                    <img src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                                @else
                                    <div class="avatar-placeholder">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="user-menu-name">{{ Auth::user()->name }}</p>
                                <p class="user-menu-email">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        <div class="menu-divider"></div>
                        <a href="{{ route('asesores.profile') }}" class="menu-item">
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

    <!-- Scripts -->
    <script src="{{ asset('js/asesores/layout.js') }}"></script>
    <script src="{{ asset('js/asesores/notifications.js') }}"></script>
    @stack('scripts')
</body>
</html>
