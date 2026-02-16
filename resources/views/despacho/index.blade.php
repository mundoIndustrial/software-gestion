@extends('layouts.app')

@section('title', 'Módulo de Despacho')
@section('page-title', 'Despacho')

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="border-b border-slate-200 px-6 py-6">
            <h1 class="text-2xl font-semibold text-slate-900">Despacho</h1>
            <p class="text-sm text-slate-500 mt-1">Gestión de entregas parciales</p>
        </div>

        <!-- Buscador -->
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" class="flex gap-2">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Buscar por pedido o cliente..."
                    value="{{ $search }}"
                    class="flex-1 px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                >
                <button 
                    type="submit"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors"
                >
                    Buscar
                </button>
                @if($search)
                    <a 
                        href="{{ route('despacho.index') }}"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Stats compactas -->
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex gap-8">
                <div>
                    <span class="text-sm text-slate-500">Pedidos totales</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $pedidos->total() }}</span>
                </div>
                <div>
                    <span class="text-sm text-slate-500">En esta página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $pedidos->count() }}</span>
                </div>
                <div>
                    <span class="text-sm text-slate-500">Página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $pedidos->currentPage() }} / {{ $pedidos->lastPage() }}</span>
                </div>
            </div>
        </div>

        <!-- Tabla de pedidos -->
        <div class="bg-white overflow-hidden">
            @if($pedidos->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-center font-medium text-slate-700 w-32">
                                    Acción
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Nº Pedido
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    Creación
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    Entrega
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($pedidos as $pedido)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('despacho.show', $pedido->id) }}"
                                           class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                            Ver
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-900">
                                        {{ $pedido->numero_pedido }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $pedido->cliente ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block px-2 py-1 rounded text-xs font-medium
                                            @if($pedido->estado === 'PENDIENTE_SUPERVISOR')
                                                bg-blue-100 text-blue-800
                                            @elseif($pedido->estado === 'APROBADO_SUPERVISOR')
                                                bg-yellow-100 text-yellow-800
                                            @elseif($pedido->estado === 'EN_PRODUCCION')
                                                bg-orange-100 text-orange-800
                                            @elseif($pedido->estado === 'FINALIZADO')
                                                bg-green-100 text-green-800
                                            @elseif($pedido->estado === 'En Ejecución')
                                                bg-orange-100 text-orange-800
                                            @elseif($pedido->estado === 'Entregado')
                                                bg-green-100 text-green-800
                                            @elseif($pedido->estado === 'Pendiente')
                                                bg-blue-100 text-blue-800
                                            @elseif($pedido->estado === 'No iniciado')
                                                bg-slate-100 text-slate-800
                                            @elseif($pedido->estado === 'Anulada')
                                                bg-red-100 text-red-800
                                            @elseif($pedido->estado === 'PENDIENTE_INSUMOS')
                                                bg-purple-100 text-purple-800
                                            @else
                                                bg-slate-100 text-slate-800
                                            @endif
                                        ">
                                            {{ str_replace('_', ' ', $pedido->estado) ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-slate-600 text-xs">
                                        {{ $pedido->fecha_de_creacion_de_orden?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-slate-600 text-xs">
                                        {{ $pedido->fecha_estimada_de_entrega?->format('d/m/Y') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $pedidos->links() }}
                </div>
            @else
                <div class="px-6 py-16 text-center text-slate-500">
                    No hay pedidos disponibles
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
