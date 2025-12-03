<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Insumos') - MundoIndustrial</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('mundo_icon.png') }}" sizes="any">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS del módulo de insumos -->
    <link rel="stylesheet" href="{{ asset('css/insumos/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insumos/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insumos/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insumos/pagination.css') }}">
    
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-wrapper">
                <img src="{{ asset('images/logo2.png') }}" 
                     alt="Logo MundoIndustrial"
                     class="header-logo"
                     loading="lazy"
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
                <nav aria-label="Menú principal">
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="{{ route('insumos.dashboard') }}" 
                               class="menu-link {{ request()->routeIs('insumos.dashboard') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">dashboard</span>
                                <span class="menu-label">Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Insumos</span>
                <nav aria-label="Menú de insumos">
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="{{ route('insumos.materiales.index') }}" 
                               class="menu-link {{ request()->routeIs('insumos.materiales.*') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">inventory_2</span>
                                <span class="menu-label">Control de Insumos</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('inventario-telas.index') }}" 
                               class="menu-link {{ request()->routeIs('inventario-telas.*') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">checkroom</span>
                                <span class="menu-label">Inventario de Telas</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('insumos.metrajes.index') }}" 
                               class="menu-link {{ request()->routeIs('insumos.metrajes.*') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">straighten</span>
                                <span class="menu-label">Cálculo de Metrajes</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Volver al Dashboard - Solo para supervisor_planta y admin -->
            @if(auth()->user()->role && in_array(auth()->user()->role->name, ['supervisor_planta', 'admin']))
            <div class="menu-section">
                <nav aria-label="Navegación">
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="{{ route('dashboard') }}" 
                               class="menu-link">
                                <span class="material-symbols-rounded">arrow_back</span>
                                <span class="menu-label">Volver</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            @endif
        </div>

        <div class="sidebar-footer">
            <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
                <span class="material-symbols-rounded">light_mode</span>
                <span class="theme-text">Tema</span>
            </button>
            
            <a href="{{ route('logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="logout-btn"
               title="Salir">
                <i class="fas fa-sign-out-alt"></i>
                <span class="menu-label">Salir</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
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
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Insumos')</h1>
                </div>
            </div>

            <div class="nav-right">
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
                            <span class="user-role">Insumos</span>
                        </div>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/insumos/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
