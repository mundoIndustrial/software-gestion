@extends('layouts.app')

@section('title', 'Registro de Órdenes - MundoIndustrial')
@section('page-title', 'Registro de Órdenes')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/orders-v2/new-registros.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders-v2/cards-view.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders-v2/kanban-view.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/tracking-modal.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="orders-container">
    
    <!-- Filtros Rápidos -->
    <div class="controls-bar">

        @php
            $hoy = \Carbon\Carbon::today();
            $contadores = [
                'vencidos' => $ordenes->getCollection()->filter(function($orden) use ($hoy) {
                    return $orden->fecha_estimada_de_entrega && $orden->fecha_estimada_de_entrega < $hoy;
                })->count(),
                'en_progreso' => $ordenes->getCollection()->where('estado', 'En Ejecución')->count(),
            ];
        @endphp
        <div class="quick-filters">
            <button class="filter-btn active" data-status="todos">
                <i class="fas fa-bars"></i> Todos
            </button>
            <button class="filter-btn" data-status="vencidos">
                <i class="fas fa-exclamation-circle"></i> Retrasados <span class="filter-count">({{ $contadores['vencidos'] }})</span>
            </button>
            <button class="filter-btn" data-status="en-progreso">
                <i class="fas fa-spinner"></i> En Progreso <span class="filter-count">({{ $contadores['en_progreso'] }})</span>
            </button>
            <button class="filter-btn" data-status="entregados">
                <i class="fas fa-check-circle"></i> Entregados
            </button>
        </div>
    </div>

    <!-- VISTA TABLA -->
    <div class="view-content" id="view-tabla">
        <div class="table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th class="col-accion">Acción</th>
                        <th class="col-checkbox">
                            <input type="checkbox" class="checkbox-all">
                        </th>
                        <th class="col-pedido">Pedido</th>
                        <th class="col-cliente">Cliente</th>
                        <th class="col-estado">Estado</th>
                        <th class="col-entrega">Entrega</th>
                        <th class="col-progreso">Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordenes as $orden)
                        @php
                            $estado = $orden->estado ?? 'pendiente';
                            $estadoClass = 'estado-' . str_replace(' ', '-', strtolower($estado));
                            $diaEntrega = $orden->dia_de_entrega ?? '-';
                            
                            // Calcular progreso basado en recibos con area = 'Entrega'
                            $recibosEntregados = $orden->consecutivosRecibos()
                                ->where('area', 'Entrega')
                                ->count();
                            $totalRecibos = $orden->consecutivosRecibos()
                                ->where('activo', 1)
                                ->count();
                            $progreso = $totalRecibos > 0 ? round(($recibosEntregados / $totalRecibos) * 100) : 0;
                        @endphp
                        <tr class="table-row" data-orden-id="{{ $orden->id }}">
                            <td class="col-accion">
                                <button class="btn-menu-actions" data-orden-id="{{ $orden->id }}" data-numero-pedido="{{ $orden->numero_pedido }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="action-menu" style="display: none;">
                                    <a href="#" class="menu-item" data-action="detalle">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                    <a href="#" class="menu-item" data-action="seguimiento">
                                        <i class="fas fa-tasks"></i> Seguimiento
                                    </a>

                                </div>
                            </td>
                            <td class="col-checkbox">
                                <input type="checkbox" class="row-checkbox">
                            </td>
                            <td class="col-pedido">
                                <strong>#{{ $orden->numero_pedido ?? $orden->id }}</strong>
                            </td>
                            <td class="col-cliente">
                                <span class="client-dot">●</span>
                                {{ $orden->cliente ?? '-' }}
                            </td>
                            <td class="col-estado">
                                <span class="badge {{ $estadoClass }}">
                                    {{ $estado }}
                                </span>
                                @if($estado === 'Retraso')
                                    <span class="badge-warning">Retraso 2 días</span>
                                @endif
                            </td>
                            <td class="col-entrega">
                                @if($diaEntrega !== '-')
                                    <span class="entrega-date">↑ {{ $diaEntrega }}</span>
                                @else
                                    <span class="entrega-date">{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : '-' }}</span>
                                @endif
                            </td>
                            <td class="col-progreso">
                                <span class="progress-text">{{ $progreso }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No hay órdenes disponibles</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="pagination-container">
            <div class="pagination-info">
                <span>Mostrando 1-25 de {{ $ordenes->total() }} pedidos</span>
            </div>
            <div class="pagination-controls">
                @if($ordenes->hasPages())
                    <button class="page-btn" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left"></i> <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    @php
                        $start = max(1, $ordenes->currentPage() - 2);
                        $end = min($ordenes->lastPage(), $ordenes->currentPage() + 2);
                    @endphp
                    
                    @for($i = $start; $i <= $end; $i++)
                        <button class="page-btn {{ $i == $ordenes->currentPage() ? 'active' : '' }}">
                            {{ $i }}
                        </button>
                    @endfor
                    
                    <button class="page-btn" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="page-btn" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                        <i class="fas fa-chevron-right"></i> <i class="fas fa-chevron-right"></i>
                    </button>
                @endif
            </div>
            <div class="pagination-select">
                <select class="per-page-select">
                    <option value="25" selected>25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalles -->
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Detalles del Pedido</h2>
            <button class="btn-close">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

<!-- Modal de Tracking/Seguimiento de Prendas -->
<x-orders-components.order-tracking-modal />

@push('scripts')
<script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
<script type="module">
    import { PedidosRecibosModule } from '/js/modulos/pedidos-recibos/index.js';
    
    // Exponer el módulo globalmente para que orders-v2.js lo pueda usar
    window.pedidosRecibosModule = new PedidosRecibosModule();
    
    // Inicializar InvoiceLazyLoader para abrir facturas
    new InvoiceLazyLoader();
    console.log('✓ PedidosRecibosModule y InvoiceLazyLoader inicializados');
</script>

<!-- Sistema de Tracking/Seguimiento de Prendas -->
<script defer src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/days-selector-handler.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/date-utils.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/modal-manager.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/days-selector.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/data-loader.js') }}?v={{ time() }}"></script>
<script defer type="module" src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/ui-components.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/process-manager.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/area-cards.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/prendas-renderer.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/tracking-main.js') }}?v={{ time() }}"></script>

<script src="{{ asset('js/orders-v2.js') }}?v={{ time() }}"></script>
@endpush

@endsection
