@extends('layouts.app-without-sidebar')

@section('title', "Pendientes de Costura - Pedido {$pedido['numero_pedido']}")

@section('content')
<div class="min-h-screen bg-slate-50 w-full flex flex-col">
    <div class="w-full">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-black">Pendientes de Costura</h1>
                    <p class="text-xs sm:text-sm text-black mt-1">
                        N° Pedido: <span class="font-semibold text-black">{{ $pedido['numero_pedido'] }}</span> | 
                        Cliente: <span class="font-semibold text-black">{{ $pedido['cliente'] ?? 'No especificado' }}</span>
                        @if($pedido['asesor'])
                            | Asesor: <span class="font-semibold text-black">{{ $pedido['asesor'] }}</span>
                        @endif
                    </p>
                    <div class="mt-2 p-2 bg-orange-100 border border-orange-200 rounded">
                        <p class="text-xs font-medium text-orange-800">
                            <span class="material-symbols-rounded text-sm align-middle">filter_alt</span>
                            Mostrando solo artículos de Costura con estado Pendiente
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('gestion-bodega.pendientes-costura') }}" 
                       class="px-4 py-2 border border-slate-300 text-black hover:text-black font-medium rounded transition-colors">
                        ← Volver a Pendientes
                    </a>
                    @if($pedido['id'])
                        <button type="button"
                                onclick="abrirModalFactura({{ $pedido['id'] }})"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                            Ver Pedido Completo
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
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 8%;">Pendientes</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 16%;">Observaciones</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Fecha Pedido</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Fecha Entrega</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest" style="width: 18%;">Estado</th>
                            </tr>
                        </thead>
                        
                        <tbody id="pedidosTableBody" class="divide-y divide-slate-300">
                            @forelse($items as $item)
                                <tr class="hover:bg-slate-50 transition-colors"
                                    data-numero-pedido="{{ $item['numero_pedido'] }}"
                                    data-asesor="{{ $item['asesor'] ?? ($pedido['asesor'] ?? '') }}"
                                    data-empresa="{{ $item['empresa'] ?? ($pedido['cliente'] ?? '') }}"
                                    @if(($item['costura_estado'] ?? null) === 'Omologar')
                                        style="background-color: rgba(147, 51, 234, 0.08);"
                                    @elseif($item['estado_bodega'] === 'Entregado')
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
                                        {{ $item['talla'] ?? '—' }}
                                    </td>
                                    
                                    <!-- CANTIDAD -->
                                    <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                        {{ $item['cantidad'] ?? 0 }}
                                    </td>
                                    
                                    <!-- PENDIENTES -->
                                    <td class="px-2 py-3 border-r border-slate-300" style="width: 8%;">
                                        <textarea
                                            class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none bg-slate-50"
                                            style="font-family: 'Poppins', sans-serif;"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            placeholder="Pendientes..."
                                            rows="1"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >{{ $item['pendientes'] ?? '' }}</textarea>
                                    </td>
                                    
                                    <!-- OBSERVACIONES -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 16%;">
                                        <div class="flex gap-1">
                                            <textarea
                                                class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded bg-slate-50"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                placeholder="Notas..."
                                                rows="1"
                                                readonly
                                            >{{ $item['observaciones_bodega'] ?? '' }}</textarea>
                                            <button
                                                type="button"
                                                onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($item['prenda_nombre'] ?? 'Prenda') }}', 'prenda', '{{ $item['talla'] }}')"
                                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                title="Ver/agregar notas"
                                            >
                                                💬
                                            </button>
                                        </div>
                                    </td>
                                    
                                    <!-- FECHA PEDIDO -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                        <input
                                            type="date"
                                            class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                            value="{{ $item['fecha_pedido'] ? \Carbon\Carbon::parse($item['fecha_pedido'])->format('Y-m-d') : '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >
                                    </td>
                                    
                                    <!-- FECHA ENTREGA -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                        <input
                                            type="date"
                                            class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                            value="{{ $item['fecha_entrega'] ? \Carbon\Carbon::parse($item['fecha_entrega'])->format('Y-m-d') : '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >
                                    </td>
                                    
                                    <!-- ESTADO -->
                                    <td class="px-4 py-3" style="width: 18%;">
                                        <select
                                            class="estado-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            data-prenda-nombre="{{ $item['prenda_nombre'] ?? ($item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? '') }}"
                                            data-cantidad="{{ $item['cantidad'] ?? 0 }}"
                                            data-original-estado="{{ $item['costura_estado'] ?? '' }}"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >
                                            <option value="">ESTADO</option>
                                            <option value="Pendiente" {{ ($item['costura_estado'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                            <option value="Entregado" {{ ($item['costura_estado'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                            <option value="Omologar" {{ ($item['costura_estado'] ?? null) === 'Omologar' ? 'selected' : '' }}>OMOLOGAR</option>
                                            <option value="Anulado" {{ ($item['costura_estado'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
                                        </select>

                                        @if(!($esReadOnly ?? false))
                                        <button
                                            type="button"
                                            onclick="guardarFilaCompleta(this, '{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}')"
                                            class="w-full px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold uppercase rounded transition"
                                        >
                                            💾 Guardar
                                        </button>
                                        @else
                                        <div class="w-full px-2 py-1 bg-slate-100 text-slate-500 text-xs font-medium text-center rounded">
                                            Solo lectura
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="material-symbols-rounded text-slate-300 text-5xl">inventory_2</span>
                                            <p class="text-slate-500 font-medium mt-3">No hay artículos pendientes de costura</p>
                                            <p class="text-slate-400 text-sm mt-1">Este pedido no tiene artículos en el área de Costura con estado Pendiente</p>
                                        </div>
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
 <div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 100000;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white"> Pedido</h2>
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
<div id="modalNotas" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4" style="z-index: 100001;">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span></h2>
            <button onclick="cerrarModalNotas()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div class="px-6 py-6">
            <div class="mb-3 text-sm text-slate-700">
                <span class="font-semibold">Artículo:</span> <span id="modalNotasArticulo">—</span>
            </div>
            <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;"></div>
            
            @if(!($esReadOnly ?? false))
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Agregar nueva nota:</label>
                <textarea
                    id="notasNuevaContent"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
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
            @else
            <div class="text-center py-4">
                <button
                    type="button"
                    onclick="cerrarModalNotas()"
                    class="px-6 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                >
                    Cerrar
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('js/bodega-pedidos.js') }}"></script>

<script>
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

/**
 * Auto-resize textareas para Pendientes y Observaciones
 */
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.pendientes-input, .observaciones-input');
    
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    
    textareas.forEach(textarea => {
        autoResizeTextarea(textarea);
        
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
});
</script>
@endsection
