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

        // Toggle notification menu
        document.getElementById('notificationBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('notificationMenu');
            menu?.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            const notificationMenu = document.getElementById('notificationMenu');
            if (!e.target.closest('.notification-dropdown')) {
                notificationMenu?.classList.remove('active');
            }
        });
    </script>

    @stack('scripts')

</body>
</html>
