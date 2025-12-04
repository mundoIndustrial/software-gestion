<!-- Sidebar Supervisor de Pedidos -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('supervisor-pedidos.index') }}" class="logo-wrapper" aria-label="Ir a Supervisión de Pedidos">
            <img src="{{ asset('images/logo2.png') }}"
                 alt="Logo"
                 class="header-logo"
                 data-logo-light="{{ asset('images/logo2.png') }}"
                 data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>

    <div class="sidebar-content">
        <div class="menu-section">
            <span class="menu-section-title">Supervisión</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.index') && !request()->query('estado') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">dashboard</span>
                        <span class="menu-label">Órdenes de Producción</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Filtros Rápidos</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'No iniciado']) }}"
                       class="menu-link {{ request()->query('estado') === 'No iniciado' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">schedule</span>
                        <span class="menu-label">No Iniciadas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'En Ejecución']) }}"
                       class="menu-link {{ request()->query('estado') === 'En Ejecución' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">hourglass_top</span>
                        <span class="menu-label">En Ejecución</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'Entregado']) }}"
                       class="menu-link {{ request()->query('estado') === 'Entregado' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">check_circle</span>
                        <span class="menu-label">Entregadas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'Anulada']) }}"
                       class="menu-link {{ request()->query('estado') === 'Anulada' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">cancel</span>
                        <span class="menu-label">Anuladas</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
