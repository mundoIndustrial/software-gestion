<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Insumos') - MundoIndustrial</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('mundo_icon.png') }}" sizes="any">
    
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
    
    @php
        $userRoles = auth()->user()->roles->pluck('name')->toArray();
        $esVisualizador = in_array('visualizador_plooter', $userRoles) && count($userRoles) === 1;
    @endphp

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
            @if($esVisualizador)
                <!-- Menú simplificado para visualizador_plooter -->
                <div class="menu-section">
                    <span class="menu-section-title">Gestionar Órdenes</span>
                    <nav aria-label="Menú de recibos">
                        <ul class="menu-list">
                            <li class="menu-item">
                                <a href="{{ route('operario.dashboard') }}" 
                                   class="menu-link {{ request()->routeIs('operario.dashboard') ? 'active' : '' }}">
                                    <span class="material-symbols-rounded">receipt_long</span>
                                    <span class="menu-label">Recibos Asignados</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="menu-section">
                    <span class="menu-section-title">Insumos</span>
                    <nav aria-label="Menú de plooter">
                        <ul class="menu-list">
                            <li class="menu-item">
                                <a href="{{ route('insumos.plooter.index') }}" 
                                   class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}">
                                    <span class="material-symbols-rounded">description</span>
                                    <span class="menu-label">Gestion Plooter</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            @else
                <!-- Menú completo para otros usuarios -->
            <div class="menu-section">
                <span class="menu-section-title">Principal</span>
                <nav aria-label="Menú principal">
                    <ul class="menu-list">
                    </ul>
                </nav>
            </div>

            <div class="menu-section">
                <span class="menu-section-title">Insumos</span>
                <nav aria-label="Menú de insumos">
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="{{ route('insumos.materiales.index') }}" 
                               class="menu-link {{ request()->routeIs('insumos.materiales.*') && !request()->routeIs('insumos.materiales.reflectivo') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">inventory_2</span>
                                <span class="menu-label">Control de Insumos</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('insumos.materiales.reflectivo') }}" 
                               class="menu-link {{ request()->routeIs('insumos.materiales.reflectivo') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">visibility</span>
                                <span class="menu-label">Gestion Reflectivo</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('insumos.plooter.index') }}" 
                               class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">description</span>
                                <span class="menu-label">Gestion Plooter</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('inventario-telas.index') }}" 
                               class="menu-link {{ request()->routeIs('inventario-telas.*') ? 'active' : '' }}">
                                <span class="material-symbols-rounded">checkroom</span>
                                <span class="menu-label">Inventario de Telas</span>
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
            @endif
        </div>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}" style="width: 100%;">
                @csrf
                <button type="submit" 
                        class="logout-btn"
                        title="Cerrar Sesión"
                        style="border: none; background: none; cursor: pointer; width: 100%; text-align: left; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-label">Cerrar Sesión</span>
                </button>
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
                                <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
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
                    <div class="user-menu" id="userMenu">
                        <div class="user-menu-header">
                            <div class="user-avatar-large">
                                @if(Auth::user()->avatar)
                                    <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
                                @else
                                    <div class="avatar-placeholder" style="display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.8rem; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);">
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
                        <a href="#" class="menu-item">
                            <span class="material-symbols-rounded">person</span>
                            <span>Mi Perfil</span>
                        </a>
                        <a href="#" class="menu-item">
                            <span class="material-symbols-rounded">settings</span>
                            <span>Configuración</span>
                        </a>
                        <div class="menu-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" style="width: 100%;">
                            @csrf
                            <button type="submit" class="menu-item logout" style="border: none; background: none; cursor: pointer; width: 100%; text-align: left; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem; color: var(--danger-color);">
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
    <script src="{{ asset('js/insumos/layout.js') }}"></script>
    
    <!-- Toggle user menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('userBtn')?.addEventListener('click', function(e) {
                e.stopPropagation();
                const menu = document.getElementById('userMenu');
                menu?.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-dropdown')) {
                    document.getElementById('userMenu')?.classList.remove('show');
                }
            });
        });
    </script>
    
    <!-- CORE ARCHITECTURE LAYER - Hybrid DDD Implementation -->
    <!--   CRITICAL: Order matters! Load in this sequence:
         1. Shared Cache (base class)
         2. HttpClient (no dependencies)
         3. Domain Repository (interface)
         4. Shared Infrastructure Cache Repository (depends on CacheRepository)
         5. Infrastructure Repository (depends on HttpClient + SessionStorageCacheRepository)
         6. Application Service (depends on Repository)
         7. Bootstrap DI Container (depends on all above)
    -->
    <script src="{{ asset('js/shared/CacheRepository.js') }}"></script>
    <script src="{{ asset('js/insumos/core/infrastructure/HttpClient.js') }}"></script>
    <script src="{{ asset('js/insumos/core/domain/InsumoRepository.js') }}"></script>
    <script src="{{ asset('js/shared/infrastructure/SessionStorageCacheRepository.js') }}"></script>
    <script src="{{ asset('js/insumos/core/infrastructure/SessionStorageInsumoRepository.js') }}"></script>
    <script src="{{ asset('js/insumos/core/application/InsumoService.js') }}"></script>
    <script src="{{ asset('js/insumos/core/bootstrap.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
