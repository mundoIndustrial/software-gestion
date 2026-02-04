@extends('layouts.app')

@section('title', 'Gestión de Pedidos - Bodega')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/bodega.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-slate-50">
    <!-- Header Corporativo -->
    <div class="bg-white border-b-2 border-slate-300">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">GESTIÓN DE PEDIDOS</h1>
                    <p class="mt-2 text-xs text-slate-600 font-semibold uppercase tracking-wider">Módulo Bodega • Recepción y Entrega</p>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 font-medium">{{ auth()->user()->getRoleNames()->first() ?? 'Sin rol' }}</p>
                    </div>
                    <div class="w-10 h-10 bg-slate-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Filtros Corporativos -->
        <div class="bg-white border-2 border-slate-300 p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Buscador -->
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-3 uppercase tracking-wider">
                        🔍 Buscar Pedido
                    </label>
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Nº pedido, asesor, empresa..."
                        class="w-full px-3 py-2 border-2 border-slate-300 text-xs focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-slate-50"
                    >
                </div>

                <!-- Filtro Asesor -->
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-3 uppercase tracking-wider">
                        👤 Filtrar Asesor
                    </label>
                    <select
                        id="asesorFilter"
                        class="w-full px-3 py-2 border-2 border-slate-300 text-xs focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-white"
                    >
                        <option value="">TODOS</option>
                        @forelse($asesores as $asesor)
                            <option value="{{ $asesor }}">{{ $asesor }}</option>
                        @empty
                        @endforelse
                    </select>
                </div>

                <!-- Filtro Estado -->
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-3 uppercase tracking-wider">
                        📊 Filtrar Estado
                    </label>
                    <select
                        id="estadoFilter"
                        class="w-full px-3 py-2 border-2 border-slate-300 text-xs focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-white"
                    >
                        <option value="">TODOS</option>
                        <option value="pendiente">⏳ PENDIENTE</option>
                        <option value="entregado">✓ ENTREGADO</option>
                        <option value="retrasado">⚠ RETRASADO</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla Corporativa Jerárquica -->
        <div class="bg-white border-2 border-slate-300 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <!-- THEAD CORPORATIVO -->
                    <thead>
                        <tr class="bg-slate-100 border-b-2 border-slate-400">
                            <th class="px-4 py-3 text-left text-[11px] font-black text-slate-900 uppercase tracking-widest border-r border-slate-300">ASESOR</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-slate-900 uppercase tracking-widest border-r border-slate-300">EMPRESA</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-slate-900 uppercase tracking-widest border-r border-slate-300 flex-1">ARTÍCULO</th>
                            <th class="px-4 py-3 text-center text-[11px] font-black text-slate-900 uppercase tracking-widest border-r border-slate-300 w-16">CANT.</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-slate-900 uppercase tracking-widest border-r border-slate-300 w-56">PENDIENTE / OBSERVACIONES</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-slate-900 uppercase tracking-widest border-r border-slate-300 w-40">FECHA ENTREGA</th>
                            <th class="px-4 py-3 text-center text-[11px] font-black text-slate-900 uppercase tracking-widest w-32">ACCIÓN</th>
                        </tr>
                    </thead>
                    
                    <tbody id="pedidosTableBody" class="divide-y divide-slate-200">
                        @forelse($pedidosAgrupados as $numeroPedido => $items)
                            <!-- FILA SEPARADORA DE PEDIDO -->
                            <tr class="bg-slate-200 border-b border-slate-400 hover:bg-slate-200 transition">
                                <td colspan="7" class="px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-1 h-6 bg-slate-400"></div>
                                            <div>
                                                <span class="font-black text-slate-900 text-sm uppercase tracking-wide">PEDIDO #{!! $numeroPedido !!}</span>
                                                <span class="ml-4 text-[11px] text-slate-600 font-semibold">📅 {{ \Carbon\Carbon::parse($items[0]['fecha_pedido'])->format('d-m-Y') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            @php
                                                $allDelivered = collect($items)->every(fn($item) => $item['estado'] === 'entregado');
                                                $anyDelayed = collect($items)->some(fn($item) => $item['estado'] === 'retrasado');
                                            @endphp
                                            
                                            @if($allDelivered)
                                                <span class="inline-flex items-center px-3 py-1 text-[11px] font-black bg-blue-600 text-white uppercase tracking-wider rounded">
                                                    ✓ ENTREGADO
                                                </span>
                                            @elseif($anyDelayed)
                                                <span class="inline-flex items-center px-3 py-1 text-[11px] font-black bg-red-600 text-white uppercase tracking-wider rounded">
                                                    ⚠ RETRASADO
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 text-[11px] font-black bg-amber-500 text-white uppercase tracking-wider rounded">
                                                    ⏳ PENDIENTE
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- FILAS DE ARTÍCULOS DEL PEDIDO -->
                            @foreach($items as $item)
                                <tr class="border-b border-slate-200 hover:bg-slate-50 transition pedido-row"
                                    data-pedido="{{ $numeroPedido }}"
                                    data-asesor="{{ $item['asesor'] }}"
                                    data-estado="{{ $item['estado'] }}"
                                    data-search="{{ strtolower($numeroPedido . ' ' . $item['asesor'] . ' ' . $item['empresa'] . ' ' . $item['articulo']) }}"
                                    @if($item['estado'] === 'entregado')
                                        style="background-color: rgba(37, 99, 235, 0.05);"
                                    @endif
                                >
                                    <!-- ASESOR -->
                                    <td class="px-4 py-3 text-xs font-bold text-slate-900 border-r border-slate-200">
                                        {{ $item['asesor'] }}
                                    </td>
                                    
                                    <!-- EMPRESA -->
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-800 uppercase tracking-wide border-r border-slate-200">
                                        {{ $item['empresa'] }}
                                    </td>
                                    
                                    <!-- ARTÍCULO -->
                                    <td class="px-4 py-3 text-xs text-slate-700 border-r border-slate-200">
                                        {{ $item['articulo'] }}
                                    </td>
                                    
                                    <!-- CANTIDAD -->
                                    <td class="px-4 py-3 text-center text-xs font-black text-slate-900 border-r border-slate-200 font-mono">
                                        {{ str_pad($item['cantidad'], 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    
                                    <!-- OBSERVACIONES -->
                                    <td class="px-4 py-3 border-r border-slate-200">
                                        <textarea
                                            class="observaciones-input w-full px-2 py-1 border-2 border-slate-300 text-[11px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none font-mono
                                                @if($item['estado'] === 'entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            data-id="{{ $item['id'] }}"
                                            placeholder="Agregar notas..."
                                            rows="2"
                                        >{{ $item['observaciones'] ?? '' }}</textarea>
                                    </td>
                                    
                                    <!-- FECHA ENTREGA -->
                                    <td class="px-4 py-3 border-r border-slate-200">
                                        <input
                                            type="date"
                                            class="fecha-input w-full px-2 py-1 border-2 border-slate-300 text-[11px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-slate-50
                                                @if($item['estado'] === 'entregado')
                                                    bg-blue-50
                                                @endif"
                                            value="{{ $item['fecha_entrega'] ?? '' }}"
                                            data-id="{{ $item['id'] }}"
                                            @if($item['estado'] === 'entregado')
                                                disabled
                                            @endif
                                        >
                                    </td>
                                    
                                    <!-- ACCIÓN -->
                                    <td class="px-4 py-3 text-center">
                                        @if($item['estado'] === 'entregado')
                                            <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-black bg-blue-600 text-white uppercase tracking-wider">
                                                ✓ OK
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                class="entregar-btn px-3 py-1.5 border-2 border-black text-black text-[10px] font-black uppercase tracking-widest hover:bg-black hover:text-white transition duration-150"
                                                data-id="{{ $item['id'] }}"
                                            >
                                                ENTREGAR
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-slate-500 font-bold text-sm uppercase tracking-wide">Sin pedidos disponibles</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Estadísticas Corporativas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
            <!-- Total Pedidos -->
            <div class="bg-white border-2 border-slate-300 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black text-slate-600 uppercase tracking-wider">Total Pedidos</p>
                        <p class="text-3xl font-black text-slate-900 mt-2">{{ count($pedidosAgrupados) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-slate-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-700 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pendientes -->
            <div class="bg-white border-2 border-amber-300 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black text-amber-700 uppercase tracking-wider">Pendientes</p>
                        <p class="text-3xl font-black text-amber-600 mt-2" id="countPendiente">0</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Entregados -->
            <div class="bg-white border-2 border-blue-300 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black text-blue-700 uppercase tracking-wider">Entregados</p>
                        <p class="text-3xl font-black text-blue-600 mt-2" id="countEntregado">0</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Retrasados -->
            <div class="bg-white border-2 border-red-300 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black text-red-700 uppercase tracking-wider">Retrasados</p>
                        <p class="text-3xl font-black text-red-600 mt-2" id="countRetrasado">0</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification Corporativo -->
<div id="toast" class="fixed bottom-4 right-4 px-5 py-3 bg-slate-900 text-white rounded text-sm font-bold uppercase tracking-wider hidden flex items-center space-x-3 z-50 border-2 border-slate-700">
    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
    </svg>
    <span id="toastMessage">✓ Operación completada</span>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/bodega-pedidos.js') }}"></script>
@endpush
