@extends('insumos.layout')

@section('title', 'Inventario de Telas - Insumos')
@section('page-title', 'Inventario de Telas')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/insumos/tailwind-utils.css') }}">
<style>
    .inventory-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .inventory-table thead {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .inventory-table th {
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #495057;
    }
    
    .inventory-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .inventory-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
</style>
@endpush

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Inventario de Telas</h2>
        <p class="text-gray-600 mt-2">Gestión de telas disponibles para producción</p>
    </div>

    <!-- Telas Disponibles -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
            <h3 class="text-white text-lg font-semibold">Telas Disponibles</h3>
        </div>
        
        @if($telas->count() > 0)
            <div class="overflow-x-auto">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Nombre</th>
                            <th>Stock Actual</th>
                            <th>Metraje Sugerido</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($telas as $tela)
                            <tr>
                                <td>{{ $tela->categoria ?? 'N/A' }}</td>
                                <td class="font-medium">{{ $tela->nombre_tela ?? 'N/A' }}</td>
                                <td>{{ $tela->stock ?? 0 }} m</td>
                                <td>{{ $tela->metraje_sugerido ?? 0 }} m</td>
                                <td>
                                    @if(($tela->stock ?? 0) > 0)
                                        <span class="badge badge-success">✓ Disponible</span>
                                    @else
                                        <span class="badge badge-danger">✗ Agotado</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        Ajustar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-gray-500">
                <p>No hay telas registradas en el inventario</p>
            </div>
        @endif
    </div>

    <!-- Historial de Movimientos -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h3 class="text-white text-lg font-semibold">Historial de Movimientos</h3>
        </div>
        
        @if($historial->count() > 0)
            <div class="overflow-x-auto">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo de Acción</th>
                            <th>Cantidad</th>
                            <th>Stock Anterior</th>
                            <th>Stock Nuevo</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial as $registro)
                            <tr>
                                <td>{{ $registro->fecha_accion ? \Carbon\Carbon::parse($registro->fecha_accion)->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>
                                    @if($registro->tipo_accion === 'entrada')
                                        <span class="badge badge-success">Entrada</span>
                                    @elseif($registro->tipo_accion === 'salida')
                                        <span class="badge badge-danger">Salida</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($registro->tipo_accion) }}</span>
                                    @endif
                                </td>
                                <td>{{ $registro->cantidad ?? 0 }} m</td>
                                <td>{{ $registro->stock_anterior ?? 0 }} m</td>
                                <td>{{ $registro->stock_nuevo ?? 0 }} m</td>
                                <td>{{ $registro->observaciones ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $historial->links() }}
            </div>
        @else
            <div class="p-6 text-center text-gray-500">
                <p>No hay movimientos registrados</p>
            </div>
        @endif
    </div>
</div>
@endsection
