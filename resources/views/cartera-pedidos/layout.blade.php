<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <!-- Meta tags para WebSockets/Reverb -->
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-app-id" content="{{ config('broadcasting.connections.reverb.app_id') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    @endauth
    <title>@yield('title', 'Cartera de Pedidos') - MundoIndustrial</title>

    <!-- CSS (heredado de asesores) -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">

    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Configuración del main-content como flex */
        .main-content {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            background: white;
            flex-shrink: 0;
        }

        /* Asegurar que content-area expande correctamente */
        .content-area {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: auto;
            width: 100%;
            min-width: 0;
            min-height: 0;
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

        /* Dropdown del usuario */
        .user-dropdown {
            position: relative;
        }

        .user-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            color: #1f2937;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-weight: 600;
            color: #1f2937;
        }

        .user-role {
            font-size: 0.8rem;
            color: #6b7280;
        }

        /* Menú del usuario - SOBRESCRIBE LOS ESTILOS HEREDADOS */
        .user-menu {
            position: absolute !important;
            top: calc(100% + 8px) !important;
            right: 0 !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            min-width: 260px !important;
            z-index: 1000 !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-10px) scaleY(0.95) !important;
            transform-origin: top right !important;
            transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease !important;
            pointer-events: none !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .user-menu.active {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) scaleY(1) !important;
            pointer-events: auto !important;
        }

        .user-menu-header {
            padding: 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .user-avatar-large {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }

        .user-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-menu-name {
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .user-menu-email {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 4px 0 0 0;
        }

        .menu-divider {
            height: 1px;
            background: #e5e7eb;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: none;
            border: none;
            color: #1f2937;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.95rem;
            width: 100%;
            text-align: left;
            transition: background 0.2s;
        }

        .menu-item:hover {
            background: #f3f4f6;
        }

        .menu-item.logout {
            color: #ef4444;
        }

        .menu-item.logout:hover {
            background: #fee2e2;
        }

        /* Sidebar toggle styles */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: #1f2937;
            padding: 0.5rem;
        }

        /* Mejoras para sidebar colapsado */
        .sidebar.collapsed {
            width: 80px !important;
        }

        .sidebar.collapsed .sidebar-header {
            padding: 12px 8px;
            justify-content: center;
        }

        .sidebar.collapsed .logo-wrapper {
            display: none;
        }

        .sidebar.collapsed .sidebar-toggle {
            width: 100%;
            margin: 0;
        }

        .sidebar.collapsed .sidebar-content {
            padding: 12px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar.collapsed .menu-list {
            gap: 8px;
            width: 100%;
        }

        .sidebar.collapsed .menu-link {
            padding: 10px 8px !important;
            justify-content: center;
            width: 56px !important;
            min-height: 50px;
        }

        .sidebar.collapsed .menu-link .material-symbols-rounded {
            font-size: 1.5rem !important;
            flex-shrink: 0;
        }

        .sidebar.collapsed .menu-link .menu-label {
            display: none !important;
        }

        .sidebar.collapsed .menu-section-title {
            display: none !important;
        }

        /* Estilos para cuando el sidebar esté colapsado */
        @media (max-width: 768px) {
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            [class*="sidebar"] {
                transition: transform 0.3s ease, visibility 0.3s ease;
            }

            [class*="sidebar"].collapsed {
                transform: translateX(-100%);
                visibility: hidden;
            }

            [class*="sidebar"].active {
                transform: translateX(0);
                visibility: visible;
                z-index: 1001;
            }
        }
    </style>

    @stack('styles')

</head>
<body>
    <!-- Sidebar Cartera (Componente específico) -->
    @include('components.sidebars.sidebar-cartera')

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation Moderna -->
        <header class="top-nav">
            <div class="nav-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Cartera')</h1>
                </div>
            </div>

            <div class="nav-right">
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
                            <span class="user-role">Cartera</span>
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
                        <a href="#" class="menu-item">
                            <span class="material-symbols-rounded">person</span>
                            <span>Mi Perfil</span>
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
        </div>
    </div>

    <!-- Scripts básicos solo para funcionalidad esencial -->
    <script>
        // Log de inicio para verificar que el layout se está cargando
        console.clear();
        console.log('%c CARTERA LAYOUT CARGADO', 'color: #10b981; font-size: 16px; font-weight: bold; background: #d1fae5; padding: 10px;');
        
        // Función para inicializar controles
        function inicializarControles() {
            console.log('%c Inicializando controles de navegación', 'color: #3b82f6; font-weight: bold; font-size: 14px;');
            
            // DROPDOWN DE USUARIO
            const userBtn = document.getElementById('userBtn');
            const userMenu = document.getElementById('userMenu');
            
            console.log('userBtn:', userBtn ? ' Encontrado' : ' No encontrado');
            console.log('userMenu:', userMenu ? ' Encontrado' : ' No encontrado');
            
            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isActive = userMenu.classList.toggle('active');
                    console.log('%c👤 Usuario Menú Dropdown:', 'color: #1e40af; font-weight: bold;', isActive ? ' ABIERTO' : ' CERRADO');
                    console.log('   Classes:', userMenu.className);
                    console.log('   Display:', window.getComputedStyle(userMenu).display);
                    console.log('   Opacity:', window.getComputedStyle(userMenu).opacity);
                    console.log('   Visibility:', window.getComputedStyle(userMenu).visibility);
                    console.log('   Z-index:', window.getComputedStyle(userMenu).zIndex);
                    console.log('   Position:', window.getComputedStyle(userMenu).position);
                    
                    // Debug detallado
                    if (isActive) {
                        console.log('%c ESTRUCTURA DEL MENÚ:', 'color: #06b6d4; font-weight: bold;');
                        console.log('   innerHTML length:', userMenu.innerHTML.length);
                        console.log('   Children count:', userMenu.children.length);
                        console.log('   Parent:', userMenu.parentElement.className);
                        const rect = userMenu.getBoundingClientRect();
                        console.log('   Position:', `top: ${rect.top}px, left: ${rect.left}px, width: ${rect.width}px`);
                        console.log('   Visible area:', `height: ${rect.height}px`);
                    }
                });
                
                // Cerrar cuando se hace click fuera
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.user-dropdown')) {
                        if (userMenu.classList.contains('active')) {
                            userMenu.classList.remove('active');
                            console.log('%c👤 Usuario Menú Dropdown:  CERRADO (click fuera)', 'color: #ef4444;');
                        }
                    }
                });
                console.log(' Dropdown del usuario inicializado');
            }
            
            // TOGGLE DEL SIDEBAR - FLECHA DENTRO DEL SIDEBAR
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            console.log('🔄 sidebarToggle (FLECHA):', sidebarToggle ? ' Encontrado' : ' No encontrado');
            console.log(' sidebar:', sidebar ? ' Encontrado' : ' No encontrado');
            
            if (sidebarToggle && sidebar) {
                console.log('🔄 Event listener agregado al sidebarToggle (FLECHA)');
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    console.log('%c🔘 CLICK EN FLECHA DE SIDEBAR DETECTADO', 'color: #06b6d4; font-weight: bold; font-size: 14px;');
                    
                    // Log antes de cambios
                    console.log('   Estado ANTES:');
                    console.log('   - Classes:', sidebar.className);
                    console.log('   - Has collapsed:', sidebar.classList.contains('collapsed'));
                    
                    // Realizar cambios
                    sidebar.classList.toggle('collapsed');
                    
                    // Log después de cambios
                    console.log('   Estado DESPUÉS:');
                    console.log('   - Classes:', sidebar.className);
                    console.log('   - Has collapsed:', sidebar.classList.contains('collapsed'));
                    
                    // Log visual del estado
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    console.log('%c Sidebar:', isCollapsed ? ' COLAPSADO' : ' EXPANDIDO', 'color: ' + (isCollapsed ? '#ef4444' : '#10b981') + '; font-weight: bold; font-size: 12px;');
                    
                    // Debug de estilos computados
                    const computedStyle = window.getComputedStyle(sidebar);
                    console.log('   Estilos computados:');
                    console.log('   - Width:', computedStyle.width);
                    console.log('   - Transform:', computedStyle.transform);
                    
                    // Debug de posición
                    const rect = sidebar.getBoundingClientRect();
                    console.log('   Posición en pantalla:');
                    console.log('   - Width:', rect.width);
                    console.log('   - Visible:', rect.width > 0);
                });
                console.log(' Sidebar toggle (FLECHA) inicializado');
            } else {
                console.warn(' No se encontró sidebarToggle o sidebar');
            }
        }
        
        // Ejecutar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarControles);
        } else {
            // Si el DOM ya está listo (script cargó tarde)
            inicializarControles();
        }
        
        // Debug inmediato
        setTimeout(() => {
            console.group('%c DEBUG ESTRUCTURA INMEDIATO', 'color: #ef4444; font-weight: bold; font-size: 14px;');
            
            const header = document.querySelector('header.top-nav');
            const container = document.querySelector('.cartera-pedidos-container');
            const table = document.querySelector('.modern-table-wrapper');
            const mainContent = document.querySelector('.main-content');
            
            console.log('Header:', header ? ' Encontrado' : ' No encontrado');
            if (header) {
                const rect = header.getBoundingClientRect();
                console.log(`  Position: top ${rect.top}px, bottom ${rect.bottom}px`);
                console.log(`  Z-index: ${window.getComputedStyle(header).zIndex}`);
            }
            
            console.log('Main Content:', mainContent ? ' Encontrado' : ' No encontrado');
            console.log('Container:', container ? ' Encontrado' : ' No encontrado');
            console.log('Table:', table ? ' Encontrado' : ' No encontrado');
            if (table) {
                const rect = table.getBoundingClientRect();
                console.log(`  Position: top ${rect.top}px`);
                console.log(`  Z-index: ${window.getComputedStyle(table).zIndex}`);
            }
            
            console.groupEnd();
        }, 100);
    </script>

    <!-- Vite App Bundle (incluye Bootstrap.js con Echo initialization) -->
    @vite(['resources/js/app.js'])

    <!-- Laravel Echo - Para actualizaciones en tiempo real -->
    @auth
    <script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endauth

    @stack('scripts')
</body>
</html>
