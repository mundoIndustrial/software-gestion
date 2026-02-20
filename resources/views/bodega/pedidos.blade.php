@extends('layouts.app')

@section('module', 'bodega')

@section('page-title', 'Gestión de Pedidos')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/bodega.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto">
        <!-- Buscador -->
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" placeholder="Buscar por número de pedido o cliente..." value="{{ request('search') }}" class="flex-1 px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors">
                    Buscar
                </button>
            </form>
        </div>

        <!-- Stats compactas -->
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex gap-8 text-sm">
                <div>
                    <span class="text-slate-500">Pedidos totales</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $pedidos->total() }}</span>
                </div>
                <div>
                    <span class="text-slate-500">En esta página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $pedidos->count() }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $pedidos->currentPage() }} / {{ $pedidos->lastPage() }}</span>
                </div>
            </div>
        </div>
            <!-- Tabla de Pedidos -->
        <div class="bg-white overflow-hidden relative">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-center font-medium text-slate-700 w-32">
                                Acción
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-700">
                                <div class="flex items-center gap-2">
                                    Nº Pedido
                                    <button type="button" onclick="abrirModalFiltros('numero_pedido')" class="p-1 hover:bg-slate-200 rounded transition-colors" title="Filtrar por número de pedido">
                                        <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-700">
                                <div class="flex items-center gap-2">
                                    Cliente
                                    <button type="button" onclick="abrirModalFiltros('cliente')" class="p-1 hover:bg-slate-200 rounded transition-colors" title="Filtrar por cliente">
                                        <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-700">
                                <div class="flex items-center gap-2">
                                    Asesor
                                    <button type="button" onclick="abrirModalFiltros('asesor')" class="p-1 hover:bg-slate-200 rounded transition-colors" title="Filtrar por asesor">
                                        <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left font-medium text-slate-700">
                                <div class="flex items-center gap-2">
                                    Estado
                                    <button type="button" onclick="abrirModalFiltros('estado')" class="p-1 hover:bg-slate-200 rounded transition-colors" title="Filtrar por estado">
                                        <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-center font-medium text-slate-700">
                                <div class="flex items-center justify-center gap-2">
                                    Creación
                                    <button type="button" onclick="abrirModalFiltros('fecha')" class="p-1 hover:bg-slate-200 rounded transition-colors" title="Filtrar por fecha de creación">
                                        <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($pedidos as $pedido)
                        <tr class="hover:opacity-75 transition-opacity @if($loop->index % 2 == 0) bg-white @else bg-slate-50">
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('gestion-bodega.pedidos-show', $pedido->id) }}" class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                    Ver
                                </a>
                            </td>
                            <td class="px-6 py-4 font-medium text-black">
                                {{ $pedido->numero_pedido }}
                            </td>
                            <td class="px-6 py-4 text-black">
                                {{ $pedido->cliente }}
                            </td>
                            <td class="px-6 py-4 text-black">
                                {{ $pedido->asesor }}
                            </td>
                            <td class="px-6 py-4">
                                @if($pedido->estado === 'ENTREGADO')
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-green-50 text-green-700">
                                        ENTREGADO
                                    </span>
                                @elseif($pedido->estado === 'NO INICIADO')
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-slate-50 text-slate-700">
                                        NO INICIADO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-amber-50 text-amber-700">
                                        {{ $pedido->estado }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-black">
                                {{ $pedido->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
                                            </div>

            <!-- Paginación -->
            @if($pedidos->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $pedidos->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
