@php
    $tipoActivo = strtoupper((string) ($tipoFiltro ?? 'BORDADO'));
    if (!in_array($tipoActivo, ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO'], true)) {
        $tipoActivo = 'BORDADO';
    }
    $puedeGestionarCheckLogo = auth()->check() && auth()->user()->hasRole('visualizador_recibos_logo');
@endphp

<div class="recibos-tipo-tabs" id="recibosTipoTabs">
    <a class="recibos-tipo-tab {{ $tipoActivo === 'BORDADO' ? 'is-active' : '' }}"
       href="{{ request()->fullUrlWithQuery(['tipo' => 'BORDADO', 'page' => 1]) }}">
        Bordado <span class="tab-count">{{ (int) ($conteoBordado ?? 0) }}</span>
    </a>
    <a class="recibos-tipo-tab {{ $tipoActivo === 'ESTAMPADO' ? 'is-active' : '' }}"
       href="{{ request()->fullUrlWithQuery(['tipo' => 'ESTAMPADO', 'page' => 1]) }}">
        Estampado <span class="tab-count">{{ (int) ($conteoEstampado ?? 0) }}</span>
    </a>
    <a class="recibos-tipo-tab {{ $tipoActivo === 'DTF' ? 'is-active' : '' }}"
       href="{{ request()->fullUrlWithQuery(['tipo' => 'DTF', 'page' => 1]) }}">
        DTF <span class="tab-count">{{ (int) ($conteoDtf ?? 0) }}</span>
    </a>
    <a class="recibos-tipo-tab {{ $tipoActivo === 'SUBLIMADO' ? 'is-active' : '' }}"
       href="{{ request()->fullUrlWithQuery(['tipo' => 'SUBLIMADO', 'page' => 1]) }}">
        Sublimado <span class="tab-count">{{ (int) ($conteoSublimado ?? 0) }}</span>
    </a>
</div>

<!-- Tabla de Recibos de Bordado/Estampado -->
<div class="table-scroll-container recibos-costura-scale-90" data-vista-tipo="bordado-estampado">
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
    <div class="pagination-container mt-4" data-pagination-current-url="{{ request()->fullUrl() }}">
        <div class="pagination-info text-muted mb-2">
            Mostrando {{ $recibos->firstItem() }} a {{ $recibos->lastItem() }} de {{ $recibos->total() }} registros
        </div>
        <div class="pagination-wrapper" id="pagination-wrapper">
            {{ $recibos->appends(request()->query())->links('vendor.pagination.bootstrap-custom') }}
        </div>
    </div>

    <script src="{{ asset('js/recibos-costura/pagination.js') }}?v={{ time() }}"></script>
@endif

<style>
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
</style>
