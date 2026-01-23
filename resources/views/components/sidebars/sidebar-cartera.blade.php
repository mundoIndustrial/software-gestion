<!-- Sidebar Cartera de Pedidos -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('cartera.pedidos') }}" class="logo-wrapper" aria-label="Ir a Gestión de Cartera">
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
        <!-- Sección Principal -->
        <div class="menu-section">
            <span class="menu-section-title">Gestión de Cartera</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('cartera.pedidos') }}"
                       class="menu-link {{ Route::currentRouteName() === 'cartera.pedidos' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">assignment</span>
                        <span class="menu-label">Pedidos Pendientes</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
