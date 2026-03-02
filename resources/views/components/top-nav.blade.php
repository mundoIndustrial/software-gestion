<header class="top-nav">
    <div class="nav-left">
        <button class="mobile-toggle" id="mobileToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="breadcrumb-section">
            @if(request()->is('recibos-costura'))
                <h1 class="page-title">Recibos de Costura</h1>
            @elseif(request()->is('entregas-completas*'))
                <h1 class="page-title">Seguimiento Entregas Despacho</h1>
            @else
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
            @endif
        </div>

        @php
            $currentRoute = Route::currentRouteName();
            $currentPath = request()->path();
            $isCotizacionesPendientes = $currentRoute === 'cotizaciones.pendientes';
            $isRecibosCostura = $currentPath === 'recibos-costura';
            $searchInputId = $isCotizacionesPendientes ? 'searchInput' : 'navSearchInput';
            $searchPlaceholder = $isRecibosCostura ? 'Buscar recibos por número o cliente...' : ($isCotizacionesPendientes ? 'Buscar por número, cliente o asesora...' : 'Buscar por número o cliente...');
            $searchAriaLabel = $isRecibosCostura ? 'Búsqueda de recibos' : ($isCotizacionesPendientes ? 'Búsqueda de cotizaciones' : 'Búsqueda de órdenes');
        @endphp
        @if($currentRoute === 'registros.index' || $currentRoute === 'bodega.index' || $currentRoute === 'cotizaciones.pendientes' || $isRecibosCostura)
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
        @if(Route::currentRouteName() === 'cotizaciones.pendientes' || request()->is('recibos-costura'))
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
