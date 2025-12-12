@extends('layouts.app')

@section('title', 'Registro de Órdenes - MundoIndustrial')
@section('page-title', 'Registro de Órdenes')

@push('styles')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/column-widths.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/action-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/filter-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/row-conditional-colors.css') }}">
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
                            <div class="table-row" data-orden-id="{{ $orden->numero_pedido }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 100px; justify-content: center; position: relative;">
                                    <button class="action-view-btn" title="Ver detalles" data-orden-id="{{ $orden->numero_pedido }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="action-menu" data-orden-id="{{ $orden->numero_pedido }}">
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
                                        <select class="estado-dropdown estado-{{ str_replace(' ', '-', strtolower($orden->estado)) }}" data-orden-id="{{ $orden->numero_pedido }}">
                                            @foreach(\App\Models\PedidoProduccion::ESTADOS as $estado)
                                                <option value="{{ $estado }}" {{ $orden->estado === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Área (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="area-dropdown" data-orden-id="{{ $orden->numero_pedido }}">
                                            @foreach($areaOptions as $area)
                                                <option value="{{ $area }}" {{ $orden->area === $area ? 'selected' : '' }}>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Día de entrega (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="dia-entrega-dropdown" data-orden-id="{{ $orden->numero_pedido }}">
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
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $orden->getNombresPrendas() }}</span>
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
                                        <span>{{ $orden->novedades ?? '-' }}</span>
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
                                <div class="table-cell" style="flex: 0 0 180px;">
                                    <div class="cell-content" style="justify-content: flex-start;">
                                        <span>{{ $orden->fecha_estimada_de_entrega ? \Carbon\Carbon::parse($orden->fecha_estimada_de_entrega)->format('d/m/Y') : '-' }}</span>
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
    </script>

    <!-- MODAL DE SEGUIMIENTO DEL PEDIDO -->
    @include('components.orders-components.order-tracking-modal')

@endsection

@push('scripts')

    <!-- ORDER DETAIL MODAL MANAGER (debe cargarse antes de otros scripts) -->
    <script src="{{ asset('js/orders js/order-detail-modal-manager.js') }}?v={{ time() }}"></script>
    
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
    <script src="{{ asset('js/orders js/descripcion-prendas-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/order-navigation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/pagination.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/historial-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/realtime-listeners.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}?v={{ time() }}"></script>
    
    <!-- ACTION MENU HANDLER -->
    <script src="{{ asset('js/orders js/action-menu.js') }}?v={{ time() }}"></script>

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
