@php
    $devueltoAsesorInicial = \App\Models\PedidoProduccion::query()
        ->where('estado', 'DEVUELTO_A_ASESORA')
        ->whereNull('ocultado_en')
        ->whereNotNull('numero_pedido')
        ->where('numero_pedido', '!=', 0)
        ->count();
@endphp

<!-- Sidebar Supervisor de Pedidos -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('supervisor-pedidos.index') }}" class="logo-wrapper" aria-label="Ir a Supervisión de Pedidos">
            <img src="{{ asset('images/logo2.png') }}"
                 alt="Logo"
                 class="header-logo"
                 data-logo-light="{{ asset('images/logo2.png') }}"
                 data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
    </div>

    <div class="sidebar-content">
        <!-- Sección de Aprobación -->
        <div class="menu-section">
            <span class="menu-section-title">Estado de Aprobación</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.index') && request()->query('estado') !== 'Anulada' && request()->query('estado') !== 'DEVUELTO_A_ASESORA' && request()->query('aprobacion_cartera') !== 'no_aprobado' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">pending_actions</span>
                        <span class="menu-label">Pedidos</span>
                        <span class="badge-alert" id="ordenesPendientesCount" style="display: none;">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['aprobacion_cartera' => 'no_aprobado']) }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.index') && request()->query('aprobacion_cartera') === 'no_aprobado' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">account_balance_wallet</span>
                        <span class="menu-label">Pendiente Cartera</span>
                        <span class="badge-alert" id="pendienteCarteraCountMenu" style="display: inline-flex;">0</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'DEVUELTO_A_ASESORA']) }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.index') && request()->query('estado') === 'DEVUELTO_A_ASESORA' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">assignment_return</span>
                        <span class="menu-label">Devuelto Asesor</span>
                        <span class="badge-alert" id="devueltoAsesorCountMenu" style="display: inline-flex;">{{ $devueltoAsesorInicial }}</span>
                    </a>
                </li>
                @php
                    $pendientesActivo = request()->routeIs(
                        'supervisor-pedidos.pendientes-bordado-estampado',
                        'supervisor-pedidos.pendientes-costura',
                        'supervisor-pedidos.pendientes-reflectivo',
                        'supervisor-pedidos.pendientes-entrega',
                        'supervisor-pedidos.pendientes-control-calidad',
                        'gestion-bodega.pendientes-costura'
                    );
                @endphp
                <li class="menu-item">
                    <button class="menu-link submenu-toggle {{ $pendientesActivo ? 'active' : '' }}" type="button" style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">playlist_add_check</span>
                        <span class="menu-label">Pendientes</span>
                        <span class="badge-alert" id="controlCalidadPendientesCountMenu" data-control-calidad-badge style="display: inline-flex;">0</span>
                        <span class="material-symbols-rounded submenu-arrow">expand_more</span>
                    </button>
                    <ul class="submenu {{ $pendientesActivo ? 'open' : '' }}">
                        <li class="submenu-item">
                            <a href="{{ route('supervisor-pedidos.pendientes-bordado-estampado') }}"
                               class="menu-link {{ request()->routeIs('supervisor-pedidos.pendientes-bordado-estampado') ? 'active' : '' }}"
                               style="display:flex;align-items:center;gap:0.5rem;">
                                <span class="material-symbols-rounded">brush</span>
                                <span class="menu-label">Pendientes Logo</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('supervisor-pedidos.pendientes-costura') }}"
                               class="menu-link {{ request()->routeIs('supervisor-pedidos.pendientes-costura') ? 'active' : '' }}"
                               style="display:flex;align-items:center;gap:0.5rem;">
                                <span class="material-symbols-rounded">content_cut</span>
                                <span class="menu-label">Pendiente Costura</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('supervisor-pedidos.pendientes-reflectivo') }}"
                               class="menu-link {{ request()->routeIs('supervisor-pedidos.pendientes-reflectivo') ? 'active' : '' }}"
                               style="display:flex;align-items:center;gap:0.5rem;">
                                <span class="material-symbols-rounded">flare</span>
                                <span class="menu-label">Pendiente Reflectivo</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('supervisor-pedidos.pendientes-entrega') }}"
                               class="menu-link {{ request()->routeIs('supervisor-pedidos.pendientes-entrega') ? 'active' : '' }}"
                               style="display:flex;align-items:center;gap:0.5rem;">
                                <span class="material-symbols-rounded">local_shipping</span>
                                <span class="menu-label">Pendiente Entrega</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('supervisor-pedidos.pendientes-control-calidad') }}"
                               class="menu-link {{ request()->routeIs('supervisor-pedidos.pendientes-control-calidad') ? 'active' : '' }}"
                               style="display:flex;align-items:center;gap:0.5rem;">
                                <span class="material-symbols-rounded">fact_check</span>
                                <span class="menu-label">Pendiente C.C</span>
                                <span class="badge-alert" id="controlCalidadPendientesCount" data-control-calidad-badge>0</span>
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('gestion-bodega.pendientes-costura') }}"
                               class="menu-link {{ request()->routeIs('gestion-bodega.pendientes-costura') ? 'active' : '' }}"
                               style="display:flex;align-items:center;gap:0.5rem;">
                                <i class="fas fa-box"></i>
                                <span class="menu-label">Pendiente Bodega</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.entregas-recibidas') }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.entregas-recibidas') ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">inventory_2</span>
                        <span class="menu-label">Entregas/Recibidas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['estado' => 'Anulada']) }}"
                       class="menu-link {{ request()->query('estado') === 'Anulada' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">cancel</span>
                        <span class="menu-label">Pedidos Anulados</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.index', ['mostrar' => 'ocultos']) }}"
                       class="menu-link {{ request()->query('mostrar') === 'ocultos' ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">visibility_off</span>
                        <span class="menu-label">Pedidos Ocultos</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-pedidos.estadisticas-asesoras') }}"
                       class="menu-link {{ request()->routeIs('supervisor-pedidos.estadisticas-asesoras') ? 'active' : '' }}"
                       style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="material-symbols-rounded">insights</span>
                        <span class="menu-label">Stats Asesoras</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
