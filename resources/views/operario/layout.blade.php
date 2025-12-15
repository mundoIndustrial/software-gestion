<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Operario') - MundoIndustrial</title>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/operario/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/operario/dashboard.css') }}">

    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @stack('styles')
    
    <style>
        /* Loading overlay global */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.3s ease;
            flex-direction: column;
            gap: 30px;
        }
        
        #loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

</head>
<body>
    <!-- Loading overlay global -->
    <div id="loading-overlay">
        <!-- Spinner mejorado -->
        <div style="position: relative; width: 80px; height: 80px;">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation: spin 2s linear infinite;">
                <circle cx="40" cy="40" r="35" stroke="url(#gradient)" stroke-width="4" stroke-linecap="round"/>
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3498db;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#2ecc71;stop-opacity:1" />
                    </linearGradient>
                </defs>
            </svg>
        </div>
        
        <!-- Texto -->
        <div style="text-align: center;">
            @if(session()->has('just_logged_in'))
                <p style="margin: 0 0 12px 0; color: #2c3e50; font-size: 32px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.5px;">Bienvenido</p>
                <p style="margin: 0; color: #7f8c8d; font-size: 18px; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Estamos cargando te prometemos que será rápido <span style="font-size: 24px; display: inline-block; margin-left: 6px;">😊</span></p>
            @else
                <p style="margin: 0 0 12px 0; color: #2c3e50; font-size: 32px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.5px;">Cargando</p>
                <p style="margin: 0; color: #7f8c8d; font-size: 18px; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Te prometemos que será rápido <span style="font-size: 24px; display: inline-block; margin-left: 6px;">😊</span></p>
            @endif
        </div>
    </div>
    
    <script>
        console.log('📦 Script de loading overlay iniciado (operario)');
        
        // Ocultar loading cuando todo esté cargado
        window.addEventListener('load', function() {
            console.log('✅ Evento LOAD disparado');
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                console.log('🎯 Overlay encontrado');
                overlay.style.pointerEvents = 'none';
                console.log('🚫 pointer-events: none aplicado');
                overlay.classList.add('hidden');
                console.log('👻 Clase hidden agregada');
            } else {
                console.log('❌ Overlay NO encontrado');
            }
        });
        
        // También ocultar inmediatamente si el documento ya está completamente cargado
        console.log('📄 readyState:', document.readyState);
        if (document.readyState === 'complete') {
            console.log('⚡ Documento ya está en readyState complete');
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                console.log('🎯 Overlay encontrado en readyState complete');
                overlay.style.pointerEvents = 'none';
                overlay.classList.add('hidden');
                console.log('👻 Clase hidden agregada en readyState complete');
            }
        }
    </script>

    <!-- Main Content (Sin Sidebar) -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation Moderna -->
        <header class="top-nav">
            <div class="nav-left">
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="top-nav-content">
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
                            <span class="user-role">{{ Auth::user()->roles()->first()?->name ?? 'Operario' }}</span>
                            <span class="user-name">{{ Auth::user()->name }}</span>
                        </div>
                    </button>
                    <div class="user-menu" id="userMenu">
                        <div class="user-menu-header">
                            <div class="user-avatar-large">
                                @if(Auth::user()->avatar)
                                    <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
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
                        <a href="{{ route('operario.dashboard') }}" class="menu-item">
                            <span class="material-symbols-rounded">home</span>
                            <span>Mi Dashboard</span>
                        </a>
                        <a href="{{ route('operario.mis-pedidos') }}" class="menu-item">
                            <span class="material-symbols-rounded">assignment</span>
                            <span>Mis Pedidos</span>
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
    <script src="{{ asset('js/toast-notifications.js') }}"></script>
    <script src="{{ asset('js/operario/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
