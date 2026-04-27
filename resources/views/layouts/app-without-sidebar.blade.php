@extends('layouts.base')

@section('module', 'produccion')

@section('body')
<div class="app-container">
    <!-- SIN SIDEBAR PARA DESPACHO -->

    <div class="main-content" id="mainContent">
        <!-- Top Navigation Moderna -->
        @unless(request()->boolean('embed'))
        <header class="top-nav">
            <div class="nav-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
                
                <!--  Barra de búsqueda (solo en vista de órdenes, bodega y cotizaciones pendientes) -->
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
                
                <!-- Perfil de Usuario -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn" aria-label="Menú de usuario" aria-expanded="false" aria-controls="userMenu">
                        <div class="user-avatar">
                            @if(Auth::check() && Auth::user()->avatar)
                                <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="Avatar de {{ Auth::user()->name }}">
                            @else
                                <div class="avatar-placeholder" aria-label="Avatar">
                                    {{ strtoupper(substr(Auth::check() ? Auth::user()->name : 'U', 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ Auth::check() ? Auth::user()->name : 'Invitado' }}</span>
                            <span class="user-role">{{ Auth::check() ? (Auth::user()->role->name ?? 'Usuario') : 'Invitado' }}</span>
                        </div>
                    </button>
                    <div class="user-menu" id="userMenu" role="region" aria-label="Menú de usuario">
                        <div class="user-menu-header">
                            <div class="user-avatar-large">
                                @if(Auth::check() && Auth::user()->avatar)
                                    <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
                                @else
                                    <div class="avatar-placeholder">
                                        {{ strtoupper(substr(Auth::check() ? Auth::user()->name : 'U', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="user-menu-name">{{ Auth::check() ? Auth::user()->name : 'Invitado' }}</p>
                                <p class="user-menu-email">{{ Auth::check() ? Auth::user()->email : 'invitado@ejemplo.com' }}</p>
                            </div>
                        </div>
                        <div class="menu-divider"></div>
                        @if(Auth::check() && !Auth::user()->hasRole('bodeguero'))
                            @if(Auth::user()->hasRole('costura-reflectivo'))
                            <a href="{{ route('operario.dashboard') }}"
                               class="menu-item {{ Route::currentRouteName() === 'tableros-ordenes.index' ? 'active' : '' }}"
                               role="button"
                               data-no-intercept="1"
                               onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.href;">
                                <span class="material-symbols-rounded">list</span>
                                <span>Ver todas las ordenes</span>
                            </a>
                            @else
                            <a href="{{ route('operario.dashboard', ['todas' => 1]) }}"
                               class="menu-item {{ Route::currentRouteName() === 'tableros-ordenes.index' ? 'active' : '' }}"
                               role="button"
                               data-no-intercept="1"
                               onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.href;">
                                <span class="material-symbols-rounded">list</span>
                                <span>Ver todas las ordenes</span>
                            </a>
                            @endif
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
        </header>
        @endunless

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>

<style>
/* Estilos específicos para bodega - eliminar espacio innecesario */
body[data-module="produccion"] .page-content {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    min-height: 100vh !important;
    background: transparent !important;
}

/* Asegurar que el contenido de bodega ocupe todo el ancho */
body[data-module="produccion"] .min-h-screen {
    width: 100vw !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Eliminar cualquier espacio en el main-content para bodega */
body[data-module="produccion"] .main-content {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}

/* 🚨 CORRECCIÓN: Eliminar margen izquierdo de 200px del app-container */
body[data-module="produccion"] .app-container {
    margin: 0 !important;
    padding: 0 !important;
    width: 100vw !important;
}

/* 🚨 CORRECCIÓN: Forzar ancho completo del main-content */
body[data-module="produccion"] .main-content {
    margin-left: 0 !important;
    padding-left: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
}

/* 🚨 CORRECCIÓN: Forzar ancho completo del page-content */
body[data-module="produccion"] .page-content {
    margin-left: 0 !important;
    padding-left: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
}
</style>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <style>
        /* Despacho: Sin sidebar, ancho completo */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        /* Solo aplicar estos estilos si realmente hay un .container */
        .container {
            display: flex;
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
        }

        .container .main-content {
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            flex: 1 !important;
        }

        .main-content .page-content {
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
            max-width: 100% !important;
        }

        /* Evitar conflictos con tablas - NO forzar estilos en .main-content */
        .main-content {
            margin: initial !important;
            padding: initial !important;
        }
        
        /* Asegurar que las tablas no se vean afectadas */
        table {
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        th, td {
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/nav-search.js') }}"></script>
    @if(Route::currentRouteName() === 'cotizaciones.pendientes')
        <script src="{{ asset('js/contador/busqueda-header.js') }}"></script>
        <script>
            // Inicializar búsqueda para cotizaciones pendientes
            function initSearchBar() {

                // Buscar el input de múltiples formas
                let searchInput = document.getElementById('searchInput');
                if (!searchInput) {
                    // Intentar buscar por clase
                    searchInput = document.querySelector('.nav-search-input');
                }
                
                if (!searchInput) {
                    // Listar todos los inputs en el nav
                    const allInputs = document.querySelectorAll('input');
                    const navInputs = document.querySelectorAll('.nav-search-wrapper input, .nav-search-container input');
                    if (navInputs.length > 0) {
                        searchInput = navInputs[0];
                    }
                }
                if (searchInput && typeof aplicarBusquedaYFiltros === 'function') {
                    searchInput.addEventListener('input', aplicarBusquedaYFiltros);
                    return true;
                } else {
                    return false;
                }
            }
            
            // Intentar múltiples veces para asegurar que el DOM esté listo
            document.addEventListener('DOMContentLoaded', function() {
                if (!initSearchBar()) {
                    // Si falla, intentar después de un pequeño delay
                    setTimeout(function() {
                        initSearchBar();
                    }, 100);
                }
            });
            
            // También intentar cuando la ventana esté completamente cargada
            window.addEventListener('load', function() {
                const searchInput = document.getElementById('searchInput');
                if (searchInput && !searchInput.hasAttribute('data-initialized')) {
                    searchInput.setAttribute('data-initialized', 'true');
                    initSearchBar();
                }
            });
        </script>
    @endif
@endpush
