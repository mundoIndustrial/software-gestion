@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Modales de Bodega -->
    @include('components.orders-components.bodega-order-detail-modal')
    @include('components.orders-components.bodega-edit-modal', ['areaOptions' => $areaOptions ?? []])
    @include('components.orders-components.bodega-cell-edit-modal')
    @include('components.orders-components.bodega-tracking-modal')

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
                                    // TODOS los campos de tabla_original_bodega
                                    $columnasBase = [
                                        'estado', 'area', 'total_de_dias_', 'pedido', 'cliente', 'descripcion', 
                                        'cantidad', 'novedades', 'asesora', 'forma_de_pago', 'fecha_de_creacion_de_orden', 
                                        'encargado_orden', 'dias_orden', 'inventario', 'encargados_inventario', 'dias_inventario',
                                        'insumos_y_telas', 'encargados_insumos', 'dias_insumos', 'corte', 'encargados_de_corte',
                                        'dias_corte', 'bordado', 'codigo_de_bordado', 'dias_bordado', 'estampado', 
                                        'encargados_estampado', 'dias_estampado', 'costura', 'modulo', 'dias_costura',
                                        'reflectivo', 'encargado_reflectivo', 'total_de_dias_reflectivo', 'lavanderia',
                                        'encargado_lavanderia', 'dias_lavanderia', 'arreglos', 'encargado_arreglos',
                                        'total_de_dias_arreglos', 'marras', 'encargados_marras', 'total_de_dias_marras',
                                        'control_de_calidad', 'encargados_calidad', 'dias_c_c', 'entrega', 'encargados_entrega', 'despacho'
                                    ];
                                    
                                    $columnIndex = 0;
                                @endphp
                                
                                {{-- Columnas de tabla_original_bodega --}}
                                @foreach($columnasBase as $colName)
                                    @if($colName !== 'acciones')
                                        @php 
                                            $columnLabels = [
                                                'acciones' => 'Acciones',
                                                'estado' => 'Estado',
                                                'area' => 'Área',
                                                'total_de_dias_' => 'Total de días',
                                                'pedido' => 'Pedido',
                                                'cliente' => 'Cliente',
                                                'descripcion' => 'Descripción',
                                                'cantidad' => 'Cantidad',
                                                'novedades' => 'Novedades',
                                                'asesora' => 'Asesor',
                                                'forma_de_pago' => 'Forma de pago',
                                                'fecha_de_creacion_de_orden' => 'Fecha de creación',
                                                'encargado_orden' => 'Encargado orden',
                                                'dias_orden' => 'Días orden',
                                                'inventario' => 'Inventario',
                                                'encargados_inventario' => 'Encargados inventario',
                                                'dias_inventario' => 'Días inventario',
                                                'insumos_y_telas' => 'Insumos y telas',
                                                'encargados_insumos' => 'Encargados insumos',
                                                'dias_insumos' => 'Días insumos',
                                                'corte' => 'Corte',
                                                'encargados_de_corte' => 'Encargados corte',
                                                'dias_corte' => 'Días corte',
                                                'bordado' => 'Bordado',
                                                'codigo_de_bordado' => 'Código bordado',
                                                'dias_bordado' => 'Días bordado',
                                                'estampado' => 'Estampado',
                                                'encargados_estampado' => 'Encargados estampado',
                                                'dias_estampado' => 'Días estampado',
                                                'costura' => 'Costura',
                                                'modulo' => 'Módulo',
                                                'dias_costura' => 'Días costura',
                                                'reflectivo' => 'Reflectivo',
                                                'encargado_reflectivo' => 'Encargado reflectivo',
                                                'total_de_dias_reflectivo' => 'Total días reflectivo',
                                                'lavanderia' => 'Lavandería',
                                                'encargado_lavanderia' => 'Encargado lavandería',
                                                'dias_lavanderia' => 'Días lavandería',
                                                'arreglos' => 'Arreglos',
                                                'encargado_arreglos' => 'Encargado arreglos',
                                                'total_de_dias_arreglos' => 'Total días arreglos',
                                                'marras' => 'Marras',
                                                'encargados_marras' => 'Encargados marras',
                                                'total_de_dias_marras' => 'Total días marras',
                                                'control_de_calidad' => 'Control calidad',
                                                'encargados_calidad' => 'Encargados calidad',
                                                'dias_c_c' => 'Días control',
                                                'entrega' => 'Entrega',
                                                'encargados_entrega' => 'Encargados entrega',
                                                'despacho' => 'Despacho',
                                            ];
                                            $colLabel = $columnLabels[$colName] ?? $colName;
                                        @endphp
                                        <th class="table-header-cell" data-column="{{ $colName }}">
                                            <div class="header-content">
                                                <span class="header-text">{{ $colLabel }}</span>
                                                @if($colName !== 'acciones')
                                                    <button class="filter-btn" data-column="{{ $columnIndex }}" data-column-name="{{ $colName }}">
                                                        <i class="fas fa-filter"></i>
                                                    </button>
                                                @endif
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
                                <td colspan="60" class="no-results">
                                    No hay resultados que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        @else
                            @foreach($ordenes as $orden)
                                @php
                                    $pedidoId = $orden->pedido;
                                    $totalDias = intval($totalDiasCalculados[$pedidoId] ?? 0);
                                    $estado = $orden->estado ?? '';
                                    $conditionalClass = '';
                                    
                                    if ($estado === 'Entregado') {
                                        $conditionalClass = 'row-delivered';
                                    } elseif ($estado === 'Anulada') {
                                        $conditionalClass = 'row-anulada';
                                    } else {
                                        if ($totalDias > 20) {
                                            $conditionalClass = 'row-secondary';
                                        } elseif ($totalDias == 20) {
                                            $conditionalClass = 'row-danger-light';
                                        } elseif ($totalDias > 14 && $totalDias < 20) {
                                            $conditionalClass = 'row-warning';
                                        }
                                    }
                                @endphp
                                <tr class="table-row {{ $conditionalClass }}" data-order-id="{{ $orden->pedido }}" data-numero-pedido="{{ $orden->pedido }}" data-total-dias="{{ $totalDias }}">
                                    {{-- Columna de Acciones --}}
                                    <td class="table-cell acciones-column">
                                        <div class="cell-content">
                                            <button class="action-btn edit-btn" onclick="openEditModal({{ $orden->pedido }})" title="Editar orden">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn detail-btn" onclick="createViewButtonDropdown({{ $orden->pedido }})" title="Ver opciones">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn delete-btn" onclick="deleteOrder({{ $orden->pedido }})" title="Eliminar orden">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    
                                    @foreach($columnasBase as $colName)
                                        @if($colName === 'estado')
                                            <td class="table-cell" data-column="{{ $colName }}">
                                                <div class="cell-content" title="{{ $orden->estado ?? '' }}">
                                                    @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                        <select class="estado-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $orden->estado }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                            @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                <option value="{{ $estado }}" {{ $orden->estado === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <select class="estado-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $orden->estado }}">
                                                            @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                <option value="{{ $estado }}" {{ $orden->estado === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </div>
                                            </td>
                                        @elseif($colName === 'area')
                                            <td class="table-cell" data-column="{{ $colName }}">
                                                <div class="cell-content" title="{{ $orden->area ?? '' }}">
                                                    @php $areaValue = $orden->area ?? ''; @endphp
                                                    @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                        <select class="area-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $areaValue }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                            <option value="">Seleccionar área</option>
                                                            @foreach($areaOptions as $areaOption)
                                                                <option value="{{ $areaOption }}" {{ $areaValue === $areaOption ? 'selected' : '' }}>{{ $areaOption }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <select class="area-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $areaValue }}">
                                                            <option value="">Seleccionar área</option>
                                                            @foreach($areaOptions as $areaOption)
                                                                <option value="{{ $areaOption }}" {{ $areaValue === $areaOption ? 'selected' : '' }}>{{ $areaOption }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </div>
                                            </td>
                                        @else
                                            <td class="table-cell" data-column="{{ $colName }}">
                                                <div class="cell-content" title="{{ $orden->$colName ?? '' }}">
                                                    <span class="cell-text" data-pedido="{{ $orden->pedido }}">
                                                        @if($colName === 'total_de_dias_')
                                                            <span class="dias-value" data-dias="{{ $totalDias }}">{{ $totalDias }}</span>
                                                        @elseif(in_array($colName, ['fecha_de_creacion_de_orden', 'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega', 'despacho']))
                                                            @php
                                                                echo !empty($orden->$colName) ? \Carbon\Carbon::parse($orden->$colName)->format('d/m/Y') : '';
                                                            @endphp
                                                        @else
                                                            {{ $orden->$colName ?? '' }}
                                                        @endif
                                                    </span>
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

            {{-- Paginación --}}
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

    <!-- Scripts de Order Tracking -->
    <script src="{{ asset('js/order-tracking/modules/dateUtils.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/holidayManager.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/areaMapper.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/trackingService.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/trackingUI.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/apiClient.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/processManager.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/tableManager.js') }}"></script>
    <script src="{{ asset('js/order-tracking/modules/dropdownManager.js') }}"></script>
    <script src="{{ asset('js/order-tracking/orderTracking-v2.js') }}"></script>

    <!-- Scripts de Bodega -->
    <script src="{{ asset('js/bodega-table.js') }}"></script>
    <script src="{{ asset('js/bodega-detail-modal.js') }}"></script>
    <script src="{{ asset('js/bodega-edit-modal.js') }}"></script>
    <script src="{{ asset('js/bodega-cell-edit.js') }}"></script>
    <script src="{{ asset('js/bodega-tracking-modal.js') }}"></script>

    <!-- Script de debugging -->
    <script>
        // Esperar a que todo esté cargado
        setTimeout(() => {
            console.log('🔍 DEBUGGING MODAL');
            console.log('✅ openEditModal existe:', typeof openEditModal);
            console.log('✅ openBodegaEditModal existe:', typeof openBodegaEditModal);
            console.log('✅ closeBodegaEditModal existe:', typeof closeBodegaEditModal);
            console.log('✅ showBodegaEditModal existe:', typeof showBodegaEditModal);
            
            const modal = document.getElementById('bodegaEditModal');
            console.log('✅ Modal en DOM:', !!modal);
            if (modal) {
                console.log('✅ Modal ID:', modal.id);
                console.log('✅ Modal display:', window.getComputedStyle(modal).display);
                console.log('✅ Modal visibility:', window.getComputedStyle(modal).visibility);
                console.log('✅ Modal z-index:', window.getComputedStyle(modal).zIndex);
            }
        }, 500);
    </script>
@endsection
