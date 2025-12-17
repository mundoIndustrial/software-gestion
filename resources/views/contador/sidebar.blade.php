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
         data-logo-dark="{{ asset('images/logo2.png') }}" />
    <button class="sidebar-toggle" aria-label="Colapsar menú">
      <span class="material-symbols-rounded">chevron_left</span>
    </button>
  </div>

  <div class="sidebar-content">
    <!-- Lista del menú principal -->
    <ul class="menu-list" role="navigation" aria-label="Menú principal">
      <!-- Pendientes -->
      <li class="menu-item">
        <a href="{{ route('contador.index') }}"
           class="menu-link {{ request()->routeIs('contador.index') ? 'active' : '' }}"
           style="display:flex;align-items:center;gap:0.5rem;"
           aria-label="Ver Cotizaciones Pendientes">
          <span class="material-symbols-rounded" aria-hidden="true">schedule</span>
          <span class="menu-label">Pendientes</span>
          <span class="badge-alert" id="cotizacionesPendientesCount" style="display:none;">0</span>
        </a>
      </li>

      <!-- Ver Todas las Cotizaciones -->
      <li class="menu-item">
        <a href="{{ route('contador.todas') }}"
           class="menu-link {{ request()->routeIs('contador.todas') ? 'active' : '' }}"
           aria-label="Ver Todas las Cotizaciones">
          <span class="material-symbols-rounded" aria-hidden="true">list_alt</span>
          <span class="menu-label">Ver Todas</span>
        </a>
      </li>

      <!-- Cotizaciones por Revisar -->
      <li class="menu-item">
        <a href="{{ route('contador.por-revisar') }}"
           class="menu-link {{ request()->routeIs('contador.por-revisar') ? 'active' : '' }}"
           style="display:flex;align-items:center;gap:0.5rem;"
           aria-label="Ver Cotizaciones a Revisar">
          <span class="material-symbols-rounded" aria-hidden="true">refresh</span>
          <span class="menu-label">Cotizaciones a Revisar</span>
          @if(isset($cotizacionesRechazadas) && $cotizacionesRechazadas->count() > 0)
            <span class="badge-alert">{{ $cotizacionesRechazadas->count() }}</span>
          @endif
        </a>
      </li>

      <!-- Cotizaciones Aprobadas -->
      <li class="menu-item">
        <a href="{{ route('contador.aprobadas') }}"
           class="menu-link {{ request()->routeIs('contador.aprobadas') ? 'active' : '' }}"
           style="display:flex;align-items:center;gap:0.5rem;"
           aria-label="Ver Cotizaciones Aprobadas">
          <span class="material-symbols-rounded" aria-hidden="true">verified</span>
          <span class="menu-label">Cotizaciones Aprobadas</span>
        </a>
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
