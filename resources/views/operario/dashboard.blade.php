@extends('operario.layout')

@section('title', 'Mis Órdenes')
@section('page-title')
    @php
        $filtroReciboTitle = strtolower((string) request()->query('filtro', 'costura'));
        $filtroReciboTitle = in_array($filtroReciboTitle, ['costura', 'reflectivo', 'bodega'], true) ? $filtroReciboTitle : 'costura';
    @endphp
    <span id="dashboardPageTitle" style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded" id="dashboardPageTitleIcon">checkroom</span>
        <span id="dashboardPageTitleText">
            {{ $filtroReciboTitle === 'reflectivo' ? 'RECIBOS DE REFLECTIVO' : ($filtroReciboTitle === 'bodega' ? 'RECIBOS DE BODEGA' : 'RECIBOS DE COSTURA') }}
        </span>
    </span>
@endsection

@php
    $esVistaCostura = auth()->user()->hasRole('vista-costura');
    $filtroReciboActual = strtolower((string) request()->query('filtro', 'costura'));
    $filtroReciboActual = in_array($filtroReciboActual, ['costura', 'reflectivo', 'bodega'], true) ? $filtroReciboActual : 'costura';
    $filtroEncargadoActual = strtolower((string) request()->query('encargado', 'todos'));
    $filtroEncargadoActual = in_array($filtroEncargadoActual, ['todos', 'sin-encargado', 'control-calidad'], true)
        ? $filtroEncargadoActual
        : 'todos';
    $busquedaActual = trim((string) request()->query('q', ''));
    $perPageVistaCostura = 12;
    $pageNameVistaCostura = 'page_vc_' . str_replace('-', '_', $filtroReciboActual . '_' . $filtroEncargadoActual);
    $paginaActualVistaCostura = max(1, (int) request()->query($pageNameVistaCostura, 1));
    $modoControlCalidadVistaCostura = $esVistaCostura && $filtroEncargadoActual === 'control-calidad';
    $tabActualDashboard = (string) ($tab ?? request()->query('tab', 'pendientes'));
    $rolCosturaReflectivoId = \Illuminate\Support\Facades\DB::table('roles')
        ->where('name', 'costura-reflectivo')
        ->value('id');

    $nombresCosturaReflectivo = collect();
    if ($rolCosturaReflectivoId) {
        $nombresCosturaReflectivo = \App\Models\User::query()
            ->where(function ($query) use ($rolCosturaReflectivoId) {
                $query->whereJsonContains('roles_ids', (int) $rolCosturaReflectivoId)
                    ->orWhere('role_id', (int) $rolCosturaReflectivoId);
            })
            ->pluck('name')
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();
    }

    $ordenarLiderReflectivoPorCreacion = auth()->user()->hasRole('lider-reflectivo')
        && in_array($filtroReciboActual, ['costura', 'reflectivo'], true);
    $ordenarPorFechaAsignacionProceso = auth()->user()->hasAnyRole([
        'costurero',
        'lider-reflectivo',
        'administrador-costura',
    ]);
    $ordenarPorFechaCreacion = auth()->user()->hasRole('vista-costura');
    $ordenarPorFechaAsignacionCorte = auth()->user()->hasRole('cortador');

    $callbackOrdenamiento = function ($prenda) use ($ordenarLiderReflectivoPorCreacion, $ordenarPorFechaAsignacionProceso, $ordenarPorFechaCreacion, $ordenarPorFechaAsignacionCorte, $tabActualDashboard) {
        $reciboPrincipal = collect($prenda['recibos'] ?? [])->first();
        if (in_array($tabActualDashboard, ['pendientes', 'completados', 'completado-bodega'], true)) {
            $fechaOrden = $reciboPrincipal['created_at']
                ?? $reciboPrincipal['creado_en']
                ?? ($prenda['fecha_creacion'] ?? null);
        } elseif ($ordenarLiderReflectivoPorCreacion) {
            $fechaOrden = $reciboPrincipal['created_at']
                ?? $reciboPrincipal['creado_en']
                ?? ($prenda['fecha_creacion'] ?? null);
        } elseif ($ordenarPorFechaCreacion) {
            $fechaOrden = $reciboPrincipal['created_at']
                ?? $reciboPrincipal['creado_en']
                ?? ($prenda['fecha_creacion'] ?? null);
        } elseif ($ordenarPorFechaAsignacionCorte) {
            $fechaOrden = $reciboPrincipal['fecha_asignacion_corte']
                ?? $reciboPrincipal['fecha_proceso_corte_created_at']
                ?? ($prenda['fecha_creacion'] ?? null);
        } elseif ($ordenarPorFechaAsignacionProceso) {
            $fechaOrden = $reciboPrincipal['fecha_asignacion_costura']
                ?? $reciboPrincipal['fecha_asignacion_corte']
                ?? $reciboPrincipal['fecha_proceso_costura_created_at']
                ?? ($prenda['fecha_creacion'] ?? null);
        } else {
            $fechaOrden = $reciboPrincipal['fecha_proceso_created_at']
                ?? ($prenda['fecha_creacion'] ?? null);
        }

        if ($fechaOrden instanceof \DateTimeInterface) {
            return $fechaOrden->getTimestamp();
        }

        if (is_numeric($fechaOrden)) {
            return (int) $fechaOrden;
        }

        if (is_string($fechaOrden) && trim($fechaOrden) !== '') {
            $timestamp = strtotime($fechaOrden);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return 0;
    };

    $coleccionBaseDashboard = $modoControlCalidadVistaCostura
        ? collect($prendasConRecibosControlCalidad ?? [])
        : collect($prendasConRecibos ?? []);

    if (in_array($tabActualDashboard, ['pendientes', 'completados', 'completado-bodega'], true)) {
        $prendasOrdenadas = $coleccionBaseDashboard->sortBy($callbackOrdenamiento)->values();
    } elseif (auth()->user()->hasRole('vista-costura')) {
        $prendasOrdenadas = $coleccionBaseDashboard->sortBy($callbackOrdenamiento)->values();
    } elseif (auth()->user()->hasRole('lider-reflectivo') && in_array($filtroReciboActual, ['costura', 'reflectivo'], true)) {
        $prendasOrdenadas = $coleccionBaseDashboard->sortBy($callbackOrdenamiento)->values();
    } elseif (auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('administrador-costura')) {
        $prendasOrdenadas = $coleccionBaseDashboard->sortByDesc($callbackOrdenamiento)->values();
    } elseif (auth()->user()->hasRole('cortador')) {
        $prendasOrdenadas = $coleccionBaseDashboard->sortBy($callbackOrdenamiento)->values();
    } else {
        $prendasOrdenadas = $coleccionBaseDashboard->sortBy($callbackOrdenamiento)->values();
    }

    $prendasPaginadasVistaCostura = null;
    $prendasRenderizadas = $prendasOrdenadas;
    if ($esVistaCostura) {
        $totalVistaCostura = $prendasOrdenadas->count();
        $itemsVistaCostura = $prendasOrdenadas
            ->forPage($paginaActualVistaCostura, $perPageVistaCostura)
            ->values();

        $prendasPaginadasVistaCostura = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsVistaCostura,
            $totalVistaCostura,
            $perPageVistaCostura,
            $paginaActualVistaCostura,
            [
                'path' => request()->url(),
                'pageName' => $pageNameVistaCostura,
                'query' => request()->query(),
            ]
        );

        $prendasRenderizadas = collect($prendasPaginadasVistaCostura->items());
    }
    // Helper para obtener clase de estado
    function getEstadoClass($estado)
    {
        $estado = strtolower(trim($estado));
        if (strpos($estado, 'ejecución') !== false || strpos($estado, 'proceso') !== false) {
            return 'en-proceso';
        }
        if (strpos($estado, 'completada') !== false || strpos($estado, 'completado') !== false) {
            return 'completada';
        }
        return 'pendiente';
    }

    if (!function_exists('seleccionarReciboParaVistaOperario')) {
        function seleccionarReciboParaVistaOperario(array $recibos, string $tipoRecibo, bool $preferirParcial = false): ?array
        {
            $tipoRecibo = strtoupper(trim($tipoRecibo));

            $recibosFiltrados = array_values(array_filter($recibos, function ($recibo) use ($tipoRecibo) {
                return strtoupper(trim((string) ($recibo['tipo_recibo'] ?? ''))) === $tipoRecibo;
            }));

            if (empty($recibosFiltrados)) {
                return null;
            }

            if ($preferirParcial) {
                foreach ($recibosFiltrados as $recibo) {
                    if (!empty($recibo['pedido_parcial_id'])) {
                        return $recibo;
                    }
                }
            }

            return $recibosFiltrados[0];
        }
    }

    $rolDashboardActual = auth()->user()->hasRole('administrador-costura') ? 'administrador-costura'
        : (auth()->user()->hasRole('vista-costura') ? 'vista-costura'
            : (auth()->user()->hasRole('costura-reflectivo') ? 'costura-reflectivo'
                : (auth()->user()->hasRole('lider-reflectivo') ? 'lider-reflectivo'
                    : (auth()->user()->hasRole('confeccion-sobremedida') ? 'confeccion-sobremedida'
                        : (auth()->user()->hasRole('costurero') ? 'costurero'
                            : (auth()->user()->hasRole('cortador') || auth()->user()->hasRole('visualizador_plooter') ? 'cortador'
: (auth()->user()->hasRole('bodeguero') ? 'bodeguero' : '')))))));@endphp

