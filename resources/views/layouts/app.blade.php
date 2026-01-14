@extends('layouts.base')

@section('module', 'produccion')

@section('body')
<div class="container">
    @include('layouts.sidebar')

    <div class="main-content" id="mainContent">
        <!-- Top Navigation Moderna -->
        <header class="top-nav">
            <div class="nav-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
                
                <!-- 🆕 Barra de búsqueda (solo en vista de órdenes, bodega y cotizaciones pendientes) -->
                @php
                    $currentRoute = Route::currentRouteName();
                    $isCotizacionesPendientes = $currentRoute === 'cotizaciones.pendientes';
                    $searchInputId = $isCotizacionesPendientes ? 'searchInput' : 'navSearchInput';
                    $searchPlaceholder = $isCotizacionesPendientes ? 'Buscar por número, cliente o asesora...' : 'Buscar por número o cliente...';
                    $searchAriaLabel = $isCotizacionesPendientes ? 'Búsqueda de cotizaciones' : 'Búsqueda de órdenes';
                @endphp
                @if($currentRoute === 'registros.index' || $currentRoute === 'bodega.index' || $currentRoute === 'cotizaciones.pendientes')
                <div class="nav-search-container">
                    <div class="nav-search-wrapper">
                        <span class="material-symbols-rounded search-icon" aria-hidden="true">search</span>
                        <input 
                            type="text" 
                            id="{{ $searchInputId }}" 
                            class="nav-search-input" 
                            placeholder="{{ $searchPlaceholder }}"
                            autocomplete="off"
                            aria-label="{{ $searchAriaLabel }}"
                        >
                        <button class="nav-search-clear" id="navSearchClear" style="display: none;" aria-label="Limpiar búsqueda">
                            <span class="material-symbols-rounded" aria-hidden="true">close</span>
                        </button>
                    </div>
                    @if(Route::currentRouteName() !== 'cotizaciones.pendientes')
                    <div class="nav-search-results" id="navSearchResults" style="display: none;" role="region" aria-live="polite" aria-label="Resultados de búsqueda"></div>
                    @endif
                </div>
                @endif
            </div>

            <div class="nav-right">
                <!-- Botón Limpiar Filtros (solo en cotizaciones pendientes) -->
                @if(Route::currentRouteName() === 'cotizaciones.pendientes')
                <button 
                    id="btnLimpiarFiltros"
                    onclick="limpiarTodosFiltros()"
                    style="
                        padding: 8px 16px;
                        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
                        color: white;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        font-weight: 600;
                        font-size: 0.875rem;
                        transition: all 0.3s ease;
                        opacity: 0;
                        visibility: hidden;
                        transform: scale(0);
                        white-space: nowrap;
                        margin-right: 12px;
                    "
                    onmouseover="if(this.style.opacity === '1') { this.style.transform='scale(1) translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(249, 115, 22, 0.3)'; }"
                    onmouseout="if(this.style.opacity === '1') { this.style.transform='scale(1)'; this.style.boxShadow='none'; }"
                >
                    <i class="fas fa-redo" style="margin-right: 6px;"></i>Limpiar Filtros
                </button>
                @endif
                
                <!-- Notificaciones -->
                <div class="notification-dropdown">
                    <button class="notification-btn" id="notificationBtn" aria-label="Notificaciones" aria-expanded="false" aria-controls="notificationMenu">
                        <span class="material-symbols-rounded" aria-hidden="true">notifications</span>
                        <span class="notification-badge" id="notificationBadge" aria-label="0 notificaciones nuevas">0</span>
                    </button>
                    <div class="notification-menu" id="notificationMenu" role="region" aria-label="Menú de notificaciones">
                        <div class="notification-header">
                            <h3>Notificaciones</h3>
                            <button class="mark-all-read" aria-label="Marcar todas las notificaciones como leídas">Marcar todas</button>
                        </div>
                        <div class="notification-list" id="notificationList" role="list">
                            <div class="notification-empty">
                                <span class="material-symbols-rounded" aria-hidden="true">notifications_off</span>
                                <p>Sin notificaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Perfil de Usuario -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn" aria-label="Menú de usuario" aria-expanded="false" aria-controls="userMenu">
                        <div class="user-avatar">
                            @if(Auth::user()->avatar)
                                <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="Avatar de {{ Auth::user()->name }}">
                            @else
                                <div class="avatar-placeholder" aria-label="Avatar">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ Auth::user()->name }}</span>
                            <span class="user-role">{{ Auth::user()->role->name ?? 'Usuario' }}</span>
                        </div>
                    </button>
                    <div class="user-menu" id="userMenu" role="region" aria-label="Menú de usuario">
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
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/configuraciones/notifications-realtime.js') }}"></script>
    <script src="{{ asset('js/nav-search.js') }}"></script>
    @if(Route::currentRouteName() === 'cotizaciones.pendientes')
        <script src="{{ asset('js/contador/busqueda-header.js') }}"></script>
        <script>
            // Inicializar búsqueda para cotizaciones pendientes
            function initSearchBar() {
                console.log('🔍 Intentando inicializar búsqueda...');
                console.log('🌐 Ruta actual:', window.location.pathname);
                
                // Buscar el input de múltiples formas
                let searchInput = document.getElementById('searchInput');
                console.log('Por ID searchInput:', searchInput);
                
                if (!searchInput) {
                    // Intentar buscar por clase
                    searchInput = document.querySelector('.nav-search-input');
                    console.log('Por clase nav-search-input:', searchInput);
                }
                
                if (!searchInput) {
                    // Listar todos los inputs en el nav
                    const allInputs = document.querySelectorAll('input');
                    console.log('Todos los inputs en la página:', allInputs);
                    const navInputs = document.querySelectorAll('.nav-search-wrapper input, .nav-search-container input');
                    console.log('Inputs en nav-search:', navInputs);
                    if (navInputs.length > 0) {
                        searchInput = navInputs[0];
                        console.log('Usando primer input de nav-search:', searchInput);
                    }
                }
                
                console.log('Función disponible:', typeof aplicarBusquedaYFiltros);
                
                if (searchInput && typeof aplicarBusquedaYFiltros === 'function') {
                    searchInput.addEventListener('input', aplicarBusquedaYFiltros);
                    console.log('✅ Búsqueda inicializada correctamente en input:', searchInput.id || searchInput.className);
                    return true;
                } else {
                    console.error('❌ No se pudo inicializar la búsqueda:', {
                        inputExists: !!searchInput,
                        functionExists: typeof aplicarBusquedaYFiltros === 'function'
                    });
                    return false;
                }
            }
            
            // Intentar múltiples veces para asegurar que el DOM esté listo
            document.addEventListener('DOMContentLoaded', function() {
                console.log('📄 DOMContentLoaded disparado');
                if (!initSearchBar()) {
                    // Si falla, intentar después de un pequeño delay
                    setTimeout(function() {
                        console.log('⏰ Reintentando inicialización después de delay...');
                        initSearchBar();
                    }, 100);
                }
            });
            
            // También intentar cuando la ventana esté completamente cargada
            window.addEventListener('load', function() {
                console.log('🪟 Window load disparado');
                const searchInput = document.getElementById('searchInput');
                if (searchInput && !searchInput.hasAttribute('data-initialized')) {
                    searchInput.setAttribute('data-initialized', 'true');
                    initSearchBar();
                }
            });
        </script>
    @endif
@endpush
