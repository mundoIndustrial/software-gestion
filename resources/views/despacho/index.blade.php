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
                                    Novedades
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
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <a href="{{ route('despacho.show', $pedido->id) }}"
                                           class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                            Ver
                                        </a>
                                        <button type="button"
                                                onclick="entregarTodo({{ $pedido->id }}, '{{ addslashes($pedido->numero_pedido) }}')"
                                                class="inline-block ml-1 px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors"
                                                title="Marcar todos los ítems como entregados">
                                            Entregar
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-900">
                                        {{ $pedido->numero_pedido }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $pedido->cliente ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3" style="min-width: 320px;">
                                        <div class="flex gap-2 items-center">
                                            <textarea
                                                class="despacho-novedades-preview w-56 px-2 py-1 border border-slate-300 rounded text-xs bg-slate-50 resize-none"
                                                data-pedido-id="{{ $pedido->id }}"
                                                rows="3"
                                                readonly
                                                title="{{ $pedido->novedades ?? '' }}"
                                                style="height:72px"
                                            >{{ !empty($pedido->novedades) ? $pedido->novedades : '—' }}</textarea>
                                            <button
                                                type="button"
                                                onclick="abrirModalNovedadesDespachoIndex({{ $pedido->id }}, '{{ addslashes($pedido->numero_pedido) }}')"
                                                data-pedido-id="{{ $pedido->id }}"
                                                class="despacho-novedades-btn px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors"
                                                title="Ver novedades"
                                                style="position:relative"
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
                                            @elseif($pedido->estado === 'pendiente_cartera')
                                                bg-indigo-100 text-indigo-800
                                            @elseif($pedido->estado === 'RECHAZADO_CARTERA')
                                                bg-red-200 text-red-900
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

@include('components.modals.novedades-advanced-modal')

<script>
console.log('🚀 SCRIPT DESPACHO CARGADO - Iniciando configuración...');
window.__despachoObsUsuarioActualId = {{ auth()->id() ?? 'null' }};
window.__despachoObsUsuarioEsAdmin = {{ auth()->user()->hasRole(['Admin','SuperAdmin','admin']) ? 'true' : 'false' }};

function abrirModalNovedadesDespachoIndex(pedidoId, numeroPedido) {
    if (typeof abrirModalNovedadesAdvanced !== 'function') {
        console.error('[Despacho] abrirModalNovedadesAdvanced no está disponible');
        return;
    }

    abrirModalNovedadesAdvanced(String(pedidoId));

    const el = document.getElementById('modalNovedadesNumeroPedido');
    if (el && numeroPedido) {
        el.textContent = numeroPedido;
    }
}

(function __despachoNovedadesSoloLectura() {
    const isDespachoPage = window.location.pathname.includes('/despacho');
    if (!isDespachoPage) return;

    const warn = (msg) => {
        if (typeof showNotification === 'function') {
            showNotification(msg, 'warning');
        } else {
            alert(msg);
        }
    };

    // Bloquear acciones de escritura
    window.abrirModalAgregarNovedad = function () {
        warn('Solo lectura: no puedes agregar novedades desde Despacho');
    };
    window.editarNovedad = function () {
        warn('Solo lectura: no puedes editar novedades desde Despacho');
    };
    window.eliminarNovedad = function () {
        warn('Solo lectura: no puedes eliminar novedades desde Despacho');
    };
    window.guardarNovedadForm = function () {
        warn('Solo lectura: no puedes guardar novedades desde Despacho');
    };

    // Ocultar botón "Agregar Novedad" cuando el modal esté en el DOM
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('novedadesAdvancedModal');
        if (!modal) return;
        const btn = modal.querySelector('button[onclick="abrirModalAgregarNovedad()"]');
        if (btn) {
            btn.style.display = 'none';
        }

        // Ocultar botones de editar/eliminar (sin romper el render original)
        const container = document.getElementById('novedadesContainer');
        if (!container) return;

        const hideActionButtons = () => {
            container.querySelectorAll('button').forEach((b) => {
                b.style.display = 'none';
            });
        };

        // 1) ocultar si ya hay contenido
        hideActionButtons();

        // 2) observar cambios (cuando se renderizan novedades async)
        const observer = new MutationObserver(() => hideActionButtons());
        observer.observe(container, { childList: true, subtree: true });
    });
})();

function entregarTodo(pedidoId, numeroPedido) {
    if (!confirm(`¿Estás seguro de marcar TODOS los ítems del pedido #${numeroPedido} como entregados? Esta acción marcará el pedido como completado.`)) {
        return;
    }
    
    // Mostrar indicador de carga
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Procesando...';
    btn.disabled = true;
    
    fetch(`/despacho/${pedidoId}/entregar-todo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar notificación de éxito
            mostrarNotificacionExito(numeroPedido);
            
            // Eliminar la fila de la tabla con animación
            const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                
                row.addEventListener('transitionend', function() {
                    row.remove();
                    
                    // Verificar si no quedan pedidos
                    const tbody = document.querySelector('tbody');
                    if (tbody && tbody.children.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-500">
                                    No hay pedidos pendientes
                                </td>
                            </tr>
                        `;
                    }
                }, { once: true });
            }
        } else {
            // Restaurar botón y mostrar error
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Error: ' + (data.message || 'No se pudo procesar la solicitud'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('Error de conexión. Por favor intenta nuevamente.');
    });
}

function mostrarNotificacionExito(numeroPedido) {
    // Crear notificación flotante
    const notificacion = document.createElement('div');
    notificacion.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
    notificacion.innerHTML = `
        <div class="flex items-center gap-2">
            <span>✅</span>
            <span>Pedido #${numeroPedido} marcado como entregado completamente</span>
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
                                        <td colspan="8" class="text-center py-8 text-slate-500">
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
