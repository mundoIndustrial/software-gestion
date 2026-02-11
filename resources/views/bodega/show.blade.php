@extends('layouts.app-without-sidebar')

@section('title', "Gestión de Bodega - Pedido {$pedido['numero_pedido']}")

@section('content')
<div class="min-h-screen bg-slate-50 w-full flex flex-col">
    <div class="w-full">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-black">Gestión de Bodega</h1>
                    <p class="text-xs sm:text-sm text-black mt-1">
                        N° Pedido: <span class="font-semibold text-black">{{ $pedido['numero_pedido'] }}</span> | 
                        Cliente: <span class="font-semibold text-black">{{ $pedido['cliente'] ?? 'No especificado' }}</span>
                        @if($pedido['asesor'])
                            | Asesor: <span class="font-semibold text-black">{{ $pedido['asesor'] }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('gestion-bodega.pedidos') }}" 
                       class="px-4 py-2 border border-slate-300 text-black hover:text-black font-medium rounded transition-colors">
                        ← Volver
                    </a>
                    @if($pedido['id'])
                        <button type="button"
                                onclick="abrirModalFactura({{ $pedido['id'] }})"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                            Ver Pedido
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <!-- Tabla Moderna de Detalles -->
            <div class="bg-white h-full overflow-hidden border border-slate-300 shadow-sm rounded">
                <div class="overflow-x-auto h-full">
                    <table class="w-full border-collapse h-screen" style="table-layout: fixed;">
                        <!-- THEAD -->
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-300">
                                <th class="px-4 py-3 text-left text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 22%;">Artículo</th>
                                <th class="px-2 py-3 text-center text-[10px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 6%;">Talla</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 6%;">Cant.</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 18%;">Observaciones</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 14%;">Fecha Pedido</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 14%;">Fecha Entrega</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest" style="width: 18%;">Área / Estado</th>
                            </tr>
                        </thead>
                        
                        <tbody id="pedidosTableBody" class="divide-y divide-slate-300">
                            @forelse($items as $item)
                                <tr class="hover:bg-slate-50 transition-colors"
                                    data-numero-pedido="{{ $item['numero_pedido'] }}"
                                    @if($item['estado_bodega'] === 'Entregado')
                                        style="background-color: rgba(37, 99, 235, 0.05);"
                                    @endif
                                >
                                    <!-- DESCRIPCIÓN (PRENDA) -->
                                    @if(($item['descripcion_rowspan'] ?? 0) > 0)
                                    <td class="px-4 py-3 text-xs text-black border-r border-slate-300" rowspan="{{ $item['descripcion_rowspan'] }}" style="width: 22%;">
                                        @php
                                            $desc = $item['descripcion'];
                                            $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                            $tela = $desc['tela'] ?? null;
                                            $color = $desc['color'] ?? null;
                                            $variantes = $desc['variantes'] ?? [];
                                            $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;
                                            $genero = $primeraVariante['genero'] ?? null;
                                            $procesos = $desc['procesos'] ?? [];
                                        @endphp
                                        <div class="font-bold text-black mb-1">{{ $nombre }}</div>
                                        @if($tela || $color)
                                            <div class="text-black text-xs mb-1">
                                                @if($tela && $color)
                                                    Tela: {{ $tela }} - Color: {{ $color }}
                                                @elseif($tela)
                                                    Tela: {{ $tela }}
                                                @else
                                                    Color: {{ $color }}
                                                @endif
                                            </div>
                                        @endif
                                        @if($genero)
                                            <div class="text-black text-xs mb-1">
                                                Género: <span class="font-semibold">{{ strtoupper($genero) }}</span>
                                            </div>
                                        @endif
                                        @if(count($procesos) > 0)
                                            <div class="text-black text-xs mt-2 space-y-0.5">
                                                @foreach($procesos as $proceso)
                                                    <div class="flex items-start gap-1">
                                                        <span class="text-blue-600 font-bold">•</span>
                                                        <span>
                                                            {{ $proceso['tipo_proceso'] ?? 'Proceso' }}
                                                            @if(!empty($proceso['ubicaciones']))
                                                                @php
                                                                    $ubicaciones = $proceso['ubicaciones'];
                                                                    
                                                                    // Si es un JSON string, decodificar
                                                                    if (is_string($ubicaciones) && (strpos($ubicaciones, '[') === 0 || strpos($ubicaciones, '{') === 0)) {
                                                                        $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                                                                        if (is_array($ubicacionesDecodificadas)) {
                                                                            $ubicacionesStr = implode(', ', $ubicacionesDecodificadas);
                                                                        } else {
                                                                            $ubicacionesStr = $ubicaciones;
                                                                        }
                                                                    } elseif (is_array($ubicaciones)) {
                                                                        $ubicacionesStr = implode(', ', $ubicaciones);
                                                                    } else {
                                                                        $ubicacionesStr = $ubicaciones;
                                                                    }
                                                                @endphp
                                                                @if(!empty($ubicacionesStr))
                                                                    ({{ $ubicacionesStr }})
                                                                @endif
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    @endif
                                    
                                    <!-- TALLA -->
                                    <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                        @if(($item['tipo'] ?? null) === 'epp' || ($item['area'] ?? null) === 'EPP')
                                            —
                                        @else
                                            {{ $item['talla'] ?? '—' }}
                                        @endif
                                    </td>
                                    
                                    <!-- CANTIDAD -->
                                    <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">                                        {{ $item['cantidad_total'] }}
                                    </td>
                                    
                                    <!-- OBSERVACIONES -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 18%;">
                                        <div class="flex gap-1">
                                            <textarea
                                                class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded
                                                    @if($item['estado_bodega'] === 'Entregado')
                                                        bg-blue-50
                                                    @else
                                                        bg-slate-50
                                                    @endif"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                placeholder="Notas..."
                                                rows="1"
                                                readonly
                                            >{{ $item['observaciones'] ?? '' }}</textarea>
                                            <button
                                                type="button"
                                                onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($nombre) }}', 'prenda', '{{ $item['talla'] }}')"
                                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                title="Ver/agregar notas"
                                            >
                                                💬
                                            </button>
                                        </div>
                                    </td>
                                    
                                    <!-- FECHA PEDIDO -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 14%;">
                                        <input
                                            type="date"
                                            class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            value="{{ $item['fecha_pedido'] ?? '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                        >
                                    </td>
                                    
                                    <!-- FECHA ENTREGA -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 14%;">
                                        <input
                                            type="date"
                                            class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            value="{{ $item['fecha_entrega'] ?? '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            @if($item['estado_bodega'] === 'Entregado')
                                                disabled
                                            @endif
                                        >
                                    </td>
                                    
                                    <!-- ÁREA / ESTADO -->
                                    <td class="px-4 py-3" style="width: 18%;">
                                        <div class="space-y-2">
                                            <select
                                                class="area-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-original-area="{{ $item['area'] ?? '' }}"
                                            >
                                                <option value="">ÁREA</option>
                                                <option value="Costura" {{ ($item['area'] ?? null) === 'Costura' ? 'selected' : '' }}>COSTURA</option>
                                                <option value="EPP" {{ ($item['area'] ?? null) === 'EPP' ? 'selected' : '' }}>EPP</option>
                                            </select>

                                            <select
                                                class="estado-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-original-estado="{{ $item['estado_bodega'] ?? '' }}"
                                            >
                                                <option value="">ESTADO</option>
                                                <option value="Pendiente" {{ ($item['estado_bodega'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                                <option value="Entregado" {{ ($item['estado_bodega'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                                @if(auth()->user()->hasRole(['Bodeguero', 'Admin', 'SuperAdmin']))
                                                <option value="Anulado" {{ ($item['estado_bodega'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
                                                @endif
                                            </select>

                                            <button
                                                type="button"
                                                onclick="guardarFilaCompleta(this, '{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}')"
                                                class="w-full px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold uppercase rounded transition"
                                            >
                                                💾 Guardar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <p class="text-slate-500 font-bold text-sm">No hay ítems en este pedido</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 px-5 py-3 bg-slate-900 text-white rounded text-sm font-bold uppercase tracking-wider hidden flex items-center space-x-3 z-[99999]">
    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
    </svg>
    <span id="toastMessage">✓ Operación completada</span>
</div>

<!-- Modal de Factura -->
<div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white">📋 Pedido</h2>
            <button onclick="cerrarModalFactura()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500">⏳ Cargando factura...</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Notas -->
<div id="modalNotas" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9998 overflow-auto">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span></h2>
            <button onclick="cerrarModalNotas()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div class="px-6 py-6">
            <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;">
                <div class="flex justify-center items-center py-8">
                    <span class="text-slate-500">⏳ Cargando notas...</span>
                </div>
            </div>
            <div class="border-t border-slate-200 pt-6">
                <label class="block text-sm font-bold text-slate-900 mb-3">Agregar Nueva Nota:</label>
                <textarea
                    id="notasNuevaContent"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
                    placeholder="Escribe tu nota aquí..."
                    rows="4"
                ></textarea>
                <div class="flex gap-3 mt-4">
                    <button
                        type="button"
                        onclick="guardarNota()"
                        class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition"
                    >
                        ✓ Guardar Nota
                    </button>
                    <button
                        type="button"
                        onclick="cerrarModalNotas()"
                        class="flex-1 px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/bodega-pedidos.js') }}?v={{ time() }}"></script>
@endsection
