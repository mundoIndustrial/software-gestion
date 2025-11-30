@extends('layouts.app')

@section('content')
    <!-- Cargar estilos modernos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVJkEZSMUkrQ6usKGiOW31OvTkw8Ntg33aDg2hqKwRw5p3i3zYIp5yn8LucxE8wclvgrkx3cMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/modern-table-redesign.css') }}">

    <div class="table-container">
        <!-- HEADER -->
        <div class="table-header">
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

        <!-- TABLA -->
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <table id="tablaOrdenes" class="modern-table">
                    <!-- ENCABEZADO -->
                    <thead class="table-head">
                        @if($ordenes->isNotEmpty())
                            <tr>
                                <!-- Columna de Acciones -->
                                <th class="table-header-cell acciones-column">
                                    <div class="header-content">
                                        <span class="header-text">Acciones</span>
                                    </div>
                                </th>

                                @php 
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
                                
                                @foreach($columnasBase as $colName)
                                    @if(in_array($colName, ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id']))
                                        {{-- Ocultar columnas administrativas --}}
                                    @elseif($colName === 'dia_de_entrega' && auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                        {{-- Ocultar para supervisores --}}
                                    @else
                                        @php 
                                            $columnLabels = [
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
                                                'fecha_de_creacion_de_orden' => 'Fecha de creación',
                                                'fecha_estimada_de_entrega' => 'Fecha estimada',
                                                'encargado_orden' => 'Encargado',
                                            ];
                                            $colLabel = $columnLabels[$colName] ?? $colName;
                                        @endphp
                                        <th class="table-header-cell" data-column="{{ $colName }}">
                                            <div class="header-content">
                                                <span class="header-text">{{ $colLabel }}</span>
                                                @if($colName !== 'descripcion_prendas' && $colName !== 'cantidad_total')
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

                    <!-- CUERPO DE LA TABLA -->
                    <tbody id="tablaOrdenesBody" class="table-body">
                        @if($ordenes->isEmpty())
                            <tr class="table-row">
                                <td colspan="51" class="no-results">
                                    <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 12px; display: block; color: #d1d5db;"></i>
                                    No hay resultados que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        @else
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
                                    }
                                @endphp
                                <tr class="table-row {{ $conditionalClass }}" data-order-id="{{ $orden->id }}" data-numero-pedido="{{ $orden->numero_pedido }}">
                                    
                                    <!-- COLUMNA DE ACCIONES -->
                                    <td class="table-cell acciones-column">
                                        <div class="cell-content" style="gap: 4px;">
                                            @if($context === 'registros')
                                                <!-- Solo botón Ver para registros -->
                                                <button class="action-btn view-btn" onclick="createViewButtonDropdown({{ $orden->id }})" title="Ver opciones">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @elseif(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                <!-- Solo botón Ver para supervisores -->
                                                <button class="action-btn view-btn" onclick="createViewButtonDropdown({{ $orden->id }})" title="Ver opciones">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @else
                                                <!-- Botones completos para otros roles -->
                                                <button class="action-btn edit-btn" onclick="openEditModal({{ $orden->numero_pedido }})" title="Editar orden">
                                                    <i class="fas fa-pencil"></i>
                                                </button>
                                                <button class="action-btn view-btn" onclick="createViewButtonDropdown({{ $orden->id }})" title="Ver opciones">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn delete-btn" onclick="deleteOrder({{ $orden->numero_pedido }})" title="Eliminar orden">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- ESTADO -->
                                    <td class="table-cell" data-column="estado">
                                        <div class="cell-content">
                                            @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                <span class="badge badge-estado badge-estado.{{ strtolower(str_replace(' ', '-', $orden->estado)) }}">
                                                    {{ $orden->estado }}
                                                </span>
                                            @else
                                                <select class="estado-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $orden->estado }}">
                                                    @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $est)
                                                        <option value="{{ $est }}" {{ $orden->estado === $est ? 'selected' : '' }}>{{ $est }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- ÁREA -->
                                    <td class="table-cell" data-column="area">
                                        <div class="cell-content">
                                            @php
                                                $areaValue = $areasMap[$orden->numero_pedido] ?? $orden->area ?? '';
                                            @endphp
                                            @if(auth()->user()->role && auth()->user()->role->name === 'supervisor')
                                                <span style="color: var(--neutral-text-light);">{{ $areaValue ?: '-' }}</span>
                                            @else
                                                <select class="area-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $areaValue }}">
                                                    <option value="">Seleccionar</option>
                                                    @foreach($areaOptions as $areaOption)
                                                        <option value="{{ $areaOption }}" {{ $areaValue === $areaOption ? 'selected' : '' }}>{{ $areaOption }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- DÍA DE ENTREGA -->
                                    @if(!($colName === 'dia_de_entrega' && auth()->user()->role && auth()->user()->role->name === 'supervisor'))
                                        <td class="table-cell" data-column="dia_de_entrega">
                                            <div class="cell-content">
                                                @if($context === 'registros')
                                                    <select class="dia-entrega-dropdown" data-id="{{ $orden->numero_pedido }}" data-value="{{ $orden->dia_de_entrega ?? '' }}">
                                                        <option value="">Seleccionar</option>
                                                        <option value="15" {{ $orden->dia_de_entrega == 15 ? 'selected' : '' }}>15 días</option>
                                                        <option value="20" {{ $orden->dia_de_entrega == 20 ? 'selected' : '' }}>20 días</option>
                                                        <option value="25" {{ $orden->dia_de_entrega == 25 ? 'selected' : '' }}>25 días</option>
                                                        <option value="30" {{ $orden->dia_de_entrega == 30 ? 'selected' : '' }}>30 días</option>
                                                    </select>
                                                @else
                                                    <span style="color: var(--neutral-text-light);">{{ $orden->dia_de_entrega ? $orden->dia_de_entrega . ' días' : '-' }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif

                                    <!-- TOTAL DE DÍAS -->
                                    <td class="table-cell" data-column="total_de_dias_">
                                        <div class="cell-content">
                                            @php
                                                $diasClass = 'badge-dias';
                                                if ($totalDias >= 15) {
                                                    $diasClass .= ' critical';
                                                } elseif ($totalDias >= 10) {
                                                    $diasClass .= ' danger';
                                                } elseif ($totalDias >= 5) {
                                                    $diasClass .= ' warning';
                                                }
                                            @endphp
                                            <span class="badge {{ $diasClass }}">
                                                <i class="fas fa-clock"></i>
                                                {{ $totalDias }} días
                                            </span>
                                        </div>
                                    </td>

                                    <!-- NÚMERO DE PEDIDO -->
                                    <td class="table-cell" data-column="numero_pedido">
                                        <div class="cell-content">
                                            <span class="cell-text" style="font-weight: 600; color: var(--primary-color);">
                                                #{{ $orden->numero_pedido }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- CLIENTE -->
                                    <td class="table-cell" data-column="cliente">
                                        <div class="cell-content">
                                            <span class="cell-text">{{ $orden->cliente }}</span>
                                        </div>
                                    </td>

                                    <!-- DESCRIPCIÓN -->
                                    <td class="table-cell" data-column="descripcion_prendas">
                                        <div class="cell-content">
                                            <span class="cell-text" title="{{ $orden->descripcion_prendas }}">
                                                {{ Str::limit($orden->descripcion_prendas, 40) }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- CANTIDAD -->
                                    <td class="table-cell" data-column="cantidad_total">
                                        <div class="cell-content">
                                            <span class="cell-text" style="font-weight: 600;">{{ $orden->cantidad_total }}</span>
                                        </div>
                                    </td>

                                    <!-- NOVEDADES -->
                                    <td class="table-cell" data-column="novedades">
                                        <div class="cell-content">
                                            <span class="cell-text">{{ $orden->novedades ?: '-' }}</span>
                                        </div>
                                    </td>

                                    <!-- ASESOR -->
                                    <td class="table-cell" data-column="asesora">
                                        <div class="cell-content">
                                            <span class="cell-text">{{ $orden->asesora->name ?? $orden->asesora ?? '-' }}</span>
                                        </div>
                                    </td>

                                    <!-- FORMA DE PAGO -->
                                    <td class="table-cell" data-column="forma_de_pago">
                                        <div class="cell-content">
                                            <span class="cell-text">{{ $orden->forma_de_pago ?: '-' }}</span>
                                        </div>
                                    </td>

                                    <!-- FECHA DE CREACIÓN -->
                                    <td class="table-cell" data-column="fecha_de_creacion_de_orden">
                                        <div class="cell-content">
                                            <span class="cell-text">
                                                @if($orden->fecha_de_creacion_de_orden)
                                                    {{ \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    <!-- FECHA ESTIMADA -->
                                    <td class="table-cell" data-column="fecha_estimada_de_entrega">
                                        <div class="cell-content">
                                            <span class="cell-text">
                                                @if($orden->fecha_estimada_de_entrega)
                                                    {{ \Carbon\Carbon::parse($orden->fecha_estimada_de_entrega)->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                    </td>

                                    <!-- ENCARGADO -->
                                    <td class="table-cell" data-column="encargado_orden">
                                        <div class="cell-content">
                                            <span class="cell-text">{{ $orden->encargado_orden ?: '-' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">
                        Mostrando {{ $ordenes->firstItem() ?? 0 }}-{{ $ordenes->lastItem() ?? 0 }} de {{ $ordenes->total() }} registros
                    </span>
                </div>
                <div class="pagination-controls" id="paginationControls">
                    @if($ordenes->hasPages())
                        <button class="pagination-btn" data-page="1" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-chevron-left"></i>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $ordenes->currentPage() - 1 }}" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-chevron-left"></i>
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
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $ordenes->lastPage() }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                            <i class="fas fa-chevron-right"></i>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES Y SCRIPTS -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <script>
        window.areaOptions = @json($areaOptions);
        window.modalContext = '{{ $modalContext }}';
        window.context = '{{ $context ?? '' }}';
        window.fetchUrl = '{{ $fetchUrl }}';
        window.updateUrl = '{{ $updateUrl }}';
    </script>

    <!-- Componentes de modal -->
    <div class="order-registration-modal">
        <x-orders-components.order-registration-modal :areaOptions="$areaOptions" />
    </div>

    <div class="order-detail-modal">
        <x-orders-components.order-detail-modal />
    </div>

    <x-orders-components.order-tracking-modal />

    <!-- Scripts -->
    <script src="{{ asset('js/orders js/orders-table.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/order-navigation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/orders js/pagination.js') }}?v={{ time() }}"></script>
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
@endsection
@endsection
