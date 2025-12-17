<!-- Top Navigation Moderna -->
<header class="top-nav">
    <div class="nav-left">
        <button class="mobile-toggle" id="mobileToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="breadcrumb-section">
            <h1 class="page-title">@yield('page-title', 'Contador')</h1>
        </div>
    </div>

    <!-- Barra de Búsqueda en el Header -->
    <div class="nav-search">
        <div class="search-container">
            <input 
                type="text" 
                id="searchInput" 
                placeholder="Buscar por número, cliente o asesora..." 
                class="search-input"
                oninput="aplicarBusquedaYFiltros()"
            >
            <i class="fas fa-search search-icon"></i>
        </div>
        <button 
            id="btnLimpiarFiltros"
            onclick="limpiarTodosFiltros()"
            style="
                padding: 6px 12px;
                background: rgba(255,255,255,0.2);
                color: white;
                border: 1px solid rgba(255,255,255,0.3);
                border-radius: 6px;
                cursor: pointer;
                font-weight: 500;
                font-size: 0.85rem;
                transition: all 0.2s ease;
                opacity: 0;
                visibility: hidden;
                transform: scale(0);
                margin-left: 8px;
            "
            onmouseover="this.style.background='rgba(255,255,255,0.3)'"
            onmouseout="this.style.background='rgba(255,255,255,0.2)'"
        >
            <i class="fas fa-redo" style="margin-right: 4px;"></i>Limpiar
        </button>
    </div>

    <div class="nav-right">
        <!-- Notificaciones -->
        <div class="notification-dropdown">
            <button class="notification-btn" id="notificationBtn" aria-label="Notificaciones">
                <span class="material-symbols-rounded">notifications</span>
                <span class="notification-badge" id="notificationBadge">0</span>
            </button>
            <div class="notification-menu" id="notificationMenu">
                <div class="notification-header">
                    <h3>Notificaciones</h3>
                    <button class="mark-all-read">Marcar todas</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="notification-empty">
                        <span class="material-symbols-rounded">notifications_off</span>
                        <p>Sin notificaciones</p>
                    </div>
                </div>
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
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">Contador</span>
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
                <a href="{{ route('contador.profile') }}" class="menu-item">
                    <span class="material-symbols-rounded">person</span>
                    <span>Mi Perfil</span>
                </a>
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
