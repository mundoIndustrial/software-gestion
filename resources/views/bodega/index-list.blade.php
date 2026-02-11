@extends('layouts.app')

@section('title', 'Gestión de Pedidos - Bodega')

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="border-b border-slate-200 px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Gestión de Bodega</h1>
                    <p class="text-sm text-slate-500 mt-1">Pedidos en bodega</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->getRoleNames()->implode(', ') }}</p>
                </div>
            </div>
        </div>

        <!-- Buscador -->
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" class="flex gap-2">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Buscar por número de pedido o asesor..."
                    value="{{ $search ?? '' }}"
                    class="flex-1 px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                >
                <button 
                    type="submit"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors"
                >
                    Buscar
                </button>
                @if($search ?? false)
                    <a 
                        href="{{ route('gestion-bodega.pedidos') }}"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Stats compactas -->
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex gap-8 text-sm">
                <div>
                    <span class="text-slate-500">Pedidos totales</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $totalPedidos }}</span>
                </div>
                <div>
                    <span class="text-slate-500">En esta página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ count($pedidosPorPagina) }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $paginaActual }} / {{ ceil($totalPedidos / $porPagina) }}</span>
                </div>
            </div>
        </div>

        <!-- Tabla de Pedidos -->
        <div class="bg-white overflow-hidden">
            @if(count($pedidosPorPagina) > 0)
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
                                    Asesor
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    Creación
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($pedidosPorPagina as $pedidoData)
                                <tr class="hover:opacity-75 transition-opacity @if($pedidoData['tiene_pendientes'] ?? false) bg-yellow-100 @elseif($pedidoData['todos_entregados'] ?? false) bg-blue-100 @elseif(!empty($pedidoData['viewed_at'])) bg-gray-200 @else bg-white @endif">
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('gestion-bodega.pedidos-show', $pedidoData['id']) }}"
                                           class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                            Ver
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-black">
                                        {{ $pedidoData['numero_pedido'] }}
                                    </td>
                                    <td class="px-6 py-4 text-black">
                                        {{ $pedidoData['cliente'] ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-black">
                                        {{ $pedidoData['asesor'] ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $estado = strtoupper(trim($pedidoData['estado'] ?? ''));
                                            $colorClase = match($estado) {
                                                'ENTREGADO' => 'bg-green-50 text-green-700',
                                                'EN EJECUCIÓN' => 'bg-blue-50 text-blue-700',
                                                'PENDIENTE_SUPERVISOR' => 'bg-amber-50 text-amber-700',
                                                'PENDIENTE_INSUMOS' => 'bg-orange-50 text-orange-700',
                                                'NO INICIADO' => 'bg-slate-50 text-slate-700',
                                                'ANULADA' => 'bg-red-50 text-red-700',
                                                'DEVUELTO_A_ASESORA' => 'bg-purple-50 text-purple-700',
                                                default => 'bg-slate-50 text-slate-700'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded {{ $colorClase }}">
                                            {{ str_replace('_', ' ', $estado) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-black">
                                        {{ \Carbon\Carbon::parse($pedidoData['fecha_pedido'])->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if(ceil($totalPedidos / $porPagina) > 1)
                    <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-sm text-slate-700">
                            Mostrando <span class="font-medium">{{ count($pedidosPorPagina) }}</span> de <span class="font-medium">{{ $totalPedidos }}</span> pedidos
                        </div>
                        <div class="flex gap-2">
                            @if($paginaActual > 1)
                                <a href="{{ route('gestion-bodega.pedidos', ['page' => $paginaActual - 1] + request()->query()) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                    ← Anterior
                                </a>
                            @endif
                            
                            <span class="px-3 py-1 text-sm text-slate-600">
                                Página {{ $paginaActual }} de {{ ceil($totalPedidos / $porPagina) }}
                            </span>
                            
                            @if($paginaActual < ceil($totalPedidos / $porPagina))
                                <a href="{{ route('gestion-bodega.pedidos', ['page' => $paginaActual + 1] + request()->query()) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                    Siguiente →
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-slate-500 font-medium">No hay pedidos disponibles</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
