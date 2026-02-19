<!-- Sidebar específico para Despacho -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="{{ route('despacho.index') }}" class="logo-wrapper" aria-label="Ir a Despacho">
      <img src="{{ asset('images/logo2.png') }}"
           alt="Logo Mundo Industrial"
           class="header-logo"
           data-logo-light="{{ asset('images/logo2.png') }}"
           data-logo-dark="{{ asset('logo.png') }}" />
    </a>
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
      <span class="material-symbols-rounded">chevron_left</span>
    </button>
  </div>

  <div class="sidebar-content">
    <!-- DESPACHO: Menú optimizado -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Despacho</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('despacho.index') }}"
           class="menu-link {{ request()->routeIs('despacho.index') ? 'active' : '' }}"
           aria-label="Módulo de Despacho">
          <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
          <span class="menu-label">Despacho</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('despacho.pendientes') }}"
           class="menu-link {{ request()->routeIs('despacho.pendientes') ? 'active' : '' }}"
           aria-label="Pendientes Unificados">
          <span class="material-symbols-rounded" aria-hidden="true">pending_actions</span>
          <span class="menu-label">Pendientes</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('despacho.entregados') }}"
           class="menu-link {{ request()->routeIs('despacho.entregados') ? 'active' : '' }}"
           aria-label="Entregados - Despacho">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Entregados</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos-anulados') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos-anulados') ? 'active' : '' }}"
           aria-label="Pedidos anulados - Bodega">
          <span class="material-symbols-rounded" aria-hidden="true">cancel</span>
          <span class="menu-label">Pedidos anulados</span>
        </a>
      </li>
      </ul>
    </div>
  </div>
</aside>
