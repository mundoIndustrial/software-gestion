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
                    <button type="button"
                            onclick="abrirModalFactura({{ $pedido->id }})"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                        Ver Pedido
                    </button>
                    <button type="button"
                            onclick="imprimirTablaVacia()"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded transition-colors">
                        Imprimir
                    </button>
                </div>
            </div>
            
            <!-- Info compacta del pedido -->
            <div class="flex gap-6 text-sm">
                <div>
                    <span class="text-slate-500">Cliente:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->cliente ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Formulario de despacho -->
        <form id="formDespacho" class="bg-white overflow-hidden">
            @csrf

            <!-- Inputs de despacho -->
            <div class="px-6 py-6 border-b border-slate-200 bg-slate-50">
                <div class="grid grid-cols-1 gap-6">
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
                </div>
            </div>

            <!-- Tabla de despacho -->
            <div class="overflow-x-auto lg:overflow-visible">
                <table class="w-full text-sm min-w-[800px] lg:min-w-full border-collapse">
                    <thead class="bg-slate-50 border-b-2 border-slate-400 sticky top-0 z-50">
                        <tr>
                            <th class="px-2 lg:px-4 py-3 text-left font-medium text-slate-700 text-xs lg:text-sm border-r border-slate-400">Descripción</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Género</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Talla</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Cantidad</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Pendiente</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-20 text-xs lg:text-sm border-r border-slate-400">Parcial 1</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Pendiente</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-20 text-xs lg:text-sm border-r border-slate-400">Parcial 2</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Pendiente</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-20 text-xs lg:text-sm border-r border-slate-400">Parcial 3</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm">Pendiente</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDespacho">
                        <!-- PRENDAS -->
                        @if($prendas->count() > 0)
                            <tr class="bg-slate-100 border-b-2 border-slate-400">
                                <td colspan="11" class="px-4 py-2 font-semibold text-slate-900">
                                    Prendas
                                </td>
                            </tr>
                            @php
                                // Agrupar prendas por ID para merge de celdas
                                $prendasAgrupadas = $prendas->groupBy('id');
                            @endphp
                            @foreach($prendasAgrupadas as $prendaId => $filasGroup)
                                @php
                                    $primeraFila = $filasGroup->first();
                                    $rowSpan = $filasGroup->count();
                                @endphp
                                @foreach($filasGroup as $indexFila => $fila)
                                    <tr class="border-b border-slate-400 hover:bg-slate-50" 
                                        data-tipo="prenda"
                                        data-id="{{ $fila->id }}"
                                        data-talla-id="{{ $fila->tallaId }}"
                                        data-genero="{{ $fila->genero }}"
                                        data-cantidad="{{ $fila->cantidadTotal }}">
                                        
                                        {{-- CELDA DE DESCRIPCIÓN: Solo en la primera fila del grupo --}}
                                        @if($indexFila === 0)
                                            <td class="px-2 lg:px-4 py-3 text-slate-900 text-xs" rowspan="{{ $rowSpan }}">
                                                <div class="font-semibold text-slate-900 mb-1">
                                                    {{ $primeraFila->objetoPrenda['nombre'] ?? $primeraFila->descripcion }}
                                                    @if(isset($primeraFila->objetoPrenda['de_bodega']) && $primeraFila->objetoPrenda['de_bodega'])
                                                        <span class="text-orange-600 font-bold"> - SE SACA DE BODEGA</span>
                                                    @endif
                                                </div>
                                                
                                                <!-- Tela y Color -->
                                                @if($primeraFila->objetoPrenda && (isset($primeraFila->objetoPrenda['tela']) || isset($primeraFila->objetoPrenda['color'])))
                                                    @php
                                                        $tela = $primeraFila->objetoPrenda['tela'] ?? null;
                                                        $color = $primeraFila->objetoPrenda['color'] ?? null;
                                                    @endphp
                                                    <div class="text-slate-900 mb-1 text-xs">
                                                        @if($tela && $color)
                                                            <div>• Tela: {{ $tela }} - Color: {{ $color }}</div>
                                                        @elseif($tela)
                                                            <div>• Tela: {{ $tela }}</div>
                                                        @elseif($color)
                                                            <div>• Color: {{ $color }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                                @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['variantes']) && is_array($primeraFila->objetoPrenda['variantes']) && count($primeraFila->objetoPrenda['variantes']) > 0)
                                                    @php
                                                        // Obtener la primera variante para mostrar las características comunes
                                                        $primeraVariante = $primeraFila->objetoPrenda['variantes'][0];
                                                        $manga = $primeraVariante->manga ?? $primeraVariante['manga'] ?? null;
                                                        $manga_obs = $primeraVariante->manga_obs ?? $primeraVariante['manga_obs'] ?? '';
                                                        $broche = $primeraVariante->broche ?? $primeraVariante['broche'] ?? null;
                                                        $broche_obs = $primeraVariante->broche_obs ?? $primeraVariante['broche_obs'] ?? '';
                                                        $bolsillos = $primeraVariante->bolsillos ?? $primeraVariante['bolsillos'] ?? false;
                                                        $bolsillos_obs = $primeraVariante->bolsillos_obs ?? $primeraVariante['bolsillos_obs'] ?? '';
                                                    @endphp
                                                    <div class="text-slate-900 mb-1 text-xs space-y-0.5">
                                                        @if($manga)
                                                            <div>• Manga:{{ $manga }}{{ $manga_obs && trim($manga_obs) !== '' ? " ($manga_obs)" : '' }}</div>
                                                        @endif
                                                        @if($broche)
                                                            <div>• {{ $broche }}{{ $broche_obs && trim($broche_obs) !== '' ? " ($broche_obs)" : '' }}</div>
                                                        @endif
                                                        @if($bolsillos)
                                                            <div>• Bolsillos{{ $bolsillos_obs && trim($bolsillos_obs) !== '' ? " ($bolsillos_obs)" : '' }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['procesos']) && is_array($primeraFila->objetoPrenda['procesos']) && count($primeraFila->objetoPrenda['procesos']) > 0)
                                                    <div class="text-slate-900 mt-1 text-xs">
                                                        <div class="ml-2 mt-0.5">
                                                            @foreach($primeraFila->objetoPrenda['procesos'] as $proc)
                                                                @php
                                                                    $ubicaciones = $proc->ubicaciones ?? $proc['ubicaciones'] ?? [];
                                                                    // Si es string, intenta decodificar como JSON
                                                                    if (is_string($ubicaciones)) {
                                                                        $decoded = json_decode($ubicaciones, true);
                                                                        $ubicaciones = is_array($decoded) ? $decoded : [$ubicaciones];
                                                                    }
                                                                    $ubicacionesStr = is_array($ubicaciones) ? implode(', ', $ubicaciones) : $ubicaciones;
                                                                @endphp
                                                                <div>• {{ $proc->nombre ?? $proc->tipo_proceso ?? $proc['tipo_proceso'] ?? 'Proceso' }}{{ $ubicacionesStr && trim($ubicacionesStr) !== '' ? " ($ubicacionesStr)" : '' }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-slate-400 text-xs mt-1">— Sin procesos</div>
                                                @endif
                                            </td>
                                            
                                            {{-- CELDA DE GÉNERO: Solo en la primera fila del grupo --}}
                                            <td class="px-2 lg:px-4 py-3 text-center text-slate-600 text-xs" rowspan="{{ $rowSpan }}">
                                                {{ $primeraFila->genero ?? '—' }}
                                            </td>
                                        @endif
                                        
                                        <td class="px-2 lg:px-4 py-3 text-center text-slate-600">
                                            {{ $fila->talla }}
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3 text-center font-medium text-slate-900">
                                            {{ $fila->cantidadTotal }}
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-inicial"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-1"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-1"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-2"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-2"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-3"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                        
                                        <td class="px-2 lg:px-4 py-3">
                                            <input type="number" 
                                                   class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-3"
                                                   value="0"
                                                   placeholder="0">
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif

                        <!-- EPP -->
                        @if($epps->count() > 0)
                            <tr class="bg-slate-100 border-b-2 border-slate-400">
                                <td colspan="11" class="px-4 py-2 font-semibold text-slate-900">
                                    EPP
                                </td>
                            </tr>
                            @foreach($epps as $index => $fila)
                                <tr class="border-b border-slate-400 hover:bg-slate-50"
                                    data-tipo="epp"
                                    data-id="{{ $fila->id }}"
                                    data-cantidad="{{ $fila->cantidadTotal }}">
                                    
                                    <td class="px-2 lg:px-4 py-3 text-slate-900 text-xs">
                                        <div class="font-semibold text-slate-900"> {{ $fila->objetoEpp['nombre'] ?? $fila->objetoEpp['nombre_completo'] ?? $fila->descripcion }}</div>
                                        @if($fila->objetoEpp && isset($fila->objetoEpp['observaciones']) && $fila->objetoEpp['observaciones'] && $fila->objetoEpp['observaciones'] !== '—' && $fila->objetoEpp['observaciones'] !== '-')
                                            <div class="text-slate-600 mt-1 text-xs">{{ $fila->objetoEpp['observaciones'] }}</div>
                                        @endif
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center text-slate-600 text-xs">
                                        —
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center text-slate-600">
                                        —
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center font-medium text-slate-900">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-inicial"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-1"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-1"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-2"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-2"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 parcial-input parcial-3"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3">
                                        <input type="number" 
                                               class="w-full px-2 py-1 border border-slate-300 rounded text-center text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 pendiente-3"
                                               value="0"
                                               placeholder="0">
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        @if($prendas->count() === 0 && $epps->count() === 0)
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center text-slate-500">
                                    No hay ítems en este pedido
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Botones de acción -->
            <div class="px-6 py-4 bg-slate-50 border-t-2 border-slate-400 flex justify-between items-center">
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

<!-- Modal de Factura -->
<div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white">📋 Pedido</h2>
            <button onclick="cerrarModalFactura()" 
                    class="text-white hover:text-slate-200 text-2xl leading-none">
                ✕
            </button>
        </div>
        
        <!-- Body -->
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500">⏳ Cargando factura...</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3 sticky bottom-0">
            <button onclick="cerrarModalFactura()" 
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div id="modalExito" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="bg-green-50 px-6 py-4 border-b border-green-200">
            <h2 class="text-lg font-semibold text-green-900">✓ Despacho Guardado</h2>
        </div>
        
        <!-- Body -->
        <div class="px-6 py-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-slate-700" id="modalMensaje">Despacho guardado correctamente</p>
                    <p class="text-sm text-slate-500 mt-2" id="modalDetalles"></p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
            <button onclick="cerrarModalExito()" 
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- JavaScript para despacho -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    const formDespacho = document.getElementById('formDespacho');
    const btnGuardar = document.getElementById('btnGuardar');
    const modalExito = document.getElementById('modalExito');

    // Cargar despachos guardados al cargar la página
    cargarDespachos();

    // Event listener para guardar
    formDespacho.addEventListener('submit', async function(e) {
        e.preventDefault();
        await guardarDespacho();
    });

    /**
     * Cargar despachos guardados desde la base de datos
     */
    async function cargarDespachos() {
        try {
            const response = await fetch('{{ route("despacho.obtener", $pedido->id) }}', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
            });

            const data = await response.json();

            if (data.despachos && data.despachos.length > 0) {
                console.log('📥 Despachos cargados desde DB:', data.despachos);
                
                // Poblar los campos del formulario con los despachos guardados
                data.despachos.forEach(despacho => {
                    let fila;
                    
                    // Buscar fila: con talla_id si existe, sin talla_id si es null
                    if (despacho.talla_id) {
                        // Para prendas, buscar por talla_id (que es el item_id guardado)
                        fila = document.querySelector(
                            `#tablaDespacho tr[data-tipo="${despacho.tipo}"][data-talla-id="${despacho.talla_id}"]`
                        );
                        console.log(`🔍 Buscando: tipo=${despacho.tipo}, talla_id=${despacho.talla_id}`, fila ? '✓ Encontrada' : '✗ NO encontrada');
                    } else {
                        // Para items sin talla (EPPs), buscar solo por tipo e id
                        const filas = document.querySelectorAll(
                            `#tablaDespacho tr[data-tipo="${despacho.tipo}"][data-id="${despacho.id}"]`
                        );
                        fila = filas[0];
                        console.log(`🔍 Buscando EPP: tipo=${despacho.tipo}, id=${despacho.id}`, fila ? '✓ Encontrada' : '✗ NO encontrada');
                    }

                    if (fila) {
                        // Rellenar los valores guardados
                        if (despacho.pendiente_inicial) {
                            fila.querySelector('.pendiente-inicial').value = despacho.pendiente_inicial;
                        }
                        if (despacho.parcial_1) {
                            fila.querySelector('.parcial-1').value = despacho.parcial_1;
                        }
                        if (despacho.pendiente_1) {
                            fila.querySelector('.pendiente-1').value = despacho.pendiente_1;
                        }
                        if (despacho.parcial_2) {
                            fila.querySelector('.parcial-2').value = despacho.parcial_2;
                        }
                        if (despacho.pendiente_2) {
                            fila.querySelector('.pendiente-2').value = despacho.pendiente_2;
                        }
                        if (despacho.parcial_3) {
                            fila.querySelector('.parcial-3').value = despacho.parcial_3;
                        }
                        if (despacho.pendiente_3) {
                            fila.querySelector('.pendiente-3').value = despacho.pendiente_3;
                        }
                        console.log(' Fila poblada:', despacho);
                    }
                });
                
                console.log('📋 Todas las filas de la tabla:');
                document.querySelectorAll('#tablaDespacho tr[data-tipo]').forEach(tr => {
                    console.log('  -', tr.dataset);
                });
            }
        } catch (error) {
            console.error('Error al cargar despachos:', error);
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
            const tallaId = parseInt(fila.dataset.tallaId) || null;
            
            const pendienteInicial = parseInt(fila.querySelector('.pendiente-inicial').value) || 0;
            const parcial1 = parseInt(fila.querySelector('.parcial-1').value) || 0;
            const pendiente1 = parseInt(fila.querySelector('.pendiente-1').value) || 0;
            const parcial2 = parseInt(fila.querySelector('.parcial-2').value) || 0;
            const pendiente2 = parseInt(fila.querySelector('.pendiente-2').value) || 0;
            const parcial3 = parseInt(fila.querySelector('.parcial-3').value) || 0;
            const pendiente3 = parseInt(fila.querySelector('.pendiente-3').value) || 0;

            console.log(`📤 Enviando: tipo=${tipo}, id=${id}, tallaId=${tallaId}, dataset=`, fila.dataset);

            // Agregar siempre el registro (el usuario decide qué ingresar)
            despachos.push({
                tipo,
                id,
                talla_id: tallaId,
                genero: tipo === 'prenda' ? (fila.dataset.genero || null) : null,  //  Agregar género para prendas
                pendiente_inicial: pendienteInicial,
                parcial_1: parcial1,
                pendiente_1: pendiente1,
                parcial_2: parcial2,
                pendiente_2: pendiente2,
                parcial_3: parcial3,
                pendiente_3: pendiente3,
            });
        });

        if (despachos.length === 0) {
            alert('No hay ítems para guardar');
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
                    despachos,
                }),
            });

            // Verificar si la respuesta es JSON válido
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.error('❌ Respuesta no es JSON:', text);
                throw new Error('La respuesta del servidor no es JSON válido');
            }

            if (data.success) {
                console.log(' Despacho guardado exitosamente');
                
                // Mostrar modal de éxito
                mostrarModalExito(data);
                
                // Limpiar los inputs después de guardar exitosamente
                const filasLimpiar = document.querySelectorAll('#tablaDespacho tr[data-tipo]');
                filasLimpiar.forEach(fila => {
                    fila.querySelectorAll('input[type="number"]').forEach(input => {
                        input.value = '';
                    });
                });
                
                // Recargar despachos guardados en tiempo real (sin reload completo)
                setTimeout(() => {
                    cargarDespachos();
                }, 500);
                
            } else {
                alert('❌ Error: ' + data.message);
                if (data.errors) {
                    console.error('Errores:', data.errors);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al guardar: ' + error.message);
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '💾 Guardar Despacho';
        }
    }
});

