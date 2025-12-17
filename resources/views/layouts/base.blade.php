<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <meta name="google" content="notranslate">
    <meta http-equiv="Content-Language" content="es">
    <meta name="description" content="Plataforma integral de gestión de producción textil con seguimiento en tiempo real y análisis de datos.">
    <meta name="theme-color" content="#0066cc">
    <title>@yield('title', config('app.name', 'Mundo Industrial'))</title>

    @yield('meta')

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('mundo_icon.ico') }}">

    <!-- Script crítico para prevenir flash de tema - DEBE estar ANTES de CSS -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.setAttribute('data-theme', 'dark');
                // Marcar para que el body también aplique la clase cuando esté listo
                document.documentElement.setAttribute('data-pending-theme', 'dark');
            }
        })();
    </script>

    <!-- Estilo crítico inline -->
    <style>
        html[data-theme="dark"] body {
            background-color: #0f172a !important;
            color: #F1F5F9 !important;
        }
    </style>

    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Global (crítico) -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    
    <!-- CSS no-crítico (diferido) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" crossorigin="anonymous" referrerpolicy="no-referrer">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    </noscript>

    <!-- Vite (contiene app.css y app.js críticos) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Laravel Echo & Pusher JS (para notificaciones en tiempo real) -->
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1/dist/echo.iife.js"></script>
    
    <!-- Configurar Laravel Echo -->
    @auth
    <script>
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config('broadcasting.connections.reverb.key') }}',
            wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
            wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
            wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
            forceTLS: {{ config('broadcasting.connections.reverb.options.scheme') === 'https' ? 'true' : 'false' }},
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
        });
        console.log('✅ Laravel Echo configurado');
    </script>
    @endauth
    
    <!-- SweetAlert2 JS (diferido) -->
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toast Notifications Global (diferido) -->
    <script defer src="{{ asset('js/toast-notifications.js') }}"></script>

    <!-- Page-specific styles -->
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
            pointer-events: auto;
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
<body class="
    {{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}
    {{ request()->routeIs('cotizaciones-prenda.create') ? 'cotizaciones-prenda-create' : '' }}
" 
      data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
      data-module="@yield('module', 'default')">

    <!-- Sincronizar tema con localStorage -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const body = document.body;
            
            // Aplicar tema al body
            if (theme === 'dark') {
                body.classList.add('dark-theme');
                html.classList.add('dark-theme');
                html.setAttribute('data-theme', 'dark');
            } else {
                body.classList.remove('dark-theme');
                html.classList.remove('dark-theme');
                html.setAttribute('data-theme', 'light');
            }
            
            // Limpiar atributo de tema pendiente
            html.removeAttribute('data-pending-theme');
        })();
    </script>

    @yield('body')

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
            @if(session()->has('login_success') || (auth()->check() && session()->has('just_logged_in')))
                <p style="margin: 0 0 12px 0; color: #2c3e50; font-size: 32px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.5px;">Bienvenido</p>
                <p style="margin: 0; color: #7f8c8d; font-size: 18px; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Estamos cargando te prometemos que será rápido <span style="font-size: 24px; display: inline-block; margin-left: 6px;">😊</span></p>
            @else
                <p style="margin: 0 0 12px 0; color: #2c3e50; font-size: 32px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.5px;">Cargando</p>
                <p style="margin: 0; color: #7f8c8d; font-size: 18px; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Te prometemos que será rápido <span style="font-size: 24px; display: inline-block; margin-left: 6px;">😊</span></p>
            @endif
        </div>
    </div>
    
    <script>
        console.log('📦 Script de loading overlay iniciado');
        
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

    <!-- Core JS - Crítico para funcionalidad (sin defer) -->
    <script src="{{ asset('js/sidebar.js') }}"></script>
    
    <!-- Non-critical JS (diferido) -->
    <script defer src="{{ asset('js/sidebar-notifications.js') }}"></script>
    <script defer src="{{ asset('js/top-nav.js') }}"></script>

    <!-- Page-specific scripts -->
    @stack('scripts')
</body>
</html>
