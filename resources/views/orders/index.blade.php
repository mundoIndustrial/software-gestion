@extends('layouts.app')

@section('title', 'Registro de Ordenes - MundoIndustrial')
@section('page-title', 'Registro de Ordenes')

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
    
    <!-- Filtros Rapidos -->
    <div class="controls-bar">

        @php
            $hoy = \Carbon\Carbon::today();

            $contadores = [
                'vencidos' => $ordenes->getCollection()->filter(function($orden) use ($hoy, $fechaMaximaRecibosPorPedido) {
                    $fechaMaximaRecibos = $fechaMaximaRecibosPorPedido[$orden->id] ?? null;
                    $fechaEntrega = $fechaMaximaRecibos ?? $orden->fecha_estimada_de_entrega;
                    $estado = \Illuminate\Support\Str::lower((string) ($orden->estado ?? ''));
                    $esEntregado = \Illuminate\Support\Str::contains($estado, 'entregado');
                    return !$esEntregado && $fechaEntrega && $fechaEntrega < $hoy;
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
                        <th class="col-pedido">Pedido</th>
                        <th class="col-cliente">Cliente</th>
                        <th class="col-estado">Estado</th>
                        <th class="col-entrega">Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordenes as $orden)
                        @php
                            $estado = $orden->estado ?? 'pendiente';
                            $estadoClass = 'estado-' . str_replace(' ', '-', strtolower($estado));
                            $diaEntrega = $orden->dia_de_entrega ?? '-';
                            
                            // Obtener fecha maxima de entrega de recibos de costura
                            $fechaMaximaRecibos = $fechaMaximaRecibosPorPedido[$orden->id] ?? null;
                            $fechaEntregaVerificacion = $fechaMaximaRecibos ?? $orden->fecha_estimada_de_entrega;
                            
                            // Verificar si la orden esta vencida
                            $hoy = \Carbon\Carbon::today();
                            $esEntregado = \Illuminate\Support\Str::contains(
                                \Illuminate\Support\Str::lower((string) ($orden->estado ?? '')),
                                'entregado'
                            );
                            $esVencida = !$esEntregado && $fechaEntregaVerificacion && $fechaEntregaVerificacion < $hoy;
                        @endphp
                        <tr class="table-row" data-orden-id="{{ $orden->id }}" data-vencido="{{ $esVencida ? 'true' : 'false' }}">
                            <td class="col-accion">
                                <button class="btn-menu-actions" data-orden-id="{{ $orden->id }}" data-numero-pedido="{{ $orden->numero_pedido }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="action-menu" style="display: none;">
                                    <a href="#" class="menu-item" data-action="detalle">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                    <a href="#" class="menu-item" data-action="recibos">
                                        <i class="fas fa-receipt"></i> Ver Recibos
                                    </a>
                                    <a href="#" class="menu-item" data-action="seguimiento">
                                        <i class="fas fa-tasks"></i> Seguimiento
                                    </a>

                                </div>
                            </td>
                            <td class="col-pedido">
                                <strong>#{{ $orden->numero_pedido ?? $orden->id }}</strong>
                            </td>
                            <td class="col-cliente">
                                <span class="client-dot">&bull;</span>
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
                                @if(false && $diaEntrega !== '-')
                                    <span class="entrega-date">&uarr; {{ $diaEntrega }}</span>
                                @else
                                    <span class="entrega-date">{{ $fechaEntregaVerificacion ? $fechaEntregaVerificacion->format('d/m/Y') : '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No hay órdenes disponibles</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacion -->
        <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-700">
                Mostrando <span class="font-medium">{{ $ordenes->count() }}</span> de <span class="font-medium">{{ $ordenes->total() }}</span> pedidos
            </div>
            <div class="flex gap-2">
                @if($ordenes->hasPages())
                    @if(!$ordenes->onFirstPage())
                        <a href="{{ $ordenes->url(1) }}" class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors" title="Primera pagina">
                            &laquo; Primero
                        </a>
                        <a href="{{ $ordenes->previousPageUrl() }}" class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                            &larr; Anterior
                        </a>
                    @endif

                    <span class="px-3 py-1 text-sm text-slate-600">
                        Pagina {{ $ordenes->currentPage() }} de {{ $ordenes->lastPage() }}
                    </span>

                    @if($ordenes->hasMorePages())
                        <a href="{{ $ordenes->nextPageUrl() }}" class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                            Siguiente &rarr;
                        </a>
                        <a href="{{ $ordenes->url($ordenes->lastPage()) }}" class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors" title="Ultima pagina">
                            Ultimo &raquo;
                        </a>
                    @endif
                @endif
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
            <!-- Contenido dinamico -->
        </div>
    </div>
</div>

<!-- Modal de Tracking/Seguimiento de Prendas -->
<x-orders-components.order-tracking-modal />

<!-- Modal Selector de Recibos de Produccion -->
@include('components.modals.recibos-process-selector')

@push('scripts')
<script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
<script type="module">
    import { PedidosRecibosModule } from '/js/modulos/pedidos-recibos/index.js';
    
    // Exponer el modulo globalmente para que orders-v2.js lo pueda usar
    window.pedidosRecibosModule = new PedidosRecibosModule();
    
    // Inicializar InvoiceLazyLoader para abrir facturas
    new InvoiceLazyLoader();
    console.log('OK PedidosRecibosModule y InvoiceLazyLoader inicializados');
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




