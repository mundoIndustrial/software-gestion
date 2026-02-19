@extends('layouts.despacho-standalone')

@section('title', "Despacho - Pedido {$pedido->numero_pedido}")

@push('scripts')
<!-- Modal de Imágenes -->
<script src="{{ asset('js/ImageModal.js') }}"></script>

<script>
// Conexión WebSocket para actualizaciones en tiempo real
let socket = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function connectWebSocket() {
    try {
        // Usar WebSocket de Reverb con clave desde meta tags
        socket = new window.Echo({
            broadcaster: 'reverb',
            key: document.querySelector('meta[name="reverb-key"]')?.getAttribute('content') || 'mundo-industrial-key',
            wsHost: document.querySelector('meta[name="reverb-host"]')?.getAttribute('content') || window.location.hostname,
            wsPort: parseInt(document.querySelector('meta[name="reverb-port"]')?.getAttribute('content')) || 8080,
            wssPort: parseInt(document.querySelector('meta[name="reverb-port"]')?.getAttribute('content')) || 8080,
            forceTLS: document.querySelector('meta[name="reverb-scheme"]')?.getAttribute('content') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        // Unirse al canal público de despacho
        socket.channel('despacho.pedidos')
            .listen('.pedido.actualizado', (event) => {
                console.log('🔄 Pedido actualizado en tiempo real (despacho):', event);
                
                // Si el pedido cambió a "Entregado" y es el pedido actual, mostrar notificación
                if (event.nuevo_estado === 'Entregado' && event.pedido_id == window.pedidoId) {
                    console.log('📦 Pedido actual marcado como entregado');
                    
                    // Mostrar notificación local
                    mostrarNotificacionPedidoEntregadoLocal(event.numero_pedido);
                }
            })
            .error((error) => {
                console.error('❌ Error en WebSocket (despacho):', error);
            });

        console.log('✅ WebSocket conectado para pedido:', window.pedidoId);
    } catch (error) {
        console.error('❌ Error al conectar WebSocket:', error);
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            setTimeout(connectWebSocket, 2000 * reconnectAttempts);
        }
    }
}

// Función para mostrar notificación local
function mostrarNotificacionPedidoEntregadoLocal(numeroPedido) {
    // Crear notificación flotante
    const notificacion = document.createElement('div');
    notificacion.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
    notificacion.innerHTML = `
        <div class="flex items-center gap-2">
            <span class="material-symbols-rounded">check_circle</span>
            <span>Pedido #${numeroPedido} marcado como entregado</span>
        </div>
    `;
    
    document.body.appendChild(notificacion);
    
    // Mostrar notificación inmediatamente
    requestAnimationFrame(() => {
        notificacion.style.transform = 'translateX(0)';
    });
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notificacion)) {
                document.body.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Pasar datos del pedido a JavaScript
    window.pedidoId = {{ $pedido->id }};
    window.numeroPedido = '{{ $pedido->numero_pedido }}';
    
    // Usar el sistema waitForEcho para asegurar que Echo esté disponible
    window.waitForEcho(function() {
        console.log('🚀 Echo está listo, conectando WebSocket...');
        connectWebSocket();
    });
});
</script>
@endpush

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
                <div>
                    <span class="text-slate-500">Asesor:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->asesor->name ?? 'Sin asignar' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Forma de Pago:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->forma_de_pago ?? 'No especificada' }}</span>
                </div>
            </div>
        </div>
        
        <!-- OBSERVACIONES DESTACADAS -->
        @if($pedido->observaciones)
            <div class="mx-6 mb-3">
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <svg style="width: 18px; height: 18px; color: #000;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <strong style="color: #000;"> Observaciones:</strong>
                    </div>
                    <div style="white-space: pre-wrap; color: #000;">{{ $pedido->observaciones }}</div>
                </div>
            </div>
        @endif

        <!-- Formulario de despacho -->
        <form id="formDespacho" class="bg-white overflow-hidden">
            @csrf

            <!-- Tabla de despacho -->
            <div class="overflow-x-auto lg:overflow-visible">
                <table class="w-full text-sm min-w-[800px] lg:min-w-full border-collapse">
                    <thead class="bg-slate-50 border-b-2 border-slate-400 sticky top-0 z-50">
                        <tr>
                            <th class="px-2 lg:px-4 py-3 text-left font-medium text-slate-700 text-xs lg:text-sm border-r border-slate-400">Descripción</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Género</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Talla</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Cantidad</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-32 text-xs lg:text-sm">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDespacho">
                        <!-- PRENDAS -->
                        @if($prendas->count() > 0)
                            <tr class="bg-slate-100 border-b-2 border-slate-400">
                                <td colspan="5" class="px-4 py-2 font-semibold text-slate-900">
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
                                        
                                        <td class="px-2 lg:px-4 py-3 text-center">
                                            <button type="button" 
                                                    class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded transition-colors"
                                                    onclick="marcarEntregado(this)">
                                                Entregar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif

                        <!-- EPP -->
                        @if($epps->count() > 0)
                            <tr class="bg-slate-100 border-b-2 border-slate-400">
                                <td colspan="5" class="px-4 py-2 font-semibold text-slate-900">
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
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center">
                                        <button type="button" 
                                                class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded transition-colors"
                                                onclick="marcarEntregado(this)">
                                            Entregado
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        @if($prendas->count() === 0 && $epps->count() === 0)
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    No hay ítems en este pedido
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Info de ítems -->
            <div class="px-6 py-4 bg-slate-50 border-t-2 border-slate-400">
                <div class="text-sm text-slate-600">
                    <span class="font-medium">{{ $prendas->count() + $epps->count() }}</span> ítems en total
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
            <h2 class="text-lg font-semibold text-white"> Pedido</h2>
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

