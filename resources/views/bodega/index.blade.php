@extends('layouts.app')

@section('title', 'Bodega - MundoIndustrial')
@section('page-title', 'Bodega')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/orders-styles/registros.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/action-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/filter-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders-styles/row-conditional-colors.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bodega-column-widths.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bodega-table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/novedades-button.css') }}?v={{ time() }}">
@endpush

@section('content')

    <!-- Modales de Bodega -->
    @include('components.orders-components.bodega-order-detail-modal')
    @include('components.orders-components.bodega-edit-modal', ['areaOptions' => $areaOptions ?? []])
    @include('components.orders-components.bodega-cell-edit-modal')
    @include('components.orders-components.bodega-tracking-modal')
    @include('components.modals.novedades-edit-modal')

    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div class="table-head">
                        <div style="display: flex; align-items: center; width: 100%; gap: 15px; padding: 14px 12px;">
                            @php
                                $columnWidth = '220px';
                                $columns = [
                                    ['key' => 'acciones', 'label' => 'Acciones', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'estado', 'label' => 'Estado', 'width' => '220px', 'justify' => 'center'],
                                    ['key' => 'area', 'label' => 'Área', 'width' => '220px', 'justify' => 'center'],
                                    ['key' => 'total_de_dias_', 'label' => 'Total de días', 'width' => '220px', 'justify' => 'center'],
                                    ['key' => 'pedido', 'label' => 'Pedido', 'width' => '220px', 'justify' => 'center'],
                                    ['key' => 'cliente', 'label' => 'Cliente', 'width' => '220px', 'justify' => 'center'],
                                    ['key' => 'descripcion', 'label' => 'Descripción', 'width' => '220px', 'justify' => 'center'],
                                    ['key' => 'cantidad', 'label' => 'Cantidad', 'width' => '220px', 'justify' => 'start'],
                                    ['key' => 'novedades', 'label' => 'Novedades', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'asesora', 'label' => 'Asesor', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'forma_de_pago', 'label' => 'Forma de pago', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'fecha_de_creacion_de_orden', 'label' => 'Fecha de creación', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargado_orden', 'label' => 'Encargado orden', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_orden', 'label' => 'Días orden', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'inventario', 'label' => 'Inventario', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_inventario', 'label' => 'Encargados inventario', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_inventario', 'label' => 'Días inventario', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'insumos_y_telas', 'label' => 'Insumos y telas', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_insumos', 'label' => 'Encargados insumos', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_insumos', 'label' => 'Días insumos', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'corte', 'label' => 'Corte', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_de_corte', 'label' => 'Encargados corte', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_corte', 'label' => 'Días corte', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'bordado', 'label' => 'Bordado', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'codigo_de_bordado', 'label' => 'Código bordado', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_bordado', 'label' => 'Días bordado', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'estampado', 'label' => 'Estampado', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_estampado', 'label' => 'Encargados estampado', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_estampado', 'label' => 'Días estampado', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'costura', 'label' => 'Costura', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'modulo', 'label' => 'Módulo', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_costura', 'label' => 'Días costura', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'reflectivo', 'label' => 'Reflectivo', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargado_reflectivo', 'label' => 'Encargado reflectivo', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'total_de_dias_reflectivo', 'label' => 'Total días reflectivo', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'lavanderia', 'label' => 'Lavandería', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargado_lavanderia', 'label' => 'Encargado lavandería', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_lavanderia', 'label' => 'Días lavandería', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'arreglos', 'label' => 'Arreglos', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargado_arreglos', 'label' => 'Encargado arreglos', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'total_de_dias_arreglos', 'label' => 'Total días arreglos', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'marras', 'label' => 'Marras', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_marras', 'label' => 'Encargados marras', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'total_de_dias_marras', 'label' => 'Total días marras', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'control_de_calidad', 'label' => 'Control calidad', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_calidad', 'label' => 'Encargados calidad', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'dias_c_c', 'label' => 'Días control', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'entrega', 'label' => 'Entrega', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'encargados_entrega', 'label' => 'Encargados entrega', 'width' => '220px', 'justify' => 'flex-start'],
                                    ['key' => 'despacho', 'label' => 'Despacho', 'width' => '220px', 'justify' => 'flex-start'],
                                ];
                            @endphp
                            
                            @foreach($columns as $column)
                                <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: 0 0 {{ $column['width'] }}; width: {{ $column['width'] }}; max-width: {{ $column['width'] }}; justify-content: center;">
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
                    <div id="tablaOrdenesBody" class="table-body">
                        @forelse($ordenes as $orden)
                            @php
                                $pedidoId = $orden->pedido;
                                $totalDias = intval($totalDiasCalculados[$pedidoId] ?? 0);
                                $estado = $orden->estado ?? '';
                            @endphp
                            <div class="table-row" data-order-id="{{ $orden->pedido }}" data-numero-pedido="{{ $orden->pedido }}" data-total-dias="{{ $totalDias }}" data-estado="{{ $estado }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 220px; width: 220px; max-width: 220px; justify-content: flex-start; position: relative;">
                                    <button class="action-view-btn" title="Ver detalles" data-orden-id="{{ $orden->pedido }}" onclick="openEditModal({{ $orden->pedido }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-view-btn" title="Ver opciones" data-orden-id="{{ $orden->pedido }}" onclick="createViewButtonDropdown({{ $orden->pedido }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-view-btn" title="Eliminar orden" data-orden-id="{{ $orden->pedido }}" onclick="deleteOrder({{ $orden->pedido }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Estado (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 220px; width: 220px; max-width: 220px; justify-content: center;">
                                    <div class="cell-content">
                                        @php
                                            $estadoClass = 'estado-no-iniciado';
                                            if ($orden->estado === 'Entregado') {
                                                $estadoClass = 'estado-entregado';
                                            } elseif ($orden->estado === 'En Ejecución') {
                                                $estadoClass = 'estado-en-ejecución';
                                            } elseif ($orden->estado === 'Anulada') {
                                                $estadoClass = 'estado-anulada';
                                            }
                                        @endphp
                                        @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                            <select class="estado-dropdown {{ $estadoClass }}" data-id="{{ $orden->pedido }}" data-value="{{ $orden->estado }}" disabled style="cursor: not-allowed; opacity: 0.8;">
                                                @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estadoOpt)
                                                    <option value="{{ $estadoOpt }}" {{ $orden->estado === $estadoOpt ? 'selected' : '' }}>{{ $estadoOpt }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select class="estado-dropdown {{ $estadoClass }}" data-id="{{ $orden->pedido }}" data-value="{{ $orden->estado }}">
                                                @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estadoOpt)
                                                    <option value="{{ $estadoOpt }}" {{ $orden->estado === $estadoOpt ? 'selected' : '' }}>{{ $estadoOpt }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Área (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 220px; width: 220px; max-width: 220px; justify-content: center;">
                                    <div class="cell-content">
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
                                </div>
                                
                                @php
                                    $columnasBase = [
                                        'total_de_dias_', 'pedido', 'cliente', 'descripcion', 
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
                                @endphp
                                
                                @foreach($columnasBase as $colName)
                                    @php
                                        $colIndex = array_search($colName, array_column($columns, 'key'));
                                        $colConfig = $colIndex !== false ? $columns[$colIndex] : ['width' => '180px', 'justify' => 'flex-start'];
                                    @endphp
                                    <div class="table-cell" style="flex: 0 0 {{ $colConfig['width'] }}; width: {{ $colConfig['width'] }}; max-width: {{ $colConfig['width'] }}; justify-content: {{ $colConfig['justify'] }};">
                                        @if($colName === 'novedades')
                                            <!-- Botón de Novedades para Bodega -->
                                            <button 
                                                class="btn-edit-novedades"
                                                data-full-novedades="{{ addslashes($orden->novedades ?? '') }}"
                                                onclick="event.stopPropagation(); openNovedadesBodegaModal('{{ $orden->pedido }}', `{{ addslashes($orden->novedades ?? '') }}`)"
                                                title="Editar novedades"
                                                type="button">
                                                @if($orden->novedades)
                                                    <span class="novedades-text">{{ Str::limit($orden->novedades, 50, '...') }}</span>
                                                @else
                                                    <span class="novedades-text empty">Sin novedades</span>
                                                @endif
                                                <span class="material-symbols-rounded">edit</span>
                                            </button>
                                        @else
                                            <div class="cell-content" title="{{ $orden->$colName ?? '' }}" onclick="openCellEditModal('{{ $colName }}', '{{ addslashes($orden->$colName ?? '') }}', {{ $orden->pedido }})" style="cursor: pointer;">
                                                <span class="cell-text" data-pedido="{{ $orden->pedido }}" style="max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
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
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="table-row">
                                <div class="table-cell" style="flex: 1; justify-content: center;">
                                    <span class="cell-text">No hay resultados que coincidan con los filtros aplicados.</span>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Paginación --}}
            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Mostrando {{ $ordenes->firstItem() ?? 0 }}-{{ $ordenes->lastItem() ?? 0 }} de {{ $ordenes->total() }} registros</span>
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

    <!-- Scripts de Órdenes (Estilos y Funcionalidad) -->
    <script src="{{ asset('js/orders js/row-conditional-colors.js') }}"></script>
    <script src="{{ asset('js/orders js/filter-system.js') }}"></script>

    <!-- Scripts de Bodega -->
    <script src="{{ asset('js/bodega-table.js') }}"></script>
    <script src="{{ asset('js/bodega-detail-modal.js') }}"></script>
    <script src="{{ asset('js/bodega-edit-modal.js') }}"></script>
    <script src="{{ asset('js/bodega-cell-edit.js') }}"></script>
    <script src="{{ asset('js/bodega-tracking-modal.js') }}"></script>
    <script src="{{ asset('js/bodega-conditional-colors.js') }}"></script>
    <script src="{{ asset('js/bodega-estado-handler.js') }}"></script>

    <!-- Scripts de Novedades para Bodega -->
    <script src="{{ asset('js/orders js/novedades-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/bodega-novedades-modal.js') }}?v={{ time() }}"></script>

    <!-- Script de inicialización de colores -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que los scripts estén cargados
            setTimeout(() => {
                if (typeof window.applyAllRowConditionalColors === 'function') {
                    window.applyAllRowConditionalColors();
                } else {
                }
            }, 100);
        });
    </script>

    <!-- Script de debugging -->
    <script>
        // Esperar a que todo esté cargado
        setTimeout(() => {


            console.log(' Filas con clase table-row:', document.querySelectorAll('.table-row').length);
            
            const rows = document.querySelectorAll('.table-row');
            if (rows.length > 0) {
                console.log(' Primera fila data-estado:', rows[0].getAttribute('data-estado'));
                console.log(' Primera fila data-total-dias:', rows[0].getAttribute('data-total-dias'));
            }
        }, 500);
    </script>
@endsection

