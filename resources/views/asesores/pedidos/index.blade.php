@extends('asesores.layout')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('content')
<div class="pedidos-list-container">
    <!-- Barra de Acciones -->
    <div class="list-header">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por número o cliente..." value="{{ request('search') }}">
        </div>
        
        <div class="filter-group">
            <select id="filterEstado" class="filter-select">
                <option value="">Todos los Estados</option>
                @foreach($estados as $estado)
                    <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>
                        {{ $estado }}
                    </option>
                @endforeach
            </select>

            <select id="filterArea" class="filter-select">
                <option value="">Todas las Áreas</option>
                @foreach($areas as $area)
                    <option value="{{ $area }}" {{ request('area') == $area ? 'selected' : '' }}>
                        {{ $area }}
                    </option>
                @endforeach
            </select>
        </div>

        <a href="{{ route('asesores.pedidos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Nuevo Pedido
        </a>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="table-container">
        @if($pedidos->count() > 0)
            <table class="pedidos-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Área</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos as $pedido)
                        <tr>
                            <td>
                                <strong>#{{ $pedido->pedido }}</strong>
                            </td>
                            <td>{{ $pedido->cliente }}</td>
                            <td>
                                <span class="badge badge-info">
                                    {{ $pedido->productos->count() }} productos
                                </span>
                            </td>
                            <td>{{ $pedido->cantidad ?? 0 }}</td>
                            <td>
                                <span class="badge badge-{{ 
                                    $pedido->estado == 'Entregado' ? 'success' : 
                                    ($pedido->estado == 'En Ejecución' ? 'warning' : 
                                    ($pedido->estado == 'Anulada' ? 'danger' : 'secondary'))
                                }}">
                                    {{ $pedido->estado ?? 'Sin estado' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-light">
                                    {{ $pedido->area ?? 'Sin área' }}
                                </span>
                            </td>
                            <td>
                                {{ $pedido->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($pedido->fecha_de_creacion_de_orden)->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('asesores.pedidos.show', $pedido->pedido) }}" 
                                       class="btn-action btn-view" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('asesores.pedidos.edit', $pedido->pedido) }}" 
                                       class="btn-action btn-edit" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn-action btn-delete" 
                                            data-pedido="{{ $pedido->pedido }}"
                                            data-cliente="{{ $pedido->cliente }}"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination-container">
                {{ $pedidos->links() }}
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No hay pedidos</h3>
                <p>Aún no has creado ningún pedido. ¡Crea tu primer pedido ahora!</p>
                <a href="{{ route('asesores.pedidos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Crear Primer Pedido
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
@endpush
