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
    <a href="{{ auth()->check() && auth()->user()->hasRole('visualizador_recibos_logo') ? route('registros.recibos-bordado-estampado') : route('dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
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
    @if(auth()->user()->hasRole('visualizador_recibos_logo'))
    <div class="menu-section">
      <span class="menu-section-title">Recibos</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('registros.recibos-bordado-estampado') }}"
           class="menu-link {{ request()->routeIs('registros.recibos-bordado-estampado') ? 'active' : '' }}"
           aria-label="Recibos Bordado y Estampado">
          <span class="material-symbols-rounded" aria-hidden="true">image</span>
          <span class="menu-label">Recibos Logo</span>
        </a>
      </li>
      </ul>
    </div>

    @else
    <!-- DESPACHO: Menú simplificado solo Gestión de Bodega -->
    @if(auth()->user()->hasRole('despacho'))
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
        <a href="{{ route('despacho.pendientes') }}"
           class="menu-link {{ request()->routeIs('despacho.pendientes') ? 'active' : '' }}"
           aria-label="Pendientes Unificados">
          <span class="material-symbols-rounded" aria-hidden="true">pending_actions</span>
          <span class="menu-label">Pendientes</span>
          <span class="badge badge-pending" id="pendientes-badge" style="display: none; margin-left: 8px; background-color: #ef4444; color: white; padding: 2px 6px; border-radius: 12px; font-size: 12px; font-weight: bold; min-width: 20px; text-align: center;">0</span>
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
        <a href="{{ route('despacho.anulados') }}"
           class="menu-link {{ request()->routeIs('despacho.anulados') ? 'active' : '' }}"
           aria-label="Pedidos anulados - Despacho">
          <span class="material-symbols-rounded" aria-hidden="true">cancel</span>
          <span class="menu-label">Pedidos anulados</span>
        </a>
      </li>
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('gestion-bodega'))
    <!-- GESTION-BODEGA: Menú mínimo solo Recibos Bodega -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Bodega</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('registros.recibos-bodega') }}"
           class="menu-link {{ request()->routeIs('registros.recibos-bodega') || request()->is('recibos-bodega') ? 'active' : '' }}"
           aria-label="Recibos de bodega">
          <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
          <span class="menu-label">Pedidos</span>
        </a>
      </li>
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('bodeguero'))
    <!-- BODEGUERO: Menú simplificado solo Gestión de Bodega -->
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
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos-anulados') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos-anulados') ? 'active' : '' }}"
           aria-label="Pedidos anulados - Bodega">
          <span class="material-symbols-rounded" aria-hidden="true">cancel</span>
          <span class="menu-label">Pedidos anulados</span>
        </a>
      </li>
      @if(auth()->user()->hasRole('bodeguero'))
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos-entregados') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos-entregados') ? 'active' : '' }}"
           aria-label="Pedidos Entregados">
          <span class="material-symbols-rounded" aria-hidden="true">task_alt</span>
          <span class="menu-label">Entregados</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos-ocultos') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos-ocultos') ? 'active' : '' }}"
           aria-label="Pedidos Ocultos">
          <span class="material-symbols-rounded" aria-hidden="true">visibility_off</span>
          <span class="menu-label">Pedidos Ocultos</span>
        </a>
      </li>
      @endif
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('EPP-Bodega'))
    <!-- EPP-BODEGA: Menú con única opción Pendiente EPP -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Bodega</span>
      <ul class="menu-list" role="navigation">
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

    @elseif(auth()->user()->hasRole('Costura-Bodega'))
    <!-- COSTURA-BODEGA: Menú con única opción Pendiente Costura -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Bodega</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-costura') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-costura') ? 'active' : '' }}"
           aria-label="Pendiente Costura">
          <span class="material-symbols-rounded" aria-hidden="true">checklist</span>
          <span class="menu-label">Pendiente Costura</span>
        </a>
      </li>
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('supervisor_gerencia'))
    <!-- SUPERVISOR GERENCIA: Menú especializado para supervisores de gerencia -->
    <div class="menu-section">
      <span class="menu-section-title">Registros</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="/recibos-costura"
           class="menu-link {{ request()->is('recibos-costura') ? 'active' : '' }}"
           aria-label="Recibos de Costura - Vista Principal">
          <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
          <span class="menu-label">Recibos de Costura</span>
        </a>
      </li>
      </ul>
    </div>

    <div class="menu-section">
      <span class="menu-section-title">Gestión de Bodega</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="/supervisor-asesores/pedidos"
           class="menu-link {{ request()->is('supervisor-asesores/pedidos') ? 'active' : '' }}"
           aria-label="Ver todos los pedidos">
          <span class="material-symbols-rounded" aria-hidden="true">list</span>
          <span class="menu-label">Todos los Pedidos</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-epp-list') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-epp-list') ? 'active' : '' }}"
           aria-label="Pendientes EPP">
          <span class="material-symbols-rounded" aria-hidden="true">pending_actions</span>
          <span class="menu-label">Pendientes</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('despacho.index') }}"
           class="menu-link {{ request()->routeIs('despacho.*') ? 'active' : '' }}"
           aria-label="Despacho">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Despacho</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pedidos-anulados') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pedidos-anulados') ? 'active' : '' }}"
           aria-label="Pedidos anulados">
          <span class="material-symbols-rounded" aria-hidden="true">checklist</span>
          <span class="menu-label">Pedidos anulados</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('gestion-bodega.pendientes-costura') }}"
           class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-costura') ? 'active' : '' }}"
           aria-label="Pendiente Costura">
          <span class="material-symbols-rounded" aria-hidden="true">shield</span>
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

    <div class="menu-section">
      <span class="menu-section-title">Entregas</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('entregas-completas.index') }}"
           class="menu-link {{ request()->routeIs('entregas-completas.*') ? 'active' : '' }}"
           aria-label="Entregas Completas">
          <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
          <span class="menu-label">Entregas Completas</span>
        </a>
      </li>
      </ul>
    </div>

    <div class="menu-section">
      <span class="menu-section-title">Gestión de Cartera</span>
      <ul class="menu-list" role="navigation">
        <li class="menu-item">
          <a href="{{ route('cartera.pedidos') }}"
             class="menu-link {{ request()->routeIs('cartera.pedidos') ? 'active' : '' }}"
             style="display:flex;align-items:center;gap:0.5rem;"
             aria-label="Pedidos Pendientes">
            <span class="material-symbols-rounded">assignment</span>
            <span class="menu-label">Pedidos Pendientes</span>
          </a>
        </li>
        <li class="menu-item">
          <a href="{{ route('cartera.aprobados') }}"
             class="menu-link {{ request()->routeIs('cartera.aprobados') ? 'active' : '' }}"
             style="display:flex;align-items:center;gap:0.5rem;"
             aria-label="Aprobados">
            <span class="material-symbols-rounded">check_circle</span>
            <span class="menu-label">Aprobados</span>
          </a>
        </li>
        <li class="menu-item">
          <a href="{{ route('cartera.rechazados') }}"
             class="menu-link {{ request()->routeIs('cartera.rechazados') ? 'active' : '' }}"
             style="display:flex;align-items:center;gap:0.5rem;"
             aria-label="Cancelados">
            <span class="material-symbols-rounded">block</span>
            <span class="menu-label">Cancelados</span>
          </a>
        </li>
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('revisor_entregas'))
    <!-- REVISOR ENTREGAS: Menú especializado solo para Recibos de Costura y Reflectivo -->
    <div class="menu-section">
      <span class="menu-section-title">Gestionar Producción</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="/recibos-costura"
           class="menu-link {{ request()->is('recibos-costura') ? 'active' : '' }}"
           aria-label="Ver recibos de costura">
          <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
          <span class="menu-label">Recibos de Costura</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="/recibos-reflectivo"
           class="menu-link {{ request()->is('recibos-reflectivo') ? 'active' : '' }}"
           aria-label="Ver recibos de reflectivo">
          <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
          <span class="menu-label">Recibos de Reflectivo</span>
        </a>
      </li>
      </ul>
    </div>

    <!-- Sección Insumos para revisor_entregas -->
    <div class="menu-section">
      <span class="menu-section-title">Insumos</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('insumos.*') ? 'active' : '' }}"
                aria-label="Gestionar Insumos">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Insumos</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('insumos.materiales.index') }}"
               class="menu-link {{ request()->routeIs('insumos.materiales.*') ? 'active' : '' }}"
               aria-label="Control de Insumos">
              <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
              <span class="menu-label">Control de Insumos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('insumos.plooter.index') }}"
               class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}"
               aria-label="Gestión Plooter">
              <span class="material-symbols-rounded" aria-hidden="true">description</span>
              <span class="menu-label">Gestión Plooter</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('visualizador_plooter'))
    <!-- VISUALIZADOR PLOOTER: Menú especializado para ver recibos de costura -->
    <div class="menu-section">
      <span class="menu-section-title">Gestionar Producción</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="/recibos-costura"
           class="menu-link {{ request()->is('recibos-costura') ? 'active' : '' }}"
           aria-label="Ver recibos de costura">
          <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
          <span class="menu-label">Recibos de Costura</span>
        </a>
      </li>
      </ul>
    </div>

    <!-- Sección Insumos para visualizador_plooter -->
    <div class="menu-section">
      <span class="menu-section-title">Insumos</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('insumos.*') ? 'active' : '' }}"
                aria-label="Gestionar Insumos">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Insumos</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('insumos.materiales.index') }}"
               class="menu-link {{ request()->routeIs('insumos.materiales.*') ? 'active' : '' }}"
               aria-label="Control de Insumos">
              <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
              <span class="menu-label">Control de Insumos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('insumos.plooter.index') }}"
               class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}"
               aria-label="Gestión Plooter">
              <span class="material-symbols-rounded" aria-hidden="true">description</span>
              <span class="menu-label">Gestión Plooter</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    @elseif(auth()->user()->hasRole('visualizador_talleres'))
    <!-- VISUALIZADOR TALLERES: menú especializado solo lectura para Talleres -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Talleres</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('talleres.index') }}"
           class="menu-link {{ request()->routeIs('talleres.*') ? 'active' : '' }}"
           aria-label="Ver talleres">
          <span class="material-symbols-rounded" aria-hidden="true">workshop</span>
          <span class="menu-label">Talleres</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('seguimiento-lavanderia.index') }}"
           class="menu-link {{ request()->routeIs('seguimiento-lavanderia.*') ? 'active' : '' }}"
           aria-label="Lavandería">
          <span class="material-symbols-rounded" aria-hidden="true">local_laundry_service</span>
          <span class="menu-label">Lavandería</span>
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
      @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('lider_produccion') || auth()->user()->hasRole('supervisor_produccion')))
      <li class="menu-item">
        <a href="{{ route('seguimiento-lavanderia.index') }}"
           class="menu-link {{ request()->routeIs('seguimiento-lavanderia.*') ? 'active' : '' }}"
           aria-label="Lavandería">
          <span class="material-symbols-rounded" aria-hidden="true">local_laundry_service</span>
          <span class="menu-label">Lavandería</span>
        </a>
      </li>
      @endif
      </ul>
    </div>

    <!-- Sección Gestión de Órdenes -->
    <div class="menu-section">
      <span class="menu-section-title">Gestionar Producción</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('registros.*', 'bodega.*') ? 'active' : '' }}"
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
          <li class="submenu-item">
            <a href="/recibos-costura"
               class="menu-link {{ request()->is('recibos-costura') ? 'active' : '' }}"
               aria-label="Ver recibos de costura">
              <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
              <span class="menu-label">Recibos de Costura</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="/recibos-reflectivo"
               class="menu-link {{ request()->is('recibos-reflectivo') ? 'active' : '' }}"
               aria-label="Ver recibos de reflectivo">
              <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
              <span class="menu-label">Recibos de Reflectivo</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('registros.recibos-bordado-estampado') }}"
               class="menu-link {{ request()->routeIs('registros.recibos-bordado-estampado') ? 'active' : '' }}"
               aria-label="Ver recibos de logo">
              <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
              <span class="menu-label">Recibos de Logo</span>
            </a>
          </li>
          @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('gestion-bodega'))
          <li class="submenu-item">
            <a href="/recibos-bodega"
               class="menu-link {{ request()->is('recibos-bodega') ? 'active' : '' }}"
               aria-label="Ver recibos de bodega">
              <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
              <span class="menu-label">Recibos Bodega</span>
            </a>
          </li>
          @endif
        </ul>
      </li>
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('cartera.*', 'supervisor-pedidos.*') || request()->is('insumos*') ? 'active' : '' }}"
                aria-label="Gestión de Pedidos">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Gestión Pedidos</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('cartera.pedidos') }}"
               class="menu-link {{ request()->routeIs('cartera.pedidos') ? 'active' : '' }}"
               aria-label="Pendiente Cartera">
              <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
              <span class="menu-label">Pendiente Cartera</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('supervisor-pedidos.index') }}"
               class="menu-link {{ request()->routeIs('supervisor-pedidos.index') ? 'active' : '' }}"
               aria-label="Pendiente Aprobador">
              <span class="material-symbols-rounded" aria-hidden="true">check_circle</span>
              <span class="menu-label">Pendiente Aprobador</span>
            </a>
          </li>
          <li class="submenu-item">
            <button class="menu-link submenu-toggle {{ request()->routeIs('insumos.*') ? 'active' : '' }}"
                    aria-label="Gestionar Insumos">
              <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
              <span class="menu-label">Insumos</span>
              <span class="material-symbols-rounded submenu-arrow">expand_more</span>
            </button>
            <ul class="submenu">
              <li class="submenu-item">
                <a href="{{ route('insumos.materiales.index') }}"
                   class="menu-link {{ request()->routeIs('insumos.materiales.*') ? 'active' : '' }}"
                   aria-label="Control de Insumos">
                  <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
                  <span class="menu-label">Control de Insumos</span>
                </a>
              </li>
              <li class="submenu-item">
                <a href="{{ route('insumos.plooter.index') }}"
                   class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}"
                   aria-label="Gestión Plooter">
                  <span class="material-symbols-rounded" aria-hidden="true">description</span>
                  <span class="menu-label">Gestión Plooter</span>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Reportes (Entregas, Tableros y Vistas) -->
    <div class="menu-section">
      <span class="menu-section-title">Reportes y Análisis</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('entrega.*', 'entregas-completas.*', 'tableros.*', 'balanceo.*', 'vistas.*') ? 'active' : '' }}"
                aria-label="Reportes, entregas y análisis">
          <span class="material-symbols-rounded" aria-hidden="true">analytics</span>
          <span class="menu-label">Reportes</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <!-- Generar Reporte (con submenu) -->
          <li class="submenu-item">
            <button class="menu-link submenu-toggle"
                    aria-label="Generar reportes">
              <span class="material-symbols-rounded" aria-hidden="true">file_download</span>
              <span class="menu-label">Generar Reporte</span>
              <span class="material-symbols-rounded submenu-arrow">expand_more</span>
            </button>
            <ul class="submenu">
              <li class="submenu-item">
                <button type="button" onclick="abrirModalGenerarReporte('costura')"
                   class="menu-link"
                   aria-label="Generar reporte de recibos de costura">
                  <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
                  <span class="menu-label">Recibos de Costura</span>
                </button>
              </li>
              <li class="submenu-item">
                <button type="button" onclick="abrirModalGenerarReporte('logo')"
                   class="menu-link"
                   aria-label="Generar reporte de recibos de logo">
                  <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
                  <span class="menu-label">Recibos de Logo</span>
                </button>
              </li>
              <li class="submenu-item">
                <button type="button" onclick="abrirModalGenerarReporte('reflectivo')"
                   class="menu-link"
                   aria-label="Generar reporte de recibos de reflectivo">
                  <span class="material-symbols-rounded" aria-hidden="true">receipt_long</span>
                  <span class="menu-label">Recibos de Reflectivo</span>
                </button>
              </li>
            </ul>
          </li>

          {{-- Entregas
          <li class="submenu-item">
            <button class="menu-link submenu-toggle {{ request()->routeIs('entrega.*', 'entregas-completas.*') ? 'active' : '' }}"
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
              <li class="submenu-item">
                <a href="{{ route('entregas-completas.index') }}"
                   class="menu-link {{ request()->routeIs('entregas-completas.*') ? 'active' : '' }}"
                   aria-label="Seguimiento completo de entregas">
                  <span class="material-symbols-rounded" aria-hidden="true">timeline</span>
                  <span class="menu-label">Seguimiento</span>
                </a>
              </li>
              @if(auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('admin'))
              <li class="submenu-item">
                <a href="{{ route('entregas-talleres.index') }}"
                   class="menu-link {{ request()->routeIs('entregas-talleres.*') ? 'active' : '' }}"
                   aria-label="Entregas de talleres">
                  <span class="material-symbols-rounded" aria-hidden="true">construction</span>
                  <span class="menu-label">Talleres</span>
                </a>
              </li>
              @endif

            </ul>
          </li>
          --}}


          <!-- Tableros -->
          <li class="submenu-item">
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

          {{-- Vistas
          <li class="submenu-item">
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
          --}}
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Gestión Despacho -->
    <div class="menu-section">
      <span class="menu-section-title">Gestión Despacho</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('despacho.*', 'gestion-bodega.*') ? 'active' : '' }}"
                aria-label="Gestión de Despacho">
          <span class="material-symbols-rounded" aria-hidden="true">warehouse</span>
          <span class="menu-label">Gestión Despacho</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('despacho.index') }}"
               class="menu-link {{ request()->routeIs('despacho.*') ? 'active' : '' }}"
               aria-label="Módulo de Despacho">
              <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
              <span class="menu-label">Despacho</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('gestion-bodega.pedidos') }}"
               class="menu-link {{ request()->routeIs('gestion-bodega.pedidos') ? 'active' : '' }}"
               aria-label="Gestión de pedidos - Bodega">
              <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
              <span class="menu-label">Pendientes Pedidos</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('gestion-bodega.pendientes-costura') }}"
               class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-costura') ? 'active' : '' }}"
               aria-label="Pendiente Costura">
              <span class="material-symbols-rounded" aria-hidden="true">checklist</span>
              <span class="menu-label">Pendiente Costura</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('gestion-bodega.pendientes-epp') }}"
               class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-epp') ? 'active' : '' }}"
               aria-label="Pendiente EPP">
              <span class="material-symbols-rounded" aria-hidden="true">shield</span>
              <span class="menu-label">Pendiente EPP</span>
            </a>
          </li>
        </ul>
      </li>
      </ul>
    </div>

    <!-- Sección Gestión de Talleres -->
    @if(auth()->user()->role && in_array(auth()->user()->role->name, ['admin', 'lider_produccion', 'supervisor_produccion']))
    <div class="menu-section">
      <span class="menu-section-title">Gestión de Talleres</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="{{ route('talleres.index') }}"
           class="menu-link {{ request()->routeIs('talleres.*') ? 'active' : '' }}"
           aria-label="Gestionar talleres">
          <span class="material-symbols-rounded" aria-hidden="true">workshop</span>
          <span class="menu-label">Talleres</span>
        </a>
      </li>
      </ul>
    </div>
    @endif

    <!-- Sección Módulos -->
    @if(auth()->user()->role && in_array(auth()->user()->role->name, ['admin', 'lider_produccion', 'supervisor_produccion']))
    <div class="menu-section">
      <span class="menu-section-title">Módulos</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <a href="/supervisor-asesores/pedidos"
           class="menu-link {{ request()->is('supervisor-asesores/pedidos') ? 'active' : '' }}"
           aria-label="Ver módulo de asesores">
          <span class="material-symbols-rounded" aria-hidden="true">people</span>
          <span class="menu-label">Asesores</span>
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
    @if(auth()->user()->role && in_array(auth()->user()->role->name, ['admin', 'lider_produccion', 'supervisor_produccion']))
    <div class="menu-section">
      <span class="menu-section-title">Administración</span>
      <ul class="menu-list" role="navigation">
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ request()->routeIs('epp.*', 'users.*', 'configuracion.*', 'admin.errores.*') ? 'active' : '' }}"
                aria-label="Administración del sistema">
          <span class="material-symbols-rounded" aria-hidden="true">admin_panel_settings</span>
          <span class="menu-label">Administración</span>
          <span class="material-symbols-rounded submenu-arrow">expand_more</span>
        </button>
        <ul class="submenu">
          <li class="submenu-item">
            <a href="{{ route('epp.inicio') }}"
               class="menu-link {{ request()->routeIs('epp.*') ? 'active' : '' }}"
               aria-label="Gestionar EPPs">
              <span class="material-symbols-rounded" aria-hidden="true">health_and_safety</span>
              <span class="menu-label">EPPs</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('users.index') }}"
               class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
               aria-label="Gestionar usuarios">
              <span class="material-symbols-rounded" aria-hidden="true">group</span>
              <span class="menu-label">Usuarios</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('configuracion.index') }}"
               class="menu-link {{ request()->routeIs('configuracion.*') ? 'active' : '' }}"
               aria-label="Configuración del sistema">
              <span class="material-symbols-rounded" aria-hidden="true">settings</span>
              <span class="menu-label">Configuración</span>
            </a>
          </li>
          <li class="submenu-item">
            <a href="{{ route('admin.errores.index') }}"
               class="menu-link {{ request()->routeIs('admin.errores.*') ? 'active' : '' }}"
               aria-label="Errores del sistema">
              <span class="material-symbols-rounded" aria-hidden="true">error_outline</span>
              <span class="menu-label">Errores del Sistema</span>
            </a>
          </li>
        </ul>
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
        <a href="{{ route('insumos.materiales.index') }}"
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
    @endif
    @endif
  </div>

  <!-- Footer -->
  <div class="sidebar-footer">
  </div>
