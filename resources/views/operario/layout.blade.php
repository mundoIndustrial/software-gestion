<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-app-id" content="{{ config('broadcasting.connections.reverb.app_id') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    @endauth
    <title>@yield('title', 'Panel de Operario') - MundoIndustrial</title>

    <!-- CSS -->
    <!--
        Importante: estos CSS se cargan en modo "no render-blocking" para que el overlay
        inicial aparezca inmediatamente (evita pantalla blanca al entrar/después de login).
    -->
    <link rel="preload" href="{{ asset('css/operario/layout.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('css/operario/dashboard.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('css/operario/layout.css') }}">
        <link rel="stylesheet" href="{{ asset('css/operario/dashboard.css') }}">
    </noscript>

    <!-- Material Symbols para iconos -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" as="style" crossorigin onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    </noscript>

    <!-- Font Awesome para iconos -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" crossorigin="anonymous" referrerpolicy="no-referrer" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    </noscript>

    <!-- Google Fonts - Poppins -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" crossorigin onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

    @stack('styles')
    
    <style>
        /* Evita pantalla blanca mientras cargan estilos externos */
        html, body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

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
@php
    $rolOperarioLayout = auth()->user()->hasRole('administrador-costura') ? 'administrador-costura'
        : (auth()->user()->hasRole('vista-costura') ? 'vista-costura'
        : (auth()->user()->hasRole('costura-reflectivo') ? 'costura-reflectivo'
        : (auth()->user()->hasRole('lider-reflectivo') ? 'lider-reflectivo'
        : (auth()->user()->hasRole('confeccion-sobremedida') ? 'confeccion-sobremedida'
        : (auth()->user()->hasRole('costurero') ? 'costurero'
        : (auth()->user()->hasRole('cortador') || auth()->user()->hasRole('visualizador_plooter') ? 'cortador'
        : (auth()->user()->hasRole('bodeguero') ? 'bodeguero' : 'default')))))));
@endphp
<body data-user-role="{{ $rolOperarioLayout }}" data-module="operario">
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
        console.log(' Script de loading overlay iniciado (operario)');

        (function() {
            const overlay = document.getElementById('loading-overlay');
            if (!overlay) return;

            const TRANSITION_KEY = 'mi_loading_overlay_transition';

            const showLoadingOverlay = function() {
                // Asegurar que sea visible aunque se haya puesto display:none
                overlay.style.display = 'flex';
                overlay.classList.remove('hidden');
                overlay.style.opacity = '1';
                overlay.style.pointerEvents = 'auto';
                overlay.dataset.shownAt = String(Date.now());
            };

            const hideLoadingOverlay = function(opts) {
                const minMs = Math.max(0, parseInt(opts?.minMs || 0, 10) || 0);
                const shownAt = parseInt(overlay.dataset.shownAt || '0', 10) || 0;
                const elapsed = shownAt ? (Date.now() - shownAt) : minMs;
                const waitMs = Math.max(0, minMs - elapsed);

                window.setTimeout(function() {
                    overlay.style.pointerEvents = 'none';
                    overlay.classList.add('hidden');
                    overlay.style.opacity = '0';

                    // Fallback: en algunos navegadores/transiciones el overlay puede quedar "pegado".
                    // Forzar display:none después de la transición.
                    window.setTimeout(function() {
                        overlay.style.display = 'none';
                    }, 350);
                }, waitMs);
            };

            window.showLoadingOverlay = showLoadingOverlay;
            window.hideLoadingOverlay = hideLoadingOverlay;

            // Mantener overlay durante navegación y dejar que la página destino decida cuándo ocultarlo.
            document.addEventListener('click', function(e) {
                const link = e.target.closest && e.target.closest('a');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                if (link.target && link.target !== '_self') return;
                if (link.hasAttribute('download')) return;
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                try { sessionStorage.setItem(TRANSITION_KEY, '1'); } catch (_) {}
                showLoadingOverlay();
            }, { capture: true });

            document.addEventListener('submit', function() {
                try { sessionStorage.setItem(TRANSITION_KEY, '1'); } catch (_) {}
                showLoadingOverlay();
            }, { capture: true });

            // Ocultar overlay al cargar la página, salvo que la vista lo maneje manualmente.
            window.addEventListener('load', function() {
                const manual = !!window.__OVERLAY_MANUAL_HIDE;
                let transition = false;
                try { transition = sessionStorage.getItem(TRANSITION_KEY) === '1'; } catch (_) {}

                if (manual) {
                    // Aunque el hide sea manual, limpiar la bandera de transición para no arrastrarla.
                    try { sessionStorage.removeItem(TRANSITION_KEY); } catch (_) {}
                    return;
                }

                // Si venimos de transición, asegurar un mínimo de tiempo para evitar "parpadeos".
                hideLoadingOverlay({ minMs: transition ? 300 : 0 });
                try { sessionStorage.removeItem(TRANSITION_KEY); } catch (_) {}
            });
        })();
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
                <script>
                    window.OPERARIO_USUARIO = {
                        id: {{ Auth::id() }},
                        nombre: '{{ Auth::user()->name ?? '' }}',
                        rol: '{{ $rolOperarioLayout }}'
                    };
                    window.USUARIO_ACTUAL = {
                        id: {{ Auth::id() }},
                        nombre: '{{ Auth::user()->name ?? '' }}',
                        rol: '{{ $rolOperarioLayout }}'
                    };
                    console.log('[Operario Layout] Usuario realtime inicializado', window.USUARIO_ACTUAL);
                </script>

                <div class="top-nav-actions">
                    <div class="notificaciones-dropdown">
                        <button class="notificaciones-btn" id="notificacionesBtn" type="button">
                            <span class="material-symbols-rounded">notifications</span>
                            <span class="notificaciones-badge" id="notificacionesBadge" style="display: none;">0</span>
                        </button>
                        <div class="notificaciones-menu" id="notificacionesMenu">
                            <div class="notificaciones-header">
                                <span class="notificaciones-title">Notificaciones</span>
                                <button class="notificaciones-markall" id="notificacionesMarkAll" type="button">Marcar todas</button>
                            </div>
                            <div class="notificaciones-list" id="notificacionesList"></div>
                            <div class="notificaciones-empty" id="notificacionesEmpty" style="display: none;">No tienes notificaciones</div>
                        </div>
                    </div>

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
                        @if(Auth::user()->hasRole('administrador-costura'))
                            <a href="{{ url('/tableros_ordenes') }}" class="menu-item">
                                <span class="material-symbols-rounded">arrow_back</span>
                                <span>Volver a tableros</span>
                            </a>
                            <div class="menu-divider"></div>
                        @endif
                        @if(Auth::user()->hasRole('costura-reflectivo'))
                            <a href="{{ url('/tableros_ordenes') }}" class="menu-item">
                                <span class="material-symbols-rounded">dashboard</span>
                                <span>Ver Tableros</span>
                            </a>
                            <div class="menu-divider"></div>
                        @endif
                        @if(Auth::user()->hasRole('vista-costura'))
                            @if(request()->routeIs('entregas-talleres.*'))
                                <a href="{{ url('/operario/dashboard') }}" class="menu-item">
                                    <span class="material-symbols-rounded">arrow_back</span>
                                    <span>Volver a recibos</span>
                                </a>
                            @else
                                <a href="{{ route('entregas-talleres.index') }}" class="menu-item">
                                    <span class="material-symbols-rounded">construction</span>
                                    <span>Entregas Talleres</span>
                                </a>
                            @endif
                            <div class="menu-divider"></div>
                        @endif


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
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    <script>
        // Configuración de rutas para JavaScript
        window.APP_ROUTES = {
            buscar: '{{ route("operario.buscar") }}'
        };
    </script>
    <script src="{{ asset('js/configuraciones/toast-notifications.js') }}"></script>
    @stack('scripts')
</body>
</html>
