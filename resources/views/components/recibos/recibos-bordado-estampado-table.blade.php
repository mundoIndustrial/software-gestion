@php
    $tipoActivo = strtoupper((string) ($tipoFiltro ?? 'BORDADO'));
    if (!in_array($tipoActivo, ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO'], true)) {
        $tipoActivo = 'BORDADO';
    }
    $puedeGestionarCheckLogo = auth()->check() && auth()->user()->hasRole('visualizador_recibos_logo');

    // Usar los conteos del controller
    $conteosPorArea = $conteosPorArea ?? [
        'Corte' => 0,
        'Bordado' => 0,
        'Estampado' => 0,
        'Pendiente' => 0,
    ];

    // Obtener áreas disponibles (que tengan al menos un recibo)
    $areasDisponibles = array_filter($conteosPorArea, fn ($count) => $count > 0);
@endphp

<!-- Contenedor principal con escala 80% -->
<div class="recibos-vista-scale-80">

<!-- Contenedor de Filtros en Fila -->
<div class="recibos-filters-container">
    <!-- Sección Filtrar por Tipo de Recibo -->
    <div class="recibos-filter-section">
        <h3 class="recibos-filter-title">Filtrar por tipo de recibo</h3>
        <div class="recibos-tipo-tabs" id="recibosTipoTabs">
        <a class="recibos-tipo-tab {{ $tipoActivo === 'BORDADO' ? 'is-active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['tipo' => 'BORDADO', 'area' => request('area', ''), 'page' => 1]) }}">
            Bordado <span class="tab-count">{{ (int) ($conteoBordado ?? 0) }}</span>
        </a>
        <a class="recibos-tipo-tab {{ $tipoActivo === 'ESTAMPADO' ? 'is-active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['tipo' => 'ESTAMPADO', 'area' => request('area', ''), 'page' => 1]) }}">
            Estampado <span class="tab-count">{{ (int) ($conteoEstampado ?? 0) }}</span>
        </a>
        <a class="recibos-tipo-tab {{ $tipoActivo === 'DTF' ? 'is-active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['tipo' => 'DTF', 'area' => request('area', ''), 'page' => 1]) }}">
            DTF <span class="tab-count">{{ (int) ($conteoDtf ?? 0) }}</span>
        </a>
        <a class="recibos-tipo-tab {{ $tipoActivo === 'SUBLIMADO' ? 'is-active' : '' }}"
           href="{{ request()->fullUrlWithQuery(['tipo' => 'SUBLIMADO', 'area' => request('area', ''), 'page' => 1]) }}">
            Sublimado <span class="tab-count">{{ (int) ($conteoSublimado ?? 0) }}</span>
        </a>
    </div>
    </div>

    <!-- Sección Filtrar por Área -->
    <div class="recibos-filters-section">
        <h3 class="recibos-filter-title">Filtrar por área</h3>
        <div class="recibos-area-filters" id="recibosAreaFilters">
            <a class="recibos-area-filter {{ !request()->has('area') || request('area') === '' ? 'is-active' : '' }}"
               href="{{ request()->fullUrlWithQuery(['tipo' => $tipoActivo, 'area' => '', 'page' => 1]) }}">
                Todas las áreas <span class="area-count" style="margin-left: 4px; font-size: 10px; opacity: 0.8;">{{ array_sum($conteosPorArea) }}</span>
            </a>
            @foreach ($areasDisponibles as $area => $count)
                @php
                    // Mapeo de colores para áreas (pueden ser diferentes al valor normalizado)
                    $colorPorArea = [
                        'BORDANDO' => '#8b5cf6',
                        'BORDADO' => '#8b5cf6',
                        'ESTAMPANDO' => '#f59e0b',
                        'ESTAMPADO' => '#f59e0b',
                        'CORTE_Y_APLIQUE' => '#10b981',
                        'DISENO' => '#06b6d4',
                        'PENDIENTE' => '#06b6d4',
                        'PENDIENTE_CONFIRMAR' => '#06b6d4',
                        'PENDIENTE_DISENO' => '#06b6d4',
                        'ENTREGADO' => '#22c55e',
                        'ANULADO' => '#ef4444',
                        'HACIENDO_MUESTRA' => '#ec4899',
                        'CREACION_DE_ORDEN' => '#6b7280',
                        'BORD_POR_FUERA' => '#8b5cf6',
                    ];
                    $color = $colorPorArea[$area] ?? '#6b7280';
                    $areaLabel = ucfirst(strtolower(str_replace('_', ' ', $area)));
                @endphp
                <a class="recibos-area-filter {{ request('area') === $area ? 'is-active' : '' }}"
                   href="{{ request()->fullUrlWithQuery(['tipo' => $tipoActivo, 'area' => $area, 'page' => 1]) }}">
                    <span class="area-badge" style="background: {{ $color }}; color: white;">
                        {{ $areaLabel }} <span class="area-count" style="margin-left: 4px; font-size: 10px; opacity: 0.9;">{{ $count }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Tabla de Recibos de Bordado/Estampado -->
<div class="table-scroll-container" data-vista-tipo="bordado-estampado" style="height: 450px; overflow-y: auto;">
    <table class="table table-striped table-hover modern-table" data-vista-tipo="bordado-estampado">
        <thead class="table-header">
            <tr>
                <th class="acciones-column" style="width: 130px; min-width: 130px; text-align: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 12h8M12 8v8"></path>
                    </svg>
                </th>
                <th style="width: auto; min-width: 120px;"><span>Area</span></th>
                <th style="width: 120px;"><span>N Recibo</span></th>
                <th style="width: 120px; text-align: center;"><span>Tipo</span></th>
                <th style="width: 150px;"><span>Cliente</span></th>
                <th style="width: auto;"><span>Descripcion</span></th>
                <th style="width: 100px;"><span>Cantidad</span></th>
                <th style="width: 150px;"><span>Fecha de creacion</span></th>
            </tr>
        </thead>
        <tbody id="tablaRecibosBody">
            @if($recibos->count() > 0)
                @foreach($recibos as $recibo)
                    @php
                        $tipoRecibo = strtoupper((string) ($recibo['tipo_recibo'] ?? 'BORDADO'));
                        $esParcial = !empty($recibo['es_parcial']) || !empty($recibo['esParcial']);
                        $pedidoParcialId = $recibo['pedido_parcial_id'] ?? ($recibo['pedidoParcialId'] ?? ($recibo['parcial_id'] ?? ''));
                    @endphp
                    <tr data-orden-id="{{ $recibo['id'] }}"
                        data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                        data-numero-recibo="{{ $recibo['consecutivo_actual'] ?? '' }}"
                        data-tipo-recibo="{{ $tipoRecibo }}">

                        <td class="acciones-column" style="text-align: center; position: relative; width: 130px; min-width: 130px;">
                            @php
                                $checkLogo = (bool) ($recibo['check_logo_recibo'] ?? false);
                                $consecutivoReciboId = (int) ($recibo['consecutivo_recibo_id'] ?? 0);
                            @endphp
                            <div style="display:flex;align-items:center;justify-content:center;gap:8px;white-space:nowrap;min-width:100px;">
                                <button class="btn-ver-dropdown"
                                    title="Ver Opciones"
                                    data-menu-id="menu-recibo-{{ $recibo['id'] }}"
                                    data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                                    data-prenda-id="{{ $recibo['prenda_id'] ?? '' }}"
                                    data-numero-recibo="{{ $recibo['consecutivo_actual'] ?? '' }}"
                                    data-tipo-recibo="{{ $tipoRecibo }}"
                                    data-es-parcial="{{ $esParcial ? 'true' : 'false' }}"
                                    data-pedido-parcial-id="{{ $pedidoParcialId }}"
                                    data-recibo-id="{{ $recibo['id'] ?? '' }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($puedeGestionarCheckLogo)
                                    <button
                                        type="button"
                                        class="btn-check-recibo-logo {{ $checkLogo ? 'is-checked' : '' }}"
                                        title="Marcar recibido"
                                        data-consecutivo-recibo-id="{{ $consecutivoReciboId }}"
                                        data-checked="{{ $checkLogo ? '1' : '0' }}"
                                        style="width:30px;height:30px;border-radius:8px;border:1px solid {{ $checkLogo ? '#16a34a' : '#cbd5e1' }};background:{{ $checkLogo ? '#16a34a' : '#ffffff' }};color:{{ $checkLogo ? '#ffffff' : '#64748b' }};display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s ease;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                            </div>
                        </td>

                        <td>
                            @php
                                $areaRecibo = $recibo['area'] ?? 'Pendiente';
                                $areaBadge = 'bg-secondary';
                                if (strpos($areaRecibo, 'Corte') !== false) {
                                    $areaBadge = 'bg-success';
                                } elseif (strpos($areaRecibo, 'Estampado') !== false) {
                                    $areaBadge = 'bg-warning';
                                } elseif (strpos($areaRecibo, 'Bordado') !== false) {
                                    $areaBadge = 'bg-purple';
                                } elseif (strpos($areaRecibo, 'Pendiente') !== false) {
                                    $areaBadge = 'bg-info';
                                }
                            @endphp
                            <span class="badge {{ $areaBadge }}" style="display: inline-block;">
                                {{ $areaRecibo }}
                            </span>
                        </td>

                        <td style="text-align: center;">
                            <span style="font-weight: 600;">{{ $recibo['consecutivo_actual'] }}</span>
                        </td>

                        <td style="text-align: center;">
                            @php
                                $tipo = $tipoRecibo;
                                $tipoBadge = match ($tipo) {
                                    'BORDADO' => '#2563eb',
                                    'ESTAMPADO' => '#0f766e',
                                    'DTF' => '#7c3aed',
                                    'SUBLIMADO' => '#ea580c',
                                    default => '#475569',
                                };
                            @endphp
                            <span style="display:inline-block;padding:3px 8px;border-radius:999px;background:{{ $tipoBadge }};color:#fff;font-size:11px;font-weight:700;letter-spacing:.3px;">
                                {{ $tipo }}
                            </span>
                        </td>

                        <td style="text-align: center;">
                            @if($recibo['pedido_info'])
                                <span>{{ $recibo['pedido_info']['cliente'] }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>

                        <td data-descripcion-detallada="{{ $recibo['descripcion_detallada'] ?? '' }}">
                            @php
                                $nombreMostrar = $recibo['descripcion_detallada'] ?? '';
                                if (strlen($nombreMostrar) > 50) {
                                    $nombreMostrar = substr($nombreMostrar, 0, 47) . '...';
                                }
                            @endphp
                            <div class="table-cell" style="flex: 10;">
                                <div class="cell-content" style="justify-content: flex-start;">
                                    <span style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $nombreMostrar ?: 'Sin prenda' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td>
                            @if(isset($recibo['cantidad_total']) && $recibo['cantidad_total'] > 0)
                                <span style="font-weight: 600; color: #059669;">{{ $recibo['cantidad_total'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>
                            @if($recibo['pedido_info'] && isset($recibo['pedido_info']['fecha_creacion_orden']))
                                <span>{{ \Carbon\Carbon::parse($recibo['pedido_info']['fecha_creacion_orden'])->format('d/m/Y') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No se encontraron recibos de bordado/estampado.
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

@if($recibos instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="pagination-wrapper-compact" id="pagination-wrapper">
        {{ $recibos->appends(request()->query())->links('vendor.pagination.bootstrap-custom') }}
    </div>

    <script src="{{ asset('js/recibos-costura/pagination.js') }}?v={{ time() }}"></script>
@endif

</div> <!-- Cierre del contenedor principal recibos-vista-scale-80 -->

<script>
/**
 * Carga dinámica de áreas disponibles según el tipo de recibo
 * Cuando el usuario hace clic en un tab de tipo, se hace una llamada AJAX
 * para obtener las áreas disponibles para ese tipo
 */
(() => {
    const tipoTabs = document.querySelectorAll('.recibos-tipo-tab');
    
    tipoTabs.forEach(tab => {
        tab.addEventListener('click', async function(e) {
            // No prevenir el click, dejar que navegue normalmente
            // pero también cargar las áreas de forma silenciosa
            
            // Extraer el tipo de la URL del href
            const href = this.getAttribute('href');
            const urlParams = new URLSearchParams(new URL(href, window.location).search);
            const tipo = urlParams.get('tipo') || 'BORDADO';
            
            // Cargar áreas en paralelo (sin bloquear la navegación)
            cargarAreasDisponibles(tipo);
        });
    });
    
    async function cargarAreasDisponibles(tipo) {
        try {
            const response = await fetch(`{{ route('api.recibos-bordado-estampado.areas-disponibles') }}?tipo=${encodeURIComponent(tipo)}`);
            const data = await response.json();
            
            if (data.success && data.conteos) {
                // Guardar los conteos en una variable global para usarlos después de navegar
                sessionStorage.setItem('__areConteosPorTipo', JSON.stringify({
                    tipo: tipo,
                    conteos: data.conteos,
                    timestamp: Date.now()
                }));
            }
        } catch (error) {
            console.warn('[recibos-bordado-estampado] Error cargando áreas:', error);
            // No hacer nada, dejar que siga el flujo normal
        }
    }
})();
</script>

<style>
.recibos-vista-scale-80 {
    zoom: 0.8;
    transform-origin: top left;
}

@supports not (zoom: 1) {
    .recibos-vista-scale-80 {
        transform: scale(0.8);
        transform-origin: top left;
        width: 125%;
    }
}

.recibos-costura-scale-80 {
    zoom: 0.8;
}

@supports not (zoom: 1) {
    .recibos-costura-scale-80 {
        transform: scale(0.8);
        transform-origin: top left;
        width: 125%;
    }
}

.table-scroll-container[data-vista-tipo="bordado-estampado"] .table-header th:nth-child(1) {
    width: 130px !important;
    min-width: 130px !important;
    max-width: 130px !important;
}

.table-scroll-container[data-vista-tipo="bordado-estampado"] .modern-table tbody tr td:nth-child(1) {
    width: 130px !important;
    min-width: 130px !important;
    max-width: 130px !important;
    padding: 8px 8px !important;
    overflow: visible !important;
}

.recibos-tipo-tabs {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
}
.recibos-tipo-tab {
    text-decoration: none;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #334155;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.recibos-tipo-tab .tab-count {
    margin-left: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    padding: 2px 7px;
    font-size: 11px;
}
.recibos-tipo-tab.is-active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
}
.recibos-tipo-tab.is-active .tab-count {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

/* Estilos para la sección de filtros */
.recibos-filters-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

@media (max-width: 1024px) {
    .recibos-filters-container {
        grid-template-columns: 1fr;
    }
}

.recibos-filter-section {
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.recibos-filter-title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.recibos-filters-section {
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.recibos-area-filters {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.recibos-area-filter {
    text-decoration: none;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #334155;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.recibos-area-filter:hover {
    border-color: #94a3b8;
    transform: translateY(-1px);
}

.recibos-area-filter.is-active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
}

.area-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}
</style>
