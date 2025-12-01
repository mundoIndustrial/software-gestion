<!-- Overlay para móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Navegación superior para móviles -->
<nav class="site-nav">
  <button class="sidebar-toggle" aria-label="Abrir menú">
    <span class="material-symbols-rounded">menu</span>
  </button>
</nav>

<!-- Sidebar principal para Supervisor de Planta -->
<aside class="sidebar collapsed" id="sidebar">
  <div class="sidebar-header">
    <img src="{{ asset('images/logo2.png') }}"
         alt="Logo Mundo Industrial"
         class="header-logo"
         data-logo-light="{{ asset('images/logo2.png') }}"
         data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
    <button class="sidebar-toggle" aria-label="Colapsar menú">
      <span class="material-symbols-rounded">chevron_left</span>
    </button>
  </div>

  <div class="sidebar-content">
    <!-- Lista del menú principal -->
    <ul class="menu-list" role="navigation" aria-label="Menú principal">
      <!-- Dashboard -->
      <li class="menu-item">
        <a href="{{ route('dashboard') }}"
           class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="Ir al Dashboard">
          <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
          <span class="menu-label">Dashboard</span>
        </a>
      </li>

      <!-- Gestión de Órdenes -->
      <li class="menu-item">
        <button class="menu-link submenu-toggle {{ (request()->routeIs('registros.index') || request()->routeIs('bodega.index')) ? 'active' : '' }}"
                aria-label="Ver órdenes">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Gestionar Órdenes</span>
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

      <!-- Entregas -->
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

      <!-- Tableros -->
      <li class="menu-item">
        <a href="{{ route('tableros.index') }}"
           class="menu-link {{ request()->routeIs('tableros.index') ? 'active' : '' }}"
           aria-label="Ver tableros">
          <span class="material-symbols-rounded" aria-hidden="true">table_chart</span>
          <span class="menu-label">Tableros</span>
        </a>
      </li>

      <!-- Balanceo -->
      <li class="menu-item">
        <a href="{{ route('balanceo.index') }}"
           class="menu-link {{ request()->routeIs('balanceo.index') ? 'active' : '' }}"
           aria-label="Ver balanceo">
          <span class="material-symbols-rounded" aria-hidden="true">schedule</span>
          <span class="menu-label">Balanceo</span>
        </a>
      </li>

      <!-- Vistas -->
      <li class="menu-item">
        <button class="menu-link submenu-toggle"
                aria-label="Ver vistas">
          <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
          <span class="menu-label">Vistas</span>
          <span class="material-symbols-rounded submenu-arrow" aria-hidden="true">expand_more</span>
        </button>
        <ul class="submenu">
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
          <li class="submenu-item">
            <a href="{{ route('vistas.control-calidad') }}"
               class="menu-link"
               aria-label="Ver control de calidad">
              <span class="menu-label">Control de Calidad</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- INSUMOS - Acceso completo para Supervisor de Planta -->
      <li class="menu-item">
        <a href="{{ route('insumos.materiales.index') }}"
           class="menu-link {{ request()->routeIs('insumos.materiales.*') ? 'active' : '' }}"
           aria-label="Gestionar materiales de insumos">
          <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
          <span class="menu-label">Insumos</span>
        </a>
      </li>

      <!-- Usuarios - Acceso completo para Supervisor de Planta -->
      <li class="menu-item">
        <a href="{{ route('users.index') }}"
           class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
           aria-label="Gestionar usuarios">
          <span class="material-symbols-rounded" aria-hidden="true">group</span>
          <span class="menu-label">Usuarios</span>
        </a>
      </li>

      <!-- Configuración - Acceso completo para Supervisor de Planta -->
      <li class="menu-item">
        <a href="{{ route('configuracion.index') }}"
           class="menu-link {{ request()->routeIs('configuracion.*') ? 'active' : '' }}"
           aria-label="Configuración del sistema">
          <span class="material-symbols-rounded" aria-hidden="true">settings</span>
          <span class="menu-label">Configuración</span>
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
