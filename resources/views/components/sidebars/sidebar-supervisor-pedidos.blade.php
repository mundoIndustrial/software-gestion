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
        <!-- Sección de Aprobación -->
        <div class="menu-section">
            <span class="menu-section-title">Estado de Aprobación</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index') }}"
                       class="menu-link {{ !request()->query('aprobacion') && !request()->query('estado') ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">pending_actions</span>
                        <span class="menu-label">Pedidos</span>
                        <span class="badge-alert" id="ordenesPendientesCount" style="display: none;">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.pendientes-bordado-estampado') }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.pendientes-bordado-estampado') ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">brush</span>
                        <span class="menu-label">Pendientes Logo</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'Anulada']) }}"
                       class="menu-link {{ request()->query('estado') === 'Anulada' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">cancel</span>
                        <span class="menu-label">Pedidos Anulados</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
