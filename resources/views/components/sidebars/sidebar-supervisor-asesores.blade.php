<!-- Sidebar Supervisor de Asesores -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('supervisor-asesores.dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
            <img src="{{ asset('images/logo2.png') }}"
                 alt="Logo"
                 class="header-logo"
                 data-logo-light="{{ asset('images/logo2.png') }}"
                 data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
        </a>
        <!-- Botón chevron para colapsar (visible en desktop, oculto en móvil) -->
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>

    <div class="sidebar-content">
        <div class="menu-section">
            <span class="menu-section-title">Principal</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.dashboard') }}"
                       class="menu-link {{ request()->routeIs('supervisor-asesores.dashboard') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">dashboard</span>
                        <span class="menu-label">Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Cotizaciones</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.cotizaciones.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-asesores.cotizaciones.*') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">description</span>
                        <span class="menu-label">Todas las Cotizaciones</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Pedidos</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.pedidos.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-asesores.pedidos.*') && !request('aprobacion') && !request('tipo') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        <span class="menu-label">Todos los Pedidos</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
                       class="menu-link {{ request('aprobacion') === 'pendiente' && request('tipo') === 'logo' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">palette</span>
                        <span class="menu-label">Pendientes Logo</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Información</span>
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.asesores.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-asesores.asesores.index', 'supervisor-asesores.asesores.show') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">group</span>
                        <span class="menu-label">Asesores</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-footer">
    </div>
</aside>