@section('content')
    <div class="operario-dashboard is-modern-dashboard {{ auth()->user()->hasRole('vista-costura') ? 'is-vista-costura' : '' }}"
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
                            <p>No tienes recibos completados aún.</p>
                        </div>
                    @endif
                @elseif($modoControlCalidadVistaCostura)
                    @if($prendasRenderizadas->count() > 0)
                        @foreach($prendasRenderizadas as $prenda)
                            @php
                                $reciboPrincipal = $prenda['recibos'][0] ?? [];
                                $fechaCreacion = $prenda['fecha_creacion'] ?? ($reciboPrincipal['created_at'] ?? $reciboPrincipal['creado_en'] ?? null);
                                $fechaCreacionTimestamp = $fechaCreacion instanceof \DateTimeInterface
                                    ? $fechaCreacion->getTimestamp()
                                    : (is_numeric($fechaCreacion)
                                        ? (int) $fechaCreacion
                                        : (is_string($fechaCreacion) && trim($fechaCreacion) !== '' ? strtotime($fechaCreacion) ?: 0 : 0));
                                $reciboCompletadoArea = (bool) ($reciboPrincipal['completado_area'] ?? false);
                                $esParcial = (bool) ($prenda['es_parcial'] ?? false);
                                $parcialId = $prenda['parcial_id'] ?? null;
                                $tipoReciboControl = strtoupper((string) ($reciboPrincipal['tipo_recibo'] ?? 'COSTURA'));
                                $consecutivoActual = (string) ($reciboPrincipal['consecutivo_actual'] ?? ($prenda['numero_pedido'] ?? ''));
                                $numeroReciboBusqueda = $reciboPrincipal['consecutivo_parcial'] ?? $consecutivoActual;
                            @endphp

                            <div class="orden-card-simple card-control-calidad"
                                 data-numero="{{ $prenda['numero_pedido'] }}"
                                 data-numero-recibo="{{ $consecutivoActual }}"
                                 data-prenda="{{ strtolower((string) $textoPrendaBodega) }}"
                                 data-prenda-id="{{ $prenda['prenda_id'] }}"
                                 data-cliente="{{ strtolower((string) $prenda['cliente']) }}"
                                 data-search-text="{{ strtolower(trim(($prenda['numero_pedido'] ?? '') . ' ' . ($prenda['nombre_prenda'] ?? '') . ' ' . ($prenda['cliente'] ?? '') . ' ' . ($consecutivoActual ?? ''))) }}"
                                 data-tipo-recibo="{{ strtolower($tipoReciboControl) }}"
                                 data-fecha-creacion-costura="{{ $fechaCreacionTimestamp }}"
                                 data-fecha-creacion-reflectivo="{{ $fechaCreacionTimestamp }}"
                                 data-sin-encargado-costura="0"
                                 data-sin-encargado-reflectivo="0"
                                 data-completado-costura="{{ $reciboCompletadoArea ? '1' : '0' }}"
                                 data-completado-reflectivo="{{ $reciboCompletadoArea ? '1' : '0' }}">
                                <div class="orden-body">
                                    <div class="orden-left">
                                        <div class="orden-top">
                                            <div class="orden-numero-section">
                                                <h4 class="orden-numero">#{{ $consecutivoActual }}</h4>
                                                <span class="estado-badge pendiente" data-estado="recibo-control-calidad">EN CC</span>
                                                @if($reciboCompletadoArea)
                                                    <span class="badge-completado-costura is-on">COMPLETADO</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="orden-cliente">
                                            <p class="cliente-label">CLIENTE</p>
                                            <p class="cliente-name">{{ $prenda['cliente'] }}</p>
                                        </div>

                                        <div class="orden-prendas">
                                            <p class="prendas-label">
                                                <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                                @if(!empty($prenda['descripcion']))
                                                    <br>{!! nl2br(e($prenda['descripcion'])) !!}
                                                @endif
                                            </p>
                                        </div>

                                        <div class="mobile-ver-recibo-section">
                                            <button type="button" class="btn-ver-recibos mobile-under-state"
                                                    onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', '{{ $tipoReciboControl }}', {{ $parcialId ? (int) $parcialId : 'null' }}, '{{ addslashes((string) $numeroReciboBusqueda) }}', {{ $reciboPrincipal['id'] ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                        </div>

                                        <div class="orden-buttons">
                                            <button type="button" class="btn-ver-recibos"
                                                    onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', '{{ $tipoReciboControl }}', {{ $parcialId ? (int) $parcialId : 'null' }}, '{{ addslashes((string) $numeroReciboBusqueda) }}', {{ $reciboPrincipal['id'] ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>

                                            <button type="button" class="btn-agregar-novedad"
                                                    onclick="abrirModalNovedad('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', {{ $consecutivoActual }}); return false;">
                                                <span class="material-symbols-rounded">comment</span>
                                                AGREGAR NOVEDAD
                                            </button>

                                            @if((bool) ($prenda['tiene_parciales'] ?? false))
                                                    <button class="btn-ver-distribucion"
                                                        onclick="abrirDistribucionReciboCC(this, '{{ $tipoReciboControl }}');"
                                                        data-recibo-id="{{ $reciboPrincipal['id'] ?? '' }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-numero-recibo="{{ $consecutivoActual }}"
                                                        data-tipo-recibo="{{ $tipoReciboControl }}">
                                                    <span class="material-symbols-rounded">share</span>
                                                    VER DISTRIBUCION
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="orden-right">
                                        <div class="orden-right-center">
                                            <a href="#"
                                               class="action-arrow"
                                               onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $prenda['nombre_prenda']) }}', '{{ $tipoReciboControl }}', {{ $parcialId ? (int) $parcialId : 'null' }}, '{{ addslashes((string) $numeroReciboBusqueda) }}', {{ $reciboPrincipal['id'] ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">arrow_forward</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <span class="material-symbols-rounded">inbox</span>
                            @if(!empty($busquedaActual) && $resultadosBusquedaFueraDeArea->count() > 0)
                                <p>Encontró coincidencias en otras Areas y te las muestro arriba.</p>
                            @elseif(!empty($busquedaActual) && !empty($mensajeBusquedaDashboard))
                                <p>{{ $mensajeBusquedaDashboard }}</p>
                            @elseif(!empty($busquedaActual))
                                <p>No encontró coincidencias para "{{ $busquedaActual }}" en este tap.</p>
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
                                $reciboPrincipalBodega = $prenda['recibos'][0] ?? [];
                                $consecutivoBodega = (string) ($reciboPrincipalBodega['consecutivo_actual'] ?? $prenda['numero_pedido'] ?? '');
                                $reciboIdBodega = $reciboPrincipalBodega['id'] ?? null;
                                $areaBodega = strtoupper((string) ($reciboPrincipalBodega['area'] ?? 'INSUMOS'));
                                $tieneParcialesBodegaFlag = (bool) ($reciboPrincipalBodega['tiene_parciales'] ?? $prenda['tiene_parciales'] ?? false);
                                $pedidoIdBodega = (int) ($prenda['pedido_id'] ?? 0);
                                $prendaIdBodega = (int) ($prenda['prenda_id'] ?? 0);
                                $consecutivoOriginalBodega = is_numeric($consecutivoBodega) ? (int) $consecutivoBodega : 0;

                                $tieneParcialesBodegaDb = false;
                                if ($prendaIdBodega > 0 && $consecutivoOriginalBodega > 0) {
                                    $queryParcialesBodega = \Illuminate\Support\Facades\DB::table('recibo_por_partes')
                                        ->where('prenda_pedido_id', $prendaIdBodega)
                                        ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                                        ->where('consecutivo_original', $consecutivoOriginalBodega);

                                    // Si existe pedido_produccion_id úsalo; si no, modo recibo interno sin pedido.
                                    if ($pedidoIdBodega > 0) {
                                        $queryParcialesBodega->where('pedido_produccion_id', $pedidoIdBodega);
                                    }

                                    $tieneParcialesBodegaDb = $queryParcialesBodega->exists();
                                }

                                $tieneParcialesBodega = $tieneParcialesBodegaFlag || $tieneParcialesBodegaDb;
                                $encargadoCosturaBodega = trim((string) ($prenda['encargado_costura'] ?? ($reciboPrincipalBodega['encargado_costura'] ?? '')));
                                $procesoIdCosturaBodega = $prenda['proceso_id_costura'] ?? ($reciboPrincipalBodega['proceso_id_costura'] ?? null);
                                $mostrarComoDeshacerBodega = $encargadoCosturaBodega !== '' && !empty($procesoIdCosturaBodega);
                                $textoEncargadoCosturaBodega = $encargadoCosturaBodega !== '' ? strtoupper($encargadoCosturaBodega) : 'SIN ASIGNAR';
                                $descripcionPrendaBodega = trim((string) ($prenda['descripcion'] ?? ''));
                                $textoPrendaBodega = $descripcionPrendaBodega !== '' ? $descripcionPrendaBodega : 'SIN DESCRIPCION';
                            @endphp

                            <div class="orden-card-simple card-bodega"
                                 data-numero="{{ $consecutivoBodega }}"
                                 data-prenda="{{ strtolower((string) $prenda['nombre_prenda']) }}"
                                 data-prenda-id="{{ $prenda['prenda_id'] }}"
                                 data-cliente="{{ strtolower((string) $prenda['cliente']) }}"
                                 data-tipo-recibo="corte-para-bodega"
                                 data-numero-recibo="{{ $consecutivoBodega }}"
                                 data-sin-encargado-costura="{{ $encargadoCosturaBodega === '' ? '1' : '0' }}"
                                 data-sin-encargado-reflectivo="0"
                                 data-search-text="{{ strtolower(trim(($consecutivoBodega ?? '') . ' ' . $textoPrendaBodega . ' ' . ($prenda['cliente'] ?? ''))) }}">
                                <div class="orden-body">
                                    <div class="vista-resumen-card" onclick="event.stopPropagation();">
                                        <div class="vista-encargados-row">
                                            <div class="vista-encargado-pill vista-encargado-pill-corte">
                                                <span class="vista-encargado-pill-label">Corte</span>
                                                <span class="vista-encargado-pill-name">CORTADORES</span>
                                            </div>

                                            <div class="vista-encargado-pill vista-encargado-pill-costura">
                                                <span class="vista-encargado-pill-label">Costura</span>
                                                <span class="vista-encargado-pill-name">{{ $textoEncargadoCosturaBodega }}</span>
                                            </div>
                                        </div>

                                        <div class="vista-estado-linea">
                                            <span class="vista-estado-etiqueta">Estado:</span>
                                            <span class="badge-completado-corte {{ $mostrarComoDeshacerBodega ? 'is-on' : '' }}">
                                                {{ $mostrarComoDeshacerBodega ? 'PENDIENTE COSTURA' : 'PENDIENTE COSTURA' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="orden-left">
                                        <div class="orden-top">
                                            <div class="orden-numero-section">
                                                <h4 class="orden-numero">#{{ $consecutivoBodega }}</h4>
                                                <span class="estado-badge pendiente" data-estado="recibo-bodega">BODEGA</span>
                                            </div>
                                            <span class="badge-completado-corte is-on">CORTE-PARA-BODEGA</span>
                                        </div>

                                        <div class="orden-cliente">
                                            <p class="cliente-label">CLIENTE</p>
                                            <p class="cliente-name">BODEGA</p>
                                        </div>

                                        <div class="orden-prendas">
                                            <p class="prendas-label">
                                                {!! nl2br(e($textoPrendaBodega)) !!}
                                            </p>
                                        </div>

                                        <div class="mobile-ver-recibo-section">
                                            <button type="button" class="btn-ver-recibos mobile-under-state"
                                                    onclick="abrirDetallesRecibos('{{ $consecutivoBodega }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $textoPrendaBodega) }}', 'CORTE-PARA-BODEGA', null, '{{ $consecutivoBodega }}', {{ $reciboIdBodega ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                        </div>

                                        <div class="orden-buttons">
                                            @if(!$tieneParcialesBodega)
                                                <button type="button" class="btn-pasar-costura {{ $mostrarComoDeshacerBodega ? 'btn-deshacer-costura' : '' }}"
                                                        data-visible-filtro="bodega"
                                                        id="btn-costura-bodega-{{ $prenda['prenda_id'] }}-{{ $consecutivoBodega }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] ?: $prenda['prenda_id'] }}"
                                                        data-numero-pedido="{{ $consecutivoBodega }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $textoPrendaBodega }}"
                                                        data-tipo-recibo="CORTE-PARA-BODEGA"
                                                        data-recibo="{{ $consecutivoBodega }}"
                                                        data-area="Costura"
                                                        data-proceso-id="{{ $procesoIdCosturaBodega ?? '' }}"
                                                        data-encargado-costura="{{ $encargadoCosturaBodega }}"
                                                        data-parcial-id=""
                                                        onclick="manejarPasarACostura(this); return false;">
                                                    <span class="material-symbols-rounded">{{ $mostrarComoDeshacerBodega ? 'undo' : 'checkroom' }}</span>
                                                    {{ $mostrarComoDeshacerBodega ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                                </button>

                                                <button class="btn-pasar-cc"
                                                        data-visible-filtro="bodega"
                                                        id="btn-cc-bodega-{{ $prenda['prenda_id'] }}-{{ $consecutivoBodega }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] ?: $prenda['prenda_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $textoPrendaBodega }}"
                                                        data-tipo-recibo="CORTE-PARA-BODEGA"
                                                        data-recibo="{{ $consecutivoBodega }}"
                                                        data-area="Costura"
                                                        data-proceso-id="{{ $procesoIdCosturaBodega ?? '' }}"
                                                        type="button"
                                                        onclick="pasarAControlCalidad(this); return false;">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    PASAR A C.C
                                                </button>
                                            @endif

                                            @if($tieneParcialesBodega && $reciboIdBodega)
                                                @component('components.botones.ver-distribucion', [
                                                    'filtro' => 'bodega',
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'reciboId' => $reciboIdBodega,
                                                    'numeroRecibo' => $consecutivoBodega,
                                                    'tipoRecibo' => 'CORTE-PARA-BODEGA',
                                                ])@endcomponent
                                                @component('components.botones.editar-encargados', [
                                                    'filtro' => 'bodega',
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'reciboId' => $reciboIdBodega,
                                                    'pedidoId' => $prenda['pedido_id'] ?: $prenda['prenda_id'],
                                                    'numeroPedido' => $prenda['numero_pedido'] ?? $consecutivoBodega,
                                                    'numeroRecibo' => $consecutivoBodega,
                                                    'nombrePrenda' => $textoPrendaBodega,
                                                    'tipoRecibo' => 'CORTE-PARA-BODEGA',
                                                ])@endcomponent
                                            @endif

                                            <button type="button" class="btn-agregar-novedad"
                                                    onclick="abrirModalNovedad('{{ $consecutivoBodega }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $textoPrendaBodega) }}', {{ $consecutivoBodega }}); return false;">
                                                <span class="material-symbols-rounded">comment</span>
                                                AGREGAR NOVEDAD
                                            </button>

                                            <button type="button" class="btn-ver-recibos"
                                                    onclick="abrirDetallesRecibos('{{ $consecutivoBodega }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $textoPrendaBodega) }}', 'CORTE-PARA-BODEGA', null, '{{ $consecutivoBodega }}', {{ $reciboIdBodega ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">visibility</span>
                                                VER RECIBO
                                            </button>
                                        </div>

                                        <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                            <span class="material-symbols-rounded">more_horiz</span>
                                        </button>
                                    </div>

                                    <div class="orden-right">
                                        <div class="orden-right-center">
                                            <a href="#"
                                               class="action-arrow"
                                               onclick="abrirDetallesRecibos('{{ $consecutivoBodega }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $textoPrendaBodega) }}', 'CORTE-PARA-BODEGA', null, '{{ $consecutivoBodega }}', {{ $reciboIdBodega ?? 'null' }}); return false;">
                                                <span class="material-symbols-rounded">arrow_forward</span>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mobile-actions-drawer" id="mobile-drawer-{{ $prenda['prenda_id'] }}">
                                        @if(!$tieneParcialesBodega)
                                            <button type="button" class="btn-pasar-costura {{ $mostrarComoDeshacerBodega ? 'btn-deshacer-costura' : '' }}"
                                                    id="btn-costura-bodega-mobile-{{ $prenda['prenda_id'] }}-{{ $consecutivoBodega }}"
                                                    data-pedido-id="{{ $prenda['pedido_id'] ?: $prenda['prenda_id'] }}"
                                                    data-numero-pedido="{{ $consecutivoBodega }}"
                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                    data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                                                    data-nombre="{{ $textoPrendaBodega }}"
                                                    data-tipo-recibo="CORTE-PARA-BODEGA"
                                                    data-recibo="{{ $consecutivoBodega }}"
                                                    data-area="Costura"
                                                    data-proceso-id="{{ $procesoIdCosturaBodega ?? '' }}"
                                                    data-encargado-costura="{{ $encargadoCosturaBodega }}"
                                                    data-parcial-id=""
                                                    onclick="manejarPasarACostura(this); return false;">
                                                <span class="material-symbols-rounded">{{ $mostrarComoDeshacerBodega ? 'undo' : 'checkroom' }}</span>
                                                {{ $mostrarComoDeshacerBodega ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                            </button>

                                            <button type="button" class="btn-pasar-cc"
                                                        id="btn-cc-bodega-mobile-{{ $prenda['prenda_id'] }}-{{ $consecutivoBodega }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] ?: $prenda['prenda_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-prenda-bodega-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $textoPrendaBodega }}"
                                                        data-tipo-recibo="CORTE-PARA-BODEGA"
                                                        data-recibo="{{ $consecutivoBodega }}"
                                                        data-area="Costura"
                                                        data-proceso-id="{{ $procesoIdCosturaBodega ?? '' }}"
                                                        onclick="pasarAControlCalidad(this); return false;">
                                                <span class="material-symbols-rounded">check_circle</span>
                                                PASAR A C.C
                                            </button>
                                        @endif

                                        @if($tieneParcialesBodega && $reciboIdBodega)
                                            @component('components.botones.ver-distribucion', [
                                                'filtro' => 'bodega',
                                                'prendaId' => $prenda['prenda_id'],
                                                'reciboId' => $reciboIdBodega,
                                                'numeroRecibo' => $consecutivoBodega,
                                                'tipoRecibo' => 'CORTE-PARA-BODEGA',
                                            ])@endcomponent
                                            @component('components.botones.editar-encargados', [
                                                'filtro' => 'bodega',
                                                'prendaId' => $prenda['prenda_id'],
                                                'reciboId' => $reciboIdBodega,
                                                'pedidoId' => $prenda['pedido_id'] ?: $prenda['prenda_id'],
                                                'numeroPedido' => $prenda['numero_pedido'] ?? $consecutivoBodega,
                                                'numeroRecibo' => $consecutivoBodega,
                                                'nombrePrenda' => $textoPrendaBodega,
                                                'tipoRecibo' => 'CORTE-PARA-BODEGA',
                                            ])@endcomponent
                                        @endif

                                        <button type="button" class="btn-ver-recibos"
                                                onclick="abrirDetallesRecibos('{{ $consecutivoBodega }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $textoPrendaBodega) }}', 'CORTE-PARA-BODEGA', null, '{{ $consecutivoBodega }}', {{ $reciboIdBodega ?? 'null' }}); return false;">
                                            <span class="material-symbols-rounded">visibility</span>
                                            VER RECIBO
                                        </button>

                                        <button type="button" class="btn-agregar-novedad"
                                                onclick="abrirModalNovedad('{{ $consecutivoBodega }}', {{ $prenda['prenda_id'] }}, '{{ addslashes((string) $textoPrendaBodega) }}', {{ $consecutivoBodega }}); return false;">
                                            <span class="material-symbols-rounded">comment</span>
                                            AGREGAR NOVEDAD
                                        </button>

                                        <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                            <span class="material-symbols-rounded">more_horiz</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @continue
                        @endif
                        @php
                            $estadoClass = 'pendiente'; // Siempre pendiente, eliminar en-proceso
                            // Determinar tipo de recibo para filtro
                            // Para vista-costura y costura-reflectivo: una prenda puede tener ambos tipos de recibos
                            // Para otros roles: solo muestra reflectivos
                            $tiposRecibos = array_map(function ($r) {
                                return strtoupper($r['tipo_recibo']); }, $prenda['recibos']);
                            $tieneReflectivo = in_array('REFLECTIVO', $tiposRecibos);
                            $tieneCostura = in_array('COSTURA', $tiposRecibos);
                            $reciboReflectivoParaFiltro = collect($prenda['recibos'] ?? [])->first(function ($recibo) {
                                return strtoupper((string) ($recibo['tipo_recibo'] ?? '')) === 'REFLECTIVO';
                            });
                            
                            $mostrarReflectivoEnFiltro = $tieneReflectivo;
                            
                            if (auth()->user()->hasRole('vista-costura')) {
                                if (!$tieneReflectivo || !$reciboReflectivoParaFiltro) {
                                    $mostrarReflectivoEnFiltro = false;
                                }
                            }

                            $busquedaActiva = $busquedaActual !== '';
                            $reflectivoCompletadoEnCard = (bool) (($reciboReflectivoParaFiltro['completado_reflectivo'] ?? false) || ($reciboReflectivoParaFiltro['completado_costura'] ?? false));


                            // Obtener el Area del recibo principal para filtros
                            $reciboPrincipalFiltro = $prenda['recibos'][0] ?? null;
                            $areaReciboFiltro = strtolower(trim((string) ($reciboPrincipalFiltro['area'] ?? '')));

                            // Para vista-costura en modo filtro server-side, fijar tipo explícito para evitar cards "vacías" en cliente
                            if (auth()->user()->hasRole('vista-costura')) {
                                $esReflectivo = $filtroReciboActual === 'reflectivo' ? 'reflectivo' : ($filtroReciboActual === 'bodega' ? 'bodega' : 'costura');
                            } elseif (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo')) {
                                // Guardar tipos separados por comas para poder filtrar correctamente
                                $tiposParaFiltro = [];
                                if ($tieneCostura)
                                    $tiposParaFiltro[] = 'costura';
                                if ($mostrarReflectivoEnFiltro)
                                    $tiposParaFiltro[] = 'reflectivo';
                                $esReflectivo = implode(',', $tiposParaFiltro); // "costura,reflectivo" o "costura" o "reflectivo"
                            } else {
                                // Para otros roles, solo mostrar reflectivos
                                $esReflectivo = $mostrarReflectivoEnFiltro ? 'reflectivo' : 'costura';
                            }

                            // Por defecto:
                            // - costura-reflectivo: mostrar COSTURA por defecto (pero incluir las que tienen ambos)
                            // - vista-costura: mostrar COSTURA por defecto (pero incluir las que tienen ambos)
                            // - costurero: mostrar COSTURA por defecto
                            // - cortador: mostrar prendas con Area "Corte" (independientemente del tipo de recibo)
                            $displayInicial = '';
                            if (auth()->user()->hasRole('vista-costura')) {
                                // En vista-costura con filtro server-side, mostrar siempre lo consultado por backend
                                $displayInicial = '';
                            } elseif (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo')) {
                                // Para roles mixtos, respetar el tab activo en server-side
                                if ($filtroReciboActual === 'reflectivo') {
                                    $displayInicial = ($mostrarReflectivoEnFiltro && ($busquedaActiva || !$reflectivoCompletadoEnCard)) ? '' : 'none';
                                } else {
                                    $displayInicial = $tieneCostura ? '' : 'none';
                                }
                            } elseif (auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) || auth()->user()->hasRole('administrador-costura')) {
                                $displayInicial = $tieneCostura ? '' : 'none';
                            } elseif (auth()->user()->hasRole('cortador')) {
                                // Para cortadores: mostrar las que tienen Area "Corte"
                                $displayInicial = $areaReciboFiltro === 'corte' ? '' : 'none';
                            } else {
                                $displayInicial = $tieneReflectivo ? '' : 'none';
                            }
                        @endphp

                        @php
                            // Definir variables necesarias para el card
                            $reciboPrincipalCard = $prenda['recibos'][0] ?? null;
                            $reciboCompletadoCostura = (bool) ($reciboPrincipalCard['completado_costura'] ?? false);
                            $reciboCosturaFiltroCard = collect($prenda['recibos'] ?? [])->first(function ($recibo) {
                                return strtoupper((string) ($recibo['tipo_recibo'] ?? '')) === 'COSTURA';
                            });
                            $reciboReflectivoFiltroCard = collect($prenda['recibos'] ?? [])->first(function ($recibo) {
                                return strtoupper((string) ($recibo['tipo_recibo'] ?? '')) === 'REFLECTIVO';
                            });
                            $reciboCompletadoReflectivo = (bool) ($reciboReflectivoFiltroCard['completado_costura'] ?? false);
                            $reciboParaBusqueda = collect($prenda['recibos'] ?? [])->first(function ($recibo) {
                                return !empty($recibo['pedido_parcial_id']);
                            }) ?? $reciboPrincipalCard;

                            $tipoReciboPreferido = $reciboParaBusqueda['tipo_recibo'] ?? '';
                            $parcialIdPreferido = !empty($reciboParaBusqueda['pedido_parcial_id']) ? (int) $reciboParaBusqueda['pedido_parcial_id'] : 'null';
                            $consecutivoPreferido = $reciboParaBusqueda['consecutivo_parcial'] ?? ($reciboParaBusqueda['consecutivo_actual'] ?? '');

                            $numeroReciboBusqueda = $reciboParaBusqueda['consecutivo_parcial']
                                ?? $reciboParaBusqueda['consecutivo_actual']
                                ?? $prenda['numero_pedido'];
                            $numerosRecibosBusqueda = collect($prenda['recibos'] ?? [])
                                ->flatMap(function ($recibo) {
                                    return [
                                        $recibo['consecutivo_actual'] ?? null,
                                        $recibo['consecutivo_parcial'] ?? null,
                                    ];
                                })
                                ->filter(fn ($valor) => $valor !== null && $valor !== '')
                                ->map(fn ($valor) => (string) $valor)
                                ->unique()
                                ->values()
                                ->implode(' ');
                            $sinEncargadoCosturaCard = collect($prenda['recibos'] ?? [])->contains(function ($recibo) {
                                $tipo = strtoupper(trim((string) ($recibo['tipo_recibo'] ?? '')));
                                if ($tipo !== 'COSTURA') {
                                    return false;
                                }

                                $sinEncargado = empty(trim((string) ($recibo['encargado_costura'] ?? '')));
                                $completadoCorte = (bool) ($recibo['completado_corte'] ?? false);

                                return $sinEncargado && $completadoCorte;
                            });
                            $sinEncargadoReflectivoCard = $reciboReflectivoFiltroCard
                                && empty(trim((string) ($reciboReflectivoFiltroCard['encargado_costura'] ?? '')));
                            $sinEncargadoCosturaLider = $reciboCosturaFiltroCard
                                && empty(trim((string) ($reciboCosturaFiltroCard['encargado_costura'] ?? '')));

                            $recibosCorteAsignadosCortador = collect($prenda['recibos'] ?? [])->filter(function ($recibo) {
                                $area = strtolower(trim((string) ($recibo['area'] ?? '')));
                                return $area === 'corte';
                            })->count();
                        @endphp

                        @if(auth()->user()->hasRole('lider-reflectivo') && $filtroReciboActual === 'costura' && !$mostrarReflectivoEnFiltro && $sinEncargadoCosturaLider)
                            @continue
                        @endif
                        @if(auth()->user()->hasRole('lider-reflectivo') && $filtroReciboActual === 'costura' && !$mostrarReflectivoEnFiltro && $tieneCostura)
                            @php
                                $encargadoCosturaLider = strtolower(trim((string) ($reciboCosturaFiltroCard['encargado_costura'] ?? '')));
                                $encargadoCosturaEsReflectivo = $encargadoCosturaLider !== '' && $nombresCosturaReflectivo->contains($encargadoCosturaLider);
                            @endphp
                            @if(!$encargadoCosturaEsReflectivo)
                                @continue
                            @endif
                        @endif
                        <div class="orden-card-simple {{ ((auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) || auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('administrador-costura')) && $reciboCompletadoCostura) ? 'card-completado-costura' : '' }} {{ $tieneReflectivo ? 'borde-reflectivo' : '' }}" 
                             data-numero="{{ $prenda['numero_pedido'] }}" 
                             data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                             data-prenda-id="{{ $prenda['prenda_id'] }}"
                             data-pedido-parcial-id="{{ $prenda['recibos'][0]['pedido_parcial_id'] ?? '' }}"
                             data-cliente="{{ strtolower($prenda['cliente']) }}"
                             data-tipo-recibo="{{ $esReflectivo }}"
                             data-sin-encargado-costura="{{ $sinEncargadoCosturaCard ? '1' : '0' }}"
                             data-sin-encargado-reflectivo="{{ $sinEncargadoReflectivoCard ? '1' : '0' }}"
                             data-completado-costura="{{ $reciboCompletadoCostura ? '1' : '0' }}"
                             data-completado-reflectivo="{{ $reciboCompletadoReflectivo ? '1' : '0' }}"
                             data-numero-recibo="{{ trim($numeroReciboBusqueda . ' ' . $numerosRecibosBusqueda) }}"
                             data-fecha-completado-reflectivo="{{ ($reciboReflectivoFiltroCard && isset($reciboReflectivoFiltroCard['fecha_completado_costura'])) ? strtotime($reciboReflectivoFiltroCard['fecha_completado_costura']) : 0 }}"
                             data-fecha-creacion-reflectivo="{{ ($reciboReflectivoFiltroCard['created_at'] ?? ($reciboReflectivoFiltroCard['creado_en'] ?? '')) ? strtotime($reciboReflectivoFiltroCard['created_at'] ?? $reciboReflectivoFiltroCard['creado_en']) : 0 }}"
                             data-fecha-creacion-costura="{{ ($reciboCosturaFiltroCard['created_at'] ?? ($prenda['fecha_creacion'] ?? '')) ? strtotime($reciboCosturaFiltroCard['created_at'] ?? ($prenda['fecha_creacion'] ?? '')) : 0 }}"
                             data-fecha-asignacion-costura="{{ ($reciboCosturaFiltroCard['fecha_asignacion_costura'] ?? ($reciboCosturaFiltroCard['fecha_proceso_costura_created_at'] ?? ($prenda['fecha_creacion'] ?? ''))) ? strtotime($reciboCosturaFiltroCard['fecha_asignacion_costura'] ?? ($reciboCosturaFiltroCard['fecha_proceso_costura_created_at'] ?? ($prenda['fecha_creacion'] ?? ''))) : 0 }}"
                             data-recibos-corte-asignados="{{ $recibosCorteAsignadosCortador }}"
                             data-area-actual="{{ strtoupper(trim((string) ($reciboPrincipalCard['area'] ?? 'SIN AREA'))) }}"
                             style="display: {{ $displayInicial }}">

                            <!-- Borde izquierdo eliminado -->
                            <!-- <div class="orden-border {{ $estadoClass }}"></div> -->

                            <!-- Contenido Izquierdo -->
                            @php
                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                $reciboCompletadoArea = (bool) ($reciboPrincipal['completado_area'] ?? false);
                                $reciboCompletadoCorte = (bool) ($reciboPrincipal['completado_corte'] ?? false);
                                $areaReciboActual = (string) ($reciboPrincipal['area'] ?? '');
                                $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                $reciboCompletadoControlCalidad = (bool) ($reciboPrincipal['completado_control_calidad'] ?? false);
                                $areaReciboNormalizada = strtolower(trim($areaReciboActual));
                                $completadoVistaSegunArea = $areaReciboNormalizada === 'costura'
                                    ? $reciboCompletadoCostura
                                    : ($areaReciboNormalizada === 'corte'
                                        ? $reciboCompletadoCorte
                                        : (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)
                                            ? $reciboCompletadoControlCalidad
                                            : false));
                                $labelAreaVista = $areaReciboActual ?: '-';
                                $labelEstadoVista = $completadoVistaSegunArea
                                    ? ('COMPLETADO ' . strtoupper($labelAreaVista))
                                    : ('PENDIENTE ' . strtoupper($labelAreaVista));
                                $labelEstadoVistaCostura = $reciboCompletadoCostura
                                    ? 'COMPLETADO COSTURA'
                                    : 'PENDIENTE COSTURA';
                            @endphp
                            <div class="orden-body {{ ($reciboCompletadoArea || (auth()->user()->hasRole('vista-costura') && $completadoVistaSegunArea)) ? 'recibo-completado-area' : '' }}">
                                @php
                                    $encargadoVista = null;
                                    if ($areaReciboNormalizada === 'corte') {
                                        $encargadoVista = $reciboPrincipal['encargado_corte'] ?? null;
                                    } elseif ($areaReciboNormalizada === 'costura') {
                                        $encargadoVista = $reciboPrincipal['encargado_costura'] ?? null;
                                    } elseif (in_array($areaReciboNormalizada, ['control calidad', 'control de calidad'], true)) {
                                        $encargadoVista = $reciboPrincipal['encargado_control_calidad'] ?? null;
                                    }
                                    $encargadoVista = is_string($encargadoVista) ? trim($encargadoVista) : $encargadoVista;
                                    $textoEncargadoVista = $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO';

                                    // Obtener encargado de corte para mostrar en el card (excepto cortadores)
                                    $encargadoCorte = $reciboPrincipal['encargado_corte'] ?? null;
                                    $encargadoCorte = is_string($encargadoCorte) ? trim($encargadoCorte) : $encargadoCorte;
                                    $encargadoCosturaCard = is_string($reciboCosturaFiltroCard['encargado_costura'] ?? null) ? trim((string) $reciboCosturaFiltroCard['encargado_costura']) : ($reciboCosturaFiltroCard['encargado_costura'] ?? null);
                                    $encargadoReflectivoCard = is_string($reciboReflectivoFiltroCard['encargado_costura'] ?? null) ? trim((string) $reciboReflectivoFiltroCard['encargado_costura']) : ($reciboReflectivoFiltroCard['encargado_costura'] ?? null);
                                    $textoEncargadoCosturaCard = $reciboCosturaFiltroCard
                                        ? ($encargadoCosturaCard ? strtoupper($encargadoCosturaCard) : 'SIN ENCARGADO')
                                        : 'SIN ENCARGADO';
                                    $textoEncargadoReflectivoCard = $reciboReflectivoFiltroCard
                                        ? ($encargadoReflectivoCard ? strtoupper($encargadoReflectivoCard) : 'SIN ENCARGADO')
                                        : 'SIN ENCARGADO';
                                @endphp
                                @if(!auth()->user()->hasRole('vista-costura') && !auth()->user()->hasRole('cortador') && !auth()->user()->hasRole('visualizador_plooter') && !auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']))
                                    <div class="orden-encargado-corner" onclick="event.stopPropagation();">
                                        <strong>Encargado:</strong>
                                        @if(auth()->user()->hasRole('lider-reflectivo'))
                                            <span data-visible-filtro="costura">{{ $textoEncargadoCosturaCard }}</span>
                                            <span data-visible-filtro="reflectivo" style="display: none;">{{ $textoEncargadoReflectivoCard }}</span>
                                        @else
                                            <span>{{ $encargadoVista ? strtoupper($encargadoVista) : 'SIN ENCARGADO' }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if(auth()->user()->hasRole('vista-costura'))
                                    <div class="vista-resumen-card" onclick="event.stopPropagation();">
                                        <div class="vista-encargados-row">
                                            @if(!auth()->user()->hasRole('cortador'))
                                                <div class="vista-encargado-pill vista-encargado-pill-corte">
                                                    <span class="vista-encargado-pill-label">Corte</span>
                                                    <span class="vista-encargado-pill-name">{{ $encargadoCorte ? strtoupper($encargadoCorte) : 'SIN ASIGNAR' }}</span>
                                                </div>
                                            @endif

                                            <div class="vista-encargado-pill vista-encargado-pill-costura">
                                                <span class="vista-encargado-pill-label">Costura</span>
                                                <span class="vista-encargado-pill-name">{{ $textoEncargadoVista }}</span>
                                            </div>
                                        </div>

                                        <div class="vista-estado-linea">
                                            <span class="vista-estado-etiqueta">Estado:</span>
                                            <span class="badge-completado-corte {{ $reciboCompletadoCostura ? 'is-on' : '' }}">
                                                {{ $labelEstadoVistaCostura }}
                                            </span>
                                        </div>
                                    </div>
                                @elseif(
                                        !auth()->user()->hasRole('cortador')
                                        && !auth()->user()->hasRole('visualizador_plooter')
                                        && !auth()->user()->hasRole('lider-reflectivo')
                                        && !auth()->user()->hasRole('administrador-costura')
                                    )
                                        <div class="orden-encargado-corte" onclick="event.stopPropagation();">
                                            <strong>Encargado Corte:</strong>
                                            <span>{{ $encargadoCorte ? strtoupper($encargadoCorte) : 'SIN ASIGNAR' }}</span>
                                        </div>
                                @endif
                                <div class="orden-left">
                                    <div class="orden-top">
                                        <div class="orden-numero-section">
                                            @if(isset($prenda['recibos'][0]['consecutivo_actual']))
                                                <h4 class="orden-numero">#{{ $prenda['recibos'][0]['consecutivo_actual'] }}</h4>
                                            @else
                                                <h4 class="orden-numero">#{{ $prenda['numero_pedido'] }}</h4>
                                            @endif
                                            <span class="estado-badge {{ $estadoClass }}" data-estado="recibo-costura">
                                                {{ count(array_unique(array_map(fn($r) => strtoupper($r['tipo_recibo']), $prenda['recibos']))) }} RECIBOS
                                            </span>
                                            @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                            @if(auth()->user()->hasRole('costura-reflectivo') && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                            @if(auth()->user()->hasRole('lider-reflectivo') && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                            @if(auth()->user()->hasRole('administrador-costura') && $reciboCompletadoCostura)
                                                <span class="badge-completado-costura is-on">COMPLETADO</span>
                                            @endif
                                        </div>
                                        <!-- Badge completado para costurero - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']) && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Badge completado para costura-reflectivo - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasRole('costura-reflectivo') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Badge completado para lider-reflectivo - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasRole('lider-reflectivo') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Badge completado para administrador-costura - posicionado en esquina superior derecha solo en mobile -->
                                        @if(auth()->user()->hasRole('administrador-costura') && $reciboCompletadoCostura)
                                            <span class="badge-completado-costura is-on mobile-top-right">COMPLETADO</span>
                                        @endif
                                        <!-- Boton de mas opciones para mobile -->
                                        <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] }})">
                                            <span class="material-symbols-rounded">more_horiz</span>
                                        </button>
                                    </div>

                                    <div class="orden-cliente">
                                        <p class="cliente-label">{{ (isset($prenda['recibos'][0]) && strtoupper((string)($prenda['recibos'][0]['tipo_recibo'] ?? '')) === 'CORTE-PARA-BODEGA') ? 'SERVICIO' : 'CLIENTE' }}</p>
                                        <p class="cliente-name">{{ $prenda['cliente'] }}</p>
                                    </div>
                                    <div class="dashboard-search-area-hint" style="display: none;">
                                        <span class="material-symbols-rounded">location_on</span>
                                        <span>EN AREA: {{ strtoupper(trim((string) ($reciboPrincipalCard['area'] ?? 'SIN AREA'))) }}</span>
                                    </div>

                                    @if(auth()->user()->hasRole('lider-reflectivo'))
                                        <div class="lider-encargado-mobile" onclick="event.stopPropagation();">
                                            <span class="lider-encargado-mobile-label">Encargado</span>
                                            <span class="lider-encargado-mobile-value" data-visible-filtro="costura">{{ $textoEncargadoCosturaCard }}</span>
                                            <span class="lider-encargado-mobile-value" data-visible-filtro="reflectivo" style="display: none;">{{ $textoEncargadoReflectivoCard }}</span>
                                        </div>
                                    @endif

                                    <!-- Boton Ver Recibo (debajo del estado para mobile) -->
                                    <div class="mobile-ver-recibo-section">
                                        @component('components.botones.ver-recibo', [
                                            'numeroPedido' => $prenda['numero_pedido'],
                                            'prendaId' => $prenda['prenda_id'],
                                            'nombrePrenda' => addslashes((string)$prenda['nombre_prenda']),
                                            'tipoRecibo' => $tipoReciboPreferido,
                                            'idParcial' => $parcialIdPreferido,
                                            'consecutivo' => $consecutivoPreferido,
                                            'reciboId' => $reciboParaBusqueda['id'] ?? null,
                                            'clase' => 'mobile-under-state',
                                        ])@endcomponent
                                    </div>

                                    <div class="orden-prendas">
                                        <p class="prendas-label">
                                            <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                            @if($prenda['descripcion'])
                                                @php
                                                    $descripcionOperarioRaw = (string) ($prenda['descripcion'] ?? '');
                                                    $descripcionOperarioNormalizada = preg_replace("/\r\n?/", "\n", $descripcionOperarioRaw);
                                                    $descripcionOperarioPermitida = strip_tags($descripcionOperarioNormalizada, '<span><br>');
                                                    $descripcionOperarioRender = nl2br($descripcionOperarioPermitida, false);
                                                @endphp
                                                {!! $descripcionOperarioRender !!}
                                            @endif
                                        </p>        
                                    </div>

                                    <!-- Contenedor de Botones -->
                                    <div class="orden-buttons">
                                        @if(auth()->user()->hasRole('cortador') || auth()->user()->hasRole('visualizador_plooter'))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCorteRecibo = $areaRecibo === 'corte';
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                // Usar 'id' como clave principal, pero si no existe, intentar con otras claves
                                                $reciboId = $reciboPrincipal['id'] 
                                                    ?? $reciboPrincipal['recibo_id'] 
                                                    ?? $reciboPrincipal['consecutivo_actual'] 
                                                    ?? null;
                                            @endphp

                                            {{-- Boton para cortadores: Marcar como completado (pasa a Costura) --}}
                                            @if($esCorteRecibo && $reciboId)
                                                <button class="btn-completar-corte" 
                                                        id="btn-completar-{{ $prenda['prenda_id'] }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarCorte(this)">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    MARCAR COMPLETADO
                                                </button>
                                            @endif

                                            {{-- Boton para cortadores: Deshacer (regresa a Corte) --}}
                                            @if($esCosturaRecibo && $reciboId)
                                                <button class="btn-deshacer-corte" 
                                                        id="btn-deshacer-{{ $prenda['prenda_id'] }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerCorte(this)">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboAccionId = $reciboPrincipal['id'] ?? null;
                                                $esReciboParcial = false;
                                                $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                            @endphp

                                            {{-- Boton para costureros: Marcar como completado (sin cambiar de Area) --}}
                                            @if($esCosturaRecibo && $reciboAccionId && !$reciboCompletadoCostura)
                                                <button class="btn-completar-costura" 
                                                        type="button"
                                                        id="btn-completar-costura-{{ $prenda['prenda_id'] }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboAccionId }}"
                                                        data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarCostura(this); return false;">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    COMPLETAR
                                                </button>
                                            @endif

                                            {{-- Boton para costureros: Deshacer (regresa a pendiente) --}}
                                            @if($esCosturaRecibo && $reciboAccionId && $reciboCompletadoCostura)
                                                <button class="btn-deshacer-costura" 
                                                        type="button"
                                                        id="btn-deshacer-costura-{{ $prenda['prenda_id'] }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboAccionId }}"
                                                        data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerCostura(this); return false;">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        {{-- Bloque para administrador-costura en pestaña sobremedida: Completar recibos en Corte --}}
                                        @if(auth()->user()->hasRole('administrador-costura') && ($tab ?? 'costura') === 'sobremedida')
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCorteRecibo = $areaRecibo === 'corte';
                                                $reciboId = $reciboPrincipal['id'] 
                                                    ?? $reciboPrincipal['recibo_id'] 
                                                    ?? $reciboPrincipal['consecutivo_actual'] 
                                                    ?? null;
                                                $reciboCompletadoCorte = (bool) ($reciboPrincipal['completado_corte'] ?? false);
                                            @endphp

                                            {{-- Boton para administrador-costura: Completar recibo en Corte (pasa a Costura) --}}
                                            @if($esCorteRecibo && $reciboId && !$reciboCompletadoCorte)
                                                <button class="btn-completar-corte" 
                                                        id="btn-completar-corte-sobremedida-{{ $prenda['prenda_id'] }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarReciboCorteSobremedida(this)">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    COMPLETAR CORTE
                                                </button>
                                            @endif

                                            {{-- Boton para administrador-costura: Deshacer (regresa a Corte) --}}
                                            @if($esCorteRecibo && $reciboId && $reciboCompletadoCorte)
                                                <button class="btn-deshacer-corte" 
                                                        id="btn-deshacer-corte-sobremedida-{{ $prenda['prenda_id'] }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerReciboCorteSobremedida(this)">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        @if(auth()->user()->hasRole('vista-costura'))
                                            @foreach($prenda['recibos'] ?? [] as $reciboItem)
                                                @php
                                                    if (strtoupper((string) ($reciboItem['tipo_recibo'] ?? '')) !== 'COSTURA') {
                                                        continue;
                                                    }

                                                    $reciboId = $reciboItem['id'] ?? null;
                                                    $tieneParciales = $reciboItem['tiene_parciales'] ?? false;
                                                    $areaActual = $reciboItem['area'] ?? null;
                                                    $procesoId = $reciboItem['proceso_id_costura'] ?? null;
                                                    $encargadoCostura = $reciboItem['encargado_costura'] ?? null;
                                                    $consecutivoActual = $reciboItem['consecutivo_actual'] ?? $prenda['numero_pedido'];

                                                    $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                                    $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                                    $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                                    $tieneEncargadoCostura = !empty($encargadoCostura);
                                                    $mostrarComoDeshacerCostura = ($esCosturaProceso && $tieneEncargadoCostura && !$tieneParciales);
                                                @endphp

                                                {{-- Boton "Pasar a Costura" o "DESHACER COSTURA" (NO si hay parciales) --}}
                                                @if(!$tieneParciales)
                                                    <button type="button" class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}"
                                                            data-visible-filtro="costura"
                                                            id="btn-costura-{{ $prenda['prenda_id'] }}-{{ $consecutivoActual }}"
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-numero-pedido="{{ $prenda['numero_pedido'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="COSTURA"
                                                            data-recibo="{{ $consecutivoActual }}"
                                                            data-area="{{ $areaActual ?? '' }}"
                                                            data-proceso-id="{{ $procesoId }}"
                                                            data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                            data-parcial-id="{{ $reciboItem['pedido_parcial_id'] ?? '' }}"
                                                            onclick="manejarPasarACostura(this); return false;">
                                                        <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                        {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                                    </button>

                                                    {{-- Boton "Pasar a C.C" o "DESHACER" (NO si hay parciales) --}}
                                                    <button class="btn-pasar-cc"
                                                            data-visible-filtro="costura"
                                                            id="btn-cc-{{ $prenda['prenda_id'] }}-{{ $consecutivoActual }}"
                                                            data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                            data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                            data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                            data-tipo-recibo="COSTURA"
                                                            data-recibo="{{ $consecutivoActual }}"
                                                            data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                            data-proceso-id="{{ $procesoId }}"
                                                            type="button"
                                                            onclick="pasarAControlCalidad(this); return false;">
                                                        <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                                        {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                                    </button>
                                                @endif

                                                {{-- Boton "Ver Distribución" para vista-costura (solo si hay parciales) --}}
                                                @if($reciboId && $tieneParciales)
                                                    @component('components.botones.ver-distribucion', [
                                                        'filtro' => 'costura',
                                                        'prendaId' => $prenda['prenda_id'],
                                                        'reciboId' => $reciboId,
                                                        'numeroRecibo' => $consecutivoActual,
                                                        'tipoRecibo' => 'COSTURA',
                                                    ])@endcomponent
                                                    @component('components.botones.editar-encargados', [
                                                        'filtro' => 'costura',
                                                        'prendaId' => $prenda['prenda_id'],
                                                        'reciboId' => $reciboId,
                                                        'pedidoId' => $prenda['pedido_id'],
                                                        'numeroPedido' => $prenda['numero_pedido'],
                                                        'numeroRecibo' => $consecutivoActual,
                                                        'nombrePrenda' => $prenda['nombre_prenda'],
                                                        'tipoRecibo' => 'COSTURA',
                                                    ])@endcomponent
                                                @endif
                                            @endforeach
                                        @endif

                                        @component('components.botones.agregar-novedad', [
                                            'numeroPedido' => $prenda['numero_pedido'],
                                            'prendaId' => $prenda['prenda_id'],
                                            'nombrePrenda' => $prenda['nombre_prenda'],
                                            'consecutivo' => isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'],
                                        ])@endcomponent
                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura'))
                                            @php
                                                $reciboReflectivo = seleccionarReciboParaVistaOperario(
                                                    $prenda['recibos'] ?? [],
                                                    'REFLECTIVO'
                                                );
                                                $tieneReciboReflectivo = !empty($reciboReflectivo);
                                                $reciboReflectivoId = $reciboReflectivo['id'] ?? null;
                                                $tieneParcialesReflectivo = $reciboReflectivo['tiene_parciales'] ?? false;
                                                $encargadoCosturaRef = $reciboReflectivo['encargado_costura'] ?? null;
                                                $encargadoCosturaRef = is_string($encargadoCosturaRef) ? trim($encargadoCosturaRef) : $encargadoCosturaRef;
                                                $tieneEncargadoCosturaRef = !empty($encargadoCosturaRef);
                                                $areaReciboRef = $reciboReflectivo['area'] ?? '';
                                                $esCosturaAreaRef = strtolower(trim((string) $areaReciboRef)) === 'costura';
                                                $esControlCalidadRef = in_array(strtolower(trim((string) $areaReciboRef)), ['control calidad', 'control de calidad'], true);
                                            @endphp

                                            {{-- Boton PASAR A COSTURA/DESHACER COSTURA para vista-costura --}}
                                                @if($tieneReciboReflectivo && auth()->user()->hasRole('vista-costura'))
                                                    @php
                                                        $pedidoParcialId = isset($reciboReflectivo['pedido_parcial_id']) ? (int) $reciboReflectivo['pedido_parcial_id'] : 0;
                                                        $consecutivoParcial = $reciboReflectivo['consecutivo_parcial'] ?? ($reciboReflectivo['consecutivo_actual'] ?? null);
                                                        $esReciboReflectivoParcial = false;
                                                        $reciboReflectivoAccionId = $reciboReflectivoId;
                                                    @endphp

                                                    {{-- Boton VER RECIBO para vista-costura --}}
                                                    @component('components.botones.ver-recibo', [
                                                        'numeroPedido' => $prenda['numero_pedido'],
                                                        'prendaId' => $prenda['prenda_id'],
                                                        'nombrePrenda' => $prenda['nombre_prenda'],
                                                        'tipoRecibo' => 'REFLECTIVO',
                                                        'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                        'consecutivo' => $consecutivoParcial ?? '',
                                                        'reciboId' => $reciboReflectivo['id'] ?? null,
                                                    ])@endcomponent

                                                    @if($reciboReflectivoId && $tieneParcialesReflectivo)
                                                        @component('components.botones.ver-distribucion', [
                                                            'filtro' => 'reflectivo',
                                                            'prendaId' => $prenda['prenda_id'],
                                                            'reciboId' => $reciboReflectivoId,
                                                            'numeroRecibo' => $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'],
                                                            'tipoRecibo' => 'REFLECTIVO',
                                                        ])@endcomponent
                                                        @component('components.botones.editar-encargados', [
                                                            'filtro' => 'reflectivo',
                                                            'prendaId' => $prenda['prenda_id'],
                                                            'reciboId' => $reciboReflectivoId,
                                                            'pedidoId' => $prenda['pedido_id'],
                                                            'numeroPedido' => $prenda['numero_pedido'],
                                                            'numeroRecibo' => $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'],
                                                            'nombrePrenda' => $prenda['nombre_prenda'],
                                                            'tipoRecibo' => 'REFLECTIVO',
                                                        ])@endcomponent
                                                    @endif

                                                    @if(!$tieneParcialesReflectivo)
                                                        <button class="btn-pasar-costura {{ $tieneEncargadoCosturaRef ? 'btn-deshacer-costura' : '' }}" 
                                                                data-visible-filtro="reflectivo"
                                                                id="btn-costura-reflectivo-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-numero-pedido="{{ $prenda['numero_pedido'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="REFLECTIVO"
                                                                data-recibo="{{ $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'] }}"
                                                                data-area="{{ $areaReciboRef }}"
                                                                data-proceso-id="{{ $reciboReflectivo['proceso_id_costura'] ?? '' }}"
                                                                data-encargado-costura="{{ $encargadoCosturaRef ?? '' }}"
                                                                type="button"
                                                                onclick="manejarPasarACostura(this); return false;">
                                                            <span class="material-symbols-rounded">{{ $tieneEncargadoCosturaRef ? 'undo' : 'checkroom' }}</span>
                                                            {{ $tieneEncargadoCosturaRef ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                                        </button>

                                                            @if(!$esControlCalidadRef)
                                                                <button class="btn-pasar-cc" 
                                                                        data-visible-filtro="reflectivo"
                                                                        id="btn-cc-reflectivo-{{ $prenda['prenda_id'] }}"
                                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                        data-tipo-recibo="REFLECTIVO"
                                                                        data-recibo="{{ $reciboReflectivo['consecutivo_actual'] ?? $prenda['numero_pedido'] }}"
                                                                        data-area="{{ $areaReciboRef ?? 'REFLECTIVO' }}"
                                                                        data-proceso-id="{{ $reciboReflectivo['proceso_id'] ?? '' }}"
                                                                        type="button"
                                                                        onclick="pasarAControlCalidad(this); return false;">
                                                                    <span class="material-symbols-rounded">check_circle</span>
                                                                    PASAR A C.C
                                                                </button>
                                                            @endif
                                                    @endif
                                                @endif

                                            {{-- Botones de completar/deshacer para REFLECTIVO (solo para costura-reflectivo y lider-reflectivo) --}}
                                            @if($tieneReciboReflectivo && (auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo')))
                                                @php
                                                    $reciboId = $reciboReflectivo['id'] ?? null;
                                                    $pedidoParcialId = isset($reciboReflectivo['pedido_parcial_id']) ? (int) $reciboReflectivo['pedido_parcial_id'] : 0;
                                                    $consecutivoParcial = $reciboReflectivo['consecutivo_parcial'] ?? ($reciboReflectivo['consecutivo_actual'] ?? null);
                                                    $reciboCompletadoArea = false;

                                                    // Verificar si esta completado segun el Area
                                                    if ($esCosturaAreaRef) {
                                                        $reciboCompletadoArea = (bool) ($reciboReflectivo['completado_costura'] ?? false);
                                                    } else {
                                                        $reciboCompletadoArea = (bool) ($reciboReflectivo['completado_area'] ?? false);
                                                    }

                                                    $tieneEncargadoAsignado = false;

                                                    // Para REFLECTIVO: verificar que tenga encargado asignado
                                                    $encargadoReflectivo = $reciboReflectivo['encargado_costura'] ?? null;
                                                    $encargadoReflectivo = is_string($encargadoReflectivo) ? trim($encargadoReflectivo) : $encargadoReflectivo;
                                                    $tieneEncargadoAsignado = !empty($encargadoReflectivo);

                                                    // Para administrador-costura: siempre permitir
                                                    if (auth()->user()->hasRole('administrador-costura')) {
                                                        $tieneEncargadoAsignado = true;
                                                    }

                                                    $tipoReciboNormalizado = strtolower('REFLECTIVO');
                                                    $reciboReflectivoAccionId = $reciboReflectivo['id'] ?? ($reciboReflectivo['pedido_parcial_id'] ?? null);
                                                    $esReciboReflectivoParcial = !empty($reciboReflectivo['es_parcial']);
                                                @endphp

                                                {{-- Boton VER RECIBO para REFLECTIVO --}}
                                                @component('components.botones.ver-recibo', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'tipoRecibo' => 'REFLECTIVO',
                                                    'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                    'consecutivo' => $consecutivoParcial ?? '',
                                                    'reciboId' => $reciboReflectivo['id'] ?? null,
                                                ])@endcomponent

                                                @if($reciboReflectivoAccionId && $esCosturaAreaRef && $tieneEncargadoAsignado)
                                                    @if(!$reciboCompletadoArea)
                                                        <button class="btn-completar-costura" 
                                                                type="button"
                                                                id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboReflectivoAccionId }}"
                                                                data-es-parcial="{{ $esReciboReflectivoParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="completarCostura(this); return false;">
                                                            <span class="material-symbols-rounded">check_circle</span>
                                                            COMPLETAR {{ strtoupper('REFLECTIVO') }}
                                                        </button>
                                                    @else
                                                        <button class="btn-deshacer-costura" 
                                                                type="button"
                                                                id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboReflectivoAccionId }}"
                                                                data-es-parcial="{{ $esReciboReflectivoParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                onclick="deshacerCostura(this); return false;">
                                                            <span class="material-symbols-rounded">undo</span>
                                                            DESHACER {{ strtoupper('REFLECTIVO') }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endif
                                        @endif
                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura'))
                                            {{-- Para costura-reflectivo/lider-reflectivo/vista-costura/administrador-costura, crear un Boton por cada TIPO de recibo (sin duplicados) --}}
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    // Omitir REFLECTIVO porque ya tiene su propio bloque arriba
                                                    if (strtoupper($tipoReciboUnico) === 'REFLECTIVO') {
                                                        continue;
                                                    }

                                                    $reciboTipo = seleccionarReciboParaVistaOperario(
                                                        $prenda['recibos'] ?? [],
                                                        (string) $tipoReciboUnico,
                                                        auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                    );
                                                    if (!$reciboTipo) {
                                                        continue;
                                                    }
                                                    $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                                    $consecutivoParcial = $reciboTipo['consecutivo_parcial'] ?? ($reciboTipo['consecutivo_actual'] ?? null);
                                                @endphp
                                                @component('components.botones.ver-recibo', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'tipoRecibo' => strtoupper((string) $tipoReciboUnico),
                                                    'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                    'consecutivo' => $consecutivoParcial ?? '',
                                                    'reciboId' => $reciboTipo['recibo_id'] ?? ($reciboTipo['id'] ?? null),
                                                ])@endcomponent
                                            @endforeach

                                            {{-- Botones de completar/deshacer para costura-reflectivo, lider-reflectivo y administrador-costura --}}
                                            @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('administrador-costura'))
                                                @php
                                                    $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                                @endphp
                                                @foreach($tiposUnicos as $tipoReciboUnico)
                                                    @php
                                                        // Omitir REFLECTIVO porque ya tiene su propio bloque arriba
                                                        if (strtoupper($tipoReciboUnico) === 'REFLECTIVO') {
                                                            continue;
                                                        }

                                                        $reciboTipo = seleccionarReciboParaVistaOperario(
                                                            $prenda['recibos'] ?? [],
                                                            (string) $tipoReciboUnico,
                                                            auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                        );
                                                        $reciboAccionId = $reciboTipo['id'] ?? ($reciboTipo['pedido_parcial_id'] ?? null);
                                                        $esReciboParcial = !empty($reciboTipo['es_parcial']);
                                                        $areaRecibo = strtolower(trim((string) ($reciboTipo['area'] ?? '')));
                                                        $esCosturaArea = $areaRecibo === 'costura';
                                                        $reciboCompletadoArea = false;

                                                        // Verificar si esta completado segun el Area
                                                        if ($esCosturaArea) {
                                                            $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                        } else {
                                                            $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                        }

                                                        // Para COSTURA: verificar que el encargado tenga rol costura-reflectivo si es lider-reflectivo
                                                        // Para costura-reflectivo y administrador-costura: permitir siempre
                                                        $tieneEncargadoAsignado = false;
                                                        $esLiderReflectivo = auth()->user()->hasRole('lider-reflectivo');

                                                        if ($esLiderReflectivo) {
                                                            $encargadoCostura = $reciboTipo['encargado_costura'] ?? null;
                                                            $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                                            if (!empty($encargadoCostura)) {
                                                                $encargadoUsuario = \App\Models\User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($encargadoCostura)])->first();
                                                                $tieneEncargadoAsignado = $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');
                                                            }
                                                        } else {
                                                            // Para costura-reflectivo y administrador-costura: permitir siempre
                                                            $tieneEncargadoAsignado = true;
                                                        }

                                                        $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                                    @endphp

                                                    @if($reciboAccionId && $esCosturaArea && $tieneEncargadoAsignado)
                                                        @if(!$reciboCompletadoArea)
                                                            <button class="btn-completar-costura" 
                                                                    type="button"
                                                                    id="btn-completar-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                    data-recibo-id="{{ $reciboAccionId }}"
                                                                    data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                    data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                     onclick="completarCostura(this); return false;">
                                                                <span class="material-symbols-rounded">check_circle</span>
                                                                COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                            </button>
                                                        @else
                                                            <button class="btn-deshacer-costura" 
                                                                    type="button"
                                                                    id="btn-deshacer-{{ $tipoReciboNormalizado }}-{{ $prenda['prenda_id'] }}"
                                                                    data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                    data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                    data-recibo-id="{{ $reciboAccionId }}"
                                                                    data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                    data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                    data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                     onclick="deshacerCostura(this); return false;">
                                                                <span class="material-symbols-rounded">undo</span>
                                                                DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                            </button>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            @endif
                                        @else
                                            {{-- Para otros operarios, un solo Boton con tipo de recibo --}}
                                            @component('components.botones.ver-recibo', [
                                                'numeroPedido' => $prenda['numero_pedido'],
                                                'prendaId' => $prenda['prenda_id'],
                                                'nombrePrenda' => $prenda['nombre_prenda'],
                                                'tipoRecibo' => $tipoReciboPreferido,
                                                'idParcial' => $parcialIdPreferido,
                                                'consecutivo' => $consecutivoPreferido,
                                                'reciboId' => $reciboParaBusqueda['recibo_id'] ?? ($reciboParaBusqueda['id'] ?? null),
                                                'texto' => 'VER RECIBOS',
                                            ])@endcomponent
                                        @endif
                                    </div>

                                    <!-- Mobile Actions Drawer -->
                                    <div class="mobile-actions-drawer" id="mobile-drawer-{{ $prenda['prenda_id'] }}">
                                        @if(auth()->user()->hasRole('cortador'))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCorteRecibo = $areaRecibo === 'corte';
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboId = $reciboPrincipal['id'] 
                                                    ?? $reciboPrincipal['recibo_id'] 
                                                    ?? $reciboPrincipal['consecutivo_actual'] 
                                                    ?? null;
                                            @endphp

                                            {{-- Boton para cortadores: Marcar como completado (pasa a Costura) --}}
                                            @if($esCorteRecibo && $reciboId)
                                                <button class="btn-completar-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarCorte(this)">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    MARCAR COMPLETADO
                                                </button>
                                            @endif

                                            {{-- Boton para cortadores: Deshacer (regresa a Corte) --}}
                                            @if($esCosturaRecibo && $reciboId)
                                                <button class="btn-deshacer-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerCorte(this)">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        @if(auth()->user()->hasAnyRole(['costurero', 'confeccion-sobremedida']))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboAccionId = $reciboPrincipal['id'] ?? null;
                                                $esReciboParcial = false;
                                                $reciboCompletadoCostura = (bool) ($reciboPrincipal['completado_costura'] ?? false);
                                            @endphp

                                            {{-- Boton para costureros: Marcar como completado (sin cambiar de Area) --}}
                                            @if($esCosturaRecibo && $reciboAccionId && !$reciboCompletadoCostura)
                                                <button class="btn-completar-costura" 
                                                        type="button"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboAccionId }}"
                                                        data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                         onclick="completarCostura(this); return false;">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    COMPLETAR
                                                </button>
                                            @endif

                                            {{-- Boton para costureros: Deshacer (regresa a pendiente) --}}
                                            @if($esCosturaRecibo && $reciboAccionId && $reciboCompletadoCostura)
                                                <button class="btn-deshacer-costura" 
                                                        type="button"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboAccionId }}"
                                                        data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                         onclick="deshacerCostura(this); return false;">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @endif
                                        @endif

                                        {{-- Botones mobile para costura-reflectivo, lider-reflectivo, administrador-costura --}}
                                        @if(auth()->user()->hasAnyRole(['costura-reflectivo', 'lider-reflectivo', 'administrador-costura']))
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    $reciboTipo = seleccionarReciboParaVistaOperario(
                                                        $prenda['recibos'] ?? [],
                                                        (string) $tipoReciboUnico,
                                                        auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                    );
                                                    $reciboAccionId = $reciboTipo['id'] ?? null;
                                                    $esReciboParcial = false;
                                                    $areaRecibo = strtolower(trim((string) ($reciboTipo['area'] ?? '')));
                                                    $esCosturaArea = $areaRecibo === 'costura';
                                                    $reciboCompletadoArea = false;

                                                    // Verificar si esta completado segun el Area
                                                    if ($esCosturaArea) {
                                                        $reciboCompletadoArea = (bool) ($reciboTipo['completado_costura'] ?? false);
                                                    } else {
                                                        $reciboCompletadoArea = (bool) ($reciboTipo['completado_area'] ?? false);
                                                    }

                                                    $tipoReciboNormalizado = strtolower($tipoReciboUnico);
                                                @endphp

                                                @if($reciboAccionId && $esCosturaArea)
                                                    @if(!$reciboCompletadoArea)
                                                        <button class="btn-completar-costura" 
                                                                type="button"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboAccionId }}"
                                                                data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                 onclick="completarCostura(this); return false;">
                                                            <span class="material-symbols-rounded">check_circle</span>
                                                            COMPLETAR {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @else
                                                        <button class="btn-deshacer-costura" 
                                                                type="button"
                                                                data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                                data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                                data-recibo-id="{{ $reciboAccionId }}"
                                                                data-es-parcial="{{ $esReciboParcial ? '1' : '0' }}"
                                                                data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                                data-tipo-recibo="{{ $tipoReciboNormalizado }}"
                                                                 onclick="deshacerCostura(this); return false;">
                                                            <span class="material-symbols-rounded">undo</span>
                                                            DESHACER {{ strtoupper($tipoReciboUnico) }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif

                                        @if(auth()->user()->hasRole('vista-costura'))
                                            @foreach($prenda['recibos'] ?? [] as $reciboItem)
                                                @php
                                                    if (strtoupper((string) ($reciboItem['tipo_recibo'] ?? '')) !== 'COSTURA') {
                                                        continue;
                                                    }

                                                    $areaActual = $reciboItem['area'] ?? null;
                                                    $procesoId = $reciboItem['proceso_id_costura'] ?? null;
                                                    $encargadoCostura = $reciboItem['encargado_costura'] ?? null;
                                                    $consecutivoActual = $reciboItem['consecutivo_actual'] ?? $prenda['numero_pedido'];

                                                    $esCC = in_array(strtolower(trim($areaActual ?? '')), ['control calidad', 'control de calidad']);
                                                    $esCosturaProceso = strtolower(trim($areaActual ?? '')) === 'costura';
                                                    $encargadoCostura = is_string($encargadoCostura) ? trim($encargadoCostura) : $encargadoCostura;
                                                    $tieneEncargadoCostura = !empty($encargadoCostura);
                                                    $mostrarComoDeshacerCostura = $esCosturaProceso && $tieneEncargadoCostura;
                                                @endphp

                                                {{-- Boton "Pasar a Costura" o "DESHACER COSTURA" solo para recibos tipo COSTURA --}}
                                                <button type="button" class="btn-pasar-costura {{ $mostrarComoDeshacerCostura ? 'btn-deshacer-costura' : '' }}"
                                                        id="btn-costura-{{ $prenda['prenda_id'] }}-{{ $consecutivoActual }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        data-tipo-recibo="COSTURA"
                                                        data-recibo="{{ $consecutivoActual }}"
                                                        data-area="{{ $areaActual ?? '' }}"
                                                        data-proceso-id="{{ $procesoId }}"
                                                        data-encargado-costura="{{ is_string($encargadoCostura ?? null) ? trim($encargadoCostura) : ($encargadoCostura ?? '') }}"
                                                        data-parcial-id="{{ $reciboItem['pedido_parcial_id'] ?? '' }}"
                                                        onclick="manejarPasarACostura(this); return false;">
                                                    <span class="material-symbols-rounded">{{ $mostrarComoDeshacerCostura ? 'undo' : 'checkroom' }}</span>
                                                    {{ $mostrarComoDeshacerCostura ? 'DESHACER COSTURA' : 'PASAR A COSTURA' }}
                                                </button>

                                                {{-- Boton "Pasar a C.C" o "DESHACER" --}}
                                                <button type="button" class="btn-pasar-cc"
                                                        id="btn-cc-{{ $prenda['prenda_id'] }}-{{ $consecutivoActual }}"
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        data-tipo-recibo="COSTURA"
                                                        data-recibo="{{ $consecutivoActual }}"
                                                        data-area="{{ $areaActual ?? 'COSTURA' }}"
                                                        data-proceso-id="{{ $procesoId }}"
                                                        onclick="pasarAControlCalidad(this); return false;">
                                                    <span class="material-symbols-rounded">{{ $esCC ? 'undo' : 'check_circle' }}</span>
                                                    {{ $esCC ? 'DESHACER' : 'PASAR A C.C' }}
                                                </button>
                                            @endforeach
                                        @endif

                                        @if(auth()->user()->hasRole('costura-reflectivo') || auth()->user()->hasRole('lider-reflectivo') || auth()->user()->hasRole('vista-costura') || auth()->user()->hasRole('administrador-costura'))
                                            @php
                                                $tiposUnicos = collect($prenda['recibos'])->pluck('tipo_recibo')->map(fn($t) => strtoupper($t))->unique()->values();
                                            @endphp
                                            @foreach($tiposUnicos as $tipoReciboUnico)
                                                @php
                                                    $reciboTipo = seleccionarReciboParaVistaOperario(
                                                        $prenda['recibos'] ?? [],
                                                        (string) $tipoReciboUnico,
                                                        auth()->user()->hasAnyRole(['vista-costura', 'administrador-costura'])
                                                    );
                                                    if (!$reciboTipo) {
                                                        continue;
                                                    }
                                                    $pedidoParcialId = $reciboTipo['pedido_parcial_id'] ?? null;
                                                    $consecutivoParcial = $reciboTipo['consecutivo_parcial'] ?? ($reciboTipo['consecutivo_actual'] ?? null);
                                                @endphp
                                                @component('components.botones.ver-recibo', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'tipoRecibo' => strtoupper((string) $tipoReciboUnico),
                                                    'idParcial' => $pedidoParcialId ? (int) $pedidoParcialId : null,
                                                    'consecutivo' => $consecutivoParcial ?? '',
                                                    'reciboId' => $reciboTipo['recibo_id'] ?? ($reciboTipo['id'] ?? null),
                                                ])@endcomponent
                                            @endforeach
                                        @else
                                            @component('components.botones.ver-recibo', [
                                                'numeroPedido' => $prenda['numero_pedido'],
                                                'prendaId' => $prenda['prenda_id'],
                                                'nombrePrenda' => $prenda['nombre_prenda'],
                                                'tipoRecibo' => $tipoReciboPreferido,
                                                'idParcial' => $parcialIdPreferido,
                                                'consecutivo' => $consecutivoPreferido,
                                                'reciboId' => $reciboParaBusqueda['recibo_id'] ?? ($reciboParaBusqueda['id'] ?? null),
                                                'texto' => 'VER RECIBOS',
                                            ])@endcomponent
                                        @endif

                                        @if(auth()->user()->hasRole('cortador'))
                                            @php
                                                $reciboPrincipal = $prenda['recibos'][0] ?? null;
                                                $areaRecibo = strtolower(trim((string) ($reciboPrincipal['area'] ?? '')));
                                                $esCorteRecibo = $areaRecibo === 'corte';
                                                $esCosturaRecibo = $areaRecibo === 'costura';
                                                $reciboId = $reciboPrincipal['id'] 
                                                    ?? $reciboPrincipal['recibo_id'] 
                                                    ?? $reciboPrincipal['consecutivo_actual'] 
                                                    ?? null;
                                            @endphp

                                            @if($esCorteRecibo && $reciboId)
                                                <button class="btn-completar-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="completarCorte(this)">
                                                    <span class="material-symbols-rounded">check_circle</span>
                                                    MARCAR COMPLETADO
                                                </button>
                                            @elseif($esCosturaRecibo && $reciboId)
                                                <button class="btn-deshacer-corte" 
                                                        data-pedido-id="{{ $prenda['pedido_id'] }}"
                                                        data-prenda-id="{{ $prenda['prenda_id'] }}"
                                                        data-recibo-id="{{ $reciboId }}"
                                                        data-nombre="{{ $prenda['nombre_prenda'] }}"
                                                        onclick="deshacerCorte(this)">
                                                    <span class="material-symbols-rounded">undo</span>
                                                    DESHACER
                                                </button>
                                            @else
                                                @component('components.botones.agregar-novedad', [
                                                    'numeroPedido' => $prenda['numero_pedido'],
                                                    'prendaId' => $prenda['prenda_id'],
                                                    'nombrePrenda' => $prenda['nombre_prenda'],
                                                    'consecutivo' => isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'],
                                                ])@endcomponent
                                            @endif
                                        @else
                                        @component('components.botones.agregar-novedad', [
                                            'numeroPedido' => $prenda['numero_pedido'],
                                            'prendaId' => $prenda['prenda_id'],
                                            'nombrePrenda' => $prenda['nombre_prenda'],
                                            'consecutivo' => isset($prenda['recibos'][0]['consecutivo_actual']) ? $prenda['recibos'][0]['consecutivo_actual'] : $prenda['numero_pedido'],
                                        ])@endcomponent
                                    @endif
                                        <button class="mobile-actions-toggle" onclick="toggleMobileActions({{ $prenda['prenda_id'] ?? 'null' }})">
                                            <span class="material-symbols-rounded">more_horiz</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Contenido Derecho -->
                                <div class="orden-right">
                                    <div class="orden-right-center">
                                        <a href="#" class="action-arrow" onclick="abrirDetallesRecibos('{{ $prenda['numero_pedido'] }}', {{ $prenda['prenda_id'] ?? 'null' }}, '{{ $prenda['nombre_prenda'] }}', '{{ $tipoReciboPreferido }}', {{ $parcialIdPreferido ?? 'null' }}, '{{ $consecutivoPreferido }}', {{ $reciboParaBusqueda['recibo_id'] ?? ($reciboParaBusqueda['id'] ?? 'null') }}); return false;">
                                            <span class="material-symbols-rounded">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <p>No encontré coincidencias para "{{ $busquedaActual }}" en este tap.</p>
                                @else
                                    <p>No hay prendas con recibos de costura asignadas</p>
                                @endif
                            </div>
                        @endif
                    @endif
                @endif
            </div>

            @if($esVistaCostura && $prendasPaginadasVistaCostura && $prendasPaginadasVistaCostura->lastPage() > 1)
                @php
                    $desdeVistaCostura = (($prendasPaginadasVistaCostura->currentPage() - 1) * $prendasPaginadasVistaCostura->perPage()) + 1;
                    $hastaVistaCostura = min($prendasPaginadasVistaCostura->currentPage() * $prendasPaginadasVistaCostura->perPage(), $prendasPaginadasVistaCostura->total());
                    $conteoPaginaVistaCostura = max(0, $hastaVistaCostura - $desdeVistaCostura + 1);
                    $inicioBotones = max(1, $prendasPaginadasVistaCostura->currentPage() - 2);
                    $finBotones = min($prendasPaginadasVistaCostura->lastPage(), $inicioBotones + 4);
                    if (($finBotones - $inicioBotones) < 4) {
                        $inicioBotones = max(1, $finBotones - 4);
                    }
                @endphp
                <div id="dashboardPagination" class="dashboard-pagination-container">
                    <div class="pagination-info">
                        Mostrando {{ $conteoPaginaVistaCostura }} de {{ $prendasPaginadasVistaCostura->total() }} registros
                    </div>
                    <div class="pagination-buttons">
                        <a class="pagination-btn {{ $prendasPaginadasVistaCostura->onFirstPage() ? 'disabled' : '' }}"
                           href="{{ $prendasPaginadasVistaCostura->onFirstPage() ? '#' : $prendasPaginadasVistaCostura->previousPageUrl() }}">
                            <span class="material-symbols-rounded">chevron_left</span>
                        </a>
                        @for ($pagina = $inicioBotones; $pagina <= $finBotones; $pagina++)
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
            <div style="display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; border-radius: 50%; background: #fef3c7; margin: 0 auto 1rem; font-size: 2rem;">âš ï¸</div>
            <h3 id="modalConfirmacionTitulo" style="margin: 0 0 0.75rem 0; font-size: 1.25rem; font-weight: 700; color: #111827; text-align: center;">¿Eliminar novedad?</h3>
            <p id="modalConfirmacionTexto" style="margin: 0 0 1.5rem 0; color: #6b7280; text-align: center; line-height: 1.5; font-size: 0.95rem;">Esta acción no se puede deshacer.</p>
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
                    <textarea id="novedadDescripcionText" rows="5" style="width: 100%; padding: 0.9rem; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 0.95rem;" placeholder="Escribe tu novedad aquí­..."></textarea>
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
                    <p style="margin: 0.25rem 0 0 0; opacity: 0.9; font-size: 0.75rem;" id="modalSubtitulo">Seleccione el tipo de asignación</p>
                </div>
                <button type="button" onclick="cerrarModalCostura()" style="background: rgba(255,255,255,0.2); border: none; border-radius: 8px; padding: 0.5rem; cursor: pointer; color: white; transition: background 0.2s; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="font-size: 1.25rem;">close</span>
                </button>
            </div>

            <!-- Contenido principal -->
            <div id="modalMainContent" style="flex: 1; overflow-y: auto; padding: 1rem;">
                <!-- Opciones de Asignación -->
                <div id="opcionesAsignacion" style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Tipo de Asignación</h4>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                        <button type="button" id="btnModuloCompleto" onclick="seleccionarOpcionAsignacion('completo')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">inventory_2</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Módulo Completo</h5>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Asignar todas las prendas a un solo módulo</p>
                                </div>
                            </div>
                        </button>

                        <button type="button" id="btnDistribuirModulos" onclick="seleccionarOpcionAsignacion('distribuir')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">share</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Distribuir por Módulos</h5>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Repartir prendas entre múltiples módulos</p>
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
        display: none;
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

    /* Distribución por módulos - estilos */
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
