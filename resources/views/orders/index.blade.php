@extends('layouts.app')

@section('content')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/dropdown-styles.css') }}">

    <div class="table-container">
        <div class="table-header" id="tableHeader">
            <h1 class="table-title">
                <i class="fas {{ $icon }}"></i>
                {{ $title }}
            </h1>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="buscarOrden" placeholder="Buscar por número de orden..." class="search-input">
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
                                @foreach(array_keys($ordenes->first()->getAttributes()) as $index => $columna)
                                    @if($columna !== 'id' && $columna !== 'tiempo')
                                        <th class="table-header-cell" data-column="{{ $columna }}">
                                            <div class="header-content">
                                                <span class="header-text">{{ ucfirst(str_replace('_', ' ', $columna)) }}</span>
                                                @if($columna !== 'acciones')
                                                    <button class="filter-btn" data-column="{{ $columna }}" data-column-name="{{ $columna }}">
                                                        <i class="fas fa-filter"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </th>
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
                                    $conditionalClass = '';
                                    if ($estado === 'Entregado') {
                                        $conditionalClass = 'row-delivered';
                                    } elseif ($estado === 'Anulada') {
                                        $conditionalClass = 'row-anulada';
                                    } elseif ($totalDias > 14 && $totalDias < 20) {
                                        $conditionalClass = 'row-warning';
                                    } elseif ($totalDias == 20) {
                                        $conditionalClass = 'row-danger-light';
                                    } elseif ($totalDias > 20) {
                                        $conditionalClass = 'row-secondary';
                                    }
                                @endphp
                                <tr class="table-row {{ $conditionalClass }}" data-order-id="{{ $orden->pedido }}">
                                    <td class="table-cell acciones-column">
                                        <div class="cell-content">
                                            <button class="action-btn delete-btn" onclick="deleteOrder({{ $orden->pedido }})"
                                                title="Eliminar orden"
                                                style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; margin-right: 5px; border-radius: 4px; cursor: pointer;">
                                                Borrar
                                            </button>
                                            <button class="action-btn detail-btn" onclick="viewDetail({{ $orden->pedido }})"
                                                title="Ver detalle"
                                                style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                                Ver
                                            </button>
                                        </div>
                                    </td>
                                    @foreach($orden->getAttributes() as $key => $valor)
                                        @if($key !== 'id' && $key !== 'tiempo')
                                            <td class="table-cell" data-column="{{ $key }}">
                                                <div class="cell-content" title="{{ $valor }}">
                                                    @if($key === 'estado')
                                                        <select class="estado-dropdown" data-id="{{ $orden->pedido }}"
                                                            data-value="{{ $valor }}">
                                                            @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                <option value="{{ $estado }}" {{ $valor === $estado ? 'selected' : '' }}>{{ $estado }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($key === 'area')
                                                        <select class="area-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $valor }}">
                                                            @foreach($areaOptions as $areaOption)
                                                                <option value="{{ $areaOption }}" {{ $valor === $areaOption ? 'selected' : '' }}>
                                                                    {{ $areaOption }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <span class="cell-text">
                                                            @if($key === 'total_de_dias_')
                                                                {{ $totalDiasCalculados[$orden->pedido] ?? 'N/A' }}
                                                            @else
                                                                {{ $valor }}
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
                        <i class="fas fa-search search-icon"></i>
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
            </div>
            <div class="cell-modal-footer">
                <button id="saveCellEdit" class="btn btn-primary">Guardar (Enter)</button>
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
    </script>

    <script src="{{ asset('js/orders js/orders-table.js') }}"></script>

    <div class="order-registration-modal">
        <x-order-registration-modal :areaOptions="$areaOptions" />
    </div>

    <div class="order-detail-modal">
        <x-order-detail-modal />
    </div>

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

    <script src="{{ asset('js/orders js/modern-table.js') }}"></script>

    <!-- AJAX Pagination Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paginationControls = document.getElementById('paginationControls');
        let isLoading = false;
        
        if (paginationControls) {
            paginationControls.addEventListener('click', function(e) {
                const btn = e.target.closest('.pagination-btn');
                
                if (!btn || btn.disabled || isLoading) return;
                
                const page = btn.dataset.page;
                if (!page) return;
                
                isLoading = true;
                
                // Indicador de carga rápido
                const tableBody = document.getElementById('tablaOrdenesBody');
                tableBody.style.transition = 'opacity 0.1s';
                tableBody.style.opacity = '0.3';
                tableBody.style.pointerEvents = 'none';
                
                // Construir URL con parámetros actuales
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                
                // Hacer petición AJAX
                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Parsear HTML de forma más eficiente
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Actualizar contenido de forma rápida
                    const newTableBody = doc.getElementById('tablaOrdenesBody');
                    if (newTableBody) {
                        tableBody.innerHTML = newTableBody.innerHTML;
                    }
                    
                    const newPaginationControls = doc.getElementById('paginationControls');
                    if (newPaginationControls) {
                        paginationControls.innerHTML = newPaginationControls.innerHTML;
                    }
                    
                    const newPaginationInfo = doc.getElementById('paginationInfo');
                    const paginationInfo = document.getElementById('paginationInfo');
                    if (newPaginationInfo && paginationInfo) {
                        paginationInfo.innerHTML = newPaginationInfo.innerHTML;
                    }
                    
                    // Actualizar URL
                    window.history.pushState({}, '', url.toString());
                    
                    // Restaurar inmediatamente
                    tableBody.style.opacity = '1';
                    tableBody.style.pointerEvents = 'auto';
                    isLoading = false;
                    
                    // Scroll instantáneo
                    document.querySelector('.table-container').scrollIntoView({ 
                        behavior: 'auto', 
                        block: 'start' 
                    });
                })
                .catch(error => {
                    console.error('Error al cargar página:', error);
                    tableBody.style.opacity = '1';
                    tableBody.style.pointerEvents = 'auto';
                    isLoading = false;
                });
            });
        }
    });
    </script>

    <!-- Real-time updates script for orders -->
    <script>
    // Initialize real-time listeners for orders
    function initializeOrdenesRealtimeListeners() {
        console.log('=== ÓRDENES - Inicializando Echo para tiempo real ===');
        console.log('window.Echo disponible:', !!window.Echo);

        if (!window.Echo) {
            console.error('❌ Echo NO está disponible. Reintentando en 500ms...');
            setTimeout(initializeOrdenesRealtimeListeners, 500);
            return;
        }

        console.log('✅ Echo disponible. Suscribiendo al canal "ordenes"...');

        // Canal de Órdenes
        const ordenesChannel = window.Echo.channel('ordenes');

        ordenesChannel.subscribed(() => {
            console.log('✅ Suscrito al canal "ordenes"');
        });

        ordenesChannel.error((error) => {
            console.error('❌ Error en canal "ordenes":', error);
        });

        ordenesChannel.listen('OrdenUpdated', (e) => {
            console.log('🎉 Evento OrdenUpdated recibido!', e);
            handleOrdenUpdate(e.orden, e.action);
        });

        console.log('✅ Listener de órdenes configurado');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initializeOrdenesRealtimeListeners, 100);
        });
    } else {
        setTimeout(initializeOrdenesRealtimeListeners, 100);
    }
    </script>
@endsection