// ============ FUNCIONES GLOBALES PARA MODAL ============

/**
 * Abrir modal con la factura del pedido
 */
async function abrirModalFactura(pedidoId) {
    const modal = document.getElementById('modalFactura');
    const contenido = document.getElementById('facturaContenido');
    
    modal.classList.remove('hidden');
    contenido.innerHTML = '<div class="flex justify-center items-center py-12"><span class="text-slate-500">⏳ Cargando factura...</span></div>';
    
    try {
        const response = await fetch(`/despacho/${pedidoId}/factura-datos`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            },
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data) {
            // Generar HTML de la factura
            const htmlFactura = generarHTMLFactura(data);
            contenido.innerHTML = htmlFactura;
        } else {
            contenido.innerHTML = '<div class="text-center text-red-600 py-6">❌ Error al cargar la factura</div>';
        }
    } catch (error) {
        console.error('Error cargando factura:', error);
        contenido.innerHTML = '<div class="text-center text-red-600 py-6">❌ Error: ' + error.message + '</div>';
    }
}

/**
 * Cerrar modal de factura
 */
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    modal.classList.add('hidden');
}

/**
 * Generar HTML de la factura - IGUAL QUE EN ASESORES
 */
function generarHTMLFactura(datos) {
    if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {
        return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;">❌ Error: No se pudieron cargar las prendas del pedido.</div>';
    }

    // Generar las tarjetas de prendas
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        // Variantes tabla
        let variantesHTML = '';
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            // Verificar qué columnas tienen datos
            const tieneManga = prenda.variantes.some(v => v.manga);
            const tieneBroche = prenda.variantes.some(v => v.broche);
            const tieneBolsillos = prenda.variantes.some(v => v.bolsillos);
            
            variantesHTML = `
                <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                            <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                            ${tieneManga ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Manga</th>` : ''}
                            ${tieneBroche ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Botón/Broche</th>` : ''}
                            ${tieneBolsillos ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Bolsillos</th>` : ''}
                        </tr>
                    </thead>
                    <tbody>
                        ${prenda.variantes.map((var_item, varIdx) => `
                            <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${var_item.talla}</td>
                                <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${var_item.cantidad}</td>
                                ${tieneManga ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.manga ? `<strong>${var_item.manga}</strong>` : '—'}
                                        ${var_item.manga_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.manga_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                                ${tieneBroche ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.broche ? `<strong>${var_item.broche}</strong>` : '—'}
                                        ${var_item.broche_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.broche_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                                ${tieneBolsillos ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.bolsillos ? `<strong>Sí</strong>` : '—'}
                                        ${var_item.bolsillos_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.bolsillos_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        // Tela y color
        let telaHTML = '';
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            telaHTML = `
                <div style="margin-bottom: 12px;">
                    ${prenda.telas_array.map(tela => `
                        <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                            <span style="font-size: 11px; color: #374151;">
                                <strong>Tela:</strong> ${tela.tela_nombre || '—'} 
                                <strong style="margin-left: 12px;">Color:</strong> ${tela.color_nombre || '—'}
                                ${tela.referencia ? `<strong style="margin-left: 12px;">Ref:</strong> ${tela.referencia}` : ''}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
        } else if (prenda.tela || prenda.color) {
            telaHTML = `
                <div style="margin-bottom: 12px; font-size: 11px; color: #374151;">
                    <strong>Tela:</strong> ${prenda.tela || '—'} 
                    ${prenda.color ? `<strong style="margin-left: 12px;">Color:</strong> ${prenda.color}` : ''}
                </div>
            `;
        }

        // Procesos
        let procesosHTML = '';
        if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
            procesosHTML = `
                <div style="margin-bottom: 0;">
                    ${prenda.procesos.map(proc => `
                        <div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 11px;">${proc.nombre || proc.tipo}</div>
                            ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                    📍 ${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(' • ') : proc.ubicaciones}
                                </div>
                            ` : ''}
                            ${proc.tallas && (proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 || proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 || proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 || proc.tallas.sobremedida && Object.keys(proc.tallas.sobremedida).length > 0) ? `
                                <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                    ${[
                                        ...(proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 ? [`Dama: ${Object.entries(proc.tallas.dama).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 ? [`Caballero: ${Object.entries(proc.tallas.caballero).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 ? [`Unisex: ${Object.entries(proc.tallas.unisex).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.sobremedida && Object.keys(proc.tallas.sobremedida).length > 0 ? [`Sobremedida: ${Object.entries(proc.tallas.sobremedida).map(([genero, cantidad]) => `${genero}(${cantidad})`).join(', ')}`] : [])
                                    ].join(' • ')}
                                </div>
                            ` : ''}
                            ${proc.observaciones ? `
                                <div style="font-size: 10px; color: #6b7280;">
                                    ${proc.observaciones}
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        return `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">
                <!-- Header simple -->
                <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; color: #374151;">PRENDA ${idx + 1}: ${prenda.nombre}${prenda.de_bodega ? ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>' : ''}</div>
                    ${prenda.descripcion ? `<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">${prenda.descripcion}</div>` : ''}
                </div>
                
                <!-- Telas (movido aquí) -->
                ${telaHTML}
                
                <!-- Imagen pequeña -->
                ${(prenda.imagenes && prenda.imagenes.length > 0) ? `
                    <div style="float: right; margin-left: 12px; margin-bottom: 8px;">
                        <img src="${prenda.imagenes[0].ruta || prenda.imagenes[0].url || prenda.imagenes[0]}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                    </div>
                ` : ''}
                
                <!-- Contenido compacto -->
                <div style="${(prenda.imagenes && prenda.imagenes.length > 0) ? 'margin-right: 100px;' : ''}">
                    <!-- Variantes -->
                    ${variantesHTML ? variantesHTML.replace(/margin: 12px 0/g, 'margin-bottom: 12px;').replace(/border: 1px solid #e0e7ff/g, 'border: 1px solid #e5e7eb;').replace(/background: #f0f9ff/g, 'background: #f9fafb;').replace(/color: #1e40af/g, 'color: #374151;') : ''}
                    
                    <!-- Procesos -->
                    ${procesosHTML ? procesosHTML.replace(/margin: 12px 0/g, 'margin-bottom: 0;').replace(/border: 1px solid #e0e7ff/g, 'border: 1px solid #e5e7eb;') : ''}
                </div>
                
                <div style="clear: both;"></div>
            </div>
        `;
    }).join('');

    // EPPs
    const eppsHTML = (datos.epps && datos.epps.length > 0) ? `
        <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
            <div style="font-size: 12px !important; font-weight: 700; color: #1e40af; background: #f0f9ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;"> EPP (${datos.epps.length})</div>
            <div style="padding: 12px; space-y: 8px;">
                ${datos.epps.map(epp => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; margin-bottom: 8px; border-left: 3px solid #3b82f6; border-radius: 2px; background: #f8fafc;">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e40af; margin-bottom: 4px;">${epp.nombre_completo || epp.nombre}</div>
                            ${epp.observaciones && epp.observaciones !== '—' && epp.observaciones !== '-' ? `<div style="font-size: 11px; color: #475569;">${epp.observaciones}</div>` : ''}
                        </div>
                        <div style="font-weight: 600; color: #1e40af; font-size: 14px; margin-left: 12px;">
                            ${epp.cantidad}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    ` : '';

    // Totales
    const totalHTML = `
        <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px; border: 2px solid #d1d5db; text-align: right;">
            <div style="font-size: 12px; margin-bottom: 8px;">
                <strong>Total Ítems:</strong> ${datos.total_items || 0}
            </div>
            ${datos.valor_total ? `
                <div style="font-size: 12px; margin-bottom: 8px;">
                    <strong>Subtotal:</strong> $${parseFloat(datos.valor_total).toLocaleString('es-CO')}
                </div>
            ` : ''}
            ${datos.total_general ? `
                <div style="font-size: 14px; font-weight: 700; color: #1e40af; padding-top: 8px; border-top: 2px solid #d1d5db;">
                    <strong>Total:</strong> $${parseFloat(datos.total_general).toLocaleString('es-CO')}
                </div>
            ` : ''}
        </div>
    `;

    return `
        <div>
            <!-- Header factura -->
            <div style="background: #1e3a8a; color: white; padding: 16px; border-radius: 6px; margin-bottom: 12px; text-align: center;">
                <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">FACTURA DE PEDIDO</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 12px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Número</div>
                        <div style="font-weight: 600;">${datos.numero_pedido}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Cliente</div>
                        <div style="font-weight: 600;">${datos.cliente}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Asesora</div>
                        <div style="font-weight: 600;">${datos.asesora}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 8px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Forma de Pago</div>
                        <div style="font-weight: 600;">${datos.forma_de_pago}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Fecha</div>
                        <div style="font-weight: 600;">${datos.fecha}</div>
                    </div>
                </div>
            </div>

            ${datos.observaciones ? `
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 11px;">
                    <strong style="color: #92400e;">📋 Observaciones:</strong>
                    <div style="margin-top: 4px; white-space: pre-wrap; color: #666;">${datos.observaciones}</div>
                </div>
            ` : ''}

            <!-- Prendas -->
            ${prendasHTML}

            <!-- EPPs -->
            ${eppsHTML}

            <!-- Totales -->
            ${totalHTML}
        </div>
    `;
}

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(data) {
    const modal = document.getElementById('modalExito');
    const mensaje = document.getElementById('modalMensaje');
    const detalles = document.getElementById('modalDetalles');
    
    mensaje.textContent = '✓ ' + (data.message || 'Despacho guardado correctamente');
    detalles.textContent = `${data.despachos_procesados} ítem(s) procesado(s) y guardado(s)`;
    
    modal.classList.remove('hidden');
    
    // Cerrar automáticamente después de 5 segundos
    setTimeout(() => {
        cerrarModalExito();
    }, 5000);
}

/**
 * Cerrar modal de éxito
 */
function cerrarModalExito() {
    const modal = document.getElementById('modalExito');
    modal.classList.add('hidden');
}

/**
 * Imprimir tabla de despacho vacía
 */
function imprimirTablaVacia() {
    const tabla = document.querySelector('table');
    
    if (!tabla) {
        alert('No se encontró la tabla');
        return;
    }

    // Clonar la tabla
    const tablaClonada = tabla.cloneNode(true);

    // Hacer transparente el contenido de los inputs
    const inputs = tablaClonada.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        input.style.color = 'transparent';
        input.style.background = 'white';
    });

    // Crear ventana de impresión
    const ventana = window.open('', '', 'width=1200,height=800');
    
    const htmlContent = '<!DOCTYPE html>' +
        '<html lang="es">' +
        '<head>' +
        '<meta charset="UTF-8">' +
        '<meta name="viewport" content="width=device-width, initial-scale=1.0">' +
        '<title>Despacho - Imprimir</title>' +
        '<style>' +
        '@page { margin: 8mm; size: letter portrait; }' +
        '* { margin: 0; padding: 0; box-sizing: border-box; }' +
        'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 12px; background: white; overflow-x: auto; }' +
        'table { width: 100%; border-collapse: collapse; margin-top: 5px; }' +
        'thead { background: #f1f5f9; border-bottom: 2px solid #cbd5e1; }' +
        'th { padding: 2px 1px; text-align: center; font-weight: 600; font-size: 12px; border: 1px solid #e2e8f0; line-height: 1; }' +
        'th:first-child { font-size: 12px; padding: 3px 2px; }' +
        'td { padding: 3px 2px; border: 1px solid #e2e8f0; font-size: 9px; }' +
        'td:first-child { padding: 8px 3px; font-size: 12px; font-weight: 600; }' +
        'tbody tr:nth-child(even) { background: white; }' +
        'tbody tr.bg-slate-50 { background: #f1f5f9; }' +
        'tbody tr.bg-slate-50 td { background: #f1f5f9; font-weight: 600; padding: 6px 2px; font-size: 10px; }' +
        'input[type="number"] { width: 100%; border: 1px solid #cbd5e1; padding: 1px 0px; background: white; font-size: 8px; text-align: center; height: 16px; }' +
        '@media print { body { margin: 0; padding: 0; } table { page-break-inside: avoid; } tr { page-break-inside: avoid; } }' +
        '</style>' +
        '</head>' +
        '<body>' +
        tablaClonada.outerHTML +
        '<script>' +
        'window.print();' +
        'window.onafterprint = function() { window.close(); };' +
        '<\/script>' +
        '</body>' +
        '</html>';
    
    ventana.document.write(htmlContent);
    ventana.document.close();
}

</script>

<style>
    input[type="number"] {
        font-variant-numeric: tabular-nums;
    }
</style>

@endsection
