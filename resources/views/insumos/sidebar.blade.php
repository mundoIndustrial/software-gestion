<!-- Overlay para móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Navegación superior para móviles -->
<nav class="site-nav">
  <button class="sidebar-toggle" aria-label="Abrir menú">
    <span class="material-symbols-rounded">menu</span>
  </button>
</nav>

<!-- Sidebar principal -->
<aside class="sidebar collapsed" id="sidebar">
  <div class="sidebar-header">
    <img src="{{ asset('images/logo2.png') }}"
         alt="Logo Mundo Industrial"
         class="header-logo"
         data-logo-light="{{ asset('images/logo2.png') }}"
         data-logo-dark="{{ asset('logo.png') }}" />
    <button class="sidebar-toggle" aria-label="Colapsar menú">
      <span class="material-symbols-rounded">chevron_left</span>
    </button>
  </div>

  <div class="sidebar-content">
    <!-- Lista del menú principal -->
    <ul class="menu-list" role="navigation" aria-label="Menú principal">
      <!-- Dashboard -->
      <li class="menu-item">
        <a href="{{ route('insumos.dashboard') }}"
           class="menu-link {{ request()->routeIs('insumos.dashboard') ? 'active' : '' }}"
           aria-label="Ir al Dashboard">
          <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
          <span class="menu-label">Dashboard</span>
        </a>
      </li>

      <!-- Control de Insumos -->
      <li class="menu-item">
        <a href="{{ route('insumos.materiales.index') }}"
           class="menu-link {{ request()->routeIs('insumos.materiales.*') ? 'active' : '' }}"
           aria-label="Control de Insumos">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Control de Insumos</span>
        </a>
      </li>

      <!-- Salir -->
      <li class="menu-item">
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit"
                  class="menu-link"
                  style="border:none;background:none;cursor:pointer;width:100%;"
                  aria-label="Cerrar sesión">
            <span class="material-symbols-rounded" aria-hidden="true">logout</span>
            <span class="menu-label">Salir</span>
          </button>
        </form>
      </li>
    </ul>
  </div>

  <!-- Footer con toggle de tema -->
  <div class="sidebar-footer">
    <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
      <div class="theme-label">
        <span class="material-symbols-rounded" aria-hidden="true">light_mode</span>
        <span class="theme-text">Modo Claro</span>
      </div>
      <div class="theme-toggle-track">
        <div class="theme-toggle-indicator"></div>
      </div>
    </button>
  </div>
</aside>
