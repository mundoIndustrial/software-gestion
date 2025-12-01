@extends('layouts.app')

@section('content')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/dropdown-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/descripcion-prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/viewButtonDropdown.css') }}">

    <div class="table-container">
        <div class="table-header" id="tableHeader">
            <h1 class="table-title">
                <i class="fas {{ $icon }}"></i>
                {{ $title }}
            </h1>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="buscarOrden" placeholder="Buscar por pedido o cliente..." class="search-input">
                </div>
            </div>

            <!-- llamada de botones de la  tabla -->
            <div class="table-actions"></div>
        </div>

        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <table id="tablaOrdenes" class="modern-table">
                    <thead class="table-head">
                        @if($ordenes->isNotEmpty())
                            <tr>
                                <th class="table-header-cell acciones-column">
                                    <div class="header-content">
                                        <span class="header-text">Acciones</span>
                                    </div>
                                </th>
                                @php 
                                    // Definir orden de columnas base (antes de procesos)
                                    $columnasBase = [
                                        'estado',
                                        'area',
                                        'dia_de_entrega',
                                        'total_de_dias_',
                                        'numero_pedido',
                                        'cliente',
                                        'descripcion_prendas',
                                        'cantidad_total',
                                        'novedades',
                                        'asesora',
                                        'forma_de_pago',
                                        'fecha_de_creacion_de_orden',
                                        'fecha_estimada_de_entrega',
                                        'encargado_orden',
                                    ];
                                    
                                    $columnIndex = 0;
                                @endphp
                                
                                {{-- Columnas base --}}
                                @foreach($columnasBase as $colName)
                                    @if($colName !== 'acciones')
                                        {{-- Ocultar columnas de administración --}}
                                        @if(in_array($colName, ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id']))
                                            {{-- Columnas ocultas --}}
                                        {{-- Ocultar día de entrega para supervisores --}}
                                        @elseif($colName === 'dia_de_entrega' && auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                            {{-- Columna oculta para supervisores --}}
                                        @else
                                            @php 
                                                $columnLabels = [
                                                    'acciones' => 'Acciones',
                                                    'estado' => 'Estado',
                                                    'area' => 'Área',
                                                    'dia_de_entrega' => 'Día de entrega',
                                                    'total_de_dias_' => 'Total de días',
                                                    'numero_pedido' => 'Pedido',
                                                    'cliente' => 'Cliente',
                                                    'descripcion_prendas' => 'Descripción',
                                                    'cantidad_total' => 'Cantidad',
                                                    'novedades' => 'Novedades',
                                                    'asesora' => 'Asesor',
                                                    'forma_de_pago' => 'Forma de pago',
                                                    'fecha_de_creacion_de_orden' => 'Fecha de creación de orden',
                                                    'fecha_estimada_de_entrega' => 'Fecha estimada de entrega',
                                                    'encargado_orden' => 'Encargado orden',
                                                ];
                                                $colLabel = $columnLabels[$colName] ?? $colName;
                                            @endphp
                                            <th class="table-header-cell" data-column="{{ $colName }}">
                                                <div class="header-content">
                                                    <span class="header-text">{{ $colLabel }}</span>
                                                    @if($colName !== 'acciones' && $colName !== 'cantidad_total')
                                                        <button class="filter-btn" data-column="{{ $columnIndex }}" data-column-name="{{ $colName }}">
                                                            <i class="fas fa-filter"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </th>
                                            @php $columnIndex++; @endphp
                                        @endif
                                    @else
                                        <th class="table-header-cell" data-column="acciones">
                                            <div class="header-content">
                                                <span class="header-text">Acciones</span>
                                            </div>
                                        </th>
                                        @php $columnIndex++; @endphp
                                    @endif
                                @endforeach
                            </tr>
                        @endif
                    </thead>
                    <tbody id="tablaOrdenesBody" class="table-body">
                        @if($ordenes->isEmpty())
                            <tr class="table-row">
                                <td colspan="51" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                                    No hay resultados que coincidan con los filtros aplicados.
                                    <br><small style="color: #999;">Debug: Total órdenes = {{ $ordenes->count() }}, CurrentPage = {{ $ordenes->currentPage() }}, LastPage = {{ $ordenes->lastPage() }}</small>
                                </td>
                            </tr>
                        @else
                            <!-- DEBUG: Total órdenes = {{ $ordenes->count() }}, CurrentPage = {{ $ordenes->currentPage() }}, LastPage = {{ $ordenes->lastPage() }} -->
                            @foreach($ordenes as $orden)
                                @php
                                    $totalDias = intval($totalDiasCalculados[$orden->numero_pedido] ?? 0);
                                    $estado = $orden->estado ?? '';
                                    $diaDeEntrega = $orden->dia_de_entrega ? intval($orden->dia_de_entrega) : null;
                                    $conditionalClass = '';
                                    
                                    if ($estado === 'Entregado') {
                                        $conditionalClass = 'row-delivered';
                                    } elseif ($estado === 'Anulada') {
                                        $conditionalClass = 'row-anulada';
                                    } elseif ($diaDeEntrega !== null && $diaDeEntrega > 0) {
                                        if ($totalDias >= 15) {
                                            $conditionalClass = 'row-dia-entrega-critical';
                                        } elseif ($totalDias >= 10 && $totalDias <= 14) {
                                            $conditionalClass = 'row-dia-entrega-danger';
                                        } elseif ($totalDias >= 5 && $totalDias <= 9) {
                                            $conditionalClass = 'row-dia-entrega-warning';
                                        }
                                    } else {
                                        if ($totalDias > 20) {
                                            $conditionalClass = 'row-secondary';
                                        } elseif ($totalDias == 20) {
                                            $conditionalClass = 'row-danger-light';
                                        } elseif ($totalDias > 14 && $totalDias < 20) {
                                            $conditionalClass = 'row-warning';
                                        }
                                    }
                                    
                                    // Definir columnas base en orden
                                    $columnasBase = [
                                        'estado', 'area', 'dia_de_entrega', 'total_de_dias_', 'numero_pedido', 'cliente',
                                        'descripcion_prendas', 'cantidad_total', 'novedades', 'asesora', 'forma_de_pago',
                                        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'encargado_orden'
                                    ];
                                    
                                    // Campos de proceso
                                    $procesosOrdenados = [
                                        'insumos_y_telas', 'encargados_insumos',
                                        'corte', 'encargados_de_corte',
                                        'bordado', 'codigo_de_bordado',
                                        'estampado', 'encargados_estampado',
                                        'costura', 'modulo',
                                        'reflectivo', 'encargado_reflectivo',
                                        'lavanderia', 'encargado_lavanderia',
                                        'arreglos', 'encargado_arreglos',
                                        'control_de_calidad', 'encargados_calidad',
                                        'entrega', 'encargados_entrega',
                                        'despacho'
                                    ];
                                @endphp
                                <tr class="table-row {{ $conditionalClass }}" data-order-id="{{ $orden->id }}" data-numero-pedido="{{ $orden->numero_pedido }}" data-total-dias="{{ intval($totalDiasCalculados[$orden->numero_pedido] ?? 0) }}">
                                    <!-- DEBUG: Pedido={{ $orden->pedido ?? $orden->numero_pedido }}, Estado={{ $orden->estado }}, Area={{ $orden->area ?? 'NULL' }}, Cliente={{ $orden->cliente }} -->
                                    {{-- Columna de Acciones --}}
                                    <td class="table-cell acciones-column" style="min-width: 100px !important;">
                                        <div class="cell-content" style="display: flex; gap: 6px; flex-wrap: nowrap; align-items: center; justify-content: center; padding: 4px 0;">
                                            @if($context === 'registros')
                                                <button class="action-btn detail-btn" onclick="createViewButtonDropdown({{ $orden->numero_pedido }})" title="Ver opciones" style="background-color: #28a745; color: white; border: 1px solid #28a745; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">
                                                    <i class="fas fa-eye" style="margin-right: 4px;"></i> Ver
                                                </button>
                                            @elseif(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                <button class="action-btn detail-btn" onclick="createViewButtonDropdown({{ $orden->numero_pedido }})" title="Ver opciones" style="background-color: #28a745; color: white; border: 1px solid #28a745; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">
                                                    <i class="fas fa-eye" style="margin-right: 4px;"></i> Ver
                                                </button>
                                            @else
                                                <button class="action-btn edit-btn" onclick="openEditModal({{ $orden->numero_pedido }})" title="Editar orden" style="background-color: #007bff; color: white; border: 1px solid #007bff; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">Editar</button>
                                                <button class="action-btn detail-btn" onclick="createViewButtonDropdown({{ $orden->numero_pedido }})" title="Ver opciones" style="background-color: #28a745; color: white; border: 1px solid #28a745; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">Ver</button>
                                                <button class="action-btn delete-btn" onclick="deleteOrder({{ $orden->numero_pedido }})" title="Eliminar orden" style="background-color: #dc3545; color: white; border: 1px solid #dc3545; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">Borrar</button>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    @foreach($columnasBase as $colName)
                                        @if($colName === 'dia_de_entrega' && auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                            {{-- Ocultar para supervisores --}}
                                        @else
                                            <td class="table-cell" data-column="{{ $colName }}">
                                                <div class="cell-content" title="{{ $orden->{$colName} ?? '' }}">
                                                    @if($colName === 'estado')
                                                        @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                            <select class="estado-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $orden->$colName }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                                @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                    <option value="{{ $estado }}" {{ $orden->$colName === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <select class="estado-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $orden->$colName }}">
                                                                @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                    <option value="{{ $estado }}" {{ $orden->$colName === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    @elseif($colName === 'area')
                                                        @php
                                                            $areaValue = $areasMap[$orden->numero_pedido] ?? $orden->area ?? '';
                                                        @endphp
                                                        @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                            <select class="area-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $areaValue }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                                <option value="">Seleccionar área</option>
                                                                @foreach($areaOptions as $areaOption)
                                                                    <option value="{{ $areaOption }}" {{ $areaValue === $areaOption ? 'selected' : '' }}>{{ $areaOption }}</option>
                                                                @endforeach
                                                                @if($areaValue && !in_array($areaValue, $areaOptions))
                                                                    <option value="{{ $areaValue }}" selected>{{ $areaValue }}</option>
                                                                @endif
                                                            </select>
                                                        @else
                                                            <select class="area-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $areaValue }}">
                                                                <option value="">Seleccionar área</option>
                                                                @foreach($areaOptions as $areaOption)
                                                                    <option value="{{ $areaOption }}" {{ $areaValue === $areaOption ? 'selected' : '' }}>{{ $areaOption }}</option>
                                                                @endforeach
                                                                @if($areaValue && !in_array($areaValue, $areaOptions))
                                                                    <option value="{{ $areaValue }}" selected>{{ $areaValue }}</option>
                                                                @endif
                                                            </select>
                                                        @endif
                                                    @elseif($colName === 'dia_de_entrega' && $context === 'registros')
                                                        <select class="dia-entrega-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $orden->$colName ?? '' }}">
                                                            <option value="" {{ is_null($orden->$colName) ? 'selected' : '' }}>Seleccionar</option>
                                                            <option value="15" {{ $orden->$colName == 15 ? 'selected' : '' }}>15 días</option>
                                                            <option value="20" {{ $orden->$colName == 20 ? 'selected' : '' }}>20 días</option>
                                                            <option value="25" {{ $orden->$colName == 25 ? 'selected' : '' }}>25 días</option>
                                                            <option value="30" {{ $orden->$colName == 30 ? 'selected' : '' }}>30 días</option>
                                                        </select>
                                                    @else
                                                        <span class="cell-text" data-pedido="{{ $orden->numero_pedido }}">
                                                            @if($colName === 'total_de_dias_')
                                                                <span class="dias-value" data-dias="{{ intval($totalDiasCalculados[$orden->numero_pedido] ?? 0) }}">{{ intval($totalDiasCalculados[$orden->numero_pedido] ?? 0) }}</span>
                                                            @elseif($colName === 'asesora')
                                                                {{ $orden->asesora->name ?? ($orden->$colName ?? '') }}
                                                            @elseif($colName === 'numero_pedido')
                                                                {{ $orden->numero_pedido }}
                                                            @elseif($colName === 'descripcion_prendas')
                                                                <div class="descripcion-preview" data-full-content="{{ base64_encode($orden->descripcion_prendas) }}">
                                                                    <x-descripcion-prendas-formateada :descripcion="$orden->descripcion_prendas" />
                                                                </div>
                                                            @elseif($colName === 'cantidad_total')
                                                                {{ $orden->cantidad_total }}
                                                            @elseif($colName === 'encargado_orden')
                                                                {{ $encargadosCreacionOrdenMap[$orden->numero_pedido] ?? '' }}
                                                            @elseif(in_array($colName, ['fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega']))
                                                                @php
                                                                    echo !empty($orden->$colName) ? \Carbon\Carbon::parse($orden->$colName)->format('d/m/Y') : '';
                                                                @endphp
                                                            @else
                                                                {{ $orden->$colName ?? '' }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Mostrando {{ $ordenes->firstItem() }}-{{ $ordenes->lastItem() }} de {{ $ordenes->total() }} registros</span>
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

    <!-- Modal para filtros -->
    <div id="filterModal" class="filter-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Filtrar por: <span id="filterColumnName"></span></h3>
                <button class="modal-close" id="closeModal"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="modal-search">
                    <div class="search-input-wrapper">
                        <input type="text" id="filterSearch" placeholder="Buscar valores..." style="color: black;">
                    </div>
                </div>

                <div class="filter-options">
                    <div class="filter-actions">
                        <button id="selectAll" class="action-btn select-all">
                            <i class="fas fa-check-double"></i> Seleccionar todos
                        </button>
                        <button id="deselectAll" class="action-btn deselect-all">
                            <i class="fas fa-times"></i> Deseleccionar todos
                        </button>
                    </div>

                    <div class="filter-list" id="filterList"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelFilter">Cancelar</button>
                <button class="btn btn-primary" id="applyFilter">Aplicar filtro</button>
            </div>
        </div>
    </div>

    <!-- Modal para vista completa de celda -->
    <div id="cellModal" class="cell-modal">
        <div class="cell-modal-content">
            <div class="cell-modal-header">
                <h3 class="cell-modal-title">Editar celda</h3>
                <button class="modal-close" id="closeCellModal"><i class="fas fa-times"></i></button>
            </div>
            <div class="cell-modal-body">
                <textarea id="cellEditInput" class="cell-edit-input" rows="5"
                    style="width: 100%; text-align: left; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                <small id="cellEditHint" style="display: block; margin-top: 8px; color: #666; font-style: italic;"></small>
            </div>
            <div class="cell-modal-footer">
                <button id="saveCellEdit" class="btn btn-primary">Guardar</button>
                <button id="cancelCellEdit" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay"></div>

    <script>
        // Pasar opciones de area a JS
        window.areaOptions = @json($areaOptions);
        window.modalContext = '{{ $modalContext }}';
        window.context = '{{ $context ?? '' }}';
        window.fetchUrl = '{{ $fetchUrl }}';
        window.updateUrl = '{{ $updateUrl }}';
        
        // Verificar que las funciones de tracking estén disponibles
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Verificando funciones de tracking...');
            console.log('createViewButtonDropdown disponible:', typeof createViewButtonDropdown === 'function');
            console.log('openOrderTracking disponible:', typeof openOrderTracking === 'function');
            console.log('closeOrderTracking disponible:', typeof closeOrderTracking === 'function');
        });
    </script>

    <div class="order-registration-modal">
        <x-orders-components.order-registration-modal :areaOptions="$areaOptions" />
    </div>

    <div class="order-detail-modal">
        <x-orders-components.order-detail-modal />
    </div>

    <!-- Modal de Seguimiento del Pedido -->
    <x-orders-components.order-tracking-modal />

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

    <script>
        // DEBUG: Verificar estado de la tabla antes de cargar scripts
        console.log('%c=== DEBUG: ESTADO INICIAL DE LA TABLA ===', 'color: #ff0000; font-weight: bold; font-size: 16px;');
        console.log('Total órdenes en HTML: {{ $ordenes->count() }}');
        console.log('Órdenes vacías: {{ $ordenes->isEmpty() ? "SI" : "NO" }}');
        console.log('Página actual: {{ $ordenes->currentPage() }}');
        console.log('Total páginas: {{ $ordenes->lastPage() }}');
        
        setTimeout(() => {
            const tabla = document.getElementById('tablaOrdenes');
            const tbody = tabla ? tabla.querySelector('tbody') : null;
            const filas = tbody ? tbody.querySelectorAll('tr:not(.no-results)') : [];
            
            console.log('%c=== DEBUG: VERIFICACIÓN DEL DOM ===', 'color: #0000ff; font-weight: bold; font-size: 16px;');
            console.log('Tabla encontrada:', !!tabla);
            console.log('tbody encontrado:', !!tbody);
            console.log('Total filas (excluye no-results):', filas.length);
            console.log('Total celdas en primera fila:', filas.length > 0 ? filas[0].querySelectorAll('td').length : 0);
            
            if (filas.length > 0) {
                const primeraFila = filas[0];
                console.log('Primera fila data-order-id:', primeraFila.dataset.orderId);
                console.log('Primera fila HTML (primeros 200 chars):', primeraFila.innerHTML.substring(0, 200));
            }
        }, 100);
    </script>

    <!-- MODULAR MODERN TABLE (SOLID Architecture) -->
    <script src="{{ asset('js/modern-table/modules/storageManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/tableRenderer.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/styleManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/filterManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/dragManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/columnManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/dropdownManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/notificationManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/paginationManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modules/searchManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modern-table/modern-table-v2.js') }}?v={{ time() }}"></script>
    
    <!-- ORDER TRACKING MODULES (SOLID Architecture) -->
    <script src="{{ asset('js/order-tracking/modules/dateUtils.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/holidayManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/areaMapper.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/apiClient.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/trackingService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/trackingUI.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/tableManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/processManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/modules/dropdownManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/order-tracking/orderTracking-v2.js') }}?v={{ time() }}"></script>
    
    <!-- ORDERS TABLE MODULES (SOLID Architecture) -->
    <script src="{{ asset('js/orders js/modules/formatting.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/storageModule.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/notificationModule.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/rowManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/updates.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/dropdownManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/modules/diaEntregaModule.js') }}?v={{ time() }}"></script>
    
    <!-- FIX: Descripción de prendas en modal (DEBE CARGAR ANTES de orders-table-v2.js) -->
    <script src="{{ asset('js/orders js/descripcion-prendas-fix.js') }}?v={{ time() }}"></script>
    
    <!-- SCRIPTS REFACTORIZADOS CON MÓDULOS -->
    <!-- Versión V2: Usa módulos SOLID y elimina ~79% código duplicado -->
    <script src="{{ asset('js/orders js/orders-table-v2.js') }}?v={{ time() }}"></script>
    
    <!-- SCRIPTS COMPLEMENTARIOS (sin cambios) -->
    <script src="{{ asset('js/orders js/descripcion-prendas-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/order-navigation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/pagination.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/historial-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/realtime-listeners.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}?v={{ time() }}"></script>
@endsection
