<!-- Top Navigation Moderna -->
<header class="top-nav">
    <div class="nav-left">
        <button class="mobile-toggle" id="mobileToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="breadcrumb-section">
            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        </div>
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
                    <span class="user-role">Asesor</span>
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
                <a href="{{ route('asesores.profile') }}" class="menu-item">
                    <span class="material-symbols-rounded">person</span>
                    <span>Mi Perfil</span>
                </a>
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">settings</span>
                    <span>Configuración</span>
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
