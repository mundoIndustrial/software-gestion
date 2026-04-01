@extends('layouts.app')

@section('title', 'Registro de Órdenes - MundoIndustrial')
@section('page-title', 'Registro de Órdenes')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/orders-v2/new-registros.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders-v2/cards-view.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/orders-v2/kanban-view.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="orders-container">
    <!-- Encabezado -->
    <div class="orders-header">
        <div class="header-left">
            <h1 class="page-title">
                <i class="fas fa-list"></i> Registro de Órdenes
            </h1>
        </div>
        
        <div class="header-search">
            <i class="fas fa-search"></i>
            <input type="text" id="globalSearch" placeholder="Buscar por número o cliente..." class="search-input">
        </div>
        
        <div class="header-right">
            <button class="btn-actions">
                <i class="fas fa-sliders-h"></i> Acciones
            </button>
            <button class="btn-new-order">
                <i class="fas fa-plus"></i> Nuevo Pedido
            </button>
        </div>
    </div>

    <!-- Selector de Vistas & Filtros -->
    <div class="controls-bar">
        <div class="view-selector">
            <button class="view-btn active" data-view="tabla" title="Vista de Tabla">
                Tabla
            </button>
            <button class="view-btn" data-view="tarjetas" title="Vista de Tarjetas">
                Tarjetas
            </button>
            <button class="view-btn" data-view="kanban" title="Vista Kanban">
                Kanban
            </button>
        </div>

        @php
            $contadores = [
                'pendientes' => $ordenes->getCollection()->where('estado', 'Pendiente')->count(),
                'vencidos' => $ordenes->getCollection()->where('estado', 'Anulada')->count(),
                'en_progreso' => $ordenes->getCollection()->where('estado', 'En Ejecución')->count(),
            ];
        @endphp
        <div class="quick-filters">
            <button class="filter-btn active" data-status="todos">
                <i class="fas fa-bars"></i> Todos
            </button>
            <button class="filter-btn" data-status="pendientes">
                <i class="fas fa-hourglass-start"></i> Pendientes <span class="filter-count">({{ $contadores['pendientes'] }})</span>
            </button>
            <button class="filter-btn" data-status="vencidos">
                <i class="fas fa-exclamation-circle"></i> Vencidos <span class="filter-count">({{ $contadores['vencidos'] }})</span>
            </button>
            <button class="filter-btn" data-status="en-progreso">
                <i class="fas fa-spinner"></i> En Progreso <span class="filter-count">({{ $contadores['en_progreso'] }})</span>
            </button>
            <button class="filter-btn" data-status="completados">
                <i class="fas fa-check-circle"></i> Completados
            </button>
            <button class="filter-btn filter-advanced">
                <i class="fas fa-filter"></i> Filtros
            </button>
        </div>

        <div class="controls-right">
            <button class="btn-icon" title="Actualizar">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn-icon" title="Opciones">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    </div>

    <!-- VISTA TABLA -->
    <div class="view-content" id="view-tabla">
        <div class="table-wrapper">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th class="col-pedido">Pedido</th>
                        <th class="col-cliente">Cliente</th>
                        <th class="col-estado">Estado</th>
                        <th class="col-entrega">Entrega</th>
                        <th class="col-progreso">Progreso</th>
                        <th class="col-accion">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordenes as $orden)
                        @php
                            $estado = $orden->estado ?? 'pendiente';
                            $estadoClass = 'estado-' . str_replace(' ', '-', strtolower($estado));
                            $diaEntrega = $orden->dia_de_entrega ?? '-';
                            $progreso = rand(10, 95); // Placeholder - obtener del backend si está disponible
                        @endphp
                        <tr class="table-row" data-orden-id="{{ $orden->id }}">
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
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: {{ $progreso }}%"></div>
                                    <span class="progress-text">{{ $progreso }}%</span>
                                </div>
                            </td>
                            <td class="col-accion">
                                <button class="btn-menu-actions" data-orden-id="{{ $orden->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="action-menu" style="display: none;">
                                    <a href="#" class="menu-item" data-action="detalle">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                    <a href="#" class="menu-item" data-action="seguimiento">
                                        <i class="fas fa-tasks"></i> Seguimiento
                                    </a>
                                    <a href="#" class="menu-item" data-action="editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="#" class="menu-item" data-action="descargar">
                                        <i class="fas fa-download"></i> Descargar Recibo
                                    </a>
                                </div>
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

    <!-- VISTA TARJETAS -->
    <div class="view-content" id="view-tarjetas" style="display: none;">
        <div class="cards-grid">
            @forelse($ordenes as $orden)
                <div class="order-card" data-orden-id="{{ $orden->id }}">
                    <div class="card-header">
                        <h3>#{{ $orden->numero_pedido ?? $orden->id }}</h3>
                        <button class="btn-card-menu">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="card-field">
                            <span class="label">Cliente</span>
                            <span class="value">{{ $orden->cliente ?? '-' }}</span>
                        </div>
                        <div class="card-field">
                            <span class="label">Estado</span>
                            <span class="badge {{ 'estado-' . str_replace(' ', '-', strtolower($orden->estado ?? 'pendiente')) }}">
                                {{ $orden->estado ?? 'Pendiente' }}
                            </span>
                        </div>
                        <div class="card-field">
                            <span class="label">Entrega</span>
                            <span class="value">{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="card-field">
                            <span class="label">Progreso</span>
                            <div class="progress-bar-card" style="width: {{ rand(10, 95) }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No hay órdenes disponibles</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- VISTA KANBAN -->
    <div class="view-content" id="view-kanban" style="display: none;">
        <div class="kanban-board">
            @php
                $estados = ['Pendiente', 'En Ejecución', 'Completado', 'Anulada'];
            @endphp
            @foreach($estados as $estadoKey)
                <div class="kanban-column" data-estado="{{ $estadoKey }}">
                    <div class="column-header">
                        <h3>{{ $estadoKey }}</h3>
                        <span class="column-count">0</span>
                    </div>
                    <div class="kanban-items">
                        @foreach($ordenes->where('estado', $estadoKey) as $orden)
                            <div class="kanban-card" draggable="true" data-orden-id="{{ $orden->id }}">
                                <div class="card-title">#{{ $orden->numero_pedido ?? $orden->id }}</div>
                                <div class="card-client">{{ $orden->cliente ?? '-' }}</div>
                                <div class="card-footer">
                                    <span class="progress-badge">{{ rand(10, 95) }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
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

@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeViews();
    initializeFilters();
    initializeSearch();
    initializeActionMenus();
});

function initializeViews() {
    const viewBtns = document.querySelectorAll('.view-btn');
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetView = this.dataset.view;
            switchView(targetView);
        });
    });
}

function switchView(viewName) {
    // Ocultar todas las vistas
    document.querySelectorAll('.view-content').forEach(view => {
        view.style.display = 'none';
    });
    
    // Mostrar vista seleccionada
    document.getElementById(`view-${viewName}`).style.display = 'block';
    
    // Actualizar botones
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === viewName);
    });
}

function initializeFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.dataset.status;
            console.log('Filtrar por:', status);
            // Aquí iría la lógica de filtrado
        });
    });
}

function initializeSearch() {
    const searchInput = document.getElementById('globalSearch');
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        console.log('Buscando:', query);
        // Aquí iría la lógica de búsqueda
    });
}

function initializeActionMenus() {
    const menuBtns = document.querySelectorAll('.btn-menu-actions');
    menuBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.closest('td').querySelector('.action-menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        });
    });
}
</script>
@endpush

@endsection
