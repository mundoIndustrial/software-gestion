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
                                <tr class="hover:bg-slate-50 transition-colors 
                                    @if($pedido->estado_entrega === 'completo') bg-blue-100
                                    @elseif($pedido->estado_entrega === 'parcial') bg-yellow-100
                                    @endif" 
                                    data-pedido-id="{{ $pedido->id }}">
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
                                        {{ $pedido->fecha_de_creacion_de_orden?->format('d/m/Y h:i A') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-slate-600 text-xs">
                                        {{ $pedido->fecha_entrega_prendas ?? '—' }}
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
console.log('🚀 SCRIPT DESPACHO CARGADO - Iniciando configuración...');
window.__despachoObsUsuarioActualId = {{ auth()->id() ?? 'null' }};
window.__despachoObsUsuarioEsAdmin = {{ auth()->user()->hasRole(['Admin','SuperAdmin','admin']) ? 'true' : 'false' }};

console.log('🔍 Variables globales configuradas:');
console.log('  - Usuario ID:', window.__despachoObsUsuarioActualId);
console.log('  - Es Admin:', window.__despachoObsUsuarioEsAdmin);

// WebSocket para actualizaciones en tiempo real
let socket = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function connectWebSocket() {
    console.log('🔌 Iniciando conexión WebSocket para despacho...');
    console.log('🔍 Reverb Key:', document.querySelector('meta[name="reverb-key"]')?.getAttribute('content'));
    console.log('🔍 Reverb Host:', document.querySelector('meta[name="reverb-host"]')?.getAttribute('content'));
    console.log('🔍 Reverb Port:', document.querySelector('meta[name="reverb-port"]')?.getAttribute('content'));
    
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

        // Escuchar eventos de pedidos en el canal público de despacho
        console.log('🔧 Creando canal despacho.pedidos...');
        const despachoChannel = socket.channel('despacho.pedidos');
        
        if (!despachoChannel) {
            console.error('❌ No se pudo crear el canal despacho.pedidos');
            return;
        }
        
        console.log('✅ Canal despacho.pedidos creado, configurando listener...');
        
        despachoChannel.listen('.pedido.actualizado', (event) => {
            console.log('📦 Pedido actualizado en tiempo real (despacho):', event);
            
            // Log adicional para debugging
            console.log('🔍 Debug evento recibido:', {
                'pedido_id': event.pedido_id,
                'numero_pedido': event.numero_pedido,
                'nuevo_estado': event.nuevo_estado,
                'anterior_estado': event.anterior_estado,
                'action': event.action,
                'changedFields': event.changedFields,
                'timestamp': event.timestamp
            });
            
            // Mostrar notificación de que se recibió un evento
            console.log('🎯 Evento recibido - Verificando si hay que actualizar la lista...');
            
            // Si estamos en la lista principal y el pedido cambió a "Entregado", eliminarlo
            if (event.nuevo_estado === 'Entregado') {
                    console.log('🔄 Eliminando pedido entregado de la lista:', event.numero_pedido);
                    
                    // Buscar el pedido en la tabla y eliminarlo con animación
                    const pedidoRow = document.querySelector(`tr[data-pedido-id="${event.pedido_id}"]`);
                    if (pedidoRow) {
                        // Agregar animación de fade out usando CSS transitions
                        pedidoRow.style.transition = 'all 0.3s ease';
                        pedidoRow.style.opacity = '0';
                        pedidoRow.style.transform = 'translateX(-20px)';
                        
                        // Eliminar después de que la animación complete
                        pedidoRow.addEventListener('transitionend', function() {
                            pedidoRow.remove();
                            
                            // Mostrar notificación
                            mostrarNotificacionPedidoEntregado(event.numero_pedido);
                            
                            // Verificar si no quedan pedidos
                            const tbody = document.querySelector('tbody');
                            if (tbody && tbody.children.length === 0) {
                                tbody.innerHTML = `
                                    <tr>
                                        <td colspan="7" class="text-center py-8 text-slate-500">
                                            No hay pedidos pendientes
                                        </td>
                                    </tr>
                                `;
                            }
                        }, { once: true }); // Solo se ejecuta una vez
                    }
                }
                // Si el pedido cambió a "Pendiente" y no está en la lista, agregarlo o recargar
                else if (event.nuevo_estado === 'Pendiente') {
                    console.log('🔄 Pedido cambió a Pendiente, verificando lista:', event.numero_pedido);
                    
                    // Verificar si el pedido NO está en la lista actual
                    const pedidoRow = document.querySelector(`tr[data-pedido-id="${event.pedido_id}"]`);
                    if (!pedidoRow) {
                        console.log('📋 Pedido no encontrado en lista, recargando para mostrarlo...');
                        console.log('🔄 Recargando página por cambio en bodega...');
                        // Recargar la página para mostrar el nuevo pedido pendiente
                        window.location.reload();
                    } else {
                        console.log('✅ Pedido ya está en la lista');
                    }
                }
                // Si hay cambios en bodega (items count, etc.), recargar para estar seguros
                else if (event.changedFields && (event.changedFields.bodega_items_count || event.changedFields.bodega_pendientes_count)) {
                    console.log('🔄 Hay cambios en bodega, verificando si hay que recargar...');
                    console.log('📊 Info de bodega:', event.changedFields);
                    
                    // Si hay items pendientes y el pedido no está en la lista, recargar
                    if (event.changedFields.bodega_pendientes_count > 0) {
                        const pedidoRow = document.querySelector(`tr[data-pedido-id="${event.pedido_id}"]`);
                        if (!pedidoRow) {
                            console.log('🔄 Hay items pendientes pero el pedido no está en lista, recargando...');
                            window.location.reload();
                        } else {
                            console.log('✅ Pedido con items pendientes ya está en lista');
                        }
                    }
                }
                // Si el pedido volvió a "Pendiente" desde "Entregado" y no está en la lista, recargar la página
                else if (event.nuevo_estado === 'Pendiente' && event.anterior_estado === 'Entregado') {
                    console.log('🔄 Pedido volvió a Pendiente, recargando lista:', event.numero_pedido);
                    
                    // Verificar si el pedido NO está en la lista actual
                    const pedidoRow = document.querySelector(`tr[data-pedido-id="${event.pedido_id}"]`);
                    if (!pedidoRow) {
                        // Recargar la página para mostrar el pedido que volvió
                        window.location.reload();
                    }
                }
            })
            .error((error) => {
                console.error('❌ Error en WebSocket (despacho):', error);
            });

        console.log('✅ WebSocket conectado para lista de despacho');
        console.log('🔍 Verificando canal público de despacho...');
        
        // Verificar si podemos suscribirnos al canal público
        const channel = socket.channel('despacho.pedidos');
        if (channel) {
            console.log('✅ Canal público despacho.pedidos creado correctamente');
        } else {
            console.error('❌ Error al crear canal público despacho.pedidos');
        }
    } catch (error) {
        console.error('❌ Error al conectar WebSocket:', error);
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            setTimeout(connectWebSocket, 2000 * reconnectAttempts);
        }
    }
}

function mostrarNotificacionPedidoEntregado(numeroPedido) {
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
    
    // Mostrar notificación
    setTimeout(() => {
        notificacion.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notificacion);
        }, 300);
    }, 3000);
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado - Iniciando WebSocket de despacho...');
    console.log('🔍 URL actual:', window.location.href);
    console.log('🔍 Pathname:', window.location.pathname);
    
    // Verificar si estamos en la página correcta
    if (window.location.pathname.includes('/despacho/pendientes')) {
        console.log('✅ Estamos en la página de despacho pendientes');
    } else {
        console.log('⚠️ No estamos en /despacho/pendientes, estamos en:', window.location.pathname);
    }
    
    // Usar el sistema waitForEcho para asegurar que Echo esté disponible
    window.waitForEcho(function() {
        console.log('🚀 Echo está listo, conectando WebSocket para lista de despacho...');
        connectWebSocket();
    });
});
</script>
<script src="{{ asset('js/despacho-index.js') }}"></script>
@endsection
