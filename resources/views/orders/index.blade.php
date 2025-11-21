@extends('layouts.app')

@section('content')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/dropdown-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/viewButtonDropdown.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pagination.css') }}">

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
                                @php $columnIndex = 0; @endphp
                                @foreach(array_keys($ordenes->first()->getAttributes()) as $index => $columna)
                                    @if($columna !== 'id' && $columna !== 'tiempo')
                                        {{-- Ocultar columna "día de entrega" para supervisores --}}
                                        @if($columna === 'dia_de_entrega' && auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                            {{-- Columna oculta para supervisores --}}
                                        @else
                                            <th class="table-header-cell" data-column="{{ $columna }}">
                                                <div class="header-content">
                                                    <span class="header-text">{{ ucfirst(str_replace('_', ' ', $columna)) }}</span>
                                                    @if($columna !== 'acciones')
                                                        <button class="filter-btn" data-column="{{ $columnIndex }}" data-column-name="{{ $columna }}">
                                                            <i class="fas fa-filter"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </th>
                                            @php $columnIndex++; @endphp
                                        @endif
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
                                </td>
                            </tr>
                        @else
                            @foreach($ordenes as $orden)
                                @php
                                    $totalDias = intval($totalDiasCalculados[$orden->pedido] ?? 0);
                                    $estado = $orden->estado ?? '';
                                    $diaDeEntrega = $orden->dia_de_entrega ? intval($orden->dia_de_entrega) : null;
                                    $conditionalClass = '';
                                    
                                    // PRIORIDAD 1: Estados especiales
                                    if ($estado === 'Entregado') {
                                        $conditionalClass = 'row-delivered';
                                    } elseif ($estado === 'Anulada') {
                                        $conditionalClass = 'row-anulada';
                                    }
                                    // PRIORIDAD 2: NUEVA LÓGICA - Día de entrega (si existe)
                                    elseif ($diaDeEntrega !== null && $diaDeEntrega > 0) {
                                        if ($totalDias >= 15) {
                                            $conditionalClass = 'row-dia-entrega-critical'; // Negro (15+)
                                        } elseif ($totalDias >= 10 && $totalDias <= 14) {
                                            $conditionalClass = 'row-dia-entrega-danger'; // Rojo (10-14)
                                        } elseif ($totalDias >= 5 && $totalDias <= 9) {
                                            $conditionalClass = 'row-dia-entrega-warning'; // Amarillo (5-9)
                                        }
                                    }
                                    // PRIORIDAD 3: LÓGICA ORIGINAL - Solo si NO hay día de entrega
                                    else {
                                        if ($totalDias > 20) {
                                            $conditionalClass = 'row-secondary';
                                        } elseif ($totalDias == 20) {
                                            $conditionalClass = 'row-danger-light';
                                        } elseif ($totalDias > 14 && $totalDias < 20) {
                                            $conditionalClass = 'row-warning';
                                        }
                                    }
                                @endphp
                                <tr class="table-row {{ $conditionalClass }}" data-order-id="{{ $orden->pedido }}">
                                    <td class="table-cell acciones-column" style="min-width: 220px !important;">
                                        <div class="cell-content" style="display: flex; gap: 8px; flex-wrap: nowrap; align-items: center; justify-content: flex-start; padding: 4px 0;">
                                            @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                <!-- Solo botón Ver para supervisores, usando el dropdown de opciones -->
                                                <button class="action-btn detail-btn" onclick="createViewButtonDropdown({{ $orden->pedido }})"
                                                    title="Ver opciones"
                                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700; flex: 1; min-width: 60px; height: 38px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); transition: all 0.2s ease;">
                                                    <i class="fas fa-eye" style="margin-right: 6px;"></i> Ver
                                                </button>
                                            @else
                                                <!-- Botones completos para otros roles con dropdown de opciones -->
                                                <button class="action-btn edit-btn" onclick="openEditModal({{ $orden->pedido }})"
                                                    title="Editar orden"
                                                    style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600; flex: 1; min-width: 45px; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap;">
                                                    Editar
                                                </button>
                                                <button class="action-btn detail-btn" onclick="createViewButtonDropdown({{ $orden->pedido }})"
                                                    title="Ver opciones"
                                                    style="background-color: green; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600; flex: 1; min-width: 45px; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap;">
                                                    Ver
                                                </button>
                                                <button class="action-btn delete-btn" onclick="deleteOrder({{ $orden->pedido }})"
                                                    title="Eliminar orden"
                                                    style="background-color:#f84c4cff ; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600; flex: 1; min-width: 45px; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap;">
                                                    Borrar
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    @foreach($orden->getAttributes() as $key => $valor)
                                        @if($key !== 'id' && $key !== 'tiempo')
                                            {{-- Ocultar celda "día de entrega" para supervisores --}}
                                            @if($key === 'dia_de_entrega' && auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                {{-- Celda oculta para supervisores --}}
                                            @else
                                            <td class="table-cell" data-column="{{ $key }}">
                                                <div class="cell-content" title="{{ $valor }}">
                                                    @if($key === 'estado')
                                                        @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                            <!-- Selector deshabilitado para supervisores -->
                                                            <select class="estado-dropdown" data-id="{{ $orden->pedido }}"
                                                                data-value="{{ $valor }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                                @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                    <option value="{{ $estado }}" {{ $valor === $estado ? 'selected' : '' }}>{{ $estado }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <!-- Selector editable para otros roles -->
                                                            <select class="estado-dropdown" data-id="{{ $orden->pedido }}"
                                                                data-value="{{ $valor }}">
                                                                @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                    <option value="{{ $estado }}" {{ $valor === $estado ? 'selected' : '' }}>{{ $estado }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    @elseif($key === 'area')
                                                        @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                            <!-- Selector deshabilitado para supervisores -->
                                                            <select class="area-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $valor }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                                @foreach($areaOptions as $areaOption)
                                                                    <option value="{{ $areaOption }}" {{ $valor === $areaOption ? 'selected' : '' }}>
                                                                        {{ $areaOption }}</option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <!-- Selector editable para otros roles -->
                                                            <select class="area-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $valor }}">
                                                                @foreach($areaOptions as $areaOption)
                                                                    <option value="{{ $areaOption }}" {{ $valor === $areaOption ? 'selected' : '' }}>
                                                                        {{ $areaOption }}</option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    @elseif($key === 'dia_de_entrega' && $context === 'registros')
                                                        <!-- Selector editable para otros roles (supervisores no llegan aquí) -->
                                                        <select class="dia-entrega-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $valor ?? '' }}">
                                                            <option value="" {{ is_null($valor) ? 'selected' : '' }}>Seleccionar</option>
                                                            <option value="15" {{ $valor == 15 ? 'selected' : '' }}>15 días</option>
                                                            <option value="20" {{ $valor == 20 ? 'selected' : '' }}>20 días</option>
                                                            <option value="25" {{ $valor == 25 ? 'selected' : '' }}>25 días</option>
                                                            <option value="30" {{ $valor == 30 ? 'selected' : '' }}>30 días</option>
                                                        </select>
                                                    @else
                                                        <span class="cell-text">
                                                            @if($key === 'total_de_dias_')
                                                                {{ $totalDiasCalculados[$orden->pedido] ?? 'N/A' }}
                                                            @elseif(in_array($key, ['fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega']))
                                                                {{-- Formatear TODAS las columnas de fecha a DD/MM/YYYY --}}
                                                                @php
                                                                    try {
                                                                        $fechaFormateada = !empty($valor) ? \Carbon\Carbon::parse($valor)->format('d/m/Y') : '';
                                                                        echo $fechaFormateada;
                                                                        // Log para debugging
                                                                        if ($key === 'fecha_de_creacion_de_orden' || $key === 'fecha_estimada_de_entrega') {
                                                                            \Log::info("BLADE: Formateando fecha", [
                                                                                'pedido' => $orden->pedido,
                                                                                'columna' => $key,
                                                                                'valor_original' => $valor,
                                                                                'valor_formateado' => $fechaFormateada
                                                                            ]);
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                        echo $valor;
                                                                        \Log::warning("BLADE: Error formateando fecha", [
                                                                            'columna' => $key,
                                                                            'valor' => $valor,
                                                                            'error' => $e->getMessage()
                                                                        ]);
                                                                    }
                                                                @endphp
                                                            @else
                                                                {{ $valor }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            @endif
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

    <script src="{{ asset('js/orders js/modern-table.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/orders-table.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/order-navigation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/pagination.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/realtime-listeners.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orderTracking.js') }}?v={{ time() }}"></script>
@endsection
