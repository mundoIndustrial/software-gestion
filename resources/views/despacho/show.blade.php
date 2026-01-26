@extends('layouts.app-without-sidebar')

@section('title', "Despacho - Pedido {$pedido->numero_pedido}")

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-6xl mx-auto">
        <!-- Header minimalista -->
        <div class="border-b border-slate-200 px-6 py-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Despacho</h1>
                    <p class="text-sm text-slate-500 mt-1">Pedido {{ $pedido->numero_pedido }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('despacho.index') }}" 
                       class="px-3 py-2 text-slate-600 hover:text-slate-900 font-medium">
                        ← Volver
                    </a>
                    <a href="{{ route('despacho.print', $pedido->id) }}"
                       target="_blank"
                       class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded transition-colors">
                        Imprimir
                    </a>
                </div>
            </div>
            
            <!-- Info compacta del pedido -->
            <div class="flex gap-6 text-sm">
                <div>
                    <span class="text-slate-500">Cliente:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->cliente ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Estado:</span>
                    <span class="font-medium text-slate-900 ml-2 @if($pedido->estado === 'Entregado') text-green-700 @elseif($pedido->estado === 'En Ejecución') text-blue-700 @else text-amber-700 @endif">
                        {{ $pedido->estado }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Formulario de despacho -->
        <form id="formDespacho" class="bg-white overflow-hidden">
            @csrf

            <!-- Inputs de despacho -->
            <div class="px-6 py-6 border-b border-slate-200 bg-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fecha_hora" class="block text-sm font-medium text-slate-700 mb-2">
                            Fecha y Hora
                        </label>
                        <input type="datetime-local" 
                               id="fecha_hora"
                               name="fecha_hora"
                               class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                               value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div>
                        <label for="cliente_empresa" class="block text-sm font-medium text-slate-700 mb-2">
                            Receptor
                        </label>
                        <input type="text" 
                               id="cliente_empresa"
                               name="cliente_empresa"
                               class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                               placeholder="Nombre del receptor"
                               value="{{ $pedido->cliente }}">
                    </div>
                </div>
            </div>

            <!-- Tabla de despacho -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-700">Descripción</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700">Talla</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-16">Cantidad</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-20">Parcial 1</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-16">Pendiente</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-20">Parcial 2</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-16">Pendiente</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-20">Parcial 3</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-700 w-16">Pendiente</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDespacho">
                        <!-- PRENDAS -->
                        @if($prendas->count() > 0)
                            <tr class="bg-slate-50">
                                <td colspan="9" class="px-4 py-2 font-semibold text-slate-900">
                                    Prendas ({{ $prendas->count() }})
                                </td>
                            </tr>
                            @foreach($prendas as $index => $fila)
                                <tr class="border-b border-slate-200 hover:bg-slate-50" 
                                    data-tipo="prenda"
                                    data-id="{{ $fila->id }}"
                                    data-talla-id="{{ $fila->tallaId }}"
                                    data-cantidad="{{ $fila->cantidadTotal }}">
                                    
                                    <td class="px-4 py-3 text-slate-900">
                                        {{ $fila->descripcion }}
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center text-slate-600">
                                        {{ $fila->talla }}
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-inicial">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-1"
                                               min="0"
                                               max="{{ $fila->cantidadTotal }}"
                                               value="0"
                                               data-cantidad="{{ $fila->cantidadTotal }}"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-1">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-2"
                                               min="0"
                                               max="{{ $fila->cantidadTotal }}"
                                               value="0"
                                               data-cantidad="{{ $fila->cantidadTotal }}"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-2">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-3"
                                               min="0"
                                               max="{{ $fila->cantidadTotal }}"
                                               value="0"
                                               data-cantidad="{{ $fila->cantidadTotal }}"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-3">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- EPP -->
                        @if($epps->count() > 0)
                            <tr class="bg-slate-50">
                                <td colspan="9" class="px-4 py-2 font-semibold text-slate-900">
                                    EPP ({{ $epps->count() }})
                                </td>
                            </tr>
                            @foreach($epps as $index => $fila)
                                <tr class="border-b border-slate-200 hover:bg-slate-50"
                                    data-tipo="epp"
                                    data-id="{{ $fila->id }}"
                                    data-cantidad="{{ $fila->cantidadTotal }}">
                                    
                                    <td class="px-4 py-3 text-slate-900">
                                        {{ $fila->descripcion }}
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center text-slate-600">
                                        —
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-inicial">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-1"
                                               min="0"
                                               max="{{ $fila->cantidadTotal }}"
                                               value="0"
                                               data-cantidad="{{ $fila->cantidadTotal }}"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-1">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-2"
                                               min="0"
                                               max="{{ $fila->cantidadTotal }}"
                                               value="0"
                                               data-cantidad="{{ $fila->cantidadTotal }}"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-2">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-3"
                                               min="0"
                                               max="{{ $fila->cantidadTotal }}"
                                               value="0"
                                               data-cantidad="{{ $fila->cantidadTotal }}"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-4 py-3 text-center font-medium text-slate-900 pendiente-3">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        @if($prendas->count() === 0 && $epps->count() === 0)
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                    No hay ítems en este pedido
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Botones de acción -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                <div class="text-sm text-slate-600">
                    <span class="font-medium">{{ $prendas->count() + $epps->count() }}</span> ítems
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('despacho.index') }}"
                       class="px-4 py-2 text-slate-600 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            id="btnGuardar"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded transition-colors">
                        Guardar Despacho
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript para cálculos de despacho -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    const formDespacho = document.getElementById('formDespacho');
    const parcialInputs = document.querySelectorAll('.parcial-input');
    const btnGuardar = document.getElementById('btnGuardar');

    // Event listeners para cálculo de pendientes
    parcialInputs.forEach(input => {
        input.addEventListener('change', calcularPendientes);
        input.addEventListener('input', calcularPendientes);
    });

    // Event listener para guardar
    formDespacho.addEventListener('submit', async function(e) {
        e.preventDefault();
        await guardarDespacho();
    });

    /**
     * Calcular pendientes automáticamente
     */
    function calcularPendientes(event) {
        const input = event.target;
        const fila = input.closest('tr');
        
        // Obtener cantidad total
        const cantidadTotal = parseInt(fila.dataset.cantidad);
        
        // Obtener valores de parciales
        const parcial1Input = fila.querySelector('.parcial-1');
        const parcial2Input = fila.querySelector('.parcial-2');
        const parcial3Input = fila.querySelector('.parcial-3');
        
        let parcial1 = parseInt(parcial1Input.value) || 0;
        let parcial2 = parseInt(parcial2Input.value) || 0;
        let parcial3 = parseInt(parcial3Input.value) || 0;

        // Validaciones
        // No permitir números negativos
        if (parcial1 < 0) parcial1 = 0;
        if (parcial2 < 0) parcial2 = 0;
        if (parcial3 < 0) parcial3 = 0;

        // No permitir exceder la cantidad total
        if (parcial1 > cantidadTotal) parcial1 = cantidadTotal;
        if (parcial2 > cantidadTotal) parcial2 = cantidadTotal;
        if (parcial3 > cantidadTotal) parcial3 = cantidadTotal;

        // Actualizar inputs si cambiaron
        parcial1Input.value = parcial1;
        parcial2Input.value = parcial2;
        parcial3Input.value = parcial3;

        // Calcular pendientes
        // P1 = Cantidad - Parcial1
        // P2 = P1 - Parcial2
        // P3 = P2 - Parcial3
        const pendiente1 = Math.max(0, cantidadTotal - parcial1);
        const pendiente2 = Math.max(0, pendiente1 - parcial2);
        const pendiente3 = Math.max(0, pendiente2 - parcial3);

        // Actualizar elementos DOM
        fila.querySelector('.pendiente-1').textContent = pendiente1;
        fila.querySelector('.pendiente-2').textContent = pendiente2;
        fila.querySelector('.pendiente-3').textContent = pendiente3;

        // Cambiar color de fondo según progreso
        if (pendiente3 === 0) {
            fila.classList.remove('hover:bg-blue-50', 'hover:bg-green-50');
            fila.classList.add('bg-green-100');
        } else if (pendiente3 < cantidadTotal) {
            fila.classList.remove('bg-green-100');
            fila.classList.add(fila.dataset.tipo === 'prenda' ? 'hover:bg-blue-50' : 'hover:bg-green-50');
        } else {
            fila.classList.remove('bg-green-100');
            fila.classList.add(fila.dataset.tipo === 'prenda' ? 'hover:bg-blue-50' : 'hover:bg-green-50');
        }
    }

    /**
     * Guardar despacho al servidor
     */
    async function guardarDespacho() {
        const despachos = [];
        const filas = document.querySelectorAll('#tablaDespacho tr[data-tipo]');

        filas.forEach(fila => {
            const tipo = fila.dataset.tipo;
            const id = parseInt(fila.dataset.id);
            
            const parcial1 = parseInt(fila.querySelector('.parcial-1').value) || 0;
            const parcial2 = parseInt(fila.querySelector('.parcial-2').value) || 0;
            const parcial3 = parseInt(fila.querySelector('.parcial-3').value) || 0;

            // Solo agregar si hay al menos un parcial
            if (parcial1 > 0 || parcial2 > 0 || parcial3 > 0) {
                despachos.push({
                    tipo,
                    id,
                    parcial_1: parcial1,
                    parcial_2: parcial2,
                    parcial_3: parcial3,
                });
            }
        });

        if (despachos.length === 0) {
            alert('Debe ingresar al menos un parcial antes de guardar');
            return;
        }

        try {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '⏳ Guardando...';

            const response = await fetch('{{ route("despacho.guardar", $pedido->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({
                    fecha_hora: document.getElementById('fecha_hora').value,
                    cliente_empresa: document.getElementById('cliente_empresa').value,
                    despachos,
                }),
            });

            const data = await response.json();

            if (data.success) {
                alert('✓ Despacho guardado exitosamente');
                // Recargar página o redirigir
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(' Error: ' + data.message);
                if (data.errors) {
                    console.error('Errores:', data.errors);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert(' Error al guardar: ' + error.message);
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '💾 Guardar Despacho';
        }
    }

    // Ejecutar cálculo inicial
    parcialInputs.forEach(input => {
        calcularPendientes({ target: input });
    });
});
</script>

<style>
    input[type="number"] {
        font-variant-numeric: tabular-nums;
    }
</style>
@endsection
