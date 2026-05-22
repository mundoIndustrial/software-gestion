@extends('operario.layout')

@section('title', 'Mis Órdenes')
@section('page-title')
    <span id="dashboardPageTitle" style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded" id="dashboardPageTitleIcon">checkroom</span>
        <span id="dashboardPageTitleText">
            {{ $dashboardPageTitleText ?? 'RECIBOS DE COSTURA' }}
        </span>
    </span>
@endsection

@section('content')
    <div class="operario-dashboard is-modern-dashboard {{ $esVistaCostura ? 'is-vista-costura' : '' }}"
         data-user-id="{{ Auth::id() }}"
         data-user-role="{{ $rolDashboardActual }}"
         data-user-name="{{ Auth::user()->name ?? '' }}">
        <!-- Busqueda -->
        <div class="search-section">
            <div class="search-controls">
                <div class="search-field">
                    <input type="text" id="searchInput" class="search-box" placeholder="Buscar por Consecutivo, Prenda o Cliente..." value="{{ $busquedaActual }}">
                </div>
                <div class="search-actions">
                    <button type="button" id="clearFilterBtn" class="clear-search-text-btn" style="display: none;" onclick="window.__dashboardClearHandler?.(); return false;">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
        <div id="searchLoadingState" class="search-loading-state" style="display: none;">
            <span class="material-symbols-rounded">progress_activity</span>
            <span>Cargando resultados...</span>
        </div>

        <!-- Mis Prendas Section -->
        <div class="ordenes-section">

            <!-- Filtros por tipo de recibo para costura-reflectivo, lider-reflectivo y vista-costura -->
            @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura'))
                <div class="filtros-badges filtros-badges-principales">
                    @if(auth()->user()->hasRole('vista-costura'))
                        <button class="badge-filtro {{ $filtroReciboActual === 'costura' ? 'badge-filtro-active' : '' }}" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                            <span class="material-symbols-rounded">checkroom</span>
                            Costura
                        </button>
                        <button class="badge-filtro {{ $filtroReciboActual === 'reflectivo' ? 'badge-filtro-active' : '' }}" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                            <span class="material-symbols-rounded">auto_awesome</span>
                            Reflectivo
                        </button>
                        <button class="badge-filtro {{ $filtroReciboActual === 'bodega' ? 'badge-filtro-active' : '' }}" data-filtro="bodega" onclick="filtrarPrendasPorRecibo('bodega')">
                            <span class="material-symbols-rounded">inventory_2</span>
                            Bodega
                        </button>
                    @else
                        <!-- Para costura-reflectivo y lider-reflectivo: mostrar ambos tags -->
                        <button class="badge-filtro {{ $filtroReciboActual === 'costura' ? 'badge-filtro-active' : '' }}" data-filtro="costura" onclick="filtrarPrendasPorRecibo('costura')">
                            <span class="material-symbols-rounded">checkroom</span>
                            Costura
                        </button>
                        <button class="badge-filtro {{ $filtroReciboActual === 'reflectivo' ? 'badge-filtro-active' : '' }}" data-filtro="reflectivo" onclick="filtrarPrendasPorRecibo('reflectivo')">
                            <span class="material-symbols-rounded">auto_awesome</span>
                            Reflectivo
                        </button>
                        <button class="badge-filtro {{ $filtroReciboActual === 'bodega' ? 'badge-filtro-active' : '' }}" data-filtro="bodega" onclick="filtrarPrendasPorRecibo('bodega')">
                            <span class="material-symbols-rounded">inventory_2</span>
                            Bodega
                        </button>
                    @endif
                </div>
                @if(auth()->user()->hasRole('vista-costura') && in_array($filtroReciboActual, ['costura', 'bodega'], true))
                    <div class="filtros-badges filtros-badges-secundarios" id="vistaCosturaEncargadoFilters">
                        <button class="badge-filtro {{ $filtroEncargadoActual === 'todos' ? 'badge-filtro-active' : '' }}" data-encargado-filtro="todos" onclick="filtrarVistaCosturaEncargados('todos')">
                            <span class="material-symbols-rounded">apps</span>
                            Todos
                        </button>
                        <button class="badge-filtro {{ $filtroEncargadoActual === 'sin-encargado' ? 'badge-filtro-active' : '' }}" data-encargado-filtro="sin-encargado" onclick="filtrarVistaCosturaEncargados('sin-encargado')">
                            <span class="material-symbols-rounded">person_off</span>
                            Sin encargado
                            <span class="badge-filtro-contador" id="badgeSinEncargadoCount" data-total-global="{{ $filtroReciboActual === 'bodega' ? ($vistaCosturaBodegaSinEncargadoCount ?? 0) : ($vistaCosturaSinEncargadoCount ?? 0) }}">{{ $filtroReciboActual === 'bodega' ? ($vistaCosturaBodegaSinEncargadoCount ?? 0) : ($vistaCosturaSinEncargadoCount ?? 0) }}</span>
                        </button>
                        <button class="badge-filtro {{ $filtroEncargadoActual === 'control-calidad' ? 'badge-filtro-active' : '' }}" data-encargado-filtro="control-calidad" onclick="filtrarControlCalidad()">
                            <span class="material-symbols-rounded">check_circle</span>
                            Control de calidad
                            <span class="badge-filtro-contador" id="badgeControlCalidadCount" data-contador-costura="{{ $conteoControlCalidadCostura ?? 0 }}" data-contador-reflectivo="{{ $conteoControlCalidadReflectivo ?? 0 }}" data-contador-bodega="{{ $conteoControlCalidadBodega ?? 0 }}" style="display: none;">{{ $filtroReciboActual === 'bodega' ? ($conteoControlCalidadBodega ?? 0) : ($conteoControlCalidadCostura ?? 0) }}</span>
                        </button>
                    </div>
                @endif
            @endif

            @if(auth()->user()->hasRole('administrador-costura'))
                <div class="filtros-badges filtros-badges-principales">
                    <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'costura' ? 'badge-filtro-active' : '' }}" data-admin-tab="costura">
                        <span class="material-symbols-rounded">checkroom</span>
                        Costura
                    </button>
                    <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'sobremedida' ? 'badge-filtro-active' : '' }}" data-admin-tab="sobremedida">
                        <span class="material-symbols-rounded">straighten</span>
                        Sobremedida
                    </button>
                    <button type="button" class="badge-filtro {{ ($tab ?? 'costura') === 'bodega' ? 'badge-filtro-active' : '' }}" data-admin-tab="bodega">
                        <span class="material-symbols-rounded">inventory_2</span>
                        Bodega
                    </button>
                </div>
            @endif

            @if(auth()->user()->hasRole('cortador'))
                <div class="filtros-badges filtros-badges-principales">
                    <button type="button" class="badge-filtro {{ ($tab ?? 'pendientes') === 'pendientes' ? 'badge-filtro-active' : '' }}" onclick="window.location.href='{{ route('operario.dashboard', ['tab' => 'pendientes']) }}'">
                        <span class="material-symbols-rounded">pending_actions</span>
                        Pendiente pedidos
                        <span class="badge-filtro-contador" id="contadorPendientes">{{ $pendientesPedidosCount ?? $prendasConRecibos->count() }}</span>
                    </button>
                    <button type="button" class="badge-filtro {{ ($tab ?? 'pendientes') === 'completados' ? 'badge-filtro-active' : '' }}" onclick="window.location.href='{{ route('operario.dashboard', ['tab' => 'completados']) }}'">
                        <span class="material-symbols-rounded">task_alt</span>
                        Completados
                        <span class="badge-filtro-contador" id="contadorCompletados">{{ $recibosCompletadosCount ?? $recibosCompletados->count() }}</span>
                    </button>
                    <button type="button" class="badge-filtro {{ ($tab ?? 'pendientes') === 'pendiente-bodega' ? 'badge-filtro-active' : '' }}" onclick="window.location.href='{{ route('operario.dashboard', ['tab' => 'pendiente-bodega']) }}'">
                        <span class="material-symbols-rounded">inventory_2</span>
                        Pendiente Bodega
                        <span class="badge-filtro-contador" id="contadorPendienteBodega">{{ $recibosBodegaPendientesCount ?? 0 }}</span>
                    </button>
                    <button type="button" class="badge-filtro {{ ($tab ?? 'pendientes') === 'completado-bodega' ? 'badge-filtro-active' : '' }}" onclick="window.location.href='{{ route('operario.dashboard', ['tab' => 'completado-bodega']) }}'">
                        <span class="material-symbols-rounded">inventory</span>
                        Completado Bodega
                        <span class="badge-filtro-contador" id="contadorCompletadoBodega">{{ $recibosBodegaCompletadosCount ?? $recibosBodegaCompletados->count() }}</span>
                    </button>
                </div>
            @endif


            <div class="ordenes-list" id="ordenesList">
                @if(auth()->user()->hasRole('cortador') && in_array(($tab ?? 'pendientes'), ['completados', 'completado-bodega'], true))
                    @php
                        $coleccionMostrar = ($tab === 'completado-bodega') ? $recibosBodegaCompletados : $recibosCompletados;
                    @endphp
                    @if(isset($coleccionMostrar) && $coleccionMostrar->count() > 0)
                        @foreach($coleccionMostrar as $recibo)
                            @php
                                $fechaCompletado = \Carbon\Carbon::parse($recibo['fecha_completado'])->format('d/m/Y H:i');
                            @endphp
                            <div class="orden-card-simple card-completado-area" 
                                 data-numero="{{ $recibo['numero_pedido'] }}" 
                                 data-prenda="{{ strtolower($recibo['nombre_prenda']) }}"
                                 data-cliente="{{ strtolower($recibo['cliente']) }}"
                                 data-tipo-recibo="{{ strtolower($recibo['tipo_recibo'] ?? 'costura') }}"
                                 data-sin-encargado-costura="0"
                                 data-sin-encargado-reflectivo="0"
                                 data-completado-costura="1"
                                 data-completado-reflectivo="1"
                                 data-recibos-corte-asignados="1"
                                 data-numero-recibo="{{ $recibo['consecutivo_actual'] }}">

                                <div class="orden-body recibo-completado-area">
                                    <div class="orden-left">
                                        <div class="orden-top">
                                            <div class="orden-numero-section">
                                                <h4 class="orden-numero">#{{ $recibo['consecutivo_actual'] }}</h4>
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            </div>
                                        </div>

                                        <div class="orden-cliente">
                                            <p class="cliente-label">{{ strtoupper((string) ($recibo['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA' ? 'SERVICIO' : 'CLIENTE' }}</p>
                                            <p class="cliente-name">{{ $recibo['cliente'] }}</p>
                                        </div>

                                        <div class="orden-prendas">
                                            <p class="prendas-label">
                                                <strong>{{ $recibo['nombre_prenda'] }}</strong>
                                                @if($recibo['descripcion'])
                                                    <br>{!! nl2br(e($recibo['descripcion'])) !!}
                                                @endif
                                            </p>        
                                        </div>

                                        <div style="margin-top: 1rem; font-size: 0.85rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">event_available</span>
                                            Completado el {{ $fechaCompletado }}
                                        </div>

                                        <!-- Boton Ver Recibo (debajo del estado para mobile) -->
                                        <div class="mobile-ver-recibo-section">
                                            @component('components.botones.ver-recibo', [
                                                'numeroPedido' => $recibo['numero_pedido'],
                                                'prendaId' => $recibo['prenda_id'],
                                                'nombrePrenda' => addslashes((string)$recibo['nombre_prenda']),
                                                'tipoRecibo' => $recibo['tipo_recibo'],
                                                'idParcial' => $recibo['id_parcial'] ?: null,
                                                'consecutivo' => $recibo['consecutivo_actual'],
                                                'consecutivoParcial' => $recibo['consecutivo_parcial'] ?: null,
                                                'reciboId' => $recibo['recibo_id'] ?? null,
                                                'clase' => 'mobile-under-state',
                                            ])@endcomponent
                                        </div>

                                        <div class="orden-buttons" style="margin-top: 1rem;">
                                            @component('components.botones.ver-recibo', [
                                                'numeroPedido' => $recibo['numero_pedido'],
                                                'prendaId' => $recibo['prenda_id'],
                                                'nombrePrenda' => addslashes((string)$recibo['nombre_prenda']),
                                                'tipoRecibo' => $recibo['tipo_recibo'],
                                                'idParcial' => $recibo['id_parcial'] ?: null,
                                                'consecutivo' => $recibo['consecutivo_actual'],
                                                'consecutivoParcial' => $recibo['consecutivo_parcial'] ?: null,
                                                'reciboId' => $recibo['recibo_id'] ?? null,
                                            ])@endcomponent
                                        </div>
                                    </div>

                                    <div class="orden-right">
                                        <div class="orden-right-center">
                                            <a href="#" class="action-arrow" onclick="abrirDetallesRecibos('{{ $recibo['numero_pedido'] }}', {{ $recibo['prenda_id'] ?? 'null' }}, '{{ $recibo['nombre_prenda'] }}', '{{ $recibo['tipo_recibo'] }}', {{ $recibo['id_parcial'] ?: 'null' }}, '{{ $recibo['consecutivo_actual'] }}', {{ $recibo['recibo_id'] ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">arrow_forward</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <span class="material-symbols-rounded">history</span>
                            <p>No tienes recibos completados aun.</p>
                        </div>
                    @endif
                @elseif($modoControlCalidadVistaCostura)
                    @if($prendasRenderizadas->count() > 0)
                        @foreach($prendasRenderizadas as $prenda)
                            @include('operario.partials.dashboard.control-calidad-card', ['prenda' => $prenda])
                        @endforeach
                    @else
                        <div class="empty-state">
                            <span class="material-symbols-rounded">inbox</span>
                            @if(!empty($busquedaActual) && $resultadosBusquedaFueraDeArea->count() > 0)
                                <p>Encontro coincidencias en otras Areas y te las muestro arriba.</p>
                            @elseif(!empty($busquedaActual) && !empty($mensajeBusquedaDashboard))
                                <p>{{ $mensajeBusquedaDashboard }}</p>
                            @elseif(!empty($busquedaActual))
                                <p>No encontro coincidencias para "{{ $busquedaActual }}" en este tap.</p>
                            @elseif($filtroReciboActual === 'bodega')
                                <p>No hay recibos de bodega para mostrar.</p>
                            @else
                                <p>No hay recibos en Control de Calidad para este tipo</p>
                            @endif
                        </div>
                    @endif
                @else
                    @if($prendasRenderizadas->count() > 0)
                        @foreach($prendasRenderizadas as $prenda)
                            @if(auth()->user()->hasRole('vista-costura') && $filtroReciboActual === 'bodega')
                                @php
                                    $bodega = $prenda['bodega_view'] ?? [];
                                @endphp
                                @include('operario.partials.dashboard.bodega-card', ['prenda' => $prenda, 'bodega' => $bodega])
                                @continue
                            @endif

                            @include('operario.partials.dashboard.normal-card-block', [
                                'prenda' => $prenda,
                                'busquedaActual' => $busquedaActual,
                                'filtroReciboActual' => $filtroReciboActual,
                                'nombresCosturaReflectivo' => $nombresCosturaReflectivo,
                            ])
                        @endforeach
                    @else
                        @if(!empty($busquedaActual) && $resultadosBusquedaFueraDeArea->count() > 0)
                            <div class="search-outside-results">
                                <div class="search-outside-results-header">
                                    <span class="material-symbols-rounded">travel_explore</span>
                                    <span>Coincidencias en otras areas</span>
                                </div>
                                <div class="search-outside-results-grid">
                                    @foreach($resultadosBusquedaFueraDeArea as $resultado)
                                        @php
                                            $numeroReciboExterno = $resultado['consecutivo_actual'] ?? $resultado['consecutivo_inicial'] ?? '';
                                            $areaExterna = $resultado['area_label'] ?? strtoupper((string) ($resultado['area'] ?? 'OTRA AREA'));
                                            $estadoExterno = $resultado['estado_label'] ?? strtoupper((string) ($resultado['estado'] ?? 'SIN ESTADO'));
                                        @endphp
                                        <div class="orden-card-simple card-busqueda-fuera-area"
                                             data-numero="{{ $resultado['numero_pedido'] }}"
                                             data-prenda="{{ strtolower((string) $resultado['nombre_prenda']) }}"
                                             data-prenda-id="{{ $resultado['prenda_id'] }}"
                                             data-cliente="{{ strtolower((string) $resultado['cliente']) }}"
                                             data-tipo-recibo="{{ strtolower((string) $resultado['tipo_recibo']) }}"
                                             data-numero-recibo="{{ strtolower(trim((string) $numeroReciboExterno)) }}"
                                             data-search-text="{{ strtolower(trim(($resultado['numero_pedido'] ?? '') . ' ' . ($resultado['nombre_prenda'] ?? '') . ' ' . ($resultado['cliente'] ?? '') . ' ' . ($resultado['area'] ?? '') . ' ' . ($resultado['estado'] ?? '') . ' ' . ($resultado['consecutivo_actual'] ?? '') . ' ' . ($resultado['consecutivo_inicial'] ?? ''))) }}">
                                            <div class="orden-body">
                                                <div class="orden-left">
                                                    <div class="orden-top">
                                                        <div class="orden-numero-section">
                                                            <h4 class="orden-numero">#{{ $numeroReciboExterno }}</h4>
                                                            <span class="estado-badge pendiente" data-estado="recibo-fuera-area">
                                                                {{ $areaExterna }}
                                                            </span>
                                                        </div>
                                                        <span class="badge-completado-corte is-on">{{ $estadoExterno }}</span>
                                                    </div>

                                                    <div class="orden-cliente">
                                                        <p class="cliente-label">CLIENTE</p>
                                                        <p class="cliente-name">{{ $resultado['cliente'] }}</p>
                                                    </div>

                                                    <div class="orden-prendas">
                                                        <p class="prendas-label">
                                                            <strong>{{ $resultado['nombre_prenda'] }}</strong>
                                                            @if(!empty($resultado['descripcion']))
                                                                <br>{!! nl2br(e($resultado['descripcion'])) !!}
                                                            @endif
                                                        </p>
                                                    </div>

                                                    <div class="search-outside-results-meta">
                                                        <span class="search-outside-chip">Area: {{ $areaExterna }}</span>
                                                        @if(!empty($resultado['tipo_recibo']))
                                                            <span class="search-outside-chip search-outside-chip-muted">{{ strtoupper((string) $resultado['tipo_recibo']) }}</span>
                                                        @endif
                                                    </div>

                                                    <div class="orden-buttons">
                                                        <button type="button" class="btn-ver-recibos"
                                                                onclick="abrirDetallesRecibos('{{ $resultado['numero_pedido'] }}', {{ $resultado['prenda_id'] }}, '{{ addslashes((string) $resultado['nombre_prenda']) }}', '{{ $resultado['tipo_recibo'] }}', null, '{{ $numeroReciboExterno }}', {{ $resultado['recibo_id'] }}); return false;">
                                                            <span class="material-symbols-rounded">visibility</span>
                                                            VER RECIBO
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($resultadosBusquedaFueraDeArea->count() === 0)
                            <div class="empty-state">
                                <span class="material-symbols-rounded">inbox</span>
                                @if(!empty($busquedaActual) && !empty($mensajeBusquedaDashboard))
                                    <p>{{ $mensajeBusquedaDashboard }}</p>
                                @elseif(!empty($busquedaActual))
                                    <p>No encontro coincidencias para "{{ $busquedaActual }}" en este tap.</p>
                                @else
                                    <p>No hay prendas con recibos de costura asignadas</p>
                                @endif
                            </div>
                        @endif
                    @endif
                @endif
            </div>

            @if($esVistaCostura && $prendasPaginadasVistaCostura && $prendasPaginadasVistaCostura->lastPage() > 1)
                <div id="dashboardPagination" class="dashboard-pagination-container">
                    <div class="pagination-info">
                        Mostrando {{ (int) ($dashboardPaginacionVistaCostura['conteo_pagina'] ?? 0) }} de {{ $prendasPaginadasVistaCostura->total() }} registros
                    </div>
                    <div class="pagination-buttons">
                        <a class="pagination-btn {{ $prendasPaginadasVistaCostura->onFirstPage() ? 'disabled' : '' }}"
                           href="{{ $prendasPaginadasVistaCostura->onFirstPage() ? '#' : $prendasPaginadasVistaCostura->previousPageUrl() }}">
                            <span class="material-symbols-rounded">chevron_left</span>
                        </a>
                        @for ($pagina = (int) ($dashboardPaginacionVistaCostura['inicio_botones'] ?? 1); $pagina <= (int) ($dashboardPaginacionVistaCostura['fin_botones'] ?? $prendasPaginadasVistaCostura->lastPage()); $pagina++)
                            <a class="pagination-btn {{ $pagina === $prendasPaginadasVistaCostura->currentPage() ? 'active' : '' }}"
                               href="{{ $prendasPaginadasVistaCostura->url($pagina) }}">
                                {{ $pagina }}
                            </a>
                        @endfor
                        <a class="pagination-btn {{ $prendasPaginadasVistaCostura->hasMorePages() ? '' : 'disabled' }}"
                           href="{{ $prendasPaginadasVistaCostura->hasMorePages() ? $prendasPaginadasVistaCostura->nextPageUrl() : '#' }}">
                            <span class="material-symbols-rounded">chevron_right</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
     </div>

    <!-- Modales -->
    <!-- Modal de Mensaje Generico -->
    <div id="modalMensaje" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div id="modalMensajeContenido" style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div id="modalMensajeIcono" style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h3 id="modalMensajeTitulo" style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600;"></h3>
            <p id="modalMensajeTexto" style="margin: 0 0 1.5rem 0; color: #666;"></p>
        </div>
    </div>

    <!-- Modal de Confirmacion -->
    <div id="modalConfirmacion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 420px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideIn 0.3s ease;">
            <div style="display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; border-radius: 50%; background: #fef3c7; margin: 0 auto 1rem; font-size: 2rem;">⚠️</div>
            <h3 id="modalConfirmacionTitulo" style="margin: 0 0 0.75rem 0; font-size: 1.25rem; font-weight: 700; color: #111827; text-align: center;">¿Eliminar novedad?</h3>
            <p id="modalConfirmacionTexto" style="margin: 0 0 1.5rem 0; color: #6b7280; text-align: center; line-height: 1.5; font-size: 0.95rem;">Esta accion no se puede deshacer.</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <button id="btnConfirmarNo" onclick="cancelarConfirmacion()" style="padding: 0.75rem 1rem; background: #f3f4f6; color: #374151; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;">Cancelar</button>
                <button id="btnConfirmarSi" onclick="confirmarEliminar()" style="padding: 0.75rem 1rem; background: #ef4444; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);">Eliminar</button>
            </div>
        </div>
    </div>

    <style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    #btnConfirmarNo:hover {
        background: #e5e7eb;
    }

    #btnConfirmarSi:hover {
        background: #dc2626;
        box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
        transform: translateY(-2px);
    }

    #btnConfirmarSi:active {
        transform: translateY(0);
    }
    </style>

    <!-- Modal de Novedades -->
    <div id="modalNovedad" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; max-width: 760px; width: 92%; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);">
            <div style="background: #111827; color: white; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                <div id="modalNovedadHeaderTitulo" style="font-weight: 800; letter-spacing: 0.5px; font-size: 0.95rem; text-transform: uppercase;">NOVEDADES</div>
                <button type="button" onclick="cerrarModalNovedad()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0.25rem;">×</button>
            </div>

            <div style="padding: 1.25rem; overflow-y: auto; max-height: calc(85vh - 56px);">
                <input type="hidden" id="novedadNumeroPedido">
                <input type="hidden" id="novedadPrendaId">

                <div style="margin-bottom: 1rem;">
                    <div style="color: #111827; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem;">Historial:</div>
                    <div id="novedadesHistorial" style="max-height: 220px; overflow-y: auto; padding-right: 0.25rem;"></div>
                </div>

                <div style="height: 1px; background: #e5e7eb; margin: 1rem 0;"></div>

                <div style="color: #111827; font-weight: 800; font-size: 1rem; margin-bottom: 0.75rem;">Agregar Nueva Novedad:</div>

                <div style="margin-bottom: 1rem;">
                    <textarea id="novedadDescripcionText" rows="5" style="width: 100%; padding: 0.9rem; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 0.95rem;" placeholder="Escribe tu novedad aqui­..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <button type="button" id="btnGuardarNovedad" onclick="guardarNovedad()" style="padding: 0.85rem 1rem; background: #22c55e; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Guardar Novedad</button>
                    <button type="button" onclick="cerrarModalNovedad()" style="padding: 0.85rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Cancelar</button>
                </div>

                <div style="display: none;">
                    <div id="novedadPrendaNombre"></div>
                    <div id="novedadReciboNumero"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Costura -->
    <div id="modalCostura" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 0.5rem;">
        <div id="modalCosturaContent" style="background: white; padding: 0; border-radius: 16px; max-width: 900px; width: 100%; max-height: 98vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); display: flex; flex-direction: column; transition: all 0.3s ease;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                <div style="flex: 1; min-width: 0;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; line-height: 1.2;">Asignar a Costura</h3>
                    <p style="margin: 0.25rem 0 0 0; opacity: 0.9; font-size: 0.75rem;" id="modalSubtitulo">Seleccione el tipo de asignacion</p>
                </div>
                <button type="button" onclick="cerrarModalCostura()" style="background: rgba(255,255,255,0.2); border: none; border-radius: 8px; padding: 0.5rem; cursor: pointer; color: white; transition: background 0.2s; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1.25rem;">close</span>
                </button>
            </div>

            <!-- Contenido principal -->
            <div id="modalMainContent" style="flex: 1; overflow-y: auto; padding: 1rem;">
                <!-- Opciones de Asignacion -->
                <div id="opcionesAsignacion" style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Tipo de Asignacion</h4>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                        <button type="button" id="btnModuloCompleto" onclick="seleccionarOpcionAsignacion('completo')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">inventory_2</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Modulo Completo</h5>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Asignar todas las prendas a un solo modulo</p>
                                </div>
                            </div>
                        </button>

                        <button type="button" id="btnDistribuirModulos" onclick="seleccionarOpcionAsignacion('distribuir')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">share</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Distribuir por Modulos</h5>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Repartir prendas entre multiples modulos</p>
                                </div>
                            </div>
                        </button>

                        <button type="button" id="btnTaller" onclick="seleccionarOpcionAsignacion('taller')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: #f97316; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">apartment</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Taller Externo</h5>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Enviar a talleres fuera de planta</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Contenido dinamico segun opcion -->
                <div id="contenidoAsignacion">
                    <!-- Se cargara dinamicamente -->
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 0.75rem; flex-shrink: 0;">
                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; flex-wrap: wrap;">
                    <button type="button" id="btnVolver" onclick="volverAOpciones()" style="padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; color: #374151; transition: all 0.2s; display: none; font-size: 0.875rem;">
                        <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle; margin-right: 0.5rem;">arrow_back</span>
                        Volver
                    </button>
                    <button type="button" onclick="cerrarModalCostura()" style="padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; color: #374151; transition: all 0.2s; font-size: 0.875rem;">Cancelar</button>
                    <button type="button" id="btnConfirmarAsignacion" onclick="confirmarAsignacion()" style="padding: 0.625rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; font-size: 0.875rem;" disabled>Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estilos responsive para mobile -->
    <style>
    .filtros-badges-secundarios {
        margin-top: -0.25rem;
        margin-bottom: 0.75rem;
    }

    .badge-filtro-contador {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 0.45rem;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
    }

    .badge-filtro-active .badge-filtro-contador {
        background: rgba(255, 255, 255, 0.22);
        color: #fff;
    }

    .search-loading-state {
        display: none;
        align-items: center;
        gap: 0.45rem;
        margin: -0.1rem 0 0.8rem;
        padding-left: 2.2rem;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .search-loading-state .material-symbols-rounded {
        font-size: 1rem;
        animation: searchSpin 1s linear infinite;
    }

    .search-section {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .search-controls {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
        width: 100%;
    }

    .search-field {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 280px;
        min-width: 0;
        padding: 0.6rem 0.85rem;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 14px;
        background: #fff;
    }

    .search-field:focus-within {
        border-color: rgba(59, 130, 246, 0.55);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
    }

    .search-field-icon {
        flex-shrink: 0;
        color: #64748b;
        font-size: 1.15rem;
    }

    .search-box {
        flex: 1 1 auto;
        min-width: 0;
        border: none;
        background: transparent;
        padding: 0;
        outline: none;
        box-shadow: none;
        width: 100%;
    }

    .search-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .clear-search-text-btn {
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #ffffff;
        color: #334155;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: none;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    .operario-dashboard.is-searching .clear-search-text-btn {
        background: #ef4444;
        border-color: #dc2626;
        color: #ffffff;
    }

    .clear-search-text-btn:hover {
        background: #f8fafc;
        border-color: rgba(100, 116, 139, 0.55);
        color: #0f172a;
    }

    .operario-dashboard.is-searching .clear-search-text-btn:hover {
        background: #dc2626;
        border-color: #b91c1c;
        color: #ffffff;
    }

    .operario-dashboard.is-searching .ordenes-section {
        opacity: 0.6;
        pointer-events: none;
        transition: opacity 0.15s ease;
    }

    .search-outside-results {
        margin-bottom: 1rem;
        padding: 0.85rem;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 16px;
        background: rgba(248, 250, 252, 0.9);
    }

    .search-outside-results-header {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.75rem;
        color: #334155;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .search-outside-results-header .material-symbols-rounded {
        font-size: 1.05rem;
    }

    .search-outside-results-grid {
        display: grid;
        gap: 0.85rem;
    }

    .card-busqueda-fuera-area {
        border-left: 4px solid #f59e0b;
    }

    .card-busqueda-fuera-area .orden-body {
        padding-bottom: 0.75rem;
    }

    .search-outside-results-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.65rem;
        margin-bottom: 0.75rem;
    }

    .search-outside-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        background: rgba(245, 158, 11, 0.14);
        color: #92400e;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .search-outside-chip-muted {
        background: rgba(148, 163, 184, 0.16);
        color: #475569;
    }

    @keyframes searchSpin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 768px) {
        #modalCostura {
            padding: 0.5rem;
            align-items: flex-start;
            padding-top: 2rem;
        }

        #modalCosturaContent {
            max-width: 100%;
            max-height: 95vh;
            border-radius: 12px;
        }

        #modalMainContent {
            padding: 1rem;
        }

        #opcionesAsignacion h4 {
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }

        #opcionesAsignacion button {
            padding: 0.75rem;
        }

        #opcionesAsignacion button .material-symbols-rounded {
            font-size: 1.125rem !important;
        }

        #opcionesAsignacion button h5 {
            font-size: 0.8rem !important;
        }

        #opcionesAsignacion button p {
            font-size: 0.7rem !important;
        }
    }

    @media (max-width: 480px) {
        #modalCosturaContent {
            max-height: 98vh;
        }

        #modalMainContent {
            padding: 0.75rem;
        }

        #opcionesAsignacion {
            margin-bottom: 1rem;
        }

        #opcionesAsignacion button {
            padding: 0.625rem;
        }

        #opcionesAsignacion button div {
            gap: 0.75rem;
        }

        #opcionesAsignacion button div > div:first-child {
            width: 36px !important;
            height: 36px !important;
        }
    }

    /* Distribucion por modulos - estilos */
    .dist-talla-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.75rem;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }

    .dist-talla-row.is-selected {
        border-color: #2563eb;
        box-shadow: 0 0 0 1px #2563eb inset;
    }

    .dist-talla-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        flex: 1;
    }

    .dist-talla-check {
        width: 16px;
        height: 16px;
        accent-color: #2563eb;
        flex-shrink: 0;
    }

    .dist-talla-text {
        min-width: 0;
    }

    .dist-talla-title {
        font-weight: 700;
        color: #111827;
        font-size: 0.875rem;
        line-height: 1.1;
    }

    .dist-talla-sub {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.15rem;
    }

    .dist-talla-disp {
        color: #dc2626;
        font-weight: 700;
    }

    .dist-talla-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .dist-talla-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dist-talla-input {
        width: 64px;
        padding: 0.25rem 0.4rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        text-align: center;
        font-size: 0.875rem;
        background: white;
    }

    .dist-talla-input:disabled {
        background: #f3f4f6;
        color: #9ca3af;
    }

    @media (max-width: 480px) {
        .dist-talla-row {
            padding: 0.6rem;
            border-radius: 12px;
        }

        .dist-talla-title {
            font-size: 0.85rem;
        }

        .dist-talla-input {
            width: 58px;
            font-size: 0.85rem;
        }

        /* En mobile, ocultar +/- para que no se apriete la UI (como la referencia) */
        .dist-talla-btn {
            display: none;
        }

        .dist-talla-right {
            gap: 0;
        }
    }
    </style>

@endsection