<!-- Modal de Confirmación para Deshacer Entregado -->
<div id="modalDeshacerEntregado" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="bg-orange-500 px-6 py-4 border-b border-orange-600">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <span class="mr-2">↶</span>
                Deshacer Entregado
            </h2>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <span class="text-orange-500 text-xl">⚠️</span>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">¿Deshacer marcado como entregado?</h3>
                    <p class="text-slate-600 text-sm">El ítem volverá a estado "Pendiente" y el pedido cambiará a "Pendiente" si todos los ítems están pendientes.</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
            <button onclick="cerrarModalDeshacerEntregado()" 
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                Cancelar
            </button>
            <button id="btnConfirmarDeshacer" 
                    class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded transition-colors">
                Sí, deshacer entregado
            </button>
        </div>
    </div>
</div>

<!-- JavaScript para despacho -->
<script>
console.log('✅ Script de despacho cargado correctamente');

/**
 * Marcar ítem como entregado
 */
async function marcarEntregado(button) {
    const fila = button.closest('tr');
    const tipo = fila.dataset.tipo;
    const itemId = parseInt(fila.dataset.id);
    const tallaId = fila.dataset.tallaId ? parseInt(fila.dataset.tallaId) : null;
    const genero = fila.dataset.genero || null;
    
    // Mostrar la URL para debugging
    const url = '{{ route("despacho.marcar-entregado", $pedido->id) }}';
    console.log('🔍 URL de marcarEntregado:', url);
    
    // Deshabilitar el botón mientras se procesa
    button.disabled = true;
    button.textContent = '⏳ Guardando...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                tipo_item: tipo,
                item_id: itemId,
                talla_id: tallaId,
                genero: genero,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Cambiar el botón a estado "Entregado" con opción de deshacer
            button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
            button.classList.remove('bg-green-500', 'hover:bg-green-600');
            button.classList.add('bg-orange-500', 'hover:bg-orange-600');
            button.onclick = function() { deshacerEntregado(this); };
            
            // Importante: habilitar el botón para permitir deshacer
            button.disabled = false;
            
            // Agregar efecto visual a la fila: color azul pastel
            fila.style.backgroundColor = '#DBEAFE'; // bg-blue-100 (azul pastel)
            
            console.log('✅ Ítem marcado como entregado:', data);
        } else {
            // Error: restaurar botón
            button.textContent = 'Entregar';
            button.disabled = false;
            alert('Error al marcar como entregado: ' + data.message);
        }
    } catch (error) {
        console.error('Error al marcar como entregado:', error);
        button.textContent = 'Entregar';
        button.disabled = false;
        alert('Error al marcar como entregado. Por favor, intenta de nuevo.');
    }
}

/**
 * Deshacer marcado como entregado
 */
