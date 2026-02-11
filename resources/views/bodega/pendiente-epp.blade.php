@extends('layouts.app')

@section('title', 'Pendientes de EPP - Bodega')
@section('page-title', 'Pendientes de EPP')

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Buscador -->
        <div class="mb-6">
            <form method="GET" class="flex gap-3">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Buscar por número de pedido, cliente, asesor, prenda o talla..."
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
                @if($search ?? false)
                    <a 
                        href="{{ route('gestion-bodega.pendientes-epp') }}"
                        class="px-6 py-3 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded-lg transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-sm">clear</span>
                            Limpiar
                        </span>
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabla de Pedidos -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            @if(count($pedidosPorPagina) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-center font-medium text-slate-700 w-32">
                                    Acción
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    <div class="flex items-center gap-2">
                                        Nº Pedido
                                        <button 
                                            type="button"
                                            onclick="abrirModalFiltros('numero_pedido')"
                                            class="p-1 hover:bg-slate-200 rounded transition-colors"
                                            title="Filtrar por número de pedido"
                                        >
                                            <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    <div class="flex items-center gap-2">
                                        Cliente
                                        <button 
                                            type="button"
                                            onclick="abrirModalFiltros('cliente')"
                                            class="p-1 hover:bg-slate-200 rounded transition-colors"
                                            title="Filtrar por cliente"
                                        >
                                            <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    <div class="flex items-center gap-2">
                                        Asesor
                                        <button 
                                            type="button"
                                            onclick="abrirModalFiltros('asesor')"
                                            class="p-1 hover:bg-slate-200 rounded transition-colors"
                                            title="Filtrar por asesor"
                                        >
                                            <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left font-medium text-slate-700">
                                    <div class="flex items-center gap-2">
                                        Fecha Entrega
                                        <button 
                                            type="button"
                                            onclick="abrirModalFiltros('fecha_entrega')"
                                            class="p-1 hover:bg-slate-200 rounded transition-colors"
                                            title="Filtrar por fecha de entrega"
                                        >
                                            <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    <div class="flex items-center justify-center gap-2">
                                        Estado
                                        <button 
                                            type="button"
                                            onclick="abrirModalFiltros('estado')"
                                            class="p-1 hover:bg-slate-200 rounded transition-colors"
                                            title="Filtrar por estado"
                                        >
                                            <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($pedidosPorPagina as $pedido)
                                @php
                                    $rowBg = ($pedido['esta_retrasado'] ?? false) ? 'bg-red-50' : 'bg-white';
                                    $estadoClass = 'px-2 py-1 text-xs font-medium rounded ';
                                    if (($pedido['estado'] ?? '') === 'Pendiente') {
                                        $estadoClass .= 'bg-orange-100 text-orange-800';
                                    } elseif (($pedido['estado'] ?? '') === 'Entregado') {
                                        $estadoClass .= 'bg-green-100 text-green-800';
                                    } elseif (($pedido['estado'] ?? '') === 'Anulado') {
                                        $estadoClass .= 'bg-red-100 text-red-800';
                                    } elseif (($pedido['estado'] ?? '') === 'En Proceso') {
                                        $estadoClass .= 'bg-blue-100 text-blue-800';
                                    } else {
                                        $estadoClass .= 'bg-slate-100 text-slate-800';
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors {{ $rowBg }}">
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('gestion-bodega.pendiente-epp-show', $pedido['id']) }}" class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">Ver</a>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-black">{{ $pedido['numero_pedido'] }}</td>
                                    <td class="px-6 py-4 text-black">{{ $pedido['cliente'] ?? '—' }}</td>
                                    <td class="px-6 py-4 text-black">{{ $pedido['asesor'] ?? '—' }}</td>
                                    <td class="px-6 py-4 text-black">
                                        @if($pedido['fecha_entrega'])
                                            <span class="{{ $pedido['esta_retrasado'] ? 'text-red-600 font-medium' : '' }}">
                                                {{ \Carbon\Carbon::parse($pedido['fecha_entrega'])->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="{{ $estadoClass }}">{{ $pedido['estado'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                    <div class="text-sm text-slate-600">
                        Mostrando {{ ($paginaActual - 1) * $porPagina + 1 }} a {{ min($paginaActual * $porPagina, $totalPedidos) }} 
                        de {{ $totalPedidos }} resultados
                    </div>
                    <div class="flex items-center gap-2">
                        @if($paginaActual > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => $paginaActual - 1]) }}" 
                               class="px-3 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                ← Anterior
                            </a>
                        @endif
                        
                        <span class="px-3 py-2 bg-slate-100 text-slate-900 text-sm font-medium rounded">
                            Página {{ $paginaActual }} de {{ ceil($totalPedidos / $porPagina) }}
                        </span>
                        
                        @if($paginaActual < ceil($totalPedidos / $porPagina))
                            <a href="{{ request()->fullUrlWithQuery(['page' => $paginaActual + 1]) }}" 
                               class="px-3 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                Siguiente →
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <span class="material-symbols-rounded text-slate-300 text-6xl">inventory_2</span>
                    <p class="text-slate-500 font-medium mt-4">No hay pedidos pendientes de EPP</p>
                    <p class="text-slate-400 text-sm mt-2">No se encontraron registros con estado "Pendiente" en el área "EPP"</p>
                    <p class="text-slate-300 text-xs mt-2">Usa el buscador para encontrar pedidos específicos</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de Filtros -->
<div id="modalFiltros" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-200 flex-shrink-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Filtrar Pedidos</h3>
                <button 
                    type="button"
                    onclick="cerrarModalFiltros()"
                    class="p-1 hover:bg-slate-100 rounded transition-colors"
                >
                    <span class="material-symbols-rounded text-slate-500">close</span>
                </button>
            </div>
            
            <!-- Buscador dentro del modal -->
            <div class="relative">
                <input 
                    type="text" 
                    id="buscadorModal"
                    placeholder="Buscar para filtrar resultados..."
                    class="w-full px-4 py-2 pl-10 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                >
                <span class="material-symbols-rounded absolute left-3 top-2.5 text-slate-400">search</span>
                <button 
                    type="button"
                    onclick="buscarEnModal()"
                    class="absolute right-2 top-2 p-1 hover:bg-slate-100 rounded transition-colors"
                    title="Buscar"
                >
                    <span class="material-symbols-rounded text-slate-600 text-sm">search</span>
                </button>
            </div>
        </div>
        
        <!-- Contenido dinámico del modal con scroll -->
        <div class="flex-1 overflow-y-auto">
            <div class="px-6 py-4 border-b border-slate-200 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">
                        <span id="contadorSeleccionados">0</span> seleccionados
                    </span>
                    <button 
                        id="btnSeleccionarTodo"
                        type="button"
                        onclick="toggleSeleccionarTodo()"
                        class="px-3 py-1 text-sm border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 rounded transition-colors"
                    >
                        Seleccionar todo
                    </button>
                </div>
            </div>
            <div id="contenidoModal" class="px-6 py-4">
                <!-- Contenido dinámico se cargará aquí -->
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="p-6 border-t border-slate-200 flex-shrink-0">
            <div class="flex gap-3">
                <button 
                    type="button"
                    onclick="aplicarFiltros()"
                    class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors"
                >
                    Aplicar Filtros
                </button>
                <button 
                    type="button"
                    onclick="cerrarModalFiltros()"
                    class="flex-1 px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                >
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Botón flotante para limpiar filtros -->
<div id="btnLimpiarFiltros" class="fixed bottom-6 right-6 z-50 hidden">
    <button 
        type="button"
        onclick="limpiarTodosLosFiltros()"
        class="bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-full shadow-lg flex items-center gap-2 transition-all duration-200 hover:scale-105"
        title="Limpiar todos los filtros"
    >
        <span class="material-symbols-rounded text-sm">filter_alt_off</span>
        <span class="text-sm font-medium">Limpiar filtros</span>
    </button>
</div>

<script>
let tipoFiltroActual = '';
let paginaActual = 1;
let terminoBusqueda = '';

function abrirModalFiltros(tipoFiltro) {
    tipoFiltroActual = tipoFiltro;
    paginaActual = 1;
    terminoBusqueda = '';
    
    // Configurar el modal según el tipo de filtro
    const modal = document.getElementById('modalFiltros');
    const titulo = modal.querySelector('h3');
    const buscador = document.getElementById('buscadorModal');
    
    // Limpiar buscador
    buscador.value = '';
    
    // Configurar título según el tipo de filtro
    switch(tipoFiltro) {
        case 'numero_pedido':
            titulo.textContent = 'Filtrar por Número de Pedido';
            break;
        case 'cliente':
            titulo.textContent = 'Filtrar por Cliente';
            break;
        case 'asesor':
            titulo.textContent = 'Filtrar por Asesor';
            break;
        case 'estado':
            titulo.textContent = 'Filtros por Estado';
            break;
        case 'fecha':
        case 'fecha_entrega':
            titulo.textContent = 'Filtrar por Fecha de Entrega';
            break;
        default:
            titulo.textContent = 'Filtrar Pedidos';
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // Cargar datos del filtro
    cargarDatosFiltro();
    
    // Enfocar buscador
    setTimeout(() => buscador.focus(), 100);
}

function cerrarModalFiltros() {
    document.getElementById('modalFiltros').classList.add('hidden');
    document.getElementById('modalFiltros').classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function cargarDatosFiltro() {
    const contenidoModal = document.getElementById('contenidoModal');
    contenidoModal.innerHTML = '<div class="flex justify-center items-center py-8"><span class="text-slate-500">Cargando...</span></div>';
    
    // URL para obtener datos de filtro
    const url = `/gestion-bodega/filtro-datos/${tipoFiltro}?page=${paginaActual}&search=${terminoBusqueda}`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al cargar datos');
        }
        return response.json();
    })
    .then(data => {
        renderizarContenidoModal(data);
    })
    .catch(error => {
        console.error('Error:', error);
        contenidoModal.innerHTML = '<div class="text-center py-8 text-red-500">Error al cargar datos</div>';
    });
}

function renderizarContenidoModal(datos) {
    const contenidoModal = document.getElementById('contenidoModal');
    let html = '<div class="space-y-2">';
    
    // Extraer los datos del objeto de respuesta
    const datosArray = datos.datos || datos;
    
    if (Array.isArray(datosArray) && datosArray.length > 0) {
        datosArray.forEach(item => {
            const valor = item.valor || item;
            const cantidad = item.cantidad || 0;
            const label = typeof valor === 'string' ? valor : String(valor);
            
            html += `
                <label class="flex items-center p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" value="${label}" class="filtro-checkbox mr-3">
                    <span class="flex-1 text-sm">${label}</span>
                    ${cantidad > 0 ? `<span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">${cantidad}</span>` : ''}
                </label>
            `;
        });
    } else {
        html += '<div class="text-center py-8 text-slate-500">No hay datos disponibles</div>';
    }
    
    html += '</div>';
    contenidoModal.innerHTML = html;
    
    // Actualizar contador
    actualizarContadorSeleccionados();
    
    // Agregar event listeners a los checkboxes
    document.querySelectorAll('.filtro-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', actualizarContadorSeleccionados);
    });
}

function buscarEnModal() {
    const termino = document.getElementById('buscadorModal').value.toLowerCase();
    const checkboxes = document.querySelectorAll('.filtro-checkbox');
    
    checkboxes.forEach(checkbox => {
        const label = checkbox.closest('label');
        const texto = label.textContent.toLowerCase();
        
        if (texto.includes(termino)) {
            label.style.display = 'flex';
        } else {
            label.style.display = 'none';
        }
    });
}

function toggleSeleccionarTodo() {
    const checkboxes = document.querySelectorAll('.filtro-checkbox');
    const btn = document.getElementById('btnSeleccionarTodo');
    const visibleCheckboxes = Array.from(checkboxes).filter(cb => cb.closest('label').style.display !== 'none');
    
    if (visibleCheckboxes.length === 0) return;
    
    const todosSeleccionados = visibleCheckboxes.every(cb => cb.checked);
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = !todosSeleccionados;
    });
    
    btn.textContent = todosSeleccionados ? 'Deseleccionar todo' : 'Seleccionar todo';
    actualizarContadorSeleccionados();
}

function actualizarContadorSeleccionados() {
    const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
    const contador = document.getElementById('contadorSeleccionados');
    contador.textContent = checkboxes.length;
    
    const btn = document.getElementById('btnSeleccionarTodo');
    const visibleCheckboxes = Array.from(document.querySelectorAll('.filtro-checkbox'))
        .filter(cb => cb.closest('label').style.display !== 'none');
    
    if (visibleCheckboxes.length > 0) {
        const todosSeleccionados = visibleCheckboxes.every(cb => cb.checked);
        btn.textContent = todosSeleccionados ? 'Deseleccionar todo' : 'Seleccionar todo';
    }
}

function aplicarFiltros() {
    const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
    const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
    
    if (valoresSeleccionados.length === 0) {
        alert('Por favor selecciona al menos una opción');
        return;
    }
    
    // Construir URL con filtros
    const url = new URL(window.location);
    url.searchParams.set(tipoFiltroActual, valoresSeleccionados.join(','));
    
    // Redirigir con los filtros aplicados
    window.location.href = url.toString();
}

// Función para limpiar todos los filtros
function limpiarTodosLosFiltros() {
    // Redirigir a la misma página sin parámetros de filtro
    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    // Eliminar todos los parámetros de filtro
    const parametrosFiltro = ['numero_pedido', 'cliente', 'asesor', 'estado', 'fecha_entrega', 'retrasados'];
    parametrosFiltro.forEach(param => params.delete(param));
    
    // Mantener solo el parámetro de búsqueda si existe
    const search = params.get('search');
    const nuevaUrl = url.pathname + (search ? '?search=' + search : '');
    
    // Redirigir
    window.location.href = nuevaUrl;
}

// Función para verificar si hay filtros activos y mostrar/ocultar el botón flotante
function verificarFiltrosActivos() {
    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    const filtrosActivos = ['numero_pedido', 'cliente', 'asesor', 'estado', 'fecha_entrega', 'retrasados'];
    const tieneFiltros = filtrosActivos.some(filtro => params.get(filtro));
    
    // Mostrar u ocultar botón flotante
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiarFiltros) {
        if (tieneFiltros) {
            btnLimpiarFiltros.classList.remove('hidden');
        } else {
            btnLimpiarFiltros.classList.add('hidden');
        }
    }
}

// Verificar filtros cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    verificarFiltrosActivos();
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalFiltros').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalFiltros();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalFiltros();
    }
});
</script>
@endsection
