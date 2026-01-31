@extends('layouts.app')

@section('title', 'Registro de Órdenes - MundoIndustrial')
@section('page-title', 'Registro de Órdenes')

@push('styles')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/orders-styles/registros.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/column-widths.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/action-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/filter-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/row-conditional-colors.css') }}">
    <link rel="stylesheet" href="{{ asset('css/novedades-button.css') }}?v={{ time() }}">
@endpush

@section('content')

    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head" id="tableHead">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    @php
                        $columns = [
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 100px', 'justify' => 'flex-start'],
                            ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 120px', 'justify' => 'center'],
                            ['key' => 'area', 'label' => 'Área', 'flex' => '0 0 auto; min-width: 180px', 'justify' => 'center'],
                            ['key' => 'dia_entrega', 'label' => 'Día de entrega', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'total_dias', 'label' => 'Total de días', 'flex' => '0 0 120px', 'justify' => 'center'],
                            ['key' => 'pedido', 'label' => 'Pedido', 'flex' => '0 0 120px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'descripcion', 'label' => 'Descripción', 'flex' => '1; margin-left: 50px', 'justify' => 'center'],
                            ['key' => 'cantidad', 'label' => 'Cantidad', 'flex' => '0 0 100px', 'justify' => 'start'],
                            ['key' => 'novedades', 'label' => 'Novedades', 'flex' => '0 0 120px', 'justify' => 'flex-start'],
                            ['key' => 'asesor', 'label' => 'Asesor', 'flex' => '0 0 120px', 'justify' => 'flex-start'],
                            ['key' => 'forma_pago', 'label' => 'Forma de pago', 'flex' => '0 0 150px', 'justify' => 'flex-start'],
                            ['key' => 'fecha_creacion', 'label' => 'Fecha de creación', 'flex' => '0 0 150px', 'justify' => 'flex-start'],
                            ['key' => 'fecha_estimada', 'label' => 'Fecha estimada entrega', 'flex' => '0 0 180px', 'justify' => 'flex-start'],
                            ['key' => 'encargado', 'label' => 'Encargado orden', 'flex' => '0 0 150px', 'justify' => 'flex-start'],
                        ];
                    @endphp
                    
                    @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                            <div class="th-wrapper">
                                <span class="header-text">{{ $column['label'] }}</span>
                                @if($column['key'] !== 'acciones')
                                    <button type="button" class="btn-filter-column" title="Filtrar {{ $column['label'] }}" onclick="openFilterModal('{{ $column['key'] }}')">
                                        <span class="material-symbols-rounded">filter_alt</span>
                                        <span class="filter-badge">0</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div id="tablaOrdenesBody" class="table-body">
                        @forelse($ordenes as $orden)
                            @php
                                // Construir descripción con tallas POR PRENDA para el modal
                                $descripcionConTallas = '';
                                $descripcionBase = $orden->descripcion_prendas ?? '';
                                
                                // VERIFICAR SI ES COTIZACIÓN TIPO REFLECTIVO
                                $esReflectivo = false;
                                if ($orden->cotizacion && $orden->cotizacion->tipoCotizacion) {
                                    $esReflectivo = ($orden->cotizacion->tipoCotizacion->codigo === 'RF');
                                }
                                
                                if (!empty($descripcionBase) || ($esReflectivo && $orden->prendas && $orden->prendas->count() > 0)) {
                                    if ($esReflectivo) {
                                        // CASO REFLECTIVO: Usar descripción tal cual (ya contiene tallas y cantidad total)
                                        $descripcionConTallas = '';
                                        
                                        if ($orden->prendas && $orden->prendas->count() > 0) {
                                            foreach ($orden->prendas as $index => $prenda) {
                                                if ($index > 0) {
                                                    $descripcionConTallas .= "\n\n";
                                                }
                                                
                                                // Agregar descripción de la prenda (ya tiene tallas incluidas)
                                                if (!empty($prenda->descripcion)) {
                                                    $descripcionConTallas .= $prenda->descripcion;
                                                }
                                            }
                                        }
                                    } else {
                                        // CASO NORMAL: Parsear por "PRENDA X:"
                                        if (strpos($descripcionBase, 'PRENDA ') !== false) {
                                            $prendas = explode('PRENDA ', $descripcionBase);
                                            $prendasCount = 0;
                                            
                                            foreach ($prendas as $index => $prendaBlock) {
                                                if ($index === 0 && empty(trim($prendaBlock))) {
                                                    continue;
                                                }
                                                
                                                $prendaBlock = trim($prendaBlock);
                                                if (empty($prendaBlock)) {
                                                    continue;
                                                }
                                                
                                                preg_match('/^(\d+):/', $prendaBlock, $matches);
                                                $numPrenda = isset($matches[1]) ? intval($matches[1]) : ($prendasCount + 1);
                                                
                                                $descripcionConTallas .= "PRENDA " . $prendaBlock;
                                                
                                                if ($orden->prendas && $orden->prendas->count() > 0) {
                                                    $prendaActual = $orden->prendas->where('numero_prenda', $numPrenda)->first();
                                                    
                                                    if (!$prendaActual && $prendasCount < $orden->prendas->count()) {
                                                        $prendaActual = $orden->prendas[$prendasCount];
                                                    }
                                                    
                                                    if ($prendaActual && $prendaActual->tallas && $prendaActual->tallas->count() > 0) {
                                                        $tallasTexto = [];
                                                        foreach ($prendaActual->tallas as $tallaRecord) {
                                                            if ($tallaRecord->cantidad > 0) {
                                                                $tallasTexto[] = "{$tallaRecord->genero}-{$tallaRecord->talla}: {$tallaRecord->cantidad}";
                                                            }
                                                        }
                                                        if (!empty($tallasTexto)) {
                                                            $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                                        }
                                                    }
                                                }
                                                
                                                $prendasCount++;
                                                if ($prendasCount < count($prendas)) {
                                                    $descripcionConTallas .= "\n\n";
                                                }
                                            }
                                        } else {
                                            // Descripción sin formato PRENDA
                                            $descripcionConTallas = $descripcionBase;
                                            
                                            if ($orden->prendas && $orden->prendas->count() > 0) {
                                                $prendaActual = $orden->prendas->first();
                                                
                                                if ($prendaActual && $prendaActual->tallas && $prendaActual->tallas->count() > 0) {
                                                    $tallasTexto = [];
                                                    foreach ($prendaActual->tallas as $tallaRecord) {
                                                        if ($tallaRecord->cantidad > 0) {
                                                            $tallasTexto[] = "{$tallaRecord->genero}-{$tallaRecord->talla}: {$tallaRecord->cantidad}";
                                                        }
                                                    }
                                                    if (!empty($tallasTexto)) {
                                                        $descripcionConTallas .= "\n\nTallas: " . implode(', ', $tallasTexto);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if (empty($descripcionConTallas)) {
                                    $descripcionConTallas = $descripcionBase;
                                }
                            @endphp
                            <div class="table-row" data-orden-id="{{ $orden->numero_pedido ?? 'sin-numero' }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 100px; justify-content: center; position: relative;">
                                    <button class="action-view-btn" title="Ver detalles" data-orden-id="{{ $orden->numero_pedido ?? 'sin-numero' }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="action-menu" data-orden-id="{{ $orden->numero_pedido ?? 'sin-numero' }}">
                                        <a href="#" class="action-menu-item" data-action="detalle">
                                            <i class="fas fa-eye"></i>
                                            <span>Detalle</span>
                                        </a>
                                        <a href="#" class="action-menu-item" data-action="seguimiento">
                                            <i class="fas fa-tasks"></i>
                                            <span>Seguimiento</span>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Estado (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="estado-dropdown estado-{{ str_replace(' ', '-', strtolower($orden->estado)) }}" data-orden-id="{{ $orden->numero_pedido ?? 'sin-numero' }}">
                                            @foreach(\App\Models\PedidoProduccion::ESTADOS as $estado)
                                                <option value="{{ $estado }}" {{ $orden->estado === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Área (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="area-dropdown" data-orden-id="{{ $orden->numero_pedido ?? 'sin-numero' }}">
                                            @foreach($areaOptions as $area)
                                                <option value="{{ $area }}" {{ $orden->area === $area ? 'selected' : '' }}>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Día de entrega (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="dia-entrega-dropdown" data-orden-id="{{ $orden->numero_pedido ?? 'sin-numero' }}">
                                            <option value="">Seleccionar</option>
                                            @foreach(\App\Models\PedidoProduccion::DIAS_ENTREGA as $dia)
                                                <option value="{{ $dia }}" {{ $orden->dia_de_entrega == $dia ? 'selected' : '' }}>{{ $dia }} días</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Total de días -->
                                <div class="table-cell" style="flex: 0 0 120px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $orden->calcularDiasHabiles() }}</span>
                                    </div>
                                </div>
                                
                                <!-- Pedido -->
                                <div class="table-cell" style="flex: 0 0 120px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">{{ $orden->numero_pedido ?? $orden->id }}</span>
                                    </div>
                                </div>
                                
                                <!-- Cliente -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $orden->cliente ?? '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Descripción -->
                                <div class="table-cell" style="flex: 10;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span style="color: #6b7280; font-size: 0.875rem; cursor: pointer; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Descripción', {{ json_encode($descripcionConTallas) }})" title="Click para ver completo">
                                            @php
                                                if ($orden->prendas && $orden->prendas->count() > 0) {
                                                    $prendasInfo = $orden->prendas->map(function($prenda) {
                                                        return $prenda->nombre_prenda ?? 'Prenda sin nombre';
                                                    })->unique()->toArray();
                                                    $descripcion = !empty($prendasInfo) ? implode(', ', $prendasInfo) : '-';
                                                    echo $descripcion . ' <span style="color: #3b82f6; font-weight: 600;">...</span>';
                                                } else {
                                                    echo '-';
                                                }
                                            @endphp
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Cantidad -->
                                <div class="table-cell" style="flex: 0 0 100px;">
                                    <div class="cell-content" style="margin-left: 50px;">
                                        <span>
                                            @php
                                                $cantidadTotal = $orden->prendas->sum('cantidad');
                                                echo $cantidadTotal > 0 ? $cantidadTotal : '-';
                                            @endphp
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Novedades -->
                                <div class="table-cell" style="flex: 0 0 120px;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <button 
                                            class="btn-edit-novedades"
                                            data-full-novedades="{{ addslashes($orden->novedades ?? '') }}"
                                            onclick="event.stopPropagation(); openNovedadesModal('{{ $orden->numero_pedido ?? 'sin-numero' }}', `{{ addslashes($orden->novedades ?? '') }}`)"
                                            title="Editar novedades"
                                            type="button">
                                            @if($orden->novedades)
                                                <span class="novedades-text">{{ Str::limit($orden->novedades, 50, '...') }}</span>
                                            @else
                                                <span class="novedades-text empty">Sin novedades</span>
                                            @endif
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Asesor -->
                                <div class="table-cell" style="flex: 0 0 120px;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span>{{ $orden->asesora?->name ?? '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Forma de pago -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span>{{ $orden->forma_de_pago ?? '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha de creación -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span>{{ $orden->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha estimada entrega -->
                                <div class="table-cell fecha-estimada-cell" style="flex: 0 0 180px;" data-fecha-estimada="{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : '-' }}">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span class="fecha-estimada-span">{{ $orden->fecha_estimada_de_entrega ? \Carbon\Carbon::parse($orden->fecha_estimada_de_entrega)->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Encargado orden -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span>
                                            @php
                                                $encargado = $orden->procesos()
                                                    ->orderBy('created_at', 'desc')
                                                    ->value('encargado');
                                                echo $encargado ?? '-';
                                            @endphp
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <p>No hay órdenes disponibles</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Mostrando 1-25 de {{ $ordenes->total() }} registros</span>
                </div>
                <div class="pagination-controls" id="paginationControls">
                    @if($ordenes->hasPages())
                        <button class="pagination-btn" data-page="1" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $ordenes->currentPage() - 1 }}" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        
                        @php
                            $start = max(1, $ordenes->currentPage() - 2);
                            $end = min($ordenes->lastPage(), $ordenes->currentPage() + 2);
                        @endphp
                        
                        @for($i = $start; $i <= $end; $i++)
                            <button class="pagination-btn page-number {{ $i == $ordenes->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                                {{ $i }}
                            </button>
                        @endfor
                        
                        <button class="pagination-btn" data-page="{{ $ordenes->currentPage() + 1 }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $ordenes->lastPage() }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modales necesarios para funcionalidad -->
    <div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

    <div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
        <x-orders-components.order-detail-modal />
    </div>

    <!-- Modal de Imagen de Orden -->
    @include('components.modal-imagen')

    <!-- Modal de Seguimiento del Pedido -->
    <x-orders-components.order-tracking-modal />

    <!-- Modal para Editar Novedades -->
    @include('components.modals.novedades-edit-modal')

    <!-- Modal de confirmación moderno para eliminar orden -->
    <div id="deleteConfirmationModal" class="delete-confirmation-modal" style="display: none;">
        <div class="delete-modal-overlay" id="deleteModalOverlay"></div>
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <div class="delete-icon-wrapper">
                    <svg class="delete-header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3 class="delete-modal-title">Confirmar Eliminación</h3>
            </div>
            <div class="delete-modal-body">
                <p class="delete-modal-message" id="deleteModalMessage">¿Estás seguro de que deseas eliminar la orden <strong id="deleteOrderId"></strong>? Esto eliminará todos los registros relacionados y no se puede deshacer.</p>
            </div>
            <div class="delete-modal-footer">
                <button class="delete-btn delete-btn-secondary" id="deleteCancelBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Cancelar
                </button>
                <button class="delete-btn delete-btn-danger" id="deleteConfirmBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Eliminar Orden
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Orden -->
    @include('components.orders-components.order-edit-modal')

    <!-- Modal de Filtros -->
    <div class="filter-modal-overlay" id="filterModalOverlay">
        <div class="filter-modal">
            <div class="filter-modal-header">
                <h3 id="filterModalTitle">Filtrar por</h3>
                <button type="button" class="filter-modal-close" onclick="closeFilterModal()">×</button>
            </div>
            <div class="filter-modal-body">
                <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar...">
                <div class="filter-options" id="filterOptions"></div>
            </div>
            <div class="filter-modal-footer">
                <button type="button" class="btn-filter-reset" onclick="resetFilters()">Limpiar</button>
                <button type="button" class="btn-filter-apply" onclick="applyFilters()">Aplicar</button>
            </div>
        </div>
    </div>

    <!-- Botón Flotante para Limpiar Filtros -->
    <button id="clearFiltersBtn" class="floating-clear-filters" onclick="clearAllFilters()" title="Limpiar todos los filtros">
        <span class="material-symbols-rounded">filter_alt_off</span>
        <div class="floating-clear-filters-tooltip">Limpiar filtros</div>
    </button>

    <script>
        // Pasar opciones de area a JS
        window.areaOptions = @json($areaOptions);

        // Sincronizar scroll horizontal del header con el contenido
        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainer = document.querySelector('.table-scroll-container');
            const tableHead = document.querySelector('.table-head');
            
            if (scrollContainer && tableHead) {
                scrollContainer.addEventListener('scroll', function() {
                    tableHead.style.transform = 'translateX(' + (-this.scrollLeft) + 'px)';
                });
            }
        });

        // ==================== MODAL DE CELDA ====================
        function abrirModalCelda(titulo, contenido) {
            let contenidoLimpio = contenido || '-';
            
            contenidoLimpio = contenidoLimpio.replace(/\*\*\*/g, '');
            contenidoLimpio = contenidoLimpio.replace(/\*\*\*\s*[A-Z\s]+:\s*\*\*\*/g, '');
            
            let prendas = contenidoLimpio.split('\n\n').filter(p => p.trim());
            
            let htmlContenido = '';
            
            prendas.forEach((prenda, index) => {
                let lineas = prenda.split('\n').map(l => l.trim()).filter(l => l);
                
                htmlContenido += '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">';
                
                lineas.forEach((linea, i) => {
                    if (linea.match(/^(\d+)\.\s+Prenda:/i) || linea.match(/^Prenda \d+:/i) || linea.match(/^PRENDA \d+:/i)) {
                        htmlContenido += `<div style="font-weight: 700; font-size: 1rem; margin-bottom: 0.5rem; color: #1f2937;">${linea}</div>`;
                    }
                    else if (linea.match(/^Color:|^Tela:|^Manga:/i)) {
                        htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;">${linea}</div>`;
                    }
                    else if (linea.match(/^DESCRIPCIÓN:|^DESCRIPCION:/i)) {
                        htmlContenido += `<div style="margin-top: 0.5rem; margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                    }
                    else if (linea.match(/^(Reflectivo|Bolsillos|Broche|Ojal|BOTÓN|BOTON|CREMALLERA):/i)) {
                        htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                    }
                    else if (linea.startsWith('•') || linea.startsWith('-')) {
                        htmlContenido += `<div style="margin-left: 1.5rem; margin-bottom: 0.25rem; color: #374151;">• ${linea.substring(1).trim()}</div>`;
                    }
                    else if (linea.match(/^Tallas:/i)) {
                        htmlContenido += `<div style="margin-top: 0.5rem; margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                    }
                    else if (linea) {
                        htmlContenido += `<div style="margin-bottom: 0.25rem; color: #374151;">${linea}</div>`;
                    }
                });
                
                htmlContenido += '</div>';
            });
            
            const modalHTML = `
                <div id="celdaModal" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    animation: fadeIn 0.3s ease;
                " onclick="if(event.target.id === 'celdaModal') cerrarModalCelda()">
                    <div style="
                        background: white;
                        border-radius: 12px;
                        padding: 2rem;
                        max-width: 600px;
                        width: 90%;
                        max-height: 80vh;
                        overflow-y: auto;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        animation: slideIn 0.3s ease;
                    " onclick="event.stopPropagation()">
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 1.5rem;
                            border-bottom: 2px solid #e5e7eb;
                            padding-bottom: 1rem;
                        ">
                            <h2 style="
                                margin: 0;
                                font-size: 1.25rem;
                                font-weight: 700;
                                color: #1f2937;
                            ">${titulo}</h2>
                            <button onclick="cerrarModalCelda()" style="
                                background: none;
                                border: none;
                                font-size: 1.5rem;
                                cursor: pointer;
                                color: #6b7280;
                                padding: 0;
                                width: 32px;
                                height: 32px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 6px;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.background='#f3f4f6'; this.style.color='#1f2937'" onmouseout="this.style.background='none'; this.style.color='#6b7280'">
                                ✕
                            </button>
                        </div>
                        <div style="
                            color: #374151;
                            font-size: 0.95rem;
                            line-height: 1.8;
                        ">
                            ${htmlContenido}
                        </div>
                        <div style="
                            margin-top: 1.5rem;
                            display: flex;
                            justify-content: flex-end;
                            gap: 0.75rem;
                        ">
                            <button onclick="cerrarModalCelda()" style="
                                background: white;
                                border: 2px solid #d1d5db;
                                color: #374151;
                                padding: 0.625rem 1.25rem;
                                border-radius: 6px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.875rem;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af'" onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            document.addEventListener('keydown', function cerrarConEsc(event) {
                if (event.key === 'Escape') {
                    cerrarModalCelda();
                    document.removeEventListener('keydown', cerrarConEsc);
                }
            });
        }

        function cerrarModalCelda() {
            const modal = document.getElementById('celdaModal');
            if (modal) {
                modal.style.animation = 'fadeIn 0.3s ease reverse';
                setTimeout(() => modal.remove(), 300);
            }
        }
    </script>

    <!-- Modal de Detalles de Pedido (Lista de Prendas) -->
    <style>
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            overflow-y: auto !important;
            z-index: 99999 !important;
        }
        
        .modal-overlay[style*="display: flex"] {
            display: flex !important;
        }
        
        .modal-content {
            position: relative !important;
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            max-height: 95vh !important;
            overflow-y: auto !important;
        }
    </style>
    
    <div id="modal-detalle-pedido" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 900px; width: 95%;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 1.5rem; border-radius: 8px 8px 0 0; position: relative;">
                <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">
                    Detalles del Pedido #<span id="detalle-pedido-numero"></span>
                </h3>
                <button onclick="cerrarModalDetallePedido()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; position: absolute; right: 1rem; top: 1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem; max-height: 70vh; overflow-y: auto;">
                <div id="detalle-contenido">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')

    <!-- ORDER DETAIL MODAL MANAGER (debe cargarse antes de otros scripts) -->
    <script src="{{ asset('js/orders js/order-detail-modal-manager.js') }}?v={{ time() }}"></script>
    
    <!-- NOVEDADES MODAL MANAGER -->
    <script src="{{ asset('js/orders js/novedades-modal.js') }}?v={{ time() }}"></script>
    
    <!-- ORDERS TABLE MODULES (SOLID Architecture) -->
    <script src="{{ asset('js/orders js/modules/formatting.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/storageModule.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/notificationModule.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/rowManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/updates.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/dropdownManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/diaEntregaModule.js') }}?v={{ time() }}"></script>
    
    <!-- CELL EDIT MODAL MODULES (DEBE CARGAR ANTES de cellClickHandler) -->
    <script src="{{ asset('js/orders js/modules/cellEditModal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/cellClickHandler.js') }}?v={{ time() }}"></script>
    
    <!-- FIX: Descripción de prendas en modal (DEBE CARGAR ANTES de orders-table-v2.js) -->
    <script src="{{ asset('js/orders js/descripcion-prendas-fix.js') }}?v={{ time() }}"></script>
    
    <!-- SCRIPTS REFACTORIZADOS CON MÓDULOS -->
    <!-- Versión V2: Usa módulos SOLID y elimina ~79% código duplicado -->
    <script src="{{ asset('js/orders js/orders-table-v2.js') }}?v={{ time() }}"></script>
    
    <!-- SCRIPTS COMPLEMENTARIOS (sin cambios) -->
    <script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/descripcion-prendas-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/order-navigation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/pagination.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/historial-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/realtime-listeners.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}?v={{ time() }}"></script>
    
    <!-- ACTION MENU HANDLER -->
    <script src="{{ asset('js/orders js/action-menu.js') }}?v={{ time() }}"></script>
    
    <!-- SISTEMA DE RECIBOS DE SUPERVISOR-PEDIDOS -->
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
    
    <!-- FUNCIONES DE COMPATIBILIDAD PARA REGISTROS -->
    <script>
        // Estado del toggle para registros
        let registrosToggleState = {
            enGaleria: false,
            modalActual: null,
            contenidoActual: null
        };
        
        // Contador de clics para debugging
        let toggleClickCount = 0;
        let lastClickTime = 0;
        
        // Sistema de detección de clics global
        let globalClickCount = 0;
        let lastGlobalClickTime = 0;
        
        // Detectar todos los clics en la página
        document.addEventListener('click', function(event) {
            globalClickCount++;
            const currentTime = Date.now();
            const timeSinceLastClick = lastGlobalClickTime ? currentTime - lastGlobalClickTime : 0;
            lastGlobalClickTime = currentTime;
            
            const target = event.target;
            const targetInfo = {
                tagName: target.tagName,
                className: target.className,
                id: target.id,
                textContent: target.textContent ? target.textContent.substring(0, 50) : '',
                onclick: target.onclick ? 'has-onclick' : 'no-onclick',
                parentInfo: target.parentElement ? {
                    tagName: target.parentElement.tagName,
                    className: target.parentElement.className,
                    id: target.parentElement.id
                } : null
            };
            
            console.log(`[GLOBAL-CLICK] Click #${globalClickCount} - Tiempo: ${timeSinceLastClick}ms`);
            console.log(`[GLOBAL-CLICK] Target:`, targetInfo);
            
            // Detectar si es un botón de toggle
            if (target.textContent && target.textContent.includes('📄')) {
                console.log(`[GLOBAL-CLICK]  BOTÓN DE FACTURA DETECTADO`);
            }
            if (target.textContent && target.textContent.includes('🖼️')) {
                console.log(`[GLOBAL-CLICK]  BOTÓN DE GALERÍA DETECTADO`);
            }
            
            // Detectar si es un botón de prenda
            if (target.closest('.prenda-header')) {
                console.log(`[GLOBAL-CLICK]  CABECERA DE PRENDA DETECTADA`);
                
                // Verificar si la función togglePrendaAccordion existe
                if (typeof window.togglePrendaAccordion === 'function') {
                    console.log(`[GLOBAL-CLICK] ✅ togglePrendaAccordion está disponible`);
                } else {
                    console.error(`[GLOBAL-CLICK] ❌ togglePrendaAccordion NO está disponible`);
                }
                
                // Intentar obtener el ID del accordion
                const header = target.closest('.prenda-header');
                if (header) {
                    const onclickAttr = header.getAttribute('onclick');
                    console.log(`[GLOBAL-CLICK] onclick attribute: ${onclickAttr}`);
                    
                    // Extraer el ID del onclick
                    const idMatch = onclickAttr.match(/togglePrendaAccordion\([^,]+,\s*['"]([^'"]+)['"]\)/);
                    if (idMatch) {
                        const accordionId = idMatch[1];
                        console.log(`[GLOBAL-CLICK] ID del accordion: ${accordionId}`);
                        
                        // SOLO prevenir el onclick original si queremos controlar completamente el toggle
                        // Por ahora, dejamos que el onclick original maneje el toggle
                        console.log(`[GLOBAL-CLICK] 📋 Dejando que el onclick original maneje el toggle`);
                        return;
                    } else {
                        console.error(`[GLOBAL-CLICK] No se pudo extraer el ID del accordion - Regex falló`);
                        console.log(`[GLOBAL-CLICK] onclick completo: "${onclickAttr}"`);
                    }
                }
            }
            
            // Detectar si es un proceso
            if (target.closest('.proceso-item')) {
                const procesoItem = target.closest('.proceso-item');
                const procesoText = procesoItem.textContent ? procesoItem.textContent.substring(0, 30) : '';
                console.log('[GLOBAL-CLICK]  PROCESO DETECTADO: ' + procesoText);
            }
        });
        
        // Función toggleFactura para registros
        window.toggleFactura = function() {
            // Incrementar contador de clics
            toggleClickCount++;
            const currentTime = Date.now();
            const timeSinceLastClick = lastClickTime ? currentTime - lastClickTime : 0;
            lastClickTime = currentTime;
            
            console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Tiempo desde último: ${timeSinceLastClick}ms`);
            
            // Buscar el modal de recibo actual (usar el selector correcto)
            const modalRecibo = document.getElementById('order-detail-modal-wrapper');
            if (!modalRecibo) {
                console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Modal no encontrado`);
                return;
            }
            
            // Buscar la galería
            const galeria = document.getElementById('galeria-modal-costura');
            
            // Estado actual del modal
            const card = modalRecibo.querySelector('.order-detail-card');
            const modalState = {
                modalDisplay: modalRecibo.style.display,
                cardDisplay: card?.style.display || 'no-card',
                galeriaDisplay: galeria?.style.display || 'no-galeria',
                enGaleria: registrosToggleState.enGaleria
            };
            
            // CORRECCIÓN: Sincronizar estado con el DOM real
            const galeriaEstaVisible = galeria && galeria.style.display === 'flex';
            const reciboEstaVisible = card && card.style.display === 'block';
            
            // Si el estado no coincide con el DOM, actualizarlo
            if (galeriaEstaVisible && !registrosToggleState.enGaleria) {
                registrosToggleState.enGaleria = true;
                console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - CORRECCIÓN: Estado sincronizado a galería`);
            } else if (reciboEstaVisible && registrosToggleState.enGaleria) {
                registrosToggleState.enGaleria = false;
                console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - CORRECCIÓN: Estado sincronizado a recibo`);
            }
            
            console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Estado actual:`, modalState);
            console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Estado sincronizado: enGaleria=${registrosToggleState.enGaleria}`);
            
            if (registrosToggleState.enGaleria) {
                // Estamos en galería, volver al recibo
                console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Volviendo al recibo`);
                
                // Ocultar galería
                if (galeria) {
                    galeria.style.display = 'none';
                }
                
                // Mostrar recibo (mostrar la card del modal)
                const card = modalRecibo.querySelector('.order-detail-card');
                if (card) {
                    card.style.display = 'block';
                }
                
                // Restaurar tamaño del modal
                modalRecibo.style.maxWidth = '672px';
                modalRecibo.style.width = '90%';
                
                // Esperar un momento para asegurar que los cambios se apliquen
                setTimeout(() => {
                    // Verificación final
                    const modalVisible = (
                        modalRecibo.offsetParent !== null && 
                        modalRecibo.style.display === 'flex' &&
                        card && card.style.display === 'block'
                    );
                    
                    registrosToggleState.enGaleria = false;
                    
                    console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Verificación final: Modal visible=${modalVisible}`);
                    
                    // Si no está visible, usar método alternativo
                    if (!modalVisible) {
                        modalRecibo.style.visibility = 'visible';
                        modalRecibo.style.opacity = '1';
                        modalRecibo.style.pointerEvents = 'auto';
                        console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Aplicado método alternativo`);
                    }
                }, 50);
            } else {
                // Estamos en recibo, mostrar galería
                console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Mostrando galería`);
                
                // Ocultar recibo (ocultar la card del modal)
                const card = modalRecibo.querySelector('.order-detail-card');
                if (card) {
                    card.style.display = 'none';
                }
                
                // Mostrar galería
                if (galeria) {
                    galeria.style.display = 'flex';
                }
                
                // Agrandar modal para galería
                modalRecibo.style.maxWidth = '95%';
                modalRecibo.style.width = '95%';
                
                // Esperar un momento para asegurar que los cambios se apliquen
                setTimeout(() => {
                    registrosToggleState.enGaleria = true;
                    console.log(`[TOGGLE-DEBUG] Click #${toggleClickCount} - Estado cambiado a galería`);
                }, 50);
            }
        };
        
        // Función toggleGaleria para registros (alias de toggleFactura)
        window.toggleGaleria = function() {
            window.toggleFactura();
        };
    </script>
    
    <!-- MODALES DE RECIBOS (de supervisor-pedidos) -->
    @include('components.modals.recibos-process-selector')
    @include('components.modals.recibos-intermediate-modal')
    @include('components.modals.recibo-dinamico-modal')

    <!-- FILTER SYSTEM -->
    <script src="{{ asset('js/orders js/filter-system.js') }}?v={{ time() }}"></script>

    <!-- ROW CONDITIONAL COLORS -->
    <script src="{{ asset('js/orders js/row-conditional-colors.js') }}?v={{ time() }}"></script>
    
    <!-- WEBSOCKET TEST (para desarrollo) -->
    <script src="{{ asset('js/orders js/websocket-test.js') }}?v={{ time() }}"></script>

    <!-- ORDER TRACKING MODULES (SOLID Architecture) -->
    <script src="{{ asset('js/order-tracking/modules/dateUtils.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/holidayManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/areaMapper.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/trackingService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/trackingUI.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/apiClient.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/processManager.js') }}?v={{ time() }}"></script>
    <!-- TableManager compatible con flexbox (no tabla HTML) -->
    <script src="{{ asset('js/order-tracking/modules/tableManager-orders-compat.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/dropdownManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/orderTracking-v2.js') }}?v={{ time() }}"></script>

    <!-- TRACKING MODAL HANDLER -->
    <script src="{{ asset('js/orders js/tracking-modal-handler.js') }}?v={{ time() }}"></script>

    <!-- DEBUG SIDEBAR WIDTHS -->
    <script src="{{ asset('js/debug-sidebar.js') }}?v={{ time() }}"></script>
@endpush