</aside>

@if(auth()->user()->hasRole(['despacho', 'asesor', 'admin', 'supervisor_gerencia']))
<script>
  const shouldSkipPendientesPolling =
    window.location.pathname.includes('/recibos-bodega');

  if (!shouldSkipPendientesPolling) {
  // Cargar contador de pendientes
  function actualizarContadorPendientes() {
    fetch('/despacho/api/pendientes-todos?per_page=1')
      .then(response => {
        // Si no es 2xx, ignorar silenciosamente (usuario sin permisos)
        if (!response.ok) {
          return null;
        }
        return response.json();
      })
      .then(data => {
        if (!data) return; // Ignorar si hubo error de permisos
        
        const badge = document.getElementById('pendientes-badge');
        const total = data.pagination?.total || 0;
        if (badge && total > 0) {
          badge.textContent = total;
          badge.style.display = 'inline-block';
        } else if (badge) {
          badge.style.display = 'none';
        }
      })
      .catch(error => {
        // Silenciar errores de red/JSON
        console.debug('[Sidebar] Contador de pendientes no disponible:', error.message);
      });
  }

  // Actualizar al cargar la página
  document.addEventListener('DOMContentLoaded', actualizarContadorPendientes);
  
  // Actualizar cada 30 segundos
  setInterval(actualizarContadorPendientes, 30000);
  }
</script>
@endif
