@extends('layouts.despacho-standalone')

@section('title', "Despacho - Pedido {$pedido->numero_pedido}")
@section('page-title', "Detalles del Pedido #{$pedido->numero_pedido}")

@push('styles')
<style>
.detalle-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin: 1.5rem;
    overflow: hidden;
}

.detalle-header {
    background: #3b82f6;
    color: white;
    padding: 1.5rem;
}

.detalle-content {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
}

.info-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.estado-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

.estado-pendiente-insumos {
    background: #fed7aa;
    color: #9a3412;
}

.estado-no-iniciado {
    background: #e0e7ff;
    color: #3730a3;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-back:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.prendas-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.prendas-table th,
.prendas-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.prendas-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.prendas-table tr:hover {
    background: #f9fafb;
}
</style>
@endpush

@section('content')
<div class="detalle-container">
    <!-- Header -->
    <div class="detalle-header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Detalles del Pedido</h1>
                <p class="text-blue-100 mt-1">
                    N° Pedido: <span class="font-semibold">{{ $pedido->numero_pedido }}</span> | 
                    Cliente: <span class="font-semibold">{{ $pedido->cliente }}</span>
                </p>
            </div>
            <a href="{{ route('despacho.pendientes') }}" class="btn-back">
                <span class="material-symbols-rounded">arrow_back</span>
                Volver a Pendientes
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="detalle-content">
        <!-- Información General -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Número de Pedido</div>
                <div class="info-value">#{{ $pedido->numero_pedido }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Cliente</div>
                <div class="info-value">{{ $pedido->cliente }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado</div>
                <div class="info-value">
                    <span class="estado-badge {{ $pedido->estado === 'PENDIENTE_INSUMOS' ? 'estado-pendiente-insumos' : 'estado-no-iniciado' }}">
                        {{ $pedido->estado === 'PENDIENTE_INSUMOS' ? 'Pendiente Insumos' : 'No Iniciado' }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha de Creación</div>
                <div class="info-value">{{ $pedido->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Forma de Pago</div>
                <div class="info-value">{{ $pedido->forma_de_pago ?: 'No especificado' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Área</div>
                <div class="info-value">{{ $pedido->area ?: 'No asignada' }}</div>
            </div>
        </div>

        <!-- Observaciones -->
        @if($pedido->observaciones)
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Observaciones</h3>
            <div class="bg-gray-50 border border-gray-200 rounded p-3">
                <p class="text-gray-700">{{ $pedido->observaciones }}</p>
            </div>
        </div>
        @endif

        <!-- Novedades -->
        @if($pedido->novedades)
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Novedades</h3>
            <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                <p class="text-yellow-800">{{ $pedido->novedades }}</p>
            </div>
        </div>
        @endif

        <!-- Prendas del Pedido -->
        <div>
            <h3 class="text-lg font-semibold mb-3">Prendas del Pedido</h3>
            <div class="bg-white border border-gray-200 rounded overflow-hidden">
                <table class="prendas-table">
                    <thead>
                        <tr>
                            <th>Prenda</th>
                            <th>Cantidad</th>
                            <th>Género</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($prendas) && count($prendas) > 0)
                            @foreach($prendas as $prenda)
                            <tr>
                                <td>{{ $prenda->nombre_prenda ?? 'N/A' }}</td>
                                <td>{{ $prenda->cantidad ?? 0 }}</td>
                                <td>{{ $prenda->genero ?? 'N/A' }}</td>
                                <td>{{ $prenda->descripcion ?? 'Sin descripción' }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" class="text-center text-gray-500 py-4">
                                    No hay prendas registradas para este pedido
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Acciones -->
        <div class="mt-6 flex gap-3">
            @if($pedido->estado === 'PENDIENTE_INSUMOS')
            <button class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded transition-colors">
                <span class="material-symbols-rounded align-middle">send</span>
                Enviar a Producción
            </button>
            @endif
            
            @if($pedido->estado === 'No iniciado')
            <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded transition-colors">
                <span class="material-symbols-rounded align-middle">play_arrow</span>
                Iniciar Producción
            </button>
            @endif
            
            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                <span class="material-symbols-rounded align-middle">receipt</span>
                Ver Factura
            </button>
        </div>
    </div>
</div>
@endsection
