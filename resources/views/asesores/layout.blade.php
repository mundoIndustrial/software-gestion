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
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 para modales profesionales -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Alpine.js para interactividad -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
                <span class="menu-section-title">Principal</span>
                <ul class="menu-list" role="navigation">
                    <li class="menu-item">
                        <a href="{{ route('asesores.dashboard') }}" 
                           class="menu-link {{ request()->routeIs('asesores.dashboard') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">dashboard</span>
                            <span class="menu-label">Dashboard</span>
                            <span class="menu-badge">New</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Cotizaciones</span>
                <ul class="menu-list" role="navigation">
                    <li class="menu-item">
                        <a href="{{ route('asesores.cotizaciones.index') }}" 
                           class="menu-link {{ request()->routeIs('asesores.cotizaciones.*') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">description</span>
                            <span class="menu-label">Mis Cotizaciones</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.pedidos.create') }}" 
                           class="menu-link {{ request()->routeIs('asesores.pedidos.create') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">add_circle</span>
                            <span class="menu-label">Nueva Cotización</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.cotizaciones-bordado.lista') }}" 
                           class="menu-link {{ request()->routeIs('asesores.cotizaciones-bordado.*') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">brush</span>
                            <span class="menu-label">Cotizaciones Bordados</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.cotizaciones-bordado.create') }}" 
                           class="menu-link">
                            <span class="material-symbols-rounded">add</span>
                            <span class="menu-label">Nuevo Bordado</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Seguimiento</span>
                <ul class="menu-list" role="navigation">
                    <li class="menu-item">
                        <a href="{{ route('asesores.pedidos.index') }}" 
                           class="menu-link {{ request()->routeIs('asesores.pedidos.index') || request()->routeIs('asesores.pedidos.show') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">assignment</span>
                            <span class="menu-label">Pedidos</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Información</span>
                <ul class="menu-list">
                    <li class="menu-item">
                        <a href="{{ route('asesores.clientes.index') }}" 
                           class="menu-link {{ request()->routeIs('asesores.clientes.*') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">group</span>
                            <span class="menu-label">Clientes</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.reportes.index') }}" 
                           class="menu-link {{ request()->routeIs('asesores.reportes.*') ? 'active' : '' }}">
                            <span class="material-symbols-rounded">bar_chart</span>
                            <span class="menu-label">Reportes</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('asesores.inventario.telas') }}" 
                           class="menu-link {{ request()->routeIs('asesores.inventario.telas') ? 'active' : '' }}">
                            <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" class="menu-icon-svg">
                                <g>
                                    <g>
                                        <g>
                                            <path d="M170.667,349.867v4.881l2.278,0.828c1.562,1.715,3.755,2.825,6.255,2.825c0.341,0,0.64-0.154,0.973-0.196l94.831,34.483
                                                l38.204-22.4l-129.664-36.463c-5.769-10.709-16.947-18.091-29.943-18.091c-14.114,0-25.6-11.486-25.6-25.6
                                                c0-32.939-26.803-59.733-59.733-59.733S8.533,257.195,8.533,290.133s26.803,59.733,59.733,59.733
                                                c4.719,0,8.533-3.823,8.533-8.533s-3.814-8.533-8.533-8.533c-23.526,0-42.667-19.14-42.667-42.667
                                                c0-23.526,19.14-42.667,42.667-42.667s42.667,19.14,42.667,42.667c0,23.526,19.14,42.667,42.667,42.667
                                                C163.012,332.8,170.667,340.454,170.667,349.867z"/>
                                            <path d="M170.667,112.649v59.605l76.51-40.269c9.668,1.314,19.584,2.338,29.611,3.081l0.256,0.486l-106.377,55.987v36.386
                                                l164.275-91.759c13.636-0.495,27.238-1.51,40.465-3.055L205.303,228.13l46.848,11.708L460.8,121.421v-8.772
                                                c25.984-11.119,42.667-25.984,42.667-44.382C503.467,23.927,406.741,0,315.733,0S128,23.927,128,68.267
                                                C128,86.665,144.683,101.53,170.667,112.649z M315.733,17.067c97.681,0,170.667,27.025,170.667,51.2
                                                c0,18.091-40.909,37.786-102.451,46.541c-0.435,0.068-0.887,0.119-1.323,0.179c-4.617,0.64-9.344,1.229-14.182,1.741
                                                c-1.451,0.154-2.935,0.273-4.403,0.41c-4.011,0.384-8.047,0.759-12.194,1.058c-2.97,0.205-5.999,0.333-9.02,0.495
                                                c-2.859,0.145-5.675,0.341-8.593,0.444c-6.11,0.213-12.279,0.333-18.5,0.333s-12.39-0.119-18.5-0.333
                                                c-2.918-0.102-5.734-0.299-8.593-0.444c-3.021-0.162-6.05-0.29-9.02-0.495c-4.147-0.299-8.184-0.674-12.194-1.058
                                                c-1.468-0.137-2.953-0.256-4.403-0.41c-4.838-0.512-9.566-1.101-14.191-1.741c-0.435-0.06-0.879-0.111-1.306-0.171
                                                c-61.551-8.764-102.46-28.459-102.46-46.549C145.067,44.092,218.052,17.067,315.733,17.067z"/>
                                            <path d="M460.8,399.403v-35.081l-148.028,87.893c-10.991-0.068-21.376-0.555-31.147-1.357L460.8,344.474v-34.876l-7.219-1.809
                                                L225.749,441.344c-8.192-2.338-15.164-4.907-20.821-7.578l50.953-29.867l-85.214-30.985v26.487
                                                C135.629,414.49,128,431.693,128,443.733C128,488.073,224.725,512,315.733,512s187.733-23.927,187.733-68.267
                                                C503.467,431.693,495.838,414.49,460.8,399.403z M315.733,494.933c-97.681,0-170.667-27.025-170.667-51.2
                                                c0-8.26,9.37-17.459,25.6-25.549c0,1.041,0.282,2.014,0.418,3.029c0.145,1.016,0.154,2.065,0.427,3.055
                                                c0.265,0.922,0.768,1.783,1.135,2.671c0.427,1.024,0.742,2.082,1.314,3.063c0.444,0.768,1.101,1.476,1.63,2.219
                                                c0.742,1.058,1.399,2.133,2.313,3.14c0.529,0.589,1.237,1.126,1.818,1.707c1.126,1.101,2.193,2.219,3.507,3.268
                                                c0.538,0.427,1.212,0.819,1.775,1.237c1.544,1.135,3.072,2.278,4.821,3.345c0.41,0.247,0.905,0.469,1.323,0.717
                                                c2.048,1.195,4.147,2.372,6.451,3.482c0.256,0.128,0.555,0.23,0.811,0.358c5.35,2.517,11.298,4.821,17.801,6.878
                                                c0.051,0.008,0.102,0.026,0.145,0.043c6.775,2.125,14.097,3.994,21.828,5.606c0.478,0.094,0.99,0.179,1.468,0.273
                                                c7.373,1.493,15.061,2.765,23.031,3.789c0.188,0.026,0.384,0.034,0.572,0.06c4.062,0.512,8.166,0.973,12.331,1.365
                                                c1.647,0.154,3.328,0.247,4.984,0.384c11.537,0.939,23.347,1.459,35.166,1.459s23.629-0.521,35.166-1.459
                                                c1.655-0.137,3.336-0.23,4.983-0.384c4.164-0.393,8.269-0.853,12.331-1.365c0.188-0.026,0.384-0.034,0.572-0.06
                                                c7.97-1.024,15.659-2.296,23.031-3.789c0.478-0.094,0.99-0.179,1.468-0.273c7.731-1.613,15.053-3.482,21.828-5.606
                                                c0.043-0.017,0.094-0.034,0.145-0.043c6.502-2.057,12.45-4.361,17.801-6.878c0.256-0.128,0.555-0.23,0.811-0.358
                                                c2.304-1.109,4.403-2.287,6.451-3.482c0.418-0.247,0.913-0.469,1.323-0.717c1.749-1.067,3.277-2.21,4.821-3.345
                                                c0.563-0.418,1.237-0.811,1.775-1.237c1.314-1.05,2.381-2.167,3.507-3.268c0.58-0.58,1.289-1.118,1.818-1.707
                                                c0.913-1.007,1.57-2.082,2.313-3.14c0.529-0.742,1.186-1.451,1.63-2.219c0.572-0.981,0.887-2.039,1.314-3.063
                                                c0.367-0.887,0.87-1.749,1.135-2.671c0.273-0.99,0.282-2.039,0.427-3.055c0.136-1.015,0.418-1.988,0.418-3.029
                                                c16.23,8.09,25.6,17.289,25.6,25.549C486.4,467.908,413.414,494.933,315.733,494.933z"/>
                                            <path d="M315.733,102.4c38.17,0,76.8-11.725,76.8-34.133c0-22.409-38.63-34.133-76.8-34.133s-76.8,11.725-76.8,34.133
                                                C238.933,90.675,277.564,102.4,315.733,102.4z"/>
                                            <polygon points="429.917,301.875 183.543,240.282 170.666,247.475 170.666,261.769 384.921,328.252 			"/>
                                            <polygon points="460.8,244.092 405.666,278.225 460.8,292.006 			"/>
                                            <polygon points="333.653,358.308 364.996,339.936 170.667,279.639 170.667,312.467 			"/>
                                            <polygon points="460.8,190.859 343.586,262.701 382.575,272.446 460.8,224.02 			"/>
                                            <polygon points="460.8,141.038 276.147,245.844 320.393,256.904 460.8,170.845 			"/>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                            <span class="menu-label">Inventario de Telas</span>
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
