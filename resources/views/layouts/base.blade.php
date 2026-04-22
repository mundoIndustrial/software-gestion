<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" onload="window._PAGE_FULLY_LOADED=true">
<head>
    
    <!-- Manejo defensivo de errores de extensiones/storage sin monkey patches -->
    <script>
        (function() {
            'use strict';

            const ignoredPatterns = ['storage is not allowed', 'message channel closed'];
            window.APP_DEBUG = {{ app()->environment('local', 'staging') ? 'true' : 'false' }};
            window._storageErrors = window._storageErrors || [];

            function shouldIgnoreStorageError(value) {
                const text = String(value || '').toLowerCase();
                return ignoredPatterns.some((pattern) => text.includes(pattern));
            }

            window.__miShouldIgnoreStorageError = shouldIgnoreStorageError;

            window.addEventListener('unhandledrejection', function(event) {
                if (!shouldIgnoreStorageError(event.reason)) {
                    return;
                }
                window._storageErrors.push(String(event.reason));
                event.preventDefault();
            }, true);

            window.addEventListener('error', function(event) {
                if (!shouldIgnoreStorageError(event.message || event.error)) {
                    return;
                }
                window._storageErrors.push(String(event.message || event.error));
                event.preventDefault();
            }, true);
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

    <!--  MESSAGE HANDLER UNIVERSAL - Para listeners de extensiones y mensajes -->
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

    <!-- jQuery y Bootstrap ya están cargados al inicio del head -->

    <!-- CSS Global (crítico) -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    
    <!-- CSS no-crítico (diferido) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <noscript>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    </noscript>

    @php
        $headModule = trim($__env->yieldContent('module', 'default'));
    @endphp

    @if($headModule !== 'insumos-materiales')
    <!-- jQuery (debe cargarse PRIMERO y esperar a que esté disponible) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    @endif
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css">
    
    <!-- Esperar a que jQuery esté disponible antes de cargar Bootstrap -->
    @if($headModule !== 'insumos-materiales')
    <script>
        // Esperar a que jQuery esté completamente cargado
        function waitForJQuery() {
            if (typeof jQuery !== 'undefined') {
                // jQuery está disponible, cargar Bootstrap
                var bootstrapScript = document.createElement('script');
                bootstrapScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js';
                bootstrapScript.onload = function() {
                    console.log(' Bootstrap 4 cargado correctamente');
                };
                document.head.appendChild(bootstrapScript);
            } else {
                // Esperar y volver a intentar
                setTimeout(waitForJQuery, 10);
            }
        }
        
        // Iniciar la espera
        waitForJQuery();
    </script>
    @endif
    
    <!-- CRÍTICO: Definir window.waitForEcho ANTES del resto de scripts -->
    <script>
        window.echoReady = false;
        window.echoReadyCallbacks = [];
        
        window.waitForEcho = function(callback) {
            if (window.echoReady && window.EchoInstance) {
                callback();
            } else {
                window.echoReadyCallbacks.push(callback);
            }
        };
        
        window.notifyEchoReady = function() {
            window.echoReady = true;
            while (window.echoReadyCallbacks.length > 0) {
                const callback = window.echoReadyCallbacks.shift();
                try {
                    callback();
                } catch (error) {
                    console.error('[Layout]  Error ejecutando callback de Echo:', error);
                }
            }
        };
        
        console.log('[Layout]  Stubs de window.waitForEcho() pre-inicializados');
    </script>
    
    <!-- Vite assets (funciona en desarrollo y producción) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Laravel Echo & Pusher JS cargado vía Vite en app.js (bootstrap.js) -->
    
    <!-- SweetAlert2 JS (diferido) -->
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toast Notifications Global (diferido) -->
    <script defer src="{{ asset('js/configuraciones/toast-notifications.js') }}"></script>

    <!-- Page-specific styles -->
    @stack('styles')
    @stack('module-styles')
    
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
      data-module="@yield('module', 'default')"
      data-notifications-ui="@yield('notifications-ui', 'default')">

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

    <!-- Core JS -->
    <script defer src="{{ asset('js/configuraciones/sidebar.js') }}"></script>
    
    <!-- Sistema de refresh automático de token CSRF (Previene error 419) -->
    <script defer src="{{ asset('js/configuraciones/csrf-refresh.js') }}"></script>
    
    <!-- Print Handler - Gestión centralizada de impresión -->
    <script>
        // PRINT HANDLER INLINE - Oculta botones flotantes en impresión
        (function() {
            'use strict';
            const hideSelectors = [
                '#floating-buttons-container',
                '#floating-buttons-container-logo',
                '#btn-factura',
                '#btn-galeria',
                '#btn-factura-logo',
                '#btn-galeria-logo'
            ];
            
            let savedStates = [];
            
            function hidePrintElements() {
                console.log('[PRINT] Ocultando botones flotantes...');
                savedStates = [];
                
                hideSelectors.forEach(selector => {
                    document.querySelectorAll(selector).forEach(el => {
                        savedStates.push({
                            el: el,
                            display: el.style.display,
                            visibility: el.style.visibility
                        });
                        el.style.display = 'none !important';
                        el.style.visibility = 'hidden !important';
                    });
                });
            }
            
            function showPrintElements() {
                console.log('[PRINT] Restaurando botones flotantes...');
                savedStates.forEach(state => {
                    state.el.style.display = state.display || '';
                    state.el.style.visibility = state.visibility || '';
                });
            }
            
            window.addEventListener('beforeprint', hidePrintElements);
            window.addEventListener('afterprint', showPrintElements);
            
            window.PrintHandler = {
                debug: function() {
                    // Debug info disponible
                },
                testPrint: function() {
                    hidePrintElements();
                    print();
                }
            };
        })();
    </script>
    
    @php
        $currentModule = trim($__env->yieldContent('module', 'default'));
        $currentNotificationsUi = trim($__env->yieldContent('notifications-ui', 'default'));
    @endphp

    <!-- Non-critical JS (diferido) -->
    <script defer src="{{ asset('js/configuraciones/sidebar-notifications.js') }}"></script>
    <script defer src="{{ asset('js/configuraciones/top-nav.js') }}"></script>
    
    <!-- SHARED CORE - DEPENDENCY INJECTION CONTAINER (con cache compartido) -->
    <!-- DEBE CARGARSE ANTES de pedidos-realtime.js y notifications-realtime.js -->
    <script src="{{ asset('js/bundles/shared-core.min.js') }}"></script>
    
    <!-- Laravel Echo - Para actualizaciones en tiempo real (solo para asesores y supervisores) -->
    @auth
    @if(auth()->user()->hasRole('asesor') || auth()->user()->hasRole('supervisor_pedidos'))
    <script src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endif
    @endauth

    <!-- Modal de Cotización Global -->
    @if($currentModule !== 'insumos-materiales')
    <script defer src="{{ asset('js/contador/cotizacion.js') }}"></script>
    @endif

    <!-- Notifications realtime system (loaded once) -->
    <!-- REQUIERE shared-core.js que ya está cargado arriba -->
    @if($currentNotificationsUi !== 'asesores' && $currentModule !== 'asesores' && $currentModule !== 'insumos-materiales')
        <script defer src="{{ asset('js/configuraciones/notifications-realtime.js') }}"></script>
    @endif
    
    <!-- Scripts de Facturas (solo para vistas que lo necesiten) -->
    @if(request()->is(['cartera/pedidos', 'cartera/aprobados', 'cartera/rechazados', 'cartera/anulados']))
    <script defer src="{{ asset('js/modulos/invoice/ModalManager.js') }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}"></script>
    @endif
    
    <!-- Modal de Imágenes Genérico -->
    <script src="{{ asset('js/ImageModal.js') }}"></script>
    
    <!-- Storage Universal y Adaptadores - CARGADO PRIMERO -->
    <script src="{{ asset('js/componentes/universal-imagenes-storage.js') }}"></script>
    <script src="{{ asset('js/componentes/storage-adapters.js') }}"></script>
    
    <!-- Page-specific scripts -->
    @stack('module-scripts')
    @stack('scripts')
    
    <!-- Modals -->
    @stack('modals')
    
    <!--  PAYLOAD NORMALIZER v3 - CARGADO EN BLADE TEMPLATES INDIVIDUALES -->
    <!-- Payload normalizer: payload-normalizer.js -->

</body>
</html>
