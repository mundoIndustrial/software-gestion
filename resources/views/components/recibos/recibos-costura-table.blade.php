<!-- Tabla de Recibos de Costura -->
<div class="table-scroll-container">
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
                    Estado
                    <button type="button" class="filter-btn" 
                            data-filter-type="estado"
                            onclick="openFilterModal('estado')" 
                            title="Filtrar por estado" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: auto; min-width: 120px;">
                    Área
                    <button type="button" class="filter-btn" 
                            data-filter-type="area"
                            onclick="openFilterModal('area')" 
                            title="Filtrar por área" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 120px;">
                    Total de días
                    <button type="button" class="filter-btn" 
                            data-filter-type="total_dias"
                            onclick="openFilterModal('total_dias')" 
                            title="Filtrar por total de días" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 120px;">
                    N° Recibo
                    <button type="button" class="filter-btn" 
                            data-filter-type="numero_recibo"
                            onclick="openFilterModal('numero_recibo')" 
                            title="Filtrar por número de recibo" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 150px;">
                    Cliente
                    <button type="button" class="filter-btn" 
                            data-filter-type="cliente"
                            onclick="openFilterModal('cliente')" 
                            title="Filtrar por cliente" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: auto;">
                    Descripción
                    <button type="button" class="filter-btn" 
                            data-filter-type="descripcion"
                            onclick="openFilterModal('descripcion')" 
                            title="Filtrar por descripción" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 100px;">
                    Cantidad
                    <button type="button" class="filter-btn" 
                            data-filter-type="cantidad"
                            onclick="openFilterModal('cantidad')" 
                            title="Filtrar por cantidad" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 120px;">
                    Novedades
                    <button type="button" class="filter-btn" 
                            data-filter-type="novedades"
                            onclick="openFilterModal('novedades')" 
                            title="Filtrar por novedades" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 150px;">
                    Fecha de creación
                    <button type="button" class="filter-btn" 
                            data-filter-type="fecha_creacion"
                            onclick="openFilterModal('fecha_creacion')" 
                            title="Filtrar por fecha de creación" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 180px;">
                    Fecha estimada entrega
                    <button type="button" class="filter-btn" 
                            data-filter-type="fecha_estimada"
                            onclick="openFilterModal('fecha_estimada')" 
                            title="Filtrar por fecha estimada de entrega" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
                <th style="width: 150px;">
                    Encargado orden
                    <button type="button" class="filter-btn" 
                            data-filter-type="encargado"
                            onclick="openFilterModal('encargado')" 
                            title="Filtrar por encargado de orden" style="
                        background: none;
                        border: none;
                        color: white;
                        margin-left: 8px;
                        cursor: pointer;
                        padding: 2px;
                        border-radius: 4px;
                        transition: background-color 0.2s;
                    " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 3H2H2l8 9.46V19l4 2v-8.54L22 3z"/>
                        </svg>
                    </button>
                </th>
            </tr>
        </thead>
        <tbody id="tablaRecibosBody">
            @if(is_array($recibos) && count($recibos) > 0)
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
                    >
                        <!-- Acciones -->
                        <td class="acciones-column" style="text-align: center; position: relative;">
                            <button class="btn-ver-dropdown" 
                                title="Ver Opciones" 
                                data-menu-id="menu-recibo-{{ $recibo['id'] }}"
                                data-pedido-id="{{ $recibo['pedido_produccion_id'] ?? '' }}"
                                data-prenda-id="{{ $recibo['prenda_id'] ?? '' }}"
                                data-tipo-recibo="{{ $recibo['tipo_recibo'] ?? 'COSTURA' }}"
                                data-es-parcial="{{ !empty($recibo['es_parcial']) ? 'true' : 'false' }}"
                                data-pedido-parcial-id="{{ $recibo['pedido_parcial_id'] ?? '' }}">
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
                        
                        <!-- Área del Recibo (del proceso más reciente) -->
                        <td>
                            @php
                                // Usar el área del proceso más reciente (pedido_info.area) en lugar del área del recibo
                                $areaRecibo = $recibo['pedido_info']['area'] ?? $recibo['area'] ?? 'Insumos';
                                $areaBadge = 'bg-secondary';
                                if (strpos($areaRecibo, 'Corte') !== false) {
                                    $areaBadge = 'bg-success'; // Verde - bueno para corte
                                } elseif (strpos($areaRecibo, 'Insumos') !== false) {
                                    $areaBadge = 'bg-info'; // Azul claro - neutro para insumos
                                } elseif (strpos($areaRecibo, 'Costura') !== false) {
                                    $areaBadge = 'bg-primary'; // Azul principal - importante
                                } elseif (strpos($areaRecibo, 'Estampado') !== false) {
                                    $areaBadge = 'bg-warning'; // Amarillo/ámbar - visible para estampado
                                } elseif (strpos($areaRecibo, 'Bordado') !== false) {
                                    $areaBadge = 'bg-purple'; // Púrpura - elegante para bordado (cambio de bg-danger)
                                }
                            @endphp
                            <span class="badge {{ $areaBadge }} area-badge-clickable" 
                                  style="cursor: pointer; transition: all 0.2s ease;"
                                  title="Click para agregar proceso"
                                  onclick="abrirModalAgregarProcesoDesdeArea('{{ $areaRecibo }}', {{ $recibo['pedido_produccion_id'] ?? 'null' }}, {{ $recibo['prenda_id'] ?? 'null' }})"
                                  onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.2)';"
                                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                                {{ $areaRecibo }}
                            </span>
                        </td>
                        
                        <!-- Total de días -->
                        <td style="text-align: center;">
                            @if(isset($recibo['dias_calculados']))
                                @if($recibo['dias_calculados'] == 0)
                                    <span class="badge bg-secondary" style="font-weight: 600;">
                                        {{ $recibo['dias_calculados'] }} días
                                    </span>
                                @else
                                    <span class="badge @if($recibo['dias_calculados'] >= 14) bg-danger @elseif($recibo['dias_calculados'] >= 5) bg-warning @else bg-success @endif" style="font-weight: 600;">
                                        {{ $recibo['dias_calculados'] }} días
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
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
                        
                        <!-- Descripción (Recibo de Costura) -->
                        <td data-descripcion-detallada="{{ $recibo['descripcion_detallada'] ?? '' }}">
                            @php
                                $nombreMostrar = $recibo['descripcion_detallada'] ?? '';
                                
                                // Truncar si es muy largo para la vista en tabla
                                if (strlen($nombreMostrar) > 50) {
                                    $nombreMostrar = substr($nombreMostrar, 0, 47) . '...';
                                }
                            @endphp
                            
                            <div class="table-cell" style="flex: 10;">
                                <div class="cell-content" style="justify-content: flex-start; cursor: pointer;" onclick="console.log('[ONCLICK TABLE CELL] 📌 Click en descripción'); event.stopPropagation(); obtenerDatosPrendaRecibo('Descripción', {{ $recibo['pedido_produccion_id'] }}, {{ $recibo['prenda_id'] }})">
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
                                // Obtener las novedades específicas de prendas para este recibo
                                $novedadesRecibo = [];
                                $novedadesTexto = '';
                                if ($recibo['pedido_info']) {
                                    $pedido = \App\Models\PedidoProduccion::find($recibo['pedido_produccion_id']);
                                    if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                                        foreach ($pedido->prendas as $prenda) {
                                            // Obtener novedades de esta prenda para este número de recibo
                                            $novedadesPrenda = $prenda->novedadesRecibo()
                                                ->where('numero_recibo', $recibo['consecutivo_actual'])
                                                ->orderBy('creado_en', 'desc')
                                                ->get();
                                            
                                            foreach ($novedadesPrenda as $novedad) {
                                                // Limpiar el texto de la novedad para evitar problemas
                                                $textoLimpio = str_replace(["\r", "\n", "'", '"'], " ", $novedad->novedad_texto);
                                                $novedadesRecibo[] = $textoLimpio;
                                            }
                                        }
                                    }
                                    
                                    // Concatenar todas las novedades para mostrar
                                    if (!empty($novedadesRecibo)) {
                                        $novedadesTexto = implode(" | ", $novedadesRecibo);
                                    }
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
                        
                        <!-- Fecha de creación -->
                        <td>
                            @if(!empty($recibo['es_parcial']) && !empty($recibo['created_at']))
                                <span>{{ \Carbon\Carbon::parse($recibo['created_at'])->format('d/m/Y') }}</span>
                            @elseif($recibo['pedido_info'] && isset($recibo['pedido_info']['fecha_creacion_orden']))
                                <span>{{ \Carbon\Carbon::parse($recibo['pedido_info']['fecha_creacion_orden'])->format('d/m/Y') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        
                        <!-- Fecha estimada entrega (No aplica para recibos) -->
                        <td>
                            <span class="fecha-estimada-span text-muted">-</span>
                        </td>
                        
                        <!-- Encargado orden (Proceso más reciente) -->
                        <td>
                            @php
                                $encargadoProceso = '-';
                                
                                if (!empty($recibo['pedido_info']['numero_pedido']) && !empty($recibo['consecutivo_actual'])) {
                                    try {
                                        $numeroPedido = (int) $recibo['pedido_info']['numero_pedido'];
                                        $prendaId = isset($recibo['prenda_id']) ? (int) $recibo['prenda_id'] : null;
                                        $numeroRecibo = (int) $recibo['consecutivo_actual'];

                                        $procesoMasReciente = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                                            ->whereNull('deleted_at')
                                            ->where(function ($q) use ($prendaId, $numeroRecibo) {
                                                if (!empty($prendaId)) {
                                                    $q->where('prenda_pedido_id', $prendaId);
                                                }
                                                $q->orWhere('numero_recibo', $numeroRecibo);
                                            })
                                            ->orderByDesc('created_at')
                                            ->first();

                                        if ($procesoMasReciente && !empty($procesoMasReciente->encargado)) {
                                            $encargadoProceso = htmlspecialchars($procesoMasReciente->encargado);
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

<!-- Modal de Filtros Dinámico -->
<div class="filter-modal" id="filterModal" style="display: none;">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 id="filterModalTitle">Filtrar por Descripción</h3>
            <button type="button" class="filter-modal-close" onclick="closeFilterModal()">×</button>
        </div>
        <div class="filter-modal-body">
            <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar...">
            <div class="filter-options" id="filterOptions">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>
        <div class="filter-modal-footer">
            <button type="button" class="btn-filter-reset" onclick="resetFilters()">Limpiar</button>
            <button type="button" class="btn-filter-apply" onclick="applyFilters()">Aplicar</button>
        </div>
    </div>
</div>
