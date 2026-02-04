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
    <!-- Sección Principal -->
    <div class="menu-section">
      <span class="menu-section-title">Principal</span>
      <ul class="menu-list" role="navigation" aria-label="Menú principal">
      @if(auth()->user()->role && auth()->user()->role->name !== 'supervisor')
      <li class="menu-item">
        <a href="{{ route('dashboard') }}"
           class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="Ir al Dashboard">
          <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
          <span class="menu-label">Dashboard</span>
        </a>
      </li>
      @endif

      </ul>
    </div>

    <!-- Sección Gestión de Órdenes -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión</span>
      <ul class="menu-list" role="navigation">
      @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
      <!-- Menú simplificado para supervisores: Solo Gestión de Órdenes > Pedidos sin submenú -->
      <li class="menu-item">
        <a href="{{ route('registros.index') }}"
           class="menu-link {{ request()->routeIs('registros.index') ? 'active' : '' }}"
           aria-label="Ver registro de órdenes">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Gestión de Órdenes</span>
        </a>
      </li>
      @else
      <!-- Menú completo para otros roles -->
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ (request()->routeIs('registros.index') || request()->routeIs('bodega.index')) ? 'active' : '' }}"
                aria-label="Ver órdenes">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Gestión de Órdenes</span>
          <span class="material-symbols-rounded submenu-arrow" aria-hidden="true">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('registros.index') }}"
               class="menu-link {{ request()->routeIs('registros.index') ? 'active' : '' }}"
               aria-label="Ver registro de órdenes">
              <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
              <span class="menu-label">Pedidos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('bodega.index') }}"
               class="menu-link {{ request()->routeIs('bodega.index') ? 'active' : '' }}"
               aria-label="Ver órdenes de bodega">
              <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
              <span class="menu-label">Bodega</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Entregas y Vistas -->
    <div class="menu-section">
      <span class="menu-section-title">Seguimiento</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ (request()->routeIs('entrega.index') && in_array(request()->route('tipo'), ['pedido', 'bodega'])) ? 'active' : '' }}"
                aria-label="Ver entregas">
          <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
          <span class="menu-label">Entregas</span>
          <span class="material-symbols-rounded submenu-arrow" aria-hidden="true">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('entrega.index', ['tipo' => 'pedido']) }}"
               class="menu-link {{ request()->routeIs('entrega.index') && request()->route('tipo') === 'pedido' ? 'active' : '' }}"
               aria-label="Ver entrega pedido">
              <span class="menu-label">Pedidos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('entrega.index', ['tipo' => 'bodega']) }}"
               class="menu-link {{ request()->routeIs('entrega.index') && request()->route('tipo') === 'bodega' ? 'active' : '' }}"
               aria-label="Ver entrega bodega">
              <span class="menu-label">Bodega</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="menu-item">
        <a href="{{ route('tableros.index') }}"
           class="menu-link {{ request()->routeIs('tableros.index') ? 'active' : '' }}"
           aria-label="Ver tableros">
          <span class="material-symbols-rounded" aria-hidden="true">table_chart</span>
          <span class="menu-label">Tableros</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('balanceo.index') }}"
           class="menu-link {{ request()->routeIs('balanceo.index') ? 'active' : '' }}"
           aria-label="Ver balanceo">
          <span class="material-symbols-rounded" aria-hidden="true">schedule</span>
          <span class="menu-label">Balanceo</span>
        </a>
      </li>

      @if(auth()->user()->role && auth()->user()->role->name === 'supervisor-admin')
      <li class="menu-item">
        <a href="{{ route('cotizaciones.index') }}"
           class="menu-link {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}"
           aria-label="Ver cotizaciones">
          <span class="material-symbols-rounded" aria-hidden="true">receipt</span>
          <span class="menu-label">Cotizaciones</span>
        </a>
      </li>
      @endif

      <li class="menu-item">
        <button class="menu-link submenu-toggle"
                aria-label="Ver vistas">
          <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
          <span class="menu-label">Vistas</span>
          <span class="material-symbols-rounded submenu-arrow" aria-hidden="true">expand_more</span>
        </button>
        <ul class="submenu">
          @if(!auth()->user()->hasRole('bodeguero'))
          <li class="submenu-item">
            <a href="{{ route('vistas.index', ['tipo' => 'corte']) }}"
               class="menu-link"
               aria-label="Ver corte">
              <span class="menu-label">Corte</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('vistas.index') }}"
               class="menu-link"
               aria-label="Ver producción">
              <span class="menu-label">Costura</span>
            </a>
          </li>
          @endif

          @if(!auth()->user()->hasRole('cortador'))
          <li class="submenu-item">
            <a href="{{ route('vistas.index', ['tipo' => 'corte', 'origen' => 'bodega']) }}"
               class="menu-link"
               aria-label="Ver corte bodega">
              <span class="menu-label">Corte Bodega</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('vistas.index', ['tipo' => 'bodega']) }}"
               class="menu-link"
               aria-label="Ver producción bodega">
              <span class="menu-label">Costura Bodega</span>
            </a>
          </li>
          @endif

          <!-- Módulo Bodega para Bodeguero -->
          @if(auth()->user()->hasRole('bodeguero'))
          <li class="submenu-item">
            <a href="{{ route('bodega.pedidos') }}"
               class="menu-link {{ request()->routeIs('bodega.pedidos') ? 'active' : '' }}"
               aria-label="Gestión de pedidos - Bodega">
              <span class="menu-label">📦 Gestión Pedidos</span>
            </a>
          </li>
          @endif

          <li class="submenu-item">
            <a href="{{ route('vistas.control-calidad') }}"
               class="menu-link"
               aria-label="Ver control de calidad">
              <span class="menu-label">Control de Calidad</span>
            </a>
          </li>
        </ul>
      </li>
      @endif
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
  </div>

  <!-- Footer -->
  <div class="sidebar-footer">
  </div>
</aside>
