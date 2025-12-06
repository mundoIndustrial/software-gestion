@extends('layouts.app')

@section('title', 'Registro de Órdenes - MundoIndustrial')
@section('page-title', 'Registro de Órdenes')

@push('styles')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}">
@endpush

@section('content')

    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    <div class="table-header-cell acciones-column" style="flex: 0 0 100px; justify-content: flex-start;">
                        <span class="header-text">Acciones</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 120px; justify-content: center;">
                        <span class="header-text">Estado</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 auto; min-width: 180px; justify-content: center;">
                        <span class="header-text">Área</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 150px; justify-content: center;">
                        <span class="header-text">Día de entrega</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 120px; justify-content: center;">
                        <span class="header-text">Total de días</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 120px; justify-content: center;">
                        <span class="header-text">Pedido</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 150px; justify-content: center;">
                        <span class="header-text">Cliente</span>
                    </div>
                    <div class="table-header-cell" style="flex: 1; margin-left: 50px;>
                        <span class="header-text">Descripción</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 100px; justify-content: start;">
                        <span class="header-text">Cantidad</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 120px; justify-content: flex-start;">
                        <span class="header-text">Novedades</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 120px; justify-content: flex-start;">
                        <span class="header-text">Asesor</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 150px; justify-content: flex-start;">
                        <span class="header-text">Forma de pago</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 150px; justify-content: flex-start;">
                        <span class="header-text">Fecha de creación</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 180px; justify-content: flex-start;">
                        <span class="header-text">Fecha estimada entrega</span>
                    </div>
                    <div class="table-header-cell" style="flex: 0 0 150px; justify-content: flex-start;">
                        <span class="header-text">Encargado orden</span>
                    </div>
                </div>
            </div>
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div id="tablaOrdenesBody" class="table-body">
                        @forelse($ordenes as $orden)
                            <div class="table-row" data-orden-id="{{ $orden->id }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 100px;">
                                    <div class="cell-content">
                                        <i class="fas fa-check-circle" style="color: #10b981; font-size: 20px;"></i>
                                    </div>
                                </div>
                                
                                <!-- Estado (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="estado-dropdown estado-{{ str_replace(' ', '-', strtolower($orden->estado)) }}" data-orden-id="{{ $orden->id }}">
                                            <option value="No iniciado" {{ $orden->estado === 'No iniciado' ? 'selected' : '' }}>No iniciado</option>
                                            <option value="En Ejecución" {{ $orden->estado === 'En Ejecución' ? 'selected' : '' }}>En Ejecución</option>
                                            <option value="Entregado" {{ $orden->estado === 'Entregado' ? 'selected' : '' }}>Entregado</option>
                                            <option value="Anulada" {{ $orden->estado === 'Anulada' ? 'selected' : '' }}>Anulada</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Área (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="area-dropdown" data-orden-id="{{ $orden->id }}" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fbbf24; color: #1f2937; font-weight: 600; font-size: 12px; cursor: pointer; width: auto;">
                                            @foreach($areaOptions as $area)
                                                <option value="{{ $area }}" {{ $orden->area === $area ? 'selected' : '' }}>{{ $area }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Día de entrega (Dropdown) -->
                                <div class="table-cell" style="flex: 0 0 auto;">
                                    <div class="cell-content">
                                        <select class="dia-entrega-dropdown" data-orden-id="{{ $orden->id }}" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #e5e7eb; background: #8b5cf6; color: white; font-weight: 600; font-size: 12px; cursor: pointer; width: auto;">
                                            <option value="">Seleccionar</option>
                                            <option value="15" {{ $orden->dia_de_entrega == 15 ? 'selected' : '' }}>15 días</option>
                                            <option value="20" {{ $orden->dia_de_entrega == 20 ? 'selected' : '' }}>20 días</option>
                                            <option value="25" {{ $orden->dia_de_entrega == 25 ? 'selected' : '' }}>25 días</option>
                                            <option value="30" {{ $orden->dia_de_entrega == 30 ? 'selected' : '' }}>30 días</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Total de días -->
                                <div class="table-cell" style="flex: 0 0 120px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>
                                            @php
                                                $diasCalculados = 0;
                                                if ($orden->fecha_de_creacion_de_orden) {
                                                    $fechaInicio = \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden);
                                                    $fechaFin = \Carbon\Carbon::now();
                                                    
                                                    // Festivos colombianos fijos
                                                    $anio = $fechaInicio->year;
                                                    $festivos = [
                                                        \Carbon\Carbon::create($anio, 1, 1)->toDateString(),   // Año Nuevo
                                                        \Carbon\Carbon::create($anio, 5, 1)->toDateString(),   // Día del Trabajo
                                                        \Carbon\Carbon::create($anio, 7, 1)->toDateString(),   // Día de la Independencia
                                                        \Carbon\Carbon::create($anio, 7, 20)->toDateString(),  // Grito de Independencia
                                                        \Carbon\Carbon::create($anio, 8, 7)->toDateString(),   // Batalla de Boyacá
                                                        \Carbon\Carbon::create($anio, 12, 8)->toDateString(),  // Inmaculada Concepción
                                                        \Carbon\Carbon::create($anio, 12, 25)->toDateString(), // Navidad
                                                    ];
                                                    
                                                    // Agregar festivos del siguiente año si es necesario
                                                    if ($fechaFin->year > $fechaInicio->year) {
                                                        $anioFin = $fechaFin->year;
                                                        $festivos = array_merge($festivos, [
                                                            \Carbon\Carbon::create($anioFin, 1, 1)->toDateString(),
                                                            \Carbon\Carbon::create($anioFin, 5, 1)->toDateString(),
                                                            \Carbon\Carbon::create($anioFin, 7, 1)->toDateString(),
                                                            \Carbon\Carbon::create($anioFin, 7, 20)->toDateString(),
                                                            \Carbon\Carbon::create($anioFin, 8, 7)->toDateString(),
                                                            \Carbon\Carbon::create($anioFin, 12, 8)->toDateString(),
                                                            \Carbon\Carbon::create($anioFin, 12, 25)->toDateString(),
                                                        ]);
                                                    }
                                                    
                                                    // Calcular días hábiles
                                                    $actual = $fechaInicio->copy();
                                                    while ($actual <= $fechaFin) {
                                                        // Verificar si no es sábado (6) ni domingo (0)
                                                        if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6) {
                                                            // Verificar si no es festivo
                                                            if (!in_array($actual->toDateString(), $festivos)) {
                                                                $diasCalculados++;
                                                            }
                                                        }
                                                        $actual->addDay();
                                                    }
                                                    
                                                    // Restar 1 porque no se cuenta el día de inicio
                                                    $diasCalculados = max(0, $diasCalculados - 1);
                                                }
                                                
                                                echo $diasCalculados > 0 ? $diasCalculados . ' día' . ($diasCalculados > 1 ? 's' : '') : '-';
                                            @endphp
                                        </span>
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
                                        <span>
                                            @php
                                                $descripciones = $orden->prendas->pluck('descripcion')->filter()->unique()->toArray();
                                                echo !empty($descripciones) ? implode(', ', $descripciones) : '-';
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

    <script>
        // Pasar opciones de area a JS
        window.areaOptions = @json($areaOptions);
        window.modalContext = '{{ $modalContext }}';
        window.context = '{{ $context ?? '' }}';
        window.fetchUrl = '{{ $fetchUrl }}';
        window.updateUrl = '{{ $updateUrl }}';

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

@endsection

@push('scripts')

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
    
    <!-- ORDER DETAIL MODAL MANAGER (debe cargarse antes de order tracking) -->
    <script src="{{ asset('js/orders js/order-detail-modal-manager.js') }}?v={{ time() }}"></script>
    
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
@endpush
