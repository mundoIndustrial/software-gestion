@extends('layouts.asesores')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos-table.css') }}">
@endpush

@section('content')

<div class="pedidos-container">
    {{-- HEADER --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0; font-size: 1.8rem; color: #2c3e50;">Mis Pedidos de Producción</h1>
        <a href="{{ route('asesores.pedidos-produccion.crear') }}" class="btn btn-primary">
            <i class="material-symbols-rounded">add</i> Nuevo Pedido
        </a>
    </div>

    {{-- FILTROS --}}
    <x-asesores.pedidos-filters
        :estados="['No iniciado', 'En Ejecución', 'Entregado', 'Anulada']"
        :formasPago="['Efectivo', 'Transferencia', 'Tarjeta']"
        :filtrosActivos="[]"
    />

    {{-- TABLA --}}
    <div class="table-wrapper">
        <x-asesores.pedidos-table
            :pedidos="$pedidos"
            :config="[
                'estadoColores' => [
                    'No iniciado' => '#95a5a6',
                    'En Ejecución' => '#f39c12',
                    'Entregado' => '#27ae60',
                    'Anulada' => '#e74c3c'
                ]
            ]"
        />
    </div>

    {{-- PAGINACIÓN (si es necesaria) --}}
    @if($pedidos instanceof \Illuminate\Pagination\Paginator)
        <div style="margin-top: 2rem;">
            {{ $pedidos->links() }}
        </div>
    @endif
</div>

@push('scripts')
{{-- SERVICIOS --}}
<script src="{{ asset('js/asesores/cotizaciones/services/ApiService.js') }}"></script>

{{-- SERVICIOS DE PEDIDOS --}}
<script src="{{ asset('js/asesores/pedidos/PedidosDataService.js') }}"></script>

{{-- MÓDULOS DE PEDIDOS --}}
<script src="{{ asset('js/asesores/pedidos/PedidosTableModule.js') }}"></script>

{{-- INICIALIZACIÓN --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando módulo de pedidos...');

        // Inicializar servicios
        window.apiService = window.apiService || new ApiService('/asesores');
        window.pedidosDataService = new PedidosDataService(window.apiService);

        // Inicializar módulos
        window.pedidosTableModule = new PedidosTableModule(window.pedidosDataService);

        console.log('✅ Módulo de pedidos inicializado correctamente');
    });
</script>
@endpush

@endsection
