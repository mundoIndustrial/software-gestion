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
                @if(Route::currentRouteName() === 'registros.index' || Route::currentRouteName() === 'bodega.index' || Route::currentRouteName() === 'cotizaciones.pendientes')
                <div class="nav-search-container">
                    <div class="nav-search-wrapper">
                        <span class="material-symbols-rounded search-icon" aria-hidden="true">search</span>
                        <input 
                            type="text" 
                            id="navSearchInput" 
                            class="nav-search-input" 
                            placeholder="Buscar por número, cliente o asesora..."
                            autocomplete="off"
                            aria-label="Búsqueda de cotizaciones"
                        >
                        <button class="nav-search-clear" id="navSearchClear" style="display: none;" aria-label="Limpiar búsqueda">
                            <span class="material-symbols-rounded" aria-hidden="true">close</span>
                        </button>
                    </div>
                    <div class="nav-search-results" id="navSearchResults" style="display: none;" role="region" aria-live="polite" aria-label="Resultados de búsqueda"></div>
                </div>
                @endif
            </div>

            <div class="nav-right">
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
    <script src="{{ asset('js/contador/notifications.js') }}"></script>
    <script src="{{ asset('js/nav-search.js') }}"></script>
@endpush
