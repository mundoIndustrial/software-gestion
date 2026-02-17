<!-- Overlay para móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Navegación superior para móviles -->
<nav class="site-nav">
  <button class="sidebar-toggle" aria-label="Abrir menú">
    <span class="material-symbols-rounded">menu</span>
  </button>
</nav>

<!-- Sidebar principal -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="{{ route('dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
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
    <!-- BODEGUERO: Menú simplificado solo Gestión de Bodega -->
    @if(auth()->user()->hasRole('bodeguero'))
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Bodega</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos') ? 'active' : '' }}"
           aria-label="Gestión de pedidos - Bodega">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Pendientes Pedidos</span>
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
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-costura') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-costura') ? 'active' : '' }}"
           aria-label="Pendiente Costura">
          <span class="material-symbols-rounded" aria-hidden="true">checklist</span>
          <span class="menu-label">Pendiente Costura</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-epp') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-epp') ? 'active' : '' }}"
           aria-label="Pendiente EPP">
          <span class="material-symbols-rounded" aria-hidden="true">shield</span>
          <span class="menu-label">Pendiente EPP</span>
        </a>
      </li>
      </ul>
    </div>

    @else
    <!-- OTROS ROLES: Menú completo -->
    
    <!-- Sección Principal -->
    <div class="menu-section">
      <span class="menu-section-title">Principal</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('dashboard') }}"
           class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="Dashboard">
          <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
          <span class="menu-label">Dashboard</span>
        </a>
      </li>
      </ul>
    </div>

    <!-- Sección Gestión de Órdenes -->
    <div class="menu-section">
      <span class="menu-section-title">Gestiónar Ordenes</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('registros.*') ? 'active' : '' }}"
                aria-label="Gestionar pedidos">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Pedidos</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('registros.index') }}"
               class="menu-link {{ request()->routeIs('registros.index') ? 'active' : '' }}"
               aria-label="Ver todos los pedidos">
              <span class="material-symbols-rounded" aria-hidden="true">list</span>
              <span class="menu-label">Todos los Pedidos</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Bodega -->
    <div class="menu-section">
      <span class="menu-section-title">Bodega</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('bodega.index') }}"
           class="menu-link {{ request()->routeIs('bodega.*') ? 'active' : '' }}"
           aria-label="Módulo de bodega">
          <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
          <span class="menu-label">Bodega</span>
        </a>
      </li>
      </ul>
    </div>

    <!-- Sección Gestión de Bodega -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Bodega</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('despacho.index') }}"
           class="menu-link {{ request()->routeIs('despacho.*') ? 'active' : '' }}"
           aria-label="Módulo de Despacho">
          <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
          <span class="menu-label">Despacho</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos') ? 'active' : '' }}"
           aria-label="Gestión de pedidos - Bodega">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Pendientes Pedidos</span>
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
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-costura') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-costura') ? 'active' : '' }}"
           aria-label="Pendiente Costura">
          <span class="material-symbols-rounded" aria-hidden="true">checklist</span>
          <span class="menu-label">Pendiente Costura</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-epp') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-epp') ? 'active' : '' }}"
           aria-label="Pendiente EPP">
          <span class="material-symbols-rounded" aria-hidden="true">shield</span>
          <span class="menu-label">Pendiente EPP</span>
        </a>
      </li>
      </ul>
    </div>

    <!-- Sección Entregas -->
    <div class="menu-section">
      <span class="menu-section-title">Entregas</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('entrega.*') ? 'active' : '' }}"
                aria-label="Gestionar entregas">
          <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
          <span class="menu-label">Entregas</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('entrega.index', ['tipo' => 'pedido']) }}"
               class="menu-link {{ request()->routeIs('entrega.index') && request()->route('tipo') === 'pedido' ? 'active' : '' }}"
               aria-label="Entregas de pedidos">
              <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
              <span class="menu-label">Pedidos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('entrega.index', ['tipo' => 'bodega']) }}"
               class="menu-link {{ request()->routeIs('entrega.index') && request()->route('tipo') === 'bodega' ? 'active' : '' }}"
               aria-label="Entregas de bodega">
              <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
              <span class="menu-label">Bodega</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Tableros y Balanceo -->
    <div class="menu-section">
      <span class="menu-section-title">Tableros</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('tableros.*', 'balanceo.*') ? 'active' : '' }}"
                aria-label="Tableros y balanceo">
          <span class="material-symbols-rounded" aria-hidden="true">table_chart</span>
          <span class="menu-label">Tableros</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('tableros.index') }}"
               class="menu-link {{ request()->routeIs('tableros.*') ? 'active' : '' }}"
               aria-label="Tableros de producción">
              <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
              <span class="menu-label">Producción</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('balanceo.index') }}"
               class="menu-link {{ request()->routeIs('balanceo.*') ? 'active' : '' }}"
               aria-label="Balanceo de producción">
              <span class="material-symbols-rounded" aria-hidden="true">schedule</span>
              <span class="menu-label">Balanceo</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Vistas -->
    <div class="menu-section">
      <span class="menu-section-title">Vistas</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('vistas.*') ? 'active' : '' }}"
                aria-label="Vistas del sistema">
          <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
          <span class="menu-label">Vistas</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('vistas.index', ['tipo' => 'pedidos']) }}"
               class="menu-link {{ request()->routeIs('vistas.index') ? 'active' : '' }}"
               aria-label="Vista de pedidos">
              <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
              <span class="menu-label">Pedidos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('vistas.index', ['tipo' => 'bodega']) }}"
               class="menu-link {{ request()->routeIs('vistas.index') ? 'active' : '' }}"
               aria-label="Vista de bodega">
              <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
              <span class="menu-label">Bodega</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('vistas.index', ['tipo' => 'corte']) }}"
               class="menu-link {{ request()->routeIs('vistas.index') ? 'active' : '' }}"
               aria-label="Vista de corte">
              <span class="material-symbols-rounded" aria-hidden="true">content_cut</span>
              <span class="menu-label">Corte</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Módulos -->
    @if(auth()->user()->role && auth()->user()->role->name === 'admin')
    <div class="menu-section">
      <span class="menu-section-title">Módulos</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('supervisor-asesores.dashboard') }}"
           class="menu-link {{ request()->routeIs('supervisor-asesores.*') ? 'active' : '' }}"
           aria-label="Ver módulo de asesores">
          <span class="material-symbols-rounded" aria-hidden="true">people</span>
          <span class="menu-label">Asesores</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('insumos.dashboard') }}"
           class="menu-link {{ request()->routeIs('insumos.*') ? 'active' : '' }}"
           aria-label="Ver módulo de insumos">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Insumos</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('contador.index') }}"
           class="menu-link {{ request()->routeIs('contador.*') ? 'active' : '' }}"
           aria-label="Ver módulo de contador">
          <span class="material-symbols-rounded" aria-hidden="true">calculate</span>
          <span class="menu-label">Contador</span>
        </a>
      </li>
      </ul>
    </div>

    <!-- Sección Administración -->
    <div class="menu-section">
      <span class="menu-section-title">Administración</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('users.index') }}"
           class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
           aria-label="Gestionar usuarios">
          <span class="material-symbols-rounded" aria-hidden="true">group</span>
          <span class="menu-label">Usuarios</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('configuracion.index') }}"
           class="menu-link {{ request()->routeIs('configuracion.*') ? 'active' : '' }}"
           aria-label="Configuración del sistema">
          <span class="material-symbols-rounded" aria-hidden="true">settings</span>
          <span class="menu-label">Configuración</span>
        </a>
      </li>
      </ul>
    </div>
    @endif

    @if(auth()->user()->role && auth()->user()->role->name === 'supervisor_planta')
    <!-- Sección Módulos para supervisor_planta -->
    <div class="menu-section">
      <span class="menu-section-title">Módulos</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('insumos.dashboard') }}"
           class="menu-link {{ request()->routeIs('insumos.*') ? 'active' : '' }}"
           aria-label="Ver módulo de insumos">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Insumos</span>
        </a>
      </li>
      </ul>
    </div>
    @endif

    <!-- Aprobador de Cotizaciones - Para cualquier usuario con este rol -->
    @if(auth()->user()->hasRole('aprobador_cotizaciones'))
    <div class="menu-section">
      <span class="menu-section-title">Aprobaciones</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('cotizaciones.pendientes') }}"
           class="menu-link {{ request()->routeIs('cotizaciones.pendientes') ? 'active' : '' }}"
           style="display:flex;align-items:center;gap:0.5rem;"
           aria-label="Ver cotizaciones">
          <span class="material-symbols-rounded" aria-hidden="true">receipt</span>
          <span class="menu-label">Cotizaciones</span>
          <span class="badge-alert" id="cotizacionesPendientesAprobadorCount" style="display:none;">0</span>
        </a>
      </li>
      </ul>
    </div>
    @endif
    @endif
  </div>

  <!-- Footer -->
  <div class="sidebar-footer">
  </div>
</aside>
