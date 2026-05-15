<!-- Tabla de Recibos de Costura -->
<div class="table-scroll-container recibos-costura-scale-90">
    <table class="table table-striped table-hover modern-table">
        <thead class="table-header">
            <tr>
                <th class="acciones-column" style="width: 60px; text-align: center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 12h8M12 8v8"></path>
                    </svg>
                </th>
                <th style="width: auto;">
                    <div class="th-wrapper">
                        <span>Estado</span>
                        <button class="btn-filter-column" type="button" data-column="estado" onclick="openColumnFilter('estado', 'Estado')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="estado">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: auto; min-width: 120px;">
                    <div class="th-wrapper">
                        <span>Área</span>
                        <button class="btn-filter-column" type="button" data-column="area" onclick="openColumnFilter('area', 'Área')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="area">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 140px;">
                    <div class="th-wrapper">
                        <span>Días</span>
                        <button class="btn-filter-column" type="button" data-column="total_dias" onclick="openColumnFilter('total_dias', 'Días')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="total_dias">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 120px;">
                    <div class="th-wrapper">
                        <span>N° Recibo</span>
                        <button class="btn-filter-column" type="button" data-column="numero_recibo" onclick="openColumnFilter('numero_recibo', 'Número de Recibo')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="numero_recibo">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 150px;">
                    <div class="th-wrapper">
                        <span>Cliente</span>
                        <button class="btn-filter-column" type="button" data-column="cliente" onclick="openColumnFilter('cliente', 'Cliente')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="cliente">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: auto;">
                    <div class="th-wrapper">
                        <span>Descripción</span>
                        <button class="btn-filter-column" type="button" data-column="descripcion" onclick="openColumnFilter('descripcion', 'Descripción')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="descripcion">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 100px;">
                    <div class="th-wrapper">
                        <span>Cantidad</span>
                    </div>
                </th>
                <th style="width: 120px;">
                    <div class="th-wrapper">
                        <span>Novedades</span>
                        <button class="btn-filter-column" type="button" data-column="novedades" onclick="openColumnFilter('novedades', 'Novedades')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="novedades">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 150px;">
                    <div class="th-wrapper">
                        <span>Fecha de creación</span>
                        <button class="btn-filter-column" type="button" data-column="fecha_creacion" onclick="openColumnFilter('fecha_creacion', 'Fecha de creación')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="fecha_creacion">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 180px;">
                    <div class="th-wrapper">
                        <span>Fecha estimada entrega</span>
                        <button class="btn-filter-column" type="button" data-column="fecha_estimada" onclick="openColumnFilter('fecha_estimada', 'Fecha estimada entrega')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="fecha_estimada">0</span>
                        </button>
                    </div>
                </th>
                <th style="width: 150px;">
                    <div class="th-wrapper">
                        <span>Encargado orden</span>
                        <button class="btn-filter-column" type="button" data-column="encargado" onclick="openColumnFilter('encargado', 'Encargado orden')">
                            <i class="fas fa-filter"></i>
                            <span class="filter-badge" data-badge="encargado">0</span>
                        </button>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody id="tablaRecibosBody">
            @if($recibos->count() > 0)
                @foreach($recibos as $recibo)
                    <tr class="@if(isset($recibo['dias_calculados']) && $recibo['dias_calculados'] > 0)
                        @if($recibo['dias_calculados'] >= 14) dias-mayor-15
                        @elseif($recibo['dias_calculados'] >= 10) dias-10-15
                        @elseif($recibo['dias_calculados'] >= 5) dias-5-9
                        @else dias-0-4 @endif
                    @endif"
                        data-orden-id="{{ $recibo['id'] }}"
                        data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                        data-numero-recibo="{{ $recibo['consecutivo_actual'] ?? '' }}"
                        data-area-normalized="{{ \Illuminate\Support\Str::lower(trim((string) ($recibo['area'] ?? $recibo['pedido_info']['area'] ?? ''))) }}"
                    >
                        <!-- Acciones -->
                        <td class="acciones-column" style="text-align: center; position: relative;">
                            <button class="btn-ver-dropdown" 
                                title="Ver Opciones" 
                                data-menu-id="menu-recibo-{{ $recibo['id'] }}"
                                data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                                data-prenda-id="{{ $recibo['prenda_id'] ?? '' }}"
                                data-numero-recibo="{{ $recibo['consecutivo_actual'] ?? '' }}"
                                data-tipo-recibo="{{ $recibo['tipo_recibo'] ?? 'COSTURA' }}"
                                data-es-parcial="{{ (!empty($recibo['es_parcial']) || !empty($recibo['esParcial'])) ? 'true' : 'false' }}"
                                data-pedido-parcial-id="{{ $recibo['pedido_parcial_id'] ?? ($recibo['pedidoParcialId'] ?? ($recibo['parcial_id'] ?? '')) }}"
                                data-recibo-id="{{ $recibo['id'] ?? '' }}"
                                data-tiene-parciales="{{ !empty($recibo['tiene_parciales']) ? 'true' : 'false' }}"
                                data-total-parciales="{{ $recibo['total_parciales'] ?? 0 }}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                        
                        <!-- Estado del Recibo (Badge) -->
                        <td style="white-space: nowrap;">
                            @php
                                $estadoRecibo = $recibo['estado'] ?? 'PENDIENTE_INSUMOS';
                                $badgeClass = 'bg-secondary';
                                $estadoTexto = $estadoRecibo;
                                if ($estadoRecibo === 'En Ejecución') {
                                    $badgeClass = 'bg-primary';
                                } elseif ($estadoRecibo === 'No iniciado') {
                                    $badgeClass = 'bg-warning';
                                } elseif ($estadoRecibo === 'PENDIENTE_INSUMOS') {
                                    $badgeClass = 'bg-info';
                                    $estadoTexto = 'Pendiente';
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }}" style="white-space: nowrap; display: inline-block;">
                                {{ $estadoTexto }}
                            </span>
                        </td>
                        
                        <!-- Area del Recibo (del proceso mas reciente) -->
                        <td>
                            @php
                                // Usar el Area del recibo guardada en la BD en lugar del Area general del pedido
                                $areaRecibo = $recibo['area'] ?? $recibo['pedido_info']['area'] ?? 'Insumos';
                                $puedeAgregarProceso = stripos((string) $areaRecibo, 'Corte') !== false;
                                $areaBadge = 'bg-secondary';
                                if (strpos($areaRecibo, 'Corte') !== false) {
                                    $areaBadge = 'bg-success'; // Verde - bueno para corte
                                } elseif (strpos($areaRecibo, 'Insumos') !== false) {
                                    $areaBadge = 'bg-info'; // Azul claro - neutro para insumos
                                } elseif (strpos($areaRecibo, 'Costura') !== false) {
                                    $areaBadge = 'bg-primary'; // Azul principal - importante
                                } elseif (strpos($areaRecibo, 'Estampado') !== false) {
                                    $areaBadge = 'bg-warning'; // Amarillo/Ambar - visible para estampado
                                } elseif (strpos($areaRecibo, 'Bordado') !== false) {
                                    $areaBadge = 'bg-purple'; // Purpura - elegante para bordado (cambio de bg-danger)
                                }
                            @endphp
                            <span class="badge {{ $areaBadge }} area-badge-clickable"
                                  style="cursor: {{ $puedeAgregarProceso ? 'pointer' : 'default' }}; transition: all 0.2s ease;"
                                  title="{{ $puedeAgregarProceso ? 'Click para agregar proceso' : 'Área actual sin acción disponible' }}"
                                  @if($puedeAgregarProceso)
                                  onclick="abrirModalAgregarProcesoDesdeArea('{{ $areaRecibo }}', {{ $recibo['pedido_produccion_id'] ?? 'null' }}, {{ $recibo['prenda_id'] ?? 'null' }}, {{ $recibo['consecutivo_actual'] ?? 'null' }})"
                                  @endif
                                  onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.2)';"
                                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                                {{ $areaRecibo }}
                            </span>
                        </td>
                        
                        <!-- Días (Total + Restantes) -->
                        <td style="text-align: center;">
                            @if(isset($recibo['dias_calculados']))
                                @if($recibo['dias_calculados'] == 0)
                                    <span class="badge bg-secondary" style="font-weight: 600;">
                                        {{ $recibo['dias_calculados'] }} dí­as
                                    </span>
                                @else
                                    <span class="badge @if($recibo['dias_calculados'] >= 14) bg-danger @elseif($recibo['dias_calculados'] >= 5) bg-warning @else bg-success @endif" style="font-weight: 600;">
                                        {{ $recibo['dias_calculados'] }} dí­as
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                            @php
                                $diasRestantes = null;
                                $diasObjetivo = (int) ($recibo['dia_de_entrega']
                                    ?? $recibo['pedido_info']['dia_de_entrega']
                                    ?? 0);
                                $diasTranscurridos = isset($recibo['dias_calculados']) ? (int) $recibo['dias_calculados'] : null;

                                if ($diasObjetivo > 0 && $diasTranscurridos !== null) {
                                    $diasRestantes = max(0, $diasObjetivo - $diasTranscurridos);
                                } elseif (!empty($recibo['fecha_estimada_de_entrega']) && $recibo['fecha_estimada_de_entrega'] !== 'null') {
                                    try {
                                        $hoy = \Carbon\Carbon::today('America/Bogota');
                                        $fechaEntrega = \Carbon\Carbon::parse($recibo['fecha_estimada_de_entrega'])->startOfDay();
                                        $diasRestantes = $fechaEntrega->lt($hoy) ? 0 : $hoy->diffInWeekdays($fechaEntrega);
                                    } catch (\Throwable $e) {
                                        $diasRestantes = null;
                                    }
                                }
                            @endphp
                            <div style="margin-top: 4px;">
                                @if($diasRestantes !== null)
                                    <span class="badge {{ $diasRestantes <= 3 ? 'bg-danger' : ($diasRestantes <= 7 ? 'bg-warning' : 'bg-success') }}" style="font-weight: 600;">
                                        Rest: {{ $diasRestantes }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 11px;">Rest: -</span>
                                @endif
                            </div>
                        </td>
                        
                        <!-- N° Recibo -->
                        <td style="text-align: center;">
                            <span style="font-weight: 600;">{{ $recibo['consecutivo_actual'] }}</span>
                        </td>
                        
                        <!-- Cliente -->
                        <td style="text-align: center;">
                            @if($recibo['pedido_info'])
                                <span>{{ $recibo['pedido_info']['cliente'] }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        
                        <!-- Descripcion (Recibo de Costura) -->
                        <td data-descripcion-detallada="{{ $recibo['descripcion_detallada'] ?? '' }}">
                            @php
                                $nombreMostrar = $recibo['descripcion_detallada'] ?? '';
                                
                                // Truncar si es muy largo para la vista en tabla
                                if (strlen($nombreMostrar) > 50) {
                                    $nombreMostrar = substr($nombreMostrar, 0, 47) . '...';
                                }
                            @endphp
                            
                            <div class="table-cell" style="flex: 10;">
                                <div class="cell-content" style="justify-content: flex-start; cursor: pointer;" onclick="console.log('[ONCLICK TABLE CELL]  Click en descripcion'); event.stopPropagation(); obtenerDatosPrendaRecibo('Descripcion', {{ $recibo['pedido_produccion_id'] }}, {{ $recibo['prenda_id'] }}, '{{ $recibo['consecutivo_actual'] ?? '' }}', {{ !empty($recibo['es_parcial']) ? 'true' : 'false' }}, {{ $recibo['pedido_parcial_id'] ?? 'null' }})">
                                    <span style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="Click para ver completo">
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
                        
                        <!-- Novedades -->
                        <td>
                            @php
                                $novedadesTexto = trim((string) ($recibo['novedades'] ?? ''));
                                if ($novedadesTexto === 'Sin novedades') {
                                    $novedadesTexto = '';
                                }
                            @endphp
                            <div class="table-cell" style="flex: 0 0 120px;">
                                <div class="cell-content" style="justify-content: flex-start;">
                                    <button 
                                        class="btn-edit-novedades"
                                        data-pedido-id="{{ $recibo['pedido_produccion_id'] }}"
                                        data-numero-recibo="{{ $recibo['consecutivo_actual'] }}"
                                        data-novedades="{{ addslashes(str_replace(["\r", "\n"], " ", $novedadesTexto)) }}"
                                        onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                                        title="Ver novedades del recibo"
                                        type="button">
                                        @if($novedadesTexto)
                                            <span class="novedades-text">{{ \Illuminate\Support\Str::limit(str_replace(["\r", "\n"], " ", $novedadesTexto), 50, '...') }}</span>
                                        @else
                                            <span class="novedades-text empty">Sin novedades</span>
                                        @endif
                                        <span class="material-symbols-rounded">edit</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Fecha de creacion -->
                        <td>
                            @if(request()->is('recibos-reflectivo') && !empty($recibo['es_parcial']) && !empty($recibo['fecha_activacion']))
                                <span>{{ \Carbon\Carbon::parse($recibo['fecha_activacion'])->format('d/m/Y') }}</span>
                            @elseif(!empty($recibo['es_parcial']) && !empty($recibo['created_at']))
                                <span>{{ \Carbon\Carbon::parse($recibo['created_at'])->format('d/m/Y') }}</span>
                            @elseif($recibo['pedido_info'] && isset($recibo['pedido_info']['fecha_creacion_orden']))
                                <span>{{ \Carbon\Carbon::parse($recibo['pedido_info']['fecha_creacion_orden'])->format('d/m/Y') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        
                        <!-- Fecha estimada entrega -->
                        <td>
                            @if(isset($recibo['fecha_estimada_de_entrega']) && !empty($recibo['fecha_estimada_de_entrega']) && $recibo['fecha_estimada_de_entrega'] !== 'null')
                                <span class="fecha-estimada-span">
                                    @php
                                        $fecha = $recibo['fecha_estimada_de_entrega'];
                                        // Si ya tiene formato d/m/Y, mostrar tal cual
                                        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
                                            echo $fecha;
                                        } else {
                                            // Si no, intentar parsear y formatear
                                            try {
                                                echo \Carbon\Carbon::parse($fecha)->format('d/m/Y');
                                            } catch (\Exception $e) {
                                                echo '-';
                                            }
                                        }
                                    @endphp
                                </span>
                            @else
                                <span class="fecha-estimada-span text-muted">-</span>
                            @endif
                        </td>
                        
                        <!-- Encargado orden (Proceso mas reciente) -->
                        <td>
                            @php
                                $encargadoProceso = '-';
                                
                                if (!empty($recibo['pedido_info']['numero_pedido']) && !empty($recibo['consecutivo_actual'])) {
                                    try {
                                        $numeroPedido = (int) $recibo['pedido_info']['numero_pedido'];
                                        $prendaId = isset($recibo['prenda_id']) ? (int) $recibo['prenda_id'] : null;
                                        $numeroRecibo = (int) $recibo['consecutivo_actual'];

                                        // Construccion de la query mas especi­fica y ordenada
                                        $query = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                                            ->where('numero_recibo', $numeroRecibo)
                                            ->whereNull('deleted_at');

                                        // Si tenemos prenda_id, añadir esa condicion tambien
                                        if (!empty($prendaId)) {
                                            $query->where('prenda_pedido_id', $prendaId);
                                        }

                                        // Obtener el proceso mas reciente: primero por fecha_fin DESC, luego por created_at DESC
                                        $procesoMasReciente = $query
                                            ->orderByDesc('fecha_fin')
                                            ->orderByDesc('created_at')
                                            ->first();

                                        if ($procesoMasReciente && !empty($procesoMasReciente->encargado)) {
                                            $encargadoProceso = htmlspecialchars(trim($procesoMasReciente->encargado));
                                        }
                                    } catch (\Exception $e) {
                                        \Log::error('[recibos-costura-table] Error obteniendo encargado por recibo: ' . $e->getMessage());
                                    }
                                }
                            @endphp
                            <span style="font-weight: 600; color: #374151;">{{ $encargadoProceso }}</span>
                        </td>
                    </tr>
                @endforeach
            @else
        <tr>
            <td colspan="12" class="text-center py-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No se encontraron recibos de costura.
                </div>
            </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<!-- Controles de Paginacion -->
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

<!-- Modal de Filtros -->
<div id="column-filter-modal-overlay" class="logo-filter-modal-overlay" onclick="closeColumnFilterModal(event)">
    <div class="logo-filter-modal" onclick="event.stopPropagation()">
        <div class="logo-filter-modal-header">
            <h3 id="logo-filter-title">Filtrar</h3>
            <button type="button" class="logo-filter-modal-close" onclick="closeColumnFilterModal()">&times;</button>
        </div>
        <div class="logo-filter-modal-body">
            <input id="logo-filter-search" class="logo-filter-search" type="text" placeholder="Buscar..." />
            <div id="logo-filter-options" class="logo-filter-options"></div>
        </div>
        <div class="logo-filter-modal-footer">
            <button type="button" class="logo-filter-btn reset" onclick="resetColumnFilter()">Reset</button>
            <button type="button" class="logo-filter-btn apply" onclick="applyColumnFilter()">Aplicar</button>
        </div>
    </div>
</div>

<!-- Boton flotante para limpiar todos los filtros -->
<button id="floating-clear-filters" class="floating-clear-filters" type="button" onclick="clearAllFilters()">
    <i class="fas fa-broom"></i>
    <div class="floating-clear-filters-tooltip">Limpiar filtros</div>
</button>

<!-- Script de filtros -->
<script src="{{ asset('js/recibos-costura/filters.js') }}?v={{ time() }}"></script>
