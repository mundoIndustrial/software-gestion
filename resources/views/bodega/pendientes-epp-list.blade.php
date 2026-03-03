@extends('layouts.app')

@section('title', 'Pendientes EPP - Bodega')
@section('page-title', 'Pendientes EPP')

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Header con título y buscador -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-black">Pendientes EPP</h1>
                    <p class="text-sm text-slate-600 mt-1">Artículos de EPP pendientes de entrega a despacho</p>
                </div>
                <div class="text-right">
                    <div class="inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-lg font-semibold">
                        Total: <span class="text-xl">{{ $total }}</span>
                    </div>
                    @if($total > 0)
                        <div class="mt-2 text-xs text-slate-600">
                            Mostrando {{ $epp_pendientes->count() }} de {{ $total }} registros
                        </div>
                    @endif
                </div>
            </div>

            <!-- Buscador -->
            <form method="GET" class="flex gap-3 mb-4">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Buscar por número de pedido, cliente, asesor o producto..."
                    value="{{ $search ?? '' }}"
                    class="flex-1 px-4 py-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-500"
                >
                <button 
                    type="submit"
                    class="px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-sm">search</span>
                        Buscar
                    </span>
                </button>
                @if($search)
                    <a 
                        href="{{ route('gestion-bodega.pendientes-epp-list') }}"
                        class="px-6 py-3 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded-lg transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-sm">clear</span>
                            Limpiar
                        </span>
                    </a>
                @endif
            </form>

            <!-- Botón de Exportación -->
            @if($total > 0)
                <div class="flex gap-3 mb-6">
                    <a 
                        href="{{ route('gestion-bodega.exportar-pendientes-epp', request()->query()) }}"
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors inline-flex items-center gap-2"
                        download
                    >
                        <span class="material-symbols-rounded text-sm">download</span>
                        Exportar a Excel
                    </a>
                </div>
            @endif

        <!-- Tabla de Pendientes EPP -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            @if($total > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold text-slate-700 text-sm">
                                    Fecha Pedido
                                </th>
                                <th class="px-6 py-4 text-left font-semibold text-slate-700 text-sm">
                                    Asesor
                                </th>
                                <th class="px-6 py-4 text-left font-semibold text-slate-700 text-sm">
                                    Cliente
                                </th>
                                <th class="px-6 py-4 text-left font-semibold text-slate-700 text-sm">
                                    Nº Pedido
                                </th>
                                <th class="px-6 py-4 text-left font-semibold text-slate-700 text-sm">
                                    Producto
                                </th>
                                <th class="px-6 py-4 text-center font-semibold text-slate-700 text-sm">
                                    Cantidad
                                </th>
                                <th class="px-6 py-4 text-left font-semibold text-slate-700 text-sm">
                                    Fecha Entrega a Despacho
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @php
                                // Agrupar por fecha
                                $epp_agrupado = [];
                                foreach ($epp_pendientes as $item) {
                                    $fecha = $item->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($item->fecha_de_creacion_de_orden)->format('Y-m-d') : 'sin-fecha';
                                    if (!isset($epp_agrupado[$fecha])) {
                                        $epp_agrupado[$fecha] = [];
                                    }
                                    $epp_agrupado[$fecha][] = $item;
                                }
                            @endphp
                            @foreach($epp_agrupado as $fecha => $items)
                                @php
                                    $rowspan = count($items);
                                    $fechaFormato = $fecha !== 'sin-fecha' ? \Carbon\Carbon::createFromFormat('Y-m-d', $fecha)->format('d/m/Y') : '—';
                                @endphp
                                @foreach($items as $indexItem => $item)
                                    @php
                                        $estaRetrasado = $item->fecha_entrega && \Carbon\Carbon::parse($item->fecha_entrega)->isPast();
                                        $rowClass = $estaRetrasado ? 'bg-red-50' : 'hover:bg-slate-50';
                                    @endphp
                                    <tr class="transition-colors {{ $rowClass }}">
                                        @if($indexItem === 0)
                                        <td class="px-6 py-4 text-slate-700 font-semibold" rowspan="{{ $rowspan }}">
                                            {{ $fechaFormato }}
                                        </td>
                                        @endif
                                        <td class="px-6 py-4 text-slate-700">
                                            {{ $item->asesor ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            {{ $item->empresa ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-black">
                                            {{ $item->numero_pedido }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            {{ $item->prenda_nombre ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-semibold text-black">
                                            {{ $item->cantidad ?? 0 }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">
                                            <div class="flex items-center gap-2">
                                                <input 
                                                    type="date" 
                                                    class="fecha-entrega-despacho px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-500 flex-1"
                                                    value="{{ $item->fecha_entrega_despacho ? $item->fecha_entrega_despacho->format('Y-m-d') : '' }}"
                                                    data-id="{{ $item->id }}"
                                                >
                                                <span 
                                                    class="estado-guardado text-sm text-green-600 font-semibold hidden"
                                                    data-id="{{ $item->id }}"
                                                >
                                                    ✓
                                                </span>
                                            </div>
                                            @if($item->fecha_entrega_despacho)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    Guardado: {{ $item->fecha_entrega_despacho->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                @if($epp_pendientes->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            Página {{ $epp_pendientes->currentPage() }} de {{ $epp_pendientes->lastPage() }}
                        </div>
                        
                        <div class="flex gap-2">
                            {{ $epp_pendientes->render('pagination::tailwind') }}
                        </div>
                    </div>
                @endif
            
            @else
                <div class="py-12 text-center">
                    <div class="flex flex-col items-center">
                        <span class="material-symbols-rounded text-slate-300 text-5xl">inventory_2</span>
                        <p class="text-slate-500 font-medium mt-3">No hay pendientes de EPP</p>
                        <p class="text-slate-400 text-sm mt-1">Todos los EPP han sido entregados a despacho</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar cambios en los inputs de fecha
    document.querySelectorAll('.fecha-entrega-despacho').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.dataset.id;
            const fechaEntrega = this.value;

            if (!fechaEntrega) {
                return;
            }

            const estadoGuardado = document.querySelector(`[data-id="${id}"].estado-guardado`);
            const inputElement = this;

            // Enviar la solicitud AJAX
            fetch(`/gestion-bodega/bodega-detalles/${id}/fecha-entrega-despacho`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    fecha_entrega_despacho: fechaEntrega
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Poner el input en verde
                    inputElement.classList.add('border-green-500', 'ring-green-500', 'bg-green-50');
                    
                    // Mostrar indicador de guardado
                    if (estadoGuardado) {
                        estadoGuardado.classList.remove('hidden');
                    }

                    // Actualizar el texto de "Guardado"
                    const fechaFormato = new Date(fechaEntrega + 'T00:00:00').toLocaleDateString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    const parent = inputElement.closest('td');
                    let savedDiv = parent.querySelector('.text-xs');
                    if (!savedDiv) {
                        savedDiv = document.createElement('div');
                        savedDiv.className = 'mt-1 text-xs text-slate-500';
                        parent.appendChild(savedDiv);
                    }
                    savedDiv.textContent = `Guardado: ${fechaFormato}`;

                    // Quitar el color verde después de 3 segundos
                    setTimeout(() => {
                        inputElement.classList.remove('border-green-500', 'ring-green-500', 'bg-green-50');
                        inputElement.classList.add('border-slate-300');
                        if (estadoGuardado) {
                            estadoGuardado.classList.add('hidden');
                        }
                    }, 3000);
                } else {
                    alert('Error al guardar: ' + data.message);
                    inputElement.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar la fecha');
                inputElement.value = '';
            });
        });
    });
});
</script>
@endsection

