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
    <img src="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png"
         alt="Logo Mundo Industrial"
         class="header-logo" />
    <button class="sidebar-toggle" aria-label="Colapsar menú">
      <span class="material-symbols-rounded">chevron_left</span>
    </button>
  </div>

  <div class="sidebar-content">
    <!-- Formulario de búsqueda -->
    <form action="#" class="search-form" role="search">
      <span class="material-symbols-rounded" aria-hidden="true">search</span>
      <input type="search"
             placeholder="Buscar..."
             aria-label="Buscar en el menú"
             required />
    </form>

    <!-- Lista del menú principal -->
    <ul class="menu-list" role="navigation" aria-label="Menú principal">
      <li class="menu-item">
        <a href="{{ route('dashboard') }}"
           class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="Ir al Dashboard">
          <span class="material-symbols-rounded" aria-hidden="true">dashboard</span>
          <span class="menu-label">Dashboard</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('registros.index') }}"
           class="menu-link {{ request()->routeIs('registros.index') ? 'active' : '' }}"
           aria-label="Ver registro de órdenes">
          <span class="material-symbols-rounded" aria-hidden="true">assignment</span>
          <span class="menu-label">Ordenes-Pedidos</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('bodega.index') }}"
           class="menu-link {{ request()->routeIs('bodega.index') ? 'active' : '' }}"
           aria-label="Ver órdenes de bodega">
          <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
          <span class="menu-label">Ordenes-Bodega</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('entrega.index', ['tipo' => 'pedido']) }}"
           class="menu-link {{ request()->routeIs('entrega.index') && request()->route('tipo') === 'pedido' ? 'active' : '' }}"
           aria-label="Ver entrega pedido">
          <span class="material-symbols-rounded" aria-hidden="true">local_shipping</span>
          <span class="menu-label">Entrega-Pedidos</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('entrega.index', ['tipo' => 'bodega']) }}"
           class="menu-link {{ request()->routeIs('entrega.index') && request()->route('tipo') === 'bodega' ? 'active' : '' }}"
           aria-label="Ver entrega bodega">
          <span class="material-symbols-rounded" aria-hidden="true">warehouse</span>
          <span class="menu-label">Entrega Bodega</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="{{ route('tableros.index') }}"
           class="menu-link {{ request()->routeIs('tableros.index') ? 'active' : '' }}"
           aria-label="Ver tableros">
          <span class="material-symbols-rounded" aria-hidden="true">table_chart</span>
          <span class="menu-label">Tableros</span>
        </a>
      </li>

      @if(auth()->user()->role === 'admin')
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
      @endif

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


</aside>
