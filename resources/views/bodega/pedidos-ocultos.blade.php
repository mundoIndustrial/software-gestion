@extends('layouts.app')

@section('title', 'Pedidos Ocultos - Bodega')
@section('page-title', 'Pedidos Ocultos')

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto">
        <!-- Buscador -->
        <div class="px-6 py-4 border-b border-slate-200">
            <form method="GET" class="flex gap-2">
                <input
                    type="text"
                    name="search"
                    placeholder="Buscar por número de pedido o cliente..."
                    value="{{ $search ?? '' }}"
                    class="flex-1 px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                >
                <button
                    type="submit"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors"
                >
                    Buscar
                </button>
                @if($search ?? false)
                    <a
                        href="{{ route('gestion-bodega.pedidos-ocultos') }}"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        
        <!-- Tabla de Pedidos Ocultos -->
        <div class="bg-white overflow-hidden relative">
            @if(count($pedidosPorPagina) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-center font-medium text-slate-700 w-20">
                                    Revisado
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700 w-20">
                                    Acción
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Nº Pedido
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    Asesor
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    Creación
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    Última Actualización
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($pedidosPorPagina as $pedidoData)
                                <tr class="hover:opacity-75 transition-opacity @if($pedidoData['tiene_cambios_nuevos'] ?? false) bg-white @elseif($pedidoData['todos_pendientes'] ?? false) bg-yellow-100 @elseif($pedidoData['todos_entregados'] ?? false) bg-blue-100 @else bg-white @endif" data-pedido-id="{{ $pedidoData['id'] }}">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox"
                                               class="w-5 h-5 rounded cursor-pointer"
                                               @if(!empty($pedidoData['pedido_revisado'])) checked @endif
                                               onchange="guardarCheckPedido({{ $pedidoData['id'] }}, this.checked)"
                                               title="Marcar pedido como revisado">
                                    </td>
                                    <td class="px-6 py-4 text-center flex gap-2 justify-center items-center">
                                        <a href="{{ route('gestion-bodega.pedidos-show', $pedidoData['id']) }}"
                                           class="inline-flex items-center justify-center p-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded transition-colors">
                                            <span class="material-symbols-rounded text-base">visibility</span>
                                        </a>
                                        <button type="button"
                                                class="inline-flex items-center justify-center p-1.5 bg-green-100 hover:bg-green-200 text-green-600 hover:text-green-700 rounded-lg transition-colors"
                                                onclick="deshacerOcultarPedido({{ $pedidoData['id'] }})"
                                                title="Mostrar este pedido nuevamente">
                                            <span class="material-symbols-rounded text-base">visibility</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-black">
                                        {{ $pedidoData['numero_pedido'] }}
                                    </td>
                                    <td class="px-6 py-4 text-black">
                                        {{ $pedidoData['cliente'] ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-black">
                                        {{ $pedidoData['asesor'] ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-black">
                                        {{ \Carbon\Carbon::parse($pedidoData['fecha_pedido'])->format('d/m/Y h:i:s A') }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-black">
                                        {{ \Carbon\Carbon::parse($pedidoData['fecha_actualizacion'])->format('d/m/Y h:i:s A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if(ceil($totalPedidos / $porPagina) > 1)
                    <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-sm text-slate-700">
                            Mostrando <span class="font-medium">{{ count($pedidosPorPagina) }}</span> de <span class="font-medium">{{ $totalPedidos }}</span> pedidos ocultos
                        </div>
                        <div class="flex gap-2">
                            @php
                                $totalPaginas = ceil($totalPedidos / $porPagina);
                                $routeName = $routeName ?? 'gestion-bodega.pedidos-ocultos';
                                $queryParams = $search ? ['search' => $search] : [];
                            @endphp
                            
                            @if($paginaActual > 1)
                                <a href="{{ route($routeName, ['page' => 1] + $queryParams) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                                   title="Primera página">
                                    « Primero
                                </a>
                                <a href="{{ route($routeName, ['page' => $paginaActual - 1] + $queryParams) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                    ← Anterior
                                </a>
                            @endif
                            
                            <span class="px-3 py-1 text-sm text-slate-600">
                                Página {{ $paginaActual }} de {{ $totalPaginas }}
                            </span>
                            
                            @if($paginaActual < $totalPaginas)
                                <a href="{{ route($routeName, ['page' => $paginaActual + 1] + $queryParams) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                    Siguiente →
                                </a>
                                <a href="{{ route($routeName, ['page' => $totalPaginas] + $queryParams) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                                   title="Última página">
                                    Último »
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-slate-500 font-medium">No hay pedidos ocultos</p>
                    <a href="{{ route('gestion-bodega.pedidos') }}"
                       class="mt-4 inline-block px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors">
                        Volver a pedidos
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
async function guardarCheckPedido(pedidoId, revisado) {
    try {
        const response = await fetch(`/gestion-bodega/pedidos/${pedidoId}/revisar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ revisado: revisado })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`Pedido ${pedidoId} marcado como ${revisado ? 'revisado' : 'no revisado'}`);
            // No recargar página, solo mostrar notificación visual
            mostrarNotificacionExito(`Pedido marcado como ${revisado ? 'revisado' : 'no revisado'}`);
        } else {
            console.error('Error al guardar revisión:', data.message);
            mostrarNotificacionError('Error al guardar la revisión');
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        mostrarNotificacionError('Error en la conexión');
    }
}

async function deshacerOcultarPedido(pedidoId) {
    try {
        const response = await fetch(`/gestion-bodega/pedidos/${pedidoId}/deshacer-ocultar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log(`Pedido ${pedidoId} mostrado nuevamente`);
            // Eliminar la fila de la tabla con animación
            const fila = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
            if (fila) {
                fila.style.opacity = '0';
                fila.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    fila.remove();
                    mostrarNotificacionExito('Pedido mostrado nuevamente');
                }, 300);
            }
        } else {
            console.error('Error al deshacer ocultamiento:', data.message);
            mostrarNotificacionError('Error al mostrar el pedido');
        }
    } catch (error) {
        console.error('Error en la petición:', error);
        mostrarNotificacionError('Error al mostrar el pedido');
    }
}

function mostrarNotificacionExito(mensaje) {
    const notificacion = document.createElement('div');
    notificacion.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notificacion.textContent = mensaje;
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.style.opacity = '0';
        notificacion.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            notificacion.remove();
        }, 300);
    }, 2000);
}

function mostrarNotificacionError(mensaje) {
    const notificacion = document.createElement('div');
    notificacion.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notificacion.textContent = mensaje;
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.style.opacity = '0';
        notificacion.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            notificacion.remove();
        }, 300);
    }, 2000);
}
</script>
@endsection
