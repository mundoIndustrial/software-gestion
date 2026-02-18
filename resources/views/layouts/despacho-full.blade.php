<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <!-- Meta tags para WebSockets/Reverb -->
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-app-id" content="{{ config('broadcasting.connections.reverb.app_id') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    @endauth
    
    <title>@yield('title', config('app.name', 'Mundo Industrial'))</title>

    @yield('meta')

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Vite App Bundle -->
    @vite(['resources/js/app.js', 'resources/js/app.css'])

    <!-- Alpine.js -->
    <script defer src="{{ asset('js/alpine.js') }}"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.min.css" />

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Global CSS -->
    <link rel="stylesheet" href="{{ asset('build/css/app-BHMndXNp.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    
    <!-- Page-specific CSS -->
    @stack('styles')
</head>

<body class="bg-slate-50" data-module="@yield('module', 'default')">
    <div class="app-container">
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Navigation Moderna -->
            <header class="top-nav">
                <div class="nav-left">
                    <div class="breadcrumb-section">
                        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
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
                                <span class="user-role">{{ auth()->user()->role?->name ?? 'user' }}</span>
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

    <!-- Core JS -->
    <script src="{{ asset('js/configuraciones/sidebar.js') }}"></script>

    <!-- GLOBAL: Usuario autenticado disponible desde el inicio -->
    <script>
        window.authUser = @json(auth()->user());
        window.userRole = '{{ auth()->user()->role?->name ?? 'user' }}';
        window.userId = '{{ auth()->id() }}';
    </script>

    <!-- Vite App Bundle (incluye Bootstrap.js con Echo initialization) -->
    @vite(['resources/js/app.js'])

    <!-- Laravel Echo - Para actualizaciones en tiempo real (solo para usuarios autorizados) -->
    @auth
    @if(auth()->user()->hasRole('asesor') || auth()->user()->hasRole('supervisor_pedidos') || auth()->user()->hasRole('despacho'))
    <script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
    @endif
    @endauth

    <!-- Scripts de Facturas (solo para vistas que lo necesiten) -->
    @if(request()->is(['cartera/pedidos', 'cartera/aprobados', 'cartera/rechazados', 'cartera/anulados']))
    <script defer src="{{ asset('js/modulos/invoice/ModalManager.js') }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}"></script>
    @endif

    <!-- Page-specific scripts -->
    @stack('scripts')

    <!-- Modals -->
    @stack('modals')

    <!--  PAYLOAD NORMALIZER v3 - CARGADO EN BLADE TEMPLATES INDIVIDUALES -->
    <!-- Payload normalizer: payload-normalizer.js -->

</body>
</html>
