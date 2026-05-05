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
          <span class="badge badge-pending" id="pendientes-badge" style="display: none; margin-left: 8px; background-color: #ef4444; color: white; padding: 2px 6px; border-radius: 12px; font-size: 12px; font-weight: bold; min-width: 20px; text-align: center;">0</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="{{ route('despacho.historial-pendientes') }}"
           class="menu-link {{ request()->routeIs('despacho.historial-pendientes') ? 'active' : '' }}"
           aria-label="Historial Pendientes">
          <span class="material-symbols-rounded" aria-hidden="true">history</span>
          <span class="menu-label">Historial Pendientes</span>
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

    <!-- FILTRO POR ASESORA (NUEVO) -->
    @if(isset($sidebarAsesores) && $sidebarAsesores->count() > 0)
    <div class="menu-section mt-4" style="border-top: 1px solid #f1f5f9; padding-top: 16px;">
      <span class="menu-section-title">Filtrar por Asesora</span>
      <ul class="menu-list">
        @foreach($sidebarAsesores as $asesora)
        <li class="menu-item">
          <a href="{{ route('despacho.index', array_merge(request()->all(), ['asesor_id' => $asesora->id, 'page' => 1])) }}"
             class="menu-link {{ $currentAsesorId == $asesora->id ? 'active' : '' }}"
             style="padding: 8px 12px; font-size: 0.85rem;">
            <span class="material-symbols-rounded" style="font-size: 1.2rem;">person</span>
            <span class="menu-label" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">{{ $asesora->name }}</span>
            <span class="badge" style="background: #ef4444; color: white; margin-left: auto; border-radius: 9999px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; flex-shrink: 0;">{{ $asesora->pedidos_asesora_count }}</span>
          </a>
        </li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>
</aside>

@if(auth()->user()->hasRole(['despacho', 'asesor', 'admin', 'supervisor_gerencia']))
<script>
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
        console.debug('[Sidebar-Despacho] Contador de pendientes no disponible:', error.message);
      });
  }

  // Actualizar al cargar la página
  document.addEventListener('DOMContentLoaded', actualizarContadorPendientes);
  
  // Actualizar cada 30 segundos
  setInterval(actualizarContadorPendientes, 30000);
</script>
@endif
