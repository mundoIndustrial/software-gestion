<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    
    <title>@yield('title', 'Despacho - Mundo Industrial')</title>
    
    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    
    <!-- Estilos adicionales para tablas -->
    <style>
        /* Estilos base para tablas de despacho (solo para index.blade.php) */
        .despacho-index .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 1.5rem;
        }
        
        .despacho-index .table-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
            font-weight: 600;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr 120px;
            gap: 1rem;
            align-items: center;
        }
        
        .despacho-index .table-row {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr 120px;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .despacho-index .table-row:hover {
            background-color: #f8fafc;
        }
        
        .despacho-index .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .despacho-index .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .despacho-index .btn-primary:hover {
            background: #2563eb;
        }
        
        .despacho-index .pagination-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .despacho-index .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .despacho-index .pagination-btn:hover:not(:disabled) {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .despacho-index .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .despacho-index .pagination-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .despacho-index .table-header,
            .despacho-index .table-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .despacho-index .table-header > *:not(:first-child),
            .despacho-index .table-row > *:not(:first-child) {
                display: none;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
<div class="app-container">
    @include('components.sidebars.sidebar-despacho')

    <div class="main-content" id="mainContent">
        <!-- Top Navigation Moderna -->
        <header class="top-nav">
            <div class="nav-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Despacho')</h1>
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
                            <span class="user-role">Despacho</span>
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
</div>

<!-- Scripts básicos -->
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
        
        // TOGGLE DEL SIDEBAR
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
        inicializarControles();
    }
</script>

<!-- Vite App Bundle -->
@vite(['resources/js/app.js'])

<!-- Scripts específicos -->
@if(request()->is(['despacho/*']))
<script src="{{ asset('js/modulos/invoice/InvoiceDataFetcher.js') }}"></script>
<script src="{{ asset('js/modulos/invoice/ModalManager.js') }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}"></script>
<script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}"></script>
@endif

@stack('scripts')
</body>
</html>
