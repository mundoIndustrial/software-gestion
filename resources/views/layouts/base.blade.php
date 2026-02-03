<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" onload="window._PAGE_FULLY_LOADED=true">
<head>
    <!--  PREVENCIÓN NUCLEAR NIVEL 0 - ANTES DE TODO  -->
    <script>
        // PASO 0: Capturar el error ANTES de que se lance - en el evento dispatchEvent
        const originalDispatchEvent = Element.prototype.dispatchEvent;
        Element.prototype.dispatchEvent = function(event) {
            if (event.message && event.message.includes('storage is not allowed')) {
                event.stopImmediatePropagation();
                event.preventDefault();
                return false;
            }
            return originalDispatchEvent.call(this, event);
        };
        
        // PASO 0.5: Interceptar Error constructor
        const OriginalError = window.Error;
        window.Error = class extends OriginalError {
            constructor(message) {
                super(message);
                if (message && message.includes('storage is not allowed')) {
                    // Retornar un error silencioso
                    this.message = '[BLOCKED] storage error';
                }
            }
        };
        window.Error.prototype = OriginalError.prototype;
    </script>

    <!-- PASO 1: Capturar errores ANTES de que se registren - MÁS AGRESIVO -->
    <script>
        window._storageErrors = [];
        
        // Reemplazar console.error completamente
        const originalError = console.error;
        console.error = function(...args) {
            const msg = String(args[0]);
            if (msg.includes('storage is not allowed') || msg.includes('message channel closed')) {
                window._storageErrors.push(msg);
                return; // No pasar a console
            }
            return originalError.apply(console, args);
        };
        
        // Reemplazar console.warn también
        const originalWarn = console.warn;
        console.warn = function(...args) {
            const msg = String(args[0]);
            if (msg.includes('storage is not allowed') || msg.includes('message channel closed')) {
                return; // No pasar a console
            }
            return originalWarn.apply(console, args);
        };
        
        // Capturar promesas rechazadas INMEDIATAMENTE - BLOQUEAR COMPLETAMENTE
        window.addEventListener('unhandledrejection', (event) => {
            const reason = String(event.reason);
            if (reason.includes('storage is not allowed') || reason.includes('message channel closed')) {
                event.preventDefault();
                window._storageErrors.push(reason);
                // NO permitir que se propague
                return false;
            }
        }, true); // Usar captura, no bubbling
        
        // Capturar errores síncronos - BLOQUEAR COMPLETAMENTE
        window.addEventListener('error', (event) => {
            if (event.message && (event.message.includes('storage is not allowed') || event.message.includes('message channel'))) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return true;
            }
        }, true);
        
        // Capturar en window.onerror - BLOQUEAR COMPLETAMENTE
        window.onerror = function(msg, url, line, col, error) {
            if (msg && (msg.includes('storage is not allowed') || msg.includes('message channel'))) {
                return true; // Prevenir el error
            }
            return false;
        };
        
        // EXTRA: Filtrar errores que lleguen a console.log también
        const originalLog = console.log;
        console.log = function(...args) {
            const msg = String(args[0]);
            if (msg.includes('Uncaught (in promise)') && msg.includes('storage is not allowed')) {
                return;
            }
            return originalLog.apply(console, args);
        };
        
        // PASO 2: Si es una extensión, interceptar chrome.runtime.onMessage
        if (typeof chrome !== 'undefined' && chrome.runtime) {
            const originalAddListener = chrome.runtime.onMessage.addListener;
            chrome.runtime.onMessage.addListener = function(callback) {
                // Envolver el callback para ignorar errores de storage
                return originalAddListener.call(this, function(message, sender, sendResponse) {
                    try {
                        return callback(message, sender, sendResponse);
                    } catch (error) {
                        if (String(error).includes('storage is not allowed')) {
                            // Ignorar silenciosamente
                            return;
                        }
                        throw error;
                    }
                });
            };
        }
    </script>

    <!-- PASO 3: Reemplazo ultra-temprano de storage -->
    <script>
        (function() {
            'use strict';
            
            const memLS = {};
            const memSS = {};
            
            const proxyLS = new Proxy({}, {
                get(t, k) {
                    if (k === 'getItem') return (key) => memLS[key] ?? null;
                    if (k === 'setItem') return (key, val) => { memLS[key] = String(val); };
                    if (k === 'removeItem') return (key) => { delete memLS[key]; };
                    if (k === 'clear') return () => { for (let k in memLS) delete memLS[k]; };
                    if (k === 'key') return (i) => Object.keys(memLS)[i] ?? null;
                    if (k === 'length') return Object.keys(memLS).length;
                    return undefined;
                }
            });
            
            const proxySS = new Proxy({}, {
                get(t, k) {
                    if (k === 'getItem') return (key) => memSS[key] ?? null;
                    if (k === 'setItem') return (key, val) => { memSS[key] = String(val); };
                    if (k === 'removeItem') return (key) => { delete memSS[key]; };
                    if (k === 'clear') return () => { for (let k in memSS) delete memSS[k]; };
                    if (k === 'key') return (i) => Object.keys(memSS)[i] ?? null;
                    if (k === 'length') return Object.keys(memSS).length;
                    return undefined;
                }
            });
            
            // Reemplazar brutalmente
            try {
                Object.defineProperty(window, 'localStorage', {
                    get: () => proxyLS,
                    set: () => {},
                    configurable: false
                });
            } catch (e) {
                window.localStorage = proxyLS;
            }
            
            try {
                Object.defineProperty(window, 'sessionStorage', {
                    get: () => proxySS,
                    set: () => {},
                    configurable: false
                });
            } catch (e) {
                window.sessionStorage = proxySS;
            }
            
            if (typeof globalThis !== 'undefined' && globalThis !== window) {
                try {
                    Object.defineProperty(globalThis, 'localStorage', {
                        get: () => proxyLS,
                        set: () => {},
                        configurable: false
                    });
                    Object.defineProperty(globalThis, 'sessionStorage', {
                        get: () => proxySS,
                        set: () => {},
                        configurable: false
                    });
                } catch (e) {}
            }
            
            window._STORAGE_PROXY_READY = true;
        })();
    </script>

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

    <!-- ⚠️ MESSAGE HANDLER UNIVERSAL - Para listeners de extensiones y mensajes -->
    <script src="{{ asset('js/message-handler-universal.js') }}"></script>

    <!-- Script crítico para prevenir flash de tema - DEBE estar ANTES de CSS -->
    <script>
        (function() {
            let theme = 'light';
            try {
                // Double-check que localStorage está disponible (proxy debe haber reemplazado)
                if (typeof window.localStorage !== 'undefined' && window.localStorage !== null) {
                    const stored = window.localStorage.getItem('theme');
                    if (stored) {
                        theme = stored;
                    }
                }
            } catch (error) {
                // No se puede acceder a localStorage (ej. iframe sandboxed)
                // El proxy debería haber manejado esto, pero por seguridad usar tema por defecto
                console.debug('[Theme] Usando tema por defecto:', error.message);
            }
            
            // Aplicar tema de forma segura
            try {
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark-theme');
                    document.documentElement.setAttribute('data-theme', 'dark');
                    // Marcar para que el body también aplique la clase cuando esté listo
                    document.documentElement.setAttribute('data-pending-theme', 'dark');
                }
            } catch (e) {
                // Ignorar errores al aplicar tema
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
            let theme = 'light';
            try {
                theme = localStorage.getItem('theme') || 'light';
            } catch (error) {
                // No se puede acceder a localStorage (ej. iframe sandboxed)
                // Usar tema por defecto (light)
            }
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
    
    <!-- Laravel Echo - Para actualizaciones en tiempo real (solo para asesores) -->
    @auth
    @if(auth()->user()->hasRole('asesor'))
    <script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endif
    @endauth

    <!-- Modal de Cotización Global -->
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>

    <!-- Notifications realtime system (loaded once) -->
    <script src="{{ asset('js/configuraciones/notifications-realtime.js') }}"></script>
    
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