async function deshacerEntregado(button) {
    const fila = button.closest('tr');
    const tipo = fila.dataset.tipo;
    const itemId = parseInt(fila.dataset.id);
    const tallaId = fila.dataset.tallaId ? parseInt(fila.dataset.tallaId) : null;
    
    // Guardar referencia al botón y datos para usarlos después
    window.deshacerEntregadoData = {
        button: button,
        fila: fila,
        tipo: tipo,
        itemId: itemId,
        tallaId: tallaId
    };
    
    // Mostrar modal de confirmación
    abrirModalDeshacerEntregado();
}

/**
 * Abrir modal de confirmación para deshacer entregado
 */
function abrirModalDeshacerEntregado() {
    const modal = document.getElementById('modalDeshacerEntregado');
    modal.classList.remove('hidden');
    
    // Configurar el botón de confirmación
    const btnConfirmar = document.getElementById('btnConfirmarDeshacer');
    btnConfirmar.onclick = confirmarDeshacerEntregado;
}

/**
 * Cerrar modal de confirmación para deshacer entregado
 */
function cerrarModalDeshacerEntregado() {
    const modal = document.getElementById('modalDeshacerEntregado');
    modal.classList.add('hidden');
    
    // Limpiar datos guardados
    window.deshacerEntregadoData = null;
}

/**
 * Confirmar y ejecutar el deshacer entregado
 */
async function confirmarDeshacerEntregado() {
    if (!window.deshacerEntregadoData) return;
    
    const { button, fila, tipo, itemId, tallaId } = window.deshacerEntregadoData;
    
    // Cerrar modal
    cerrarModalDeshacerEntregado();
    
    // Deshabilitar el botón mientras se procesa
    button.disabled = true;
    button.innerHTML = '⏳ Deshaciendo...';
    
    try {
        const response = await fetch('{{ route("despacho.deshacer-entregado", $pedido->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                tipo_item: tipo,
                item_id: itemId,
                talla_id: tallaId,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Restaurar el botón a estado inicial
            button.innerHTML = 'Entregar';
            button.classList.remove('bg-orange-500', 'hover:bg-orange-600');
            button.classList.add('bg-green-500', 'hover:bg-green-600');
            button.onclick = function() { marcarEntregado(this); };
            
            // Quitar efecto visual de la fila
            fila.style.backgroundColor = '';
            
            console.log('✅ Marcado como entregado deshecho:', data);
        } else {
            // Error: restaurar botón a estado entregado
            button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
            button.classList.remove('bg-green-500', 'hover:bg-green-600');
            button.classList.add('bg-orange-500', 'hover:bg-orange-600');
            button.onclick = function() { deshacerEntregado(this); };
            
            // Mostrar error con una alerta simple (podemos mejorarlo después)
            alert('Error al deshacer marcado como entregado: ' + data.message);
        }
    } catch (error) {
        console.error('Error al deshacer marcado como entregado:', error);
        
        // Restaurar botón a estado entregado
        button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
        button.classList.remove('bg-green-500', 'hover:bg-green-600');
        button.classList.add('bg-orange-500', 'hover:bg-orange-600');
        button.onclick = function() { deshacerEntregado(this); };
        
        alert('Error al deshacer marcado como entregado. Por favor, intenta de nuevo.');
    } finally {
        button.disabled = false;
    }
}

/**
 * Cargar estado inicial de entregas al cargar la página
 */
async function cargarEstadoEntregas() {
    try {
        const response = await fetch('{{ route("despacho.estado-entregas", $pedido->id) }}');
        const data = await response.json();
        
        if (data.success && data.entregas) {
            data.entregas.forEach(entrega => {
                // Buscar la fila correspondiente
                let selector = `tr[data-tipo="${entrega.tipo_item}"][data-id="${entrega.item_id}"]`;
                if (entrega.talla_id) {
                    selector += `[data-talla-id="${entrega.talla_id}"]`;
                }
                
                const fila = document.querySelector(selector);
                if (fila) {
                    const button = fila.querySelector('button');
                    if (button) {
                        // Marcar como entregado visualmente con opción de deshacer
                        button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
                        button.classList.remove('bg-green-500', 'hover:bg-green-600');
                        button.classList.add('bg-orange-500', 'hover:bg-orange-600');
                        button.onclick = function() { deshacerEntregado(this); };
                        fila.style.backgroundColor = '#DBEAFE'; // bg-blue-100 (azul pastel)
                    }
                }
            });
            
            console.log('✅ Estado de entregas cargado:', data.entregas.length, 'ítems entregados');
        }
    } catch (error) {
        console.error('Error al cargar estado de entregas:', error);
    }
}

