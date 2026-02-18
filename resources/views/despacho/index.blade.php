@extends('layouts.despacho-standalone')

@section('title', 'Módulo de Despacho')
@section('page-title', 'Despacho')

@section('content')
<div class="despacho-index min-h-screen bg-white">
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
                                    Observaciones
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
                                <tr class="hover:bg-slate-50 transition-colors" data-pedido-id="{{ $pedido->id }}">
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
                                        <div class="flex gap-2 items-start">
                                            <textarea
                                                class="despacho-observaciones-preview w-56 px-2 py-1 border border-slate-300 rounded text-xs bg-slate-50 resize-none"
                                                rows="2"
                                                readonly
                                                data-pedido-id="{{ $pedido->id }}"
                                            ></textarea>
                                            <button
                                                type="button"
                                                onclick="abrirModalObservacionesDespachoIndex({{ $pedido->id }}, '{{ addslashes($pedido->numero_pedido) }}')"
                                                class="despacho-obs-btn px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors"
                                                data-pedido-id="{{ $pedido->id }}"
                                                style="position:relative"
                                                title="Ver/agregar observaciones"
                                            >
                                                💬
                                            </button>
                                        </div>
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

<div id="modalObservacionesDespachoIndex" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center overflow-auto" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-2xl max-w-3xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 id="modalObsDespachoTitle" class="text-lg font-semibold text-white">Observaciones</h2>
            <button onclick="cerrarModalObservacionesDespachoIndex()"
                    class="text-white hover:text-slate-200 text-2xl leading-none">
                ✕
            </button>
        </div>

        <div class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 260px)">
            <div id="observacionesDespachoIndexHistorial" class="space-y-3"></div>
            <div class="mt-6">
                <label class="block text-xs font-medium text-slate-700 mb-2">Nueva observación</label>
                <textarea id="observacionesDespachoIndexNueva"
                          class="w-full px-3 py-2 border border-slate-300 rounded text-sm bg-white resize-none"
                          rows="3"
                          placeholder="Escribe la observación..."></textarea>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3 sticky bottom-0">
            <button onclick="cerrarModalObservacionesDespachoIndex()"
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                Cerrar
            </button>
            <button id="btnGuardarObservacionDespachoIndex" onclick="guardarObservacionDespachoIndex()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                Guardar
            </button>
        </div>
    </div>
</div>

<script>
window.__despachoObsUsuarioActualId = {{ auth()->id() ?? 'null' }};
window.__despachoObsUsuarioEsAdmin = {{ auth()->user()->hasRole(['Admin','SuperAdmin','admin']) ? 'true' : 'false' }};
</script>
<script src="{{ asset('js/despacho-index.js') }}"></script>
@endsection
