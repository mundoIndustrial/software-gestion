<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    
    <!-- Laravel Echo & Pusher JS cargado vía Vite en app.js (bootstrap.js) -->
    
    <!-- SweetAlert2 JS (diferido) -->
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toast Notifications Global (diferido) -->
    <script defer src="{{ asset('js/configuraciones/toast-notifications.js') }}"></script>

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

    <!-- GLOBAL: Usuario autenticado disponible desde el inicio -->
    <script>
        window.usuarioAutenticado = {
            @if(auth()->check())
                id: {{ auth()->user()->id }},
                nombre: "{{ auth()->user()->name }}",
                email: "{{ auth()->user()->email }}",
                rol: "{{ auth()->user()->role?->name ?? 'Sin Rol' }}"
            @else
                id: null,
                nombre: 'Usuario',
                email: '',
                rol: 'Sin Rol'
            @endif
        };
    </script>

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

    <!-- Core JS - Crítico para funcionalidad (sin defer) -->
    <script src="{{ asset('js/configuraciones/sidebar.js') }}"></script>
    
    <!-- Sistema de refresh automático de token CSRF (Previene error 419) -->
    <script src="{{ asset('js/configuraciones/csrf-refresh.js') }}"></script>
    
    <!-- Non-critical JS (diferido) -->
    <script defer src="{{ asset('js/configuraciones/sidebar-notifications.js') }}"></script>
    <script defer src="{{ asset('js/configuraciones/top-nav.js') }}"></script>
    
    <!-- Laravel Echo - Para actualizaciones en tiempo real -->
    @auth
    <script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endauth

    <!-- Modal de Cotización Global -->
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>

    <!-- Page-specific scripts -->
    @stack('scripts')
    
    <!-- Modals -->
    @stack('modals')
    
    <!--  PAYLOAD NORMALIZER v3 - CARGADO EN BLADE TEMPLATES INDIVIDUALES -->
    <!-- Implementación definitiva en: payload-normalizer-v3-definitiva.js -->

</body>
</html>
   

</body>
</html>

