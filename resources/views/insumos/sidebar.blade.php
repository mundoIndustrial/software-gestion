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
  @php
    $conteoPendientesInsumos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
      ->where('tipo_recibo', 'COSTURA')
      ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) = 'INSUMOS'")
      ->distinct('pedido_produccion_id')
      ->count('pedido_produccion_id');

    $conteoReflectivoInsumos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
      ->where('tipo_recibo', 'REFLECTIVO')
      ->whereRaw("UPPER(TRIM(COALESCE(area, ''))) = 'INSUMOS'")
      ->count();
  @endphp

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
      @if(auth()->user()->hasRole('visualizador_plooter'))
        <!-- VISUALIZADOR PLOOTER: Solo puede ver Gestion Plooter -->
        <li class="menu-item">
          <a href="{{ route('insumos.plooter.index') }}"
             class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}"
             aria-label="Gestion Plooter">
            <span class="material-symbols-rounded" aria-hidden="true">description</span>
            <span class="menu-label">Gestion Plooter</span>
          </a>
        </li>
      @else
        <!-- Control de Insumos -->
        <li class="menu-item">
          <a href="{{ route('insumos.materiales.index') }}"
             class="menu-link {{ request()->routeIs('insumos.materiales.*') && !request()->routeIs('insumos.materiales.reflectivo') ? 'active' : '' }}"
             aria-label="Control de Insumos"
             onclick="window.location.href = this.href; return true;">
            <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
            <span class="menu-label">Control de Insumos</span>
            @if($conteoPendientesInsumos > 0)
              <span style="margin-left: auto; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: #ef4444; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1;">
                {{ $conteoPendientesInsumos }}
              </span>
            @endif
          </a>
        </li>

        <!-- Gestion Reflectivo -->
        <li class="menu-item">
          <a href="{{ route('insumos.materiales.reflectivo') }}"
             class="menu-link {{ request()->routeIs('insumos.materiales.reflectivo') ? 'active' : '' }}"
             aria-label="Gestion Reflectivo"
             onclick="window.location.href = this.href; return true;">
            <span class="material-symbols-rounded" aria-hidden="true">visibility</span>
            <span class="menu-label">Gestion Reflectivo</span>
            @if($conteoReflectivoInsumos > 0)
              <span style="margin-left: auto; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: #ef4444; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1;">
                {{ $conteoReflectivoInsumos }}
              </span>
            @endif
          </a>
        </li>

        <!-- Gestion Plooter -->
        <li class="menu-item">
          <a href="{{ route('insumos.plooter.index') }}"
             class="menu-link {{ request()->routeIs('insumos.plooter.*') ? 'active' : '' }}"
             aria-label="Gestion Plooter">
            <span class="material-symbols-rounded" aria-hidden="true">description</span>
            <span class="menu-label">Gestion Plooter</span>
          </a>
        </li>

        <!-- Inventario de Telas -->
        <li class="menu-item">
          <a href="{{ route('inventario-telas.index') }}"
             class="menu-link {{ request()->routeIs('inventario-telas.*') || request()->is('inventario-telas*') ? 'active' : '' }}"
             aria-label="Inventario de Telas">
            <span class="material-symbols-rounded" aria-hidden="true">checkroom</span>
            <span class="menu-label">Inventario de Telas</span>
          </a>
        </li>

        <!-- Volver al Dashboard Principal - Solo para supervisor_planta y admin -->
        @if(auth()->user()->role && in_array(auth()->user()->role->name, ['supervisor_planta', 'admin']))
        <li class="menu-item">
          <a href="{{ route('dashboard') }}"
             class="menu-link"
             aria-label="Volver al dashboard principal">
            <span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
            <span class="menu-label">Volver</span>
          </a>
        </li>
        @endif
      @endif
    </ul>
  </div>

  <!-- Footer con botón de salir y toggle de tema -->
  <div class="sidebar-footer">
    <!-- Botón de Cerrar Sesión -->
    <form action="{{ route('logout') }}" method="POST" style="width: 100%; margin: 0;">
      @csrf
      <button type="submit"
              class="logout-btn"
              aria-label="Cerrar sesión">
        <i class="fas fa-sign-out-alt"></i>
        <span class="menu-label">Cerrar Sesión</span>
      </button>
    </form>

  </div>
</aside>

<script>
// Forzar navegación en enlaces del sidebar - ejecutar inmediatamente
(function() {
    function forceNavigation() {
        const sidebarLinks = document.querySelectorAll('.sidebar .menu-link[href]');
        console.log('[Sidebar] Configurando', sidebarLinks.length, 'enlaces');
        
        sidebarLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                // Remover listeners previos clonando el elemento
                const newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);
                
                // Agregar nuevo listener con máxima prioridad (capture phase)
                newLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('[Sidebar] Navegando a:', href);
                    window.location.href = href;
                }, true);
            }
        });
        console.log('[Sidebar] Enlaces configurados para navegación forzada');
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceNavigation);
    } else {
        forceNavigation();
    }
    
    // Reintentar después de que otros scripts se carguen
    setTimeout(forceNavigation, 1000);
})();
</script>
