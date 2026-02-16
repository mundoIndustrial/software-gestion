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
                <div>
                    <a href="{{ route('gestion-bodega.pendientes-epp') }}"
                       class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
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

        <!-- Tabla de Detalles del Pedido -->
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
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Pendientes</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 16%;">Observaciones</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Fecha Pedido</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Fecha Entrega</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest" style="width: 14%;">Estado</th>
                            </tr>
                        </thead>
                        
                        <tbody id="pedidosTableBody" class="divide-y divide-slate-300">
                            @forelse($items as $item)
                                @php
                                    // Definir variables al inicio de la fila para que estén disponibles en toda la fila
                                    $desc = $item['descripcion'];
                                    $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                    $tela = $desc['tela'] ?? null;
                                    $color = $desc['color'] ?? null;
                                    $variantes = $desc['variantes'] ?? [];
                                    $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;
                                    $genero = $primeraVariante['genero'] ?? null;
                                    $procesos = $desc['procesos'] ?? [];
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors"
                                    data-numero-pedido="{{ $item['numero_pedido'] }}"
                                    data-asesor="{{ is_string($item['asesor'] ?? null) && !empty($item['asesor']) ? $item['asesor'] : 'N/A' }}"
                                    data-empresa="{{ is_string($item['empresa'] ?? null) && !empty($item['empresa']) ? $item['empresa'] : 'N/A' }}"
                                    @if(($item['epp_estado'] ?? null) === 'Omologar')
                                        style="background-color: rgba(147, 51, 234, 0.08);"
                                    @elseif($item['estado_bodega'] === 'Entregado')
                                        style="background-color: rgba(37, 99, 235, 0.05);"
                                    @endif
                                >
                                    <!-- DESCRIPCIÓN (PRENDA) -->
                                    <td class="px-4 py-3 text-xs text-black border-r border-slate-300" style="width: 22%;">
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
                                        @if(!empty($procesos))
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
                                    
                                    <!-- TALLA -->
                                    <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                        @if(($item['tipo'] ?? null) === 'epp' || ($item['area'] ?? null) === 'EPP')
                                            —
                                        @else
                                            {{ $item['talla'] ?? '—' }}
                                        @endif
                                    </td>
                                    
                                    <!-- CANTIDAD -->
                                    <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                        {{ $item['cantidad_total'] ?? $item['cantidad'] ?? 0 }}
                                    </td>
                                    
                                    <!-- PENDIENTES -->
                                    <td class="px-2 py-3 border-r border-slate-300" style="width: 12%;">
                                        <textarea
                                            class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            data-pedido-produccion-id="{{ $item['pedido_produccion_id'] }}"
                                            data-recibo-prenda-id="{{ $item['recibo_prenda_id'] }}"
                                            placeholder="Pendientes..."
                                            rows="1"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >{{ $item['pendientes'] ?? '' }}</textarea>
                                    </td>
                                    
                                    <!-- OBSERVACIONES -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 16%;">
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
                                            ></textarea>
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
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                        <input
                                            type="date"
                                            class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            value="{{ $item['fecha_pedido'] ? $item['fecha_pedido'] : '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            data-pedido-produccion-id="{{ $item['pedido_produccion_id'] ?? '' }}"
                                            data-recibo-prenda-id="{{ $item['recibo_prenda_id'] ?? '' }}"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >
                                    </td>
                                    
                                    <!-- FECHA ENTREGA -->
                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                        <input
                                            type="date"
                                            class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            value="{{ $item['fecha_entrega'] ? $item['fecha_entrega'] : '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            data-pedido-produccion-id="{{ $item['pedido_produccion_id'] ?? '' }}"
                                            data-recibo-prenda-id="{{ $item['recibo_prenda_id'] ?? '' }}"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >
                                    </td>
                                    
                                    <!-- ESTADO -->
                                    <td class="px-4 py-3" style="width: 14%;">
                                        <select
                                            class="estado-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded
                                                    @if($item['estado_bodega'] === 'Entregado')
                                                        bg-blue-50
                                                    @else
                                                        bg-slate-50
                                                    @endif"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            data-prenda-nombre="{{ $item['prenda_nombre'] ?? ($item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? '') }}"
                                            data-cantidad="{{ $item['cantidad_total'] ?? $item['cantidad'] ?? 0 }}"
                                            data-original-estado="{{ $item['epp_estado'] ?? '' }}"
                                            @if($esReadOnly ?? false) disabled @endif
                                        >
                                            <option value="">ESTADO</option>
                                            <option value="Pendiente" {{ ($item['epp_estado'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                            <option value="Entregado" {{ ($item['epp_estado'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                            <option value="Omologar" {{ ($item['epp_estado'] ?? null) === 'Omologar' ? 'selected' : '' }}>OMOLOGAR</option>
                                            <option value="Anulado" {{ ($item['epp_estado'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
                                        </select>

                                        @if(!($esReadOnly ?? false))
                                        <button
                                            type="button"
                                            onclick="guardarFilaCompleta(this, '{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}')"
                                            class="w-full mt-1 px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold uppercase rounded transition"
                                        >
                                            💾 Guardar
                                        </button>
                                        @else
                                        <div class="w-full mt-1 px-2 py-1 bg-slate-100 text-slate-500 text-xs font-medium text-center rounded">
                                            Solo lectura
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                        <div class="space-y-2">
                                            <span class="material-symbols-rounded text-4xl text-slate-300">inventory_2</span>
                                            <p class="text-lg font-medium">No hay artículos EPP pendientes</p>
                                            <p class="text-sm">Todos los artículos EPP han sido procesados o no existen para este pedido.</p>
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

<!-- Modal de Factura -->
<div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 100000;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white">📄 Pedido</h2>
            <button onclick="cerrarModalFactura()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500">⏳ Cargando factura...</span>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Auto-resize textareas para Pendientes y Observaciones
 */
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.pendientes-input, .observaciones-input');
    
    textareas.forEach(textarea => {
        // Auto-resize inicial
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        
        // Auto-resize al cambiar el contenido
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
});

// La función abrirModalFactura está definida en bodega-pedidos.js

function abrirModalNotas(numeroPedido, talla, prenda, tipo, id) {
    const modal = document.getElementById('modalNotas');
    const historial = document.getElementById('notasHistorial');
    const numeroPedidoSpan = document.getElementById('modalNotasNumeroPedido');
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // Actualizar título
    numeroPedidoSpan.textContent = numeroPedido;
    
    // Cargar notas existentes
    fetch(`/gestion-bodega/notas/${numeroPedido}/${talla}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                renderizarNotas(data.data);
            } else {
                historial.innerHTML = '<div class="text-center text-slate-500">No hay notas</div>';
            }
        })
        .catch(error => {
            console.error('Error al cargar notas:', error);
            historial.innerHTML = '<div class="text-center text-red-500">Error al cargar notas</div>';
        });
}

function cerrarModalNotas() {
    const modal = document.getElementById('modalNotas');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function renderizarNotas(notas) {
    const historial = document.getElementById('notasHistorial');
    
    if (!notas || notas.length === 0) {
        historial.innerHTML = '<div class="text-center text-slate-500">No hay notas</div>';
        return;
    }
    
    let html = '<div class="space-y-3">';
    notas.forEach(nota => {
        const fecha = new Date(nota.created_at || nota.fecha_completa).toLocaleString('es-ES');
        html += `
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                    <span class="font-medium text-slate-900">${nota.usuario_nombre}</span>
                    <span class="text-xs text-slate-500">${fecha}</span>
                </div>
                <p class="text-sm text-slate-700">${nota.contenido || nota.nota}</p>
            </div>
        `;
    });
    html += '</div>';
    
    historial.innerHTML = html;
}

function guardarNota() {
    const numeroPedido = document.getElementById('modalNotasNumeroPedido').textContent;
    const nota = document.getElementById('nuevaNota').value.trim();
    
    if (!nota) {
        alert('Por favor ingresa una nota');
        return;
    }
    
    const talla = document.querySelector('#modalNotasNumeroPedido').dataset.talla || '';
    
    fetch('/gestion-bodega/notas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            numero_pedido: numeroPedido,
            talla: talla,
            nota: nota
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('nuevaNota').value = '';
            renderizarNotas(data.notas);
        } else {
            alert('Error al guardar la nota: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al guardar nota:', error);
        alert('Error al guardar la nota');
    });
}

function guardarFilaCompleta(button, numeroPedido, talla) {
    const row = button.closest('tr');
    const pendientesInput = row.querySelector('.pendientes-input');
    const observacionesInput = row.querySelector('.observaciones-input');
    const estadoSelect = row.querySelector('.estado-select');
    
    const datos = {
        numero_pedido: numeroPedido,
        talla: talla,
        pendientes: pendientesInput.value,
        observaciones: observacionesInput.value,
        estado: estadoSelect.value
    };
    
    // Deshabilitar botón durante el guardado
    button.disabled = true;
    button.textContent = '⏳ Guardando...';
    
    fetch('/gestion-bodega/guardar-fila', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.textContent = '✅ Guardado';
            setTimeout(() => {
                button.textContent = '💾 Guardar';
                button.disabled = false;
            }, 2000);
        } else {
            button.textContent = '❌ Error';
            setTimeout(() => {
                button.textContent = '💾 Guardar';
                button.disabled = false;
            }, 2000);
            alert('Error al guardar: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error al guardar:', error);
        button.textContent = '❌ Error';
        setTimeout(() => {
            button.textContent = '💾 Guardar';
            button.disabled = false;
        }, 2000);
        alert('Error al guardar los datos');
    });
}

// Cerrar modales con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalFactura();
        cerrarModalNotas();
    }
});
</script>

@push('scripts')
    <script src="{{ asset('js/bodega-pedidos.js') }}"></script>
@endpush
@endsection