// Cargar estado de entregas al cargar la página
document.addEventListener('DOMContentLoaded', cargarEstadoEntregas);

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

        let data;
        try {
            data = await response.json();
            console.log('[DESPACHO][factura-datos] respuesta:', data);
        } catch (parseError) {
            console.error('[DESPACHO][factura-datos] error parseando JSON:', parseError);
            contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error: respuesta inválida del servidor (no JSON)</div>';
            return;
        }

        if (data) {
            // DEBUG: Ver qué datos estamos recibiendo
            console.log('🔍 [DESPACHO] Datos recibidos del backend:', data);
            console.log('🔍 [DESPACHO] Estructura:', Object.keys(data));
            
            // Extraer datos como lo hace bodega
            const payload = (data && typeof data === 'object' && data.data) ? data.data : data;
            console.log('🔍 [DESPACHO] Payload final:', payload);
            
            // Generar HTML de la factura
            const htmlFactura = generarHTMLFactura(payload);
            contenido.innerHTML = htmlFactura;
        } else {
            contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error al cargar la factura</div>';
        }
    } catch (error) {
        console.error('Error cargando factura:', error);
        contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error: ' + error.message + '</div>';
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
 * Generar HTML de la factura - VERSIÓN BODEGA
 */
function generarHTMLFactura(datos) {
    // DEBUG: Ver estructura de datos
    console.log('📋 [DESPACHO-FACTURA] Estructura completa:', datos);
    console.log('📋 [DESPACHO-FACTURA] Prendas:', datos.prendas);
    if (datos.prendas && datos.prendas[0]) {
        console.log('📋 [DESPACHO-FACTURA] Primera prenda claves:', Object.keys(datos.prendas[0]));
        console.log('📋 [DESPACHO-FACTURA] Tallas:', datos.prendas[0].tallas);
        console.log('📋 [DESPACHO-FACTURA] Descripción:', datos.prendas[0].descripcion);
        console.log('📋 [DESPACHO-FACTURA] Variantes:', datos.prendas[0].variantes);
        console.log('📋 [DESPACHO-FACTURA] Variantes[0]:', datos.prendas[0].variantes?.[0]);
        console.log('📋 [DESPACHO-FACTURA] Variantes length:', datos.prendas[0].variantes?.length);
    }
    
    if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {
        return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;"> Error: No se pudieron cargar las prendas del pedido.</div>';
    }

    // Generar las tarjetas de prendas
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        // Usar TALLAS primero que es donde están los datos correctos
        let variantesHTML = '';
        if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            // Convertir objeto de géneros a array de tallas
            let todasLasTallas = [];
            Object.keys(prenda.tallas).forEach(genero => {
                if (typeof prenda.tallas[genero] === 'object') {
                    Object.entries(prenda.tallas[genero]).forEach(([talla, cantidad]) => {
                        todasLasTallas.push({ talla, cantidad });
                    });
                }
            });
            
            if (todasLasTallas.length > 0) {
                variantesHTML = `
                    <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                                <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${todasLasTallas.map((talla_item, varIdx) => `
                                <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${talla_item.talla || 'N/A'}</td>
                                    <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${talla_item.cantidad || 0}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        } else if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            // Fallback por si vienen como variantes
            variantesHTML = `
                <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                            <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${prenda.variantes.map((var_item, varIdx) => `
                            <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${var_item.talla || 'N/A'}</td>
                                <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${var_item.cantidad || 0}</td>
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
                                     ${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(' • ') : proc.ubicaciones}
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
                    <div style="font-size: 14px; font-weight: 600; color: #374151;">PRENDA ${idx + 1}: ${prenda.nombre_prenda || prenda.nombre}${prenda.de_bodega ? ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>' : ''}</div>
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
                ${datos.epps.map(epp => {
                    // Debug logging para EPPs en despacho
                    console.log('🖼️ [DESPACHO-FACTURA] EPP con imágenes:', {
                        nombre: epp.nombre_completo || epp.nombre,
                        cantidad: epp.cantidad,
                        imagenes_existe: !!epp.imagenes,
                        imagenes_es_array: Array.isArray(epp.imagenes),
                        imagenes_length: epp.imagenes ? epp.imagenes.length : 0,
                        imagenes: epp.imagenes
                    });
                    
                    // Generar HTML para imágenes si existen
                    const imagenesHTML = (epp.imagenes && Array.isArray(epp.imagenes) && epp.imagenes.length > 0) ? `
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">🖼️ Imágenes (${epp.imagenes.length})</div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 6px;">
                                ${epp.imagenes.map((imagen, index) => {
                                    let imgUrl = '';
                                    if (typeof imagen === 'string') {
                                        imgUrl = imagen;
                                    } else if (imagen.ruta_web) {
                                        imgUrl = imagen.ruta_web.startsWith('/') ? imagen.ruta_web : `/storage/${imagen.ruta_web}`;
                                    } else if (imagen.url) {
                                        imgUrl = imagen.url.startsWith('/') ? imagen.url : `/storage/${imagen.url}`;
                                    }
                                    
                                    return imgUrl ? `
                                        <div style="position: relative; border-radius: 3px; overflow: hidden; background: #f9fafb; border: 1px solid #e5e7eb; aspect-ratio: 1; cursor: pointer;" 
                                             title="Click para ver imagen completa"
                                             onclick="window.abrirModalImagen('${imgUrl}', '${(epp.nombre_completo || epp.nombre || 'Imagen EPP').replace(/'/g, "\\'")}')">
                                            <img src="${imgUrl}" 
                                                 alt="Imagen EPP" 
                                                 style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                                 onerror="this.style.display='none'; this.parentElement.innerHTML='⚠️';">
                                        </div>
                                    ` : '';
                                }).join('')}
                            </div>
                        </div>
                    ` : '';
                    
                    return `
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 8px; margin-bottom: 8px; border-left: 3px solid #3b82f6; border-radius: 2px; background: #f8fafc;">
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: #1e40af; margin-bottom: 4px;">${epp.nombre_completo || epp.nombre}</div>
                                ${epp.observaciones && epp.observaciones !== '—' && epp.observaciones !== '-' ? `<div style="font-size: 11px; color: #475569;">${epp.observaciones}</div>` : ''}
                                ${imagenesHTML}
                            </div>
                            <div style="font-weight: 600; color: #1e40af; font-size: 14px; margin-left: 12px; margin-top: 4px;">
                                ${epp.cantidad}
                            </div>
                        </div>
                    `;
                }).join('')}
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
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 13px;">
                    <strong style="color: #92400e;"> Observaciones:</strong>
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
 * Imprimir tabla de despacho con 11 columnas originales
 */
function imprimirTablaVacia() {
    // Construir tabla HTML con 11 columnas para impresión
    let tablaHTML = `
        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
            <thead style="background: #f1f5f9; border-bottom: 2px solid #000;">
                <tr>
                    <th style="padding: 8px 4px; text-align: left; font-weight: 600; font-size: 11px; border: 1px solid #000;">Descripción</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Género</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 50px;">Talla</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Cantidad</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 1</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 2</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 3</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                </tr>
            </thead>
            <tbody>
    `;

    // Obtener todas las filas de la tabla actual
    const filas = document.querySelectorAll('#tablaDespacho tr[data-tipo]');
    let ultimoTipo = '';
    let ultimoId = null;
    let grupoFilas = [];

    filas.forEach((fila, index) => {
        const tipo = fila.dataset.tipo;
        const id = fila.dataset.id;
        
        // Agregar encabezado de sección si cambia el tipo
        if (tipo !== ultimoTipo) {
            const nombreSeccion = tipo === 'prenda' ? 'Prendas' : 'EPP';
            tablaHTML += `
                <tr style="background: #f1f5f9;">
                    <td colspan="11" style="padding: 8px 4px; font-weight: 600; font-size: 11px; border: 1px solid #000;">${nombreSeccion}</td>
                </tr>
            `;
            ultimoTipo = tipo;
        }

        // Detectar si esta fila tiene la celda de descripción (primera fila de un grupo)
        const tds = fila.querySelectorAll('td');
        const tieneDescripcion = tds.length >= 5; // Si tiene 5 columnas, tiene descripción y género

        if (tieneDescripcion) {
            // Es la primera fila de un grupo, obtener descripción y género
            const cloneDesc = tds[0].cloneNode(true);
            cloneDesc.querySelectorAll('button').forEach(btn => btn.remove());
            const descripcion = cloneDesc.innerHTML;

            const cloneGenero = tds[1].cloneNode(true);
            cloneGenero.querySelectorAll('button').forEach(btn => btn.remove());
            const genero = cloneGenero.textContent.trim() || '—';

            const cloneTalla = tds[2].cloneNode(true);
            cloneTalla.querySelectorAll('button').forEach(btn => btn.remove());
            const talla = cloneTalla.textContent.trim() || '—';

            const cloneCantidad = tds[3].cloneNode(true);
            cloneCantidad.querySelectorAll('button').forEach(btn => btn.remove());
            const cantidad = cloneCantidad.textContent.trim() || '0';

            // Contar cuántas filas más tienen el mismo id (para rowspan)
            let rowspan = 1;
            for (let i = index + 1; i < filas.length; i++) {
                if (filas[i].dataset.id === id && filas[i].dataset.tipo === tipo) {
                    rowspan++;
                } else {
                    break;
                }
            }

            // Primera fila con descripción y género
            tablaHTML += `
                <tr style="border-bottom: 1px solid #000;">
                    <td style="padding: 8px 4px; font-size: 10px; border: 1px solid #000;" rowspan="${rowspan}">${descripcion}</td>
                    <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;" rowspan="${rowspan}">${genero}</td>
                    <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${talla}</td>
                    <td style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 10px; border: 1px solid #000;">${cantidad}</td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                </tr>
            `;
        } else {
            // Fila adicional sin descripción ni género (solo talla y cantidad)
            const cloneTalla = tds[0].cloneNode(true);
            cloneTalla.querySelectorAll('button').forEach(btn => btn.remove());
            const talla = cloneTalla.textContent.trim() || '—';

            const cloneCantidad = tds[1].cloneNode(true);
            cloneCantidad.querySelectorAll('button').forEach(btn => btn.remove());
            const cantidad = cloneCantidad.textContent.trim() || '0';

            tablaHTML += `
                <tr style="border-bottom: 1px solid #000;">
                    <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${talla}</td>
                    <td style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 10px; border: 1px solid #000;">${cantidad}</td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                </tr>
            `;
        }
    });

    tablaHTML += `
            </tbody>
        </table>
    `;

    // Crear ventana de impresión
    const ventana = window.open('', '', 'width=1200,height=800');
    
    const htmlContent = `<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Despacho - Imprimir</title>
            <style>
                @page { 
                    margin: 5mm; 
                    size: letter portrait; 
                }
                * { 
                    margin: 0; 
                    padding: 0; 
                    box-sizing: border-box; 
                }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                    font-size: 9px; 
                    background: white; 
                    padding: 0;
                    margin: 0;
                }
                h2 { 
                    text-align: center; 
                    margin-bottom: 5px; 
                    font-size: 14px;
                    page-break-after: avoid;
                }
                p { 
                    text-align: center; 
                    margin-bottom: 8px; 
                    font-size: 10px;
                    page-break-after: avoid;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    page-break-before: avoid;
                }
                thead {
                    page-break-after: avoid;
                }
                tr { 
                    page-break-inside: avoid; 
                }
                @media print { 
                    body { 
                        margin: 0; 
                        padding: 0; 
                    }
                    h2, p {
                        page-break-after: avoid;
                    }
                    table { 
                        page-break-before: avoid;
                        page-break-inside: auto;
                    }
                }
            </style>
        </head>
        <body>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; border: 3px solid #000; border-radius: 10px; background: #f9fafb;">
                <div style="text-align: left;">
                    <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: bold; color: #000;">Despacho - Pedido {{ $pedido->numero_pedido }}</h2>
                    <p style="margin: 6px 0; font-size: 14px; color: #000;"><strong>Cliente:</strong> {{ $pedido->cliente ?? '—' }}</p>
                    <p style="margin: 6px 0; font-size: 13px; color: #333;"><strong>Fecha de creación:</strong> {{ $pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : '—' }}</p>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; font-size: 11px; color: #666;"><strong>Fecha de impresión:</strong></p>
                    <p style="margin: 0; font-size: 10px; color: #666;">` + new Date().toLocaleString('es-CO', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }) + `</p>
                </div>
            </div>` + tablaHTML + `
            <script>
                window.print();
                window.onafterprint = function() { window.close(); };
            <\/script>
        </body>
        </html>`;
    
    ventana.document.write(htmlContent);
    ventana.document.close();
}

</script>

@endsection
