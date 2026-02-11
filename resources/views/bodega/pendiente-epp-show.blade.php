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
                    @if(isset($filtro_aplicado))
                        <div class="mt-2 p-2 bg-orange-100 border border-orange-200 rounded">
                            <p class="text-xs font-medium text-orange-800">
                                <span class="material-symbols-rounded text-sm align-middle">filter_alt</span>
                                {{ $filtro_aplicado['descripcion'] }}
                            </p>
                        </div>
                    @endif
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
        <div class="bg-white overflow-hidden relative">
            @if(isset($items) && count($items) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-center font-medium text-slate-700" style="width: 8%;">Talla</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700" style="width: 25%;">Prenda</th>
                                <th class="px-4 py-3 text-center font-medium text-slate-700" style="width: 8%;">Cantidad</th>
                                <th class="px-4 py-3 text-center font-medium text-slate-700" style="width: 8%;">Pendientes</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700" style="width: 15%;">Observaciones</th>
                                <th class="px-4 py-3 text-center font-medium text-slate-700" style="width: 18%;">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($items as $index => $item)
                                @php
                                    $rowspan = $item['descripcion_rowspan'] ?? 1;
                                    $isFirstRow = !isset($items[$index - 1]) || $items[$index - 1]['descripcion']['prenda_nombre'] !== $item['descripcion']['prenda_nombre'];
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    @if($isFirstRow)
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-3 text-center font-medium text-black bg-slate-50 border-r border-slate-200">
                                            {{ $item['talla'] }}
                                        </td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-3 bg-slate-50 border-r border-slate-200">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-black">{{ $item['descripcion']['prenda_nombre'] }}</div>
                                                @if(!empty($item['descripcion']['tela']))
                                                    <div class="text-xs text-slate-600">Tela: {{ $item['descripcion']['tela'] }}</div>
                                                @endif
                                                @if(!empty($item['descripcion']['color']))
                                                    <div class="text-xs text-slate-600">Color: {{ $item['descripcion']['color'] }}</div>
                                                @endif
                                                @if(!empty($item['descripcion']['genero']))
                                                    <div class="text-xs text-slate-600">Género: {{ $item['descripcion']['genero'] }}</div>
                                                @endif
                                                @if(!empty($item['descripcion']['procesos']))
                                                    <div class="text-xs text-slate-600">
                                                        Procesos: {{ is_array($item['descripcion']['procesos']) ? implode(', ', $item['descripcion']['procesos']) : $item['descripcion']['procesos'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td rowspan="{{ $rowspan }}" class="px-4 py-3 text-center font-medium text-black bg-slate-50 border-r border-slate-200">
                                            {{ $item['cantidad'] }}
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 text-center">
                                        <div class="space-y-2">
                                            <textarea
                                                class="pendientes-input w-full px-2 py-1 border border-slate-300 bg-white text-black text-center text-xs font-semibold rounded resize-none"
                                                rows="1"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-original="{{ $item['pendientes'] ?? '0' }}"
                                            >{{ $item['pendientes'] ?? '0' }}</textarea>
                                            <button
                                                type="button"
                                                onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($item['descripcion']['prenda_nombre']) }}', 'prenda', '{{ $item['talla'] }}')"
                                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                title="Ver/agregar notas"
                                            >
                                                💬
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-2">
                                            <textarea
                                                class="observaciones-input w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs rounded resize-none"
                                                rows="1"
                                                readonly
                                            >{{ $item['observaciones'] ?? '' }}</textarea>
                                            <button
                                                type="button"
                                                onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($item['descripcion']['prenda_nombre']) }}', 'prenda', '{{ $item['talla'] }}')"
                                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                title="Ver/agregar notas"
                                            >
                                                💬
                                            </button>
                                        </div>
                                    </td>
                                    
                                    <!-- ESTADO -->
                                    <td class="px-4 py-3" style="width: 18%;">
                                        <div class="space-y-2">
                                            <select
                                                class="estado-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-original-estado="{{ $item['estado'] ?? '' }}"
                                            >
                                                <option value="">ESTADO</option>
                                                <option value="Pendiente" {{ ($item['estado'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                                <option value="Entregado" {{ ($item['estado'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                                @if(auth()->user()->hasRole(['Bodeguero', 'Admin', 'SuperAdmin']))
                                                <option value="Anulado" {{ ($item['estado'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <span class="material-symbols-rounded text-slate-300 text-6xl">inventory_2</span>
                    <p class="text-slate-500 font-medium mt-4">No hay artículos EPP pendientes</p>
                    <p class="text-slate-400 text-sm mt-2">Este pedido no tiene artículos con área "EPP" y estado "Pendiente"</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de Factura -->
<div id="modalFactura" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white">📋 Pedido</h2>
            <button onclick="cerrarModalFactura()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="material-symbols-rounded text-slate-300 text-6xl animate-spin">refresh</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Notas -->
<div id="modalNotas" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span></h2>
            <button onclick="cerrarModalNotas()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div class="px-6 py-6">
            <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;">
                <!-- Las notas se cargarán aquí -->
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Agregar nueva nota:</label>
                <textarea
                    id="nuevaNota"
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

function abrirModalFactura(pedidoId) {
    const modal = document.getElementById('modalFactura');
    const contenido = document.getElementById('facturaContenido');
    
    // Mostrar modal con loading
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // Cargar contenido del pedido
    fetch(`/gestion-bodega/pedido/${pedidoId}/factura`)
        .then(response => response.text())
        .then(html => {
            contenido.innerHTML = html;
        })
        .catch(error => {
            console.error('Error al cargar factura:', error);
            contenido.innerHTML = '<div class="text-center py-8 text-red-500">Error al cargar el pedido</div>';
        });
}

function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

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
            if (data.success) {
                renderizarNotas(data.notas);
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
    
    if (notas.length === 0) {
        historial.innerHTML = '<div class="text-center text-slate-500">No hay notas</div>';
        return;
    }
    
    let html = '<div class="space-y-3">';
    notas.forEach(nota => {
        const fecha = new Date(nota.created_at).toLocaleString('es-ES');
        html += `
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                    <span class="font-medium text-slate-900">${nota.usuario_nombre}</span>
                    <span class="text-xs text-slate-500">${fecha}</span>
                </div>
                <p class="text-sm text-slate-700">${nota.nota}</p>
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
@endsection
