<!-- Sidebar Bordado de Pedidos -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('bordado.index') }}" class="logo-wrapper" aria-label="Ir a Gestión de Bordado">
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
            <span class="menu-section-title">Gestión de Bordado</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('bordado.index') }}"
                       class="menu-link {{ Route::currentRouteName() === 'bordado.index' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">assignment</span>
                        <span class="menu-label">Pedidos</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('bordado.cotizaciones') }}"
                       class="menu-link {{ Route::currentRouteName() === 'bordado.cotizaciones' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">description</span>
                        <span class="menu-label">Cotizaciones</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

<script>
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            // Guardar estado
            localStorage.setItem('bordado-sidebar-collapsed', sidebar.classList.contains('collapsed'));
        });

        // Restaurar estado
        if (localStorage.getItem('bordado-sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
</script>
