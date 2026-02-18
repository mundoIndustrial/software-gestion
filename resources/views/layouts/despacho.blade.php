@extends('layouts.base')

@section('module', 'despacho')

@section('body')
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
</script>

<!-- Vite App Bundle (incluye Bootstrap.js con Echo initialization) -->
@vite(['resources/js/app.js'])

<!-- Laravel Echo - Para actualizaciones en tiempo real (solo para usuarios autorizados) -->
@auth
@if(auth()->user()->hasRole('asesor') || auth()->user()->hasRole('supervisor_pedidos') || auth()->user()->hasRole('despacho'))
<script defer src="{{ asset('js/modulos/asesores/pedidos-realtime.js') }}"></script>
@endif
@endauth

<!-- Scripts de Facturas para vistas de despacho -->
@if(request()->is(['despacho/*']))
<script src="{{ asset('js/modulos/invoice/InvoiceDataFetcher.js') }}"></script>
<script src="{{ asset('js/modulos/invoice/ModalManager.js') }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}"></script>
<script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}"></script>
@endif

<!-- Modal de Cotización Global -->
<script src="{{ asset('js/contador/cotizacion.js') }}"></script>

<!-- Notifications realtime system (loaded once) -->
<script src="{{ asset('js/configuraciones/notifications-realtime.js') }}"></script>

@stack('scripts')
@endsection
