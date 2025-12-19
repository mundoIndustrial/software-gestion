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
            <span class="menu-section-title">Estado de Aprobación</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['aprobacion' => 'pendiente']) }}"
                       class="menu-link {{ request()->query('aprobacion') === 'pendiente' && !request()->query('tipo') ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">pending_actions</span>
                        <span class="menu-label">Pendientes</span>
                        <span class="badge-alert" id="ordenesPendientesCount" style="display: none;">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
                       class="menu-link {{ request()->query('tipo') === 'logo' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">image</span>
                        <span class="menu-label">Pendientes Logo</span>
                        <span class="badge-alert" id="ordenesPendientesLogoCount" style="display: none;">0</span>
                    </a>
                </li>
            </ul>
        </div>

    </div>
</aside>
