<!-- Tabla de Recibos de Bordado/Estampado - Versión Limpia -->
<div class="table-scroll-container recibos-costura-scale-90" data-vista-tipo="bordado-estampado">
    <table class="table table-striped table-hover modern-table" data-vista-tipo="bordado-estampado">
        <thead class="table-header">
            <tr>
                <th class="acciones-column" style="width: 60px; text-align: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 12h8M12 8v8"></path>
                    </svg>
                </th>
                <th style="width: auto; min-width: 120px;">
                    <span>Área</span>
                </th>
                <th style="width: 120px;">
                    <span>N° Recibo</span>
                </th>
                <th style="width: 120px; text-align: center;">
                    <span>Tipo</span>
                </th>
                <th style="width: 150px;">
                    <span>Cliente</span>
                </th>
                <th style="width: auto;">
                    <span>Descripción</span>
                </th>
                <th style="width: 100px;">
                    <span>Cantidad</span>
                </th>
                <th style="width: 150px;">
                    <span>Fecha de creación</span>
                </th>
            </tr>
        </thead>
        <tbody id="tablaRecibosBody">
            @if($recibos->count() > 0)
                @foreach($recibos as $recibo)
                    <tr data-orden-id="{{ $recibo['id'] }}"
                        data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                        data-numero-recibo="{{ $recibo['consecutivo_actual'] ?? '' }}">

                        <!-- Acciones -->
                        <td class="acciones-column" style="text-align: center; position: relative;">
                            <button class="btn-ver-dropdown"
                                title="Ver Opciones"
                                data-menu-id="menu-recibo-{{ $recibo['id'] }}"
                                data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                                data-prenda-id="{{ $recibo['prenda_id'] ?? '' }}"
                                data-numero-recibo="{{ $recibo['consecutivo_actual'] ?? '' }}"
                                data-tipo-recibo="{{ $recibo['tipo_recibo'] ?? 'BORDADO' }}"
                                data-es-parcial="false"
                                data-recibo-id="{{ $recibo['id'] ?? '' }}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>

                        <!-- Área -->
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

                        <!-- N° Recibo -->
                        <td style="text-align: center;">
                            <span style="font-weight: 600;">{{ $recibo['consecutivo_actual'] }}</span>
                        </td>

                        <!-- Tipo de Recibo -->
                        <td style="text-align: center;">
                            @php
                                $tipo = strtoupper($recibo['tipo_recibo'] ?? 'BORDADO');
                                $tipoBadge = ($tipo === 'BORDADO') ? '#2563eb' : '#0f766e';
                            @endphp
                            <span style="display:inline-block;padding:3px 8px;border-radius:999px;background:{{ $tipoBadge }};color:#fff;font-size:11px;font-weight:700;letter-spacing:.3px;">
                                {{ $tipo }}
                            </span>
                        </td>

                        <!-- Cliente -->
                        <td style="text-align: center;">
                            @if($recibo['pedido_info'])
                                <span>{{ $recibo['pedido_info']['cliente'] }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>

                        <!-- Descripción -->
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

                        <!-- Cantidad -->
                        <td>
                            @if(isset($recibo['cantidad_total']) && $recibo['cantidad_total'] > 0)
                                <span style="font-weight: 600; color: #059669;">{{ $recibo['cantidad_total'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <!-- Fecha de creación -->
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

<!-- Controles de Paginación -->
@if($recibos instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="pagination-container mt-4" data-pagination-current-url="{{ request()->fullUrl() }}">
        <div class="pagination-info text-muted mb-2">
            Mostrando {{ $recibos->firstItem() }} a {{ $recibos->lastItem() }} de {{ $recibos->total() }} registros
        </div>
        <div class="pagination-wrapper" id="pagination-wrapper">
            {{ $recibos->appends(request()->query())->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <script src="{{ asset('js/recibos-costura/pagination.js') }}?v={{ time() }}"></script>
@endif

