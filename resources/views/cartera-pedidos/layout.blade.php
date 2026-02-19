<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <!-- Meta tags para WebSockets/Reverb (valores públicos para el cliente) -->
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
        /* ========================================
           CORRECCIÓN DE LAYOUT PARA CARTERA
           Sobreescribir CSS de asesores que interfiere
        ======================================== */

        :root {
            --primary: rgba(7, 83, 177, 1);
        }
        
        /* Resetear main-content para cartera */
        .main-content {
            display: flex !important;
            flex-direction: column !important;
            margin-left: var(--sidebar-width) !important;
            min-height: 100vh !important;
            transition: margin-left var(--transition-normal);
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: var(--sidebar-collapsed) !important;
        }

        /* Asegurar estructura correcta de main-content */
        .main-content > .top-nav {
            order: 1 !important;
            flex-shrink: 0 !important;
            height: var(--topnav-height) !important;
            background: var(--bg-secondary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .main-content > .content-area {
            order: 2 !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: auto !important;
            min-height: 0 !important;
        }

        /* Asegurar posicionamiento del sidebar */
        .sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: var(--sidebar-width) !important;
            height: 100vh !important;
            z-index: 1000 !important;
            background: var(--bg-sidebar) !important;
            border-right: 1px solid var(--border-color) !important;
            transition: transform var(--transition-normal) !important;
        }

        .sidebar.collapsed {
            width: 80px !important;
            transform: none !important;
            left: 0 !important;
        }

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
            padding: 20px;
            background: var(--bg-primary);
        }

        /* Alias de estilos (tabla estilo Contador) para el módulo Cartera */
        .table-container {
            padding: 0;
            background: transparent;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .modern-table-wrapper {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            position: relative;
        }

        .table-scroll-container {
            overflow: auto;
            flex: 1;
            width: 100%;
            scrollbar-width: thin;
            scrollbar-color: #1e5ba8 #f9fafb;
            position: relative;
            z-index: 1;
            min-height: 390px;
            max-height: 390px;
        }

        .table-scroll-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-scroll-container::-webkit-scrollbar-track {
            background: #f9fafb;
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #1e5ba8;
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #1e40af;
        }

        .modern-table {
            width: 100%;
            display: flex;
            flex-direction: column;
            font-size: 0.875rem;
            min-width: 900px;
        }

        .table-body {
            background: transparent;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 12px;
            width: 100%;
        }

        .table-row {
            transition: all 0.2s ease;
            height: 70px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow: visible;
            position: relative;
            z-index: 1;
            width: 100%;
            min-width: 900px;
            flex-shrink: 0;
        }

        .table-row:hover {
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .table-cell {
            padding: 14px 8px;
            border-right: none;
            position: relative;
            vertical-align: middle;
            height: 100%;
            display: flex;
            align-items: center;
            min-width: 100px;
            overflow: visible;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .table-cell span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #1f2937;
        }

        .table-pagination {
            display: flex !important;
            justify-content: center;
            align-items: center;
            padding: 16px 24px;
            background: #f9fafb !important;
            border-top: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 8px;
            position: relative !important;
            z-index: 10 !important;
            visibility: visible !important;
            opacity: 1 !important;
            border-radius: 0 0 16px 16px;
        }

        /* Estilos específicos para tabla de cartera */
        .table-container-cartera {
            padding: 0;
            background: transparent;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .table-scroll-container-cartera {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            position: relative;
        }

        .table-scroll-container-cartera {
            overflow: auto;
            flex: 1;
            width: 100%;
            scrollbar-width: thin;
            scrollbar-color: #1e5ba8 #f9fafb;
            position: relative;
            z-index: 1;
            min-height: 390px;
            max-height: 390px;
        }

        .table-scroll-container-cartera::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-scroll-container-cartera::-webkit-scrollbar-track {
            background: #f9fafb;
            border-radius: 4px;
        }

        .table-scroll-container-cartera::-webkit-scrollbar-thumb {
            background: #1e5ba8;
            border-radius: 4px;
        }

        .table-scroll-container-cartera::-webkit-scrollbar-thumb:hover {
            background: #1e40af;
        }

        .modern-table-cartera {
            width: 100%;
            display: flex;
            flex-direction: column;
            font-size: 0.875rem;
            min-width: 900px;
        }

        /* Estilos para encabezados */
        .table-head {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            background: linear-gradient(135deg, var(--primary) 0%, #2b7ec9 100%) !important;
            border-radius: 16px 16px 0 0 !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 0 20px !important;
            height: 70px !important;
            width: 100% !important;
            flex-shrink: 0 !important;
            will-change: transform;
        }

        .table-header-row {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            gap: 12px !important;
            padding: 0 !important;
            background: transparent !important;
            height: 100% !important;
        }

        .table-header-cell-cartera {
            background: transparent !important;
            color: #ffffff !important;
            padding: 0 12px !important;
            border: none !important;
            position: relative !important;
            font-weight: 600 !important;
            font-size: 12px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.4px !important;
            white-space: nowrap !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            height: 100% !important;
            user-select: none !important;
            box-sizing: border-box !important;
            cursor: pointer !important;
        }

        .table-header-cell-cartera:hover {
            opacity: 0.9;
        }

        .table-body-cartera {
            background: transparent;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 12px;
            width: 100%;
        }

        /* Estilos para filas y celdas de tabla */
        .table-row-cartera {
            transition: all 0.2s ease;
            height: 70px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow: visible;
            position: relative;
            z-index: 1;
            width: 100%;
            min-width: 900px;
            flex-shrink: 0;
        }

        .table-row-cartera:hover {
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .table-cell-cartera {
            padding: 14px 8px;
            border-right: none;
            position: relative;
            vertical-align: middle;
            height: 100%;
            display: flex;
            align-items: center;
            min-width: 100px;
            overflow: visible;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .table-cell-cartera span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #1f2937;
        }

        .pagination-container {
            display: flex !important;
            justify-content: center;
            align-items: center;
            padding: 16px 24px;
            background: #f9fafb !important;
            border-top: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 8px;
            position: relative !important;
            z-index: 10 !important;
            visibility: visible !important;
            opacity: 1 !important;
            border-radius: 0 0 16px 16px;
        }

        .pagination-btn {
            background: rgba(30, 91, 168, 0.2);
            border: 1px solid rgba(30, 91, 168, 0.4);
            color: #1e5ba8;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }

        .pagination-btn:hover:not(:disabled) {
            background: #1e5ba8;
            border-color: #1e5ba8;
            color: #ffffff;
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
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

        .nav-search {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 0 1 auto;
        }

        .search-container {
            position: relative;
            width: 300px;
        }

        .nav-search .search-input {
            width: 100%;
            padding: 8px 12px 8px 32px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #1f2937;
        }

        .nav-search .search-input:focus {
            outline: none;
            background: #ffffff !important;
            border-color: #1e5ba8 !important;
            box-shadow: 0 0 0 3px rgba(30, 91, 168, 0.1);
        }

        .nav-search .search-input::placeholder {
            color: #9ca3af;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 0.85rem;
            pointer-events: none;
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
            transform: none !important;
            left: 0 !important;
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

            @if(request()->is('cartera/*'))
            <div class="nav-search">
                <div class="search-container">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Buscar por número, cliente o ID..." 
                        class="search-input"
                    >
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            @endif

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
        // Función para inicializar controles
        function inicializarControles() {
            // DROPDOWN DE USUARIO
            const userBtn = document.getElementById('userBtn');
            const userMenu = document.getElementById('userMenu');
            
            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('active');
                });
                
                // Cerrar cuando se hace click fuera
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.user-dropdown')) {
                        userMenu.classList.remove('active');
                    }
                });
            }
            
            // TOGGLE DEL SIDEBAR - FLECHA DENTRO DEL SIDEBAR
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    sidebar.classList.toggle('collapsed');
                });
            }
        }
        
        // Ejecutar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarControles);
        } else {
            // Si el DOM ya está listo (script cargó tarde)
            inicializarControles();
        }

        // Búsqueda contextual en header para vistas de Cartera
        function elementoVisible(el) {
            if (!el) return false;
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
            const rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        }

        function obtenerScopeActivo() {
            const body = document.querySelector('.content-area') || document.body;
            return body;
        }

        function obtenerRowsActivasCartera() {
            const scope = obtenerScopeActivo();
            const rows = scope.querySelectorAll('.table-row, .table-row-cartera');
            return rows;
        }

        function aplicarBusquedaCarteraLocal() {
            const input = document.getElementById('searchInput');
            const term = (input?.value || '').toLowerCase().trim();
            const rows = obtenerRowsActivasCartera();

            rows.forEach(row => {
                if (!term) {
                    row.style.display = '';
                    return;
                }

                const datasetValues = Object.values(row.dataset || {})
                    .map(v => (v ?? '').toString().toLowerCase())
                    .join(' ');
                const textoVisible = (row.textContent || '').toLowerCase();
                const mostrar = datasetValues.includes(term) || textoVisible.includes(term);
                row.style.display = mostrar ? '' : 'none';
            });
        }

        function inicializarBusquedaHeaderCartera() {
            const input = document.getElementById('searchInput');
            if (!input) return;

            const path = window.location.pathname || '';
            const esPedidos = path.includes('/cartera/pedidos');

            // En /cartera/pedidos la búsqueda ya está conectada a la API en app.js
            if (esPedidos) return;

            if (!input.dataset.listenerBoundCartera) {
                input.addEventListener('input', aplicarBusquedaCarteraLocal);
                input.dataset.listenerBoundCartera = '1';
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarBusquedaHeaderCartera);
        } else {
            inicializarBusquedaHeaderCartera();
        }
    </script>

    <!-- Vite App Bundle (incluye Bootstrap.js con Echo initialization) -->
    @vite(['resources/js/app.js'])

    <!-- Laravel Echo - Para actualizaciones en tiempo real (solo para usuarios autorizados) -->
    @auth
    @if(auth()->user()->hasRole('asesor') || auth()->user()->hasRole('supervisor_pedidos') || auth()->user()->hasRole('despacho') || auth()->user()->hasRole('cartera'))
    <script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endif
    @endauth

    <!-- Scripts de Facturas para vistas de cartera -->
    @if(request()->is(['cartera/aprobados', 'cartera/rechazados', 'cartera/anulados']))
    <script src="{{ asset('js/modulos/invoice/InvoiceDataFetcher.js') }}"></script>
    <script src="{{ asset('js/modulos/invoice/ModalManager.js') }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}"></script>
    <script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}"></script>
    @endif

    <!-- Scripts individuales para vistas específicas -->
    @if(request()->is('cartera/aprobados'))
    <!-- Sistema de filtros compartido -->
    <script src="{{ asset('js/cartera-pedidos/cartera-filtros-compartidos.js') }}"></script>
    <!-- Script específico de la vista (versión limpia) -->
    <script src="{{ asset('js/cartera-pedidos/cartera-aprobados-limpio.js') }}"></script>
    @endif
    
    @if(request()->is('cartera/rechazados'))
    <!-- Sistema de filtros compartido -->
    <script src="{{ asset('js/cartera-pedidos/cartera-filtros-compartidos.js') }}"></script>
    <!-- Script específico de la vista (versión limpia) -->
    <script src="{{ asset('js/cartera-pedidos/cartera-rechazados-limpio.js') }}"></script>
    @endif
    
    @if(request()->is('cartera/anulados'))
    <!-- Sistema de filtros compartido -->
    <script src="{{ asset('js/cartera-pedidos/cartera-filtros-compartidos.js') }}"></script>
    <!-- Script específico de la vista (versión limpia) -->
    <script src="{{ asset('js/cartera-pedidos/cartera-anulados-limpio.js') }}"></script>
    @endif

    <!-- Modal de Cotización Global -->
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>

    <!-- Notifications realtime system (loaded once) -->
    <script src="{{ asset('js/configuraciones/notifications-realtime.js') }}"></script>

    @stack('scripts')
</body>
</html>
