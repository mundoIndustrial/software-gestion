@extends('layouts.app')

@section('title', 'Gestión de Pedidos - Bodega')
@section('page-title', 'Gestión de Bodega')

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
                        href="{{ route('gestion-bodega.pedidos') }}"
                        class="px-4 py-2 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors"
                    >
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <!-- Stats compactas -->
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex gap-8 text-sm">
                <div>
                    <span class="text-slate-500">Pedidos totales</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $totalPedidos }}</span>
                </div>
                <div>
                    <span class="text-slate-500">En esta página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ count($pedidosPorPagina) }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Página</span>
                    <span class="block text-2xl font-semibold text-slate-900">{{ $paginaActual }} / {{ ceil($totalPedidos / $porPagina) }}</span>
                </div>
            </div>
        </div>

        <!-- Tabla de Pedidos -->
        <div class="bg-white overflow-hidden relative">
            @if(count($pedidosPorPagina) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
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
                                <th class="px-6 py-3 text-center font-medium text-slate-700">
                                    <div class="flex items-center justify-center gap-2">
                                        Creación
                                        <button 
                                            type="button"
                                            onclick="abrirModalFiltros('fecha')"
                                            class="p-1 hover:bg-slate-200 rounded transition-colors"
                                            title="Filtrar por fecha de creación"
                                        >
                                            <span class="material-symbols-rounded text-slate-600 text-sm">filter_alt</span>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($pedidosPorPagina as $pedidoData)
                                <tr class="hover:opacity-75 transition-opacity @if($pedidoData['tiene_pendientes'] ?? false) bg-yellow-100 @elseif($pedidoData['todos_entregados'] ?? false) bg-blue-100 @elseif(!empty($pedidoData['viewed_at'])) bg-gray-200 @else bg-white @endif">
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('gestion-bodega.pedidos-show', $pedidoData['id']) }}"
                                           class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                            Ver
                                        </a>
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
                                    <td class="px-6 py-4">
                                        @php
                                            $estado = strtoupper(trim($pedidoData['estado'] ?? ''));
                                            $colorClase = match($estado) {
                                                'ENTREGADO' => 'bg-green-50 text-green-700',
                                                'EN EJECUCIÓN' => 'bg-blue-50 text-blue-700',
                                                'PENDIENTE_SUPERVISOR' => 'bg-amber-50 text-amber-700',
                                                'PENDIENTE_INSUMOS' => 'bg-orange-50 text-orange-700',
                                                'NO INICIADO' => 'bg-slate-50 text-slate-700',
                                                'ANULADA' => 'bg-red-50 text-red-700',
                                                'DEVUELTO_A_ASESORA' => 'bg-purple-50 text-purple-700',
                                                default => 'bg-slate-50 text-slate-700'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded {{ $colorClase }}">
                                            {{ str_replace('_', ' ', $estado) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-black">
                                        {{ \Carbon\Carbon::parse($pedidoData['fecha_pedido'])->format('d/m/Y') }}
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
                            Mostrando <span class="font-medium">{{ count($pedidosPorPagina) }}</span> de <span class="font-medium">{{ $totalPedidos }}</span> pedidos
                        </div>
                        <div class="flex gap-2">
                            @if($paginaActual > 1)
                                <a href="{{ route('gestion-bodega.pedidos', ['page' => $paginaActual - 1] + request()->query()) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                    ← Anterior
                                </a>
                            @endif
                            
                            <span class="px-3 py-1 text-sm text-slate-600">
                                Página {{ $paginaActual }} de {{ ceil($totalPedidos / $porPagina) }}
                            </span>
                            
                            @if($paginaActual < ceil($totalPedidos / $porPagina))
                                <a href="{{ route('gestion-bodega.pedidos', ['page' => $paginaActual + 1] + request()->query()) }}"
                                   class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm font-medium rounded transition-colors">
                                    Siguiente →
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-slate-500 font-medium">No hay pedidos disponibles</p>
                </div>
            @endif
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
            
            <div id="contenidoModal" class="p-6">
                <!-- Contenido dinámico se cargará aquí -->
            </div>
            
            <!-- Paginación dentro del modal -->
            <div id="paginacionModal" class="px-6 pb-4 border-t border-slate-200 flex-shrink-0">
                <!-- Paginación dinámica se cargará aquí -->
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="p-6 border-t border-slate-200 bg-slate-50 flex-shrink-0">
            <div class="flex gap-3">
                <button 
                    type="button"
                    onclick="aplicarFiltroSeleccionado()"
                    class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors"
                >
                    Aplicar Filtro
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

<script>
let tipoFiltroActual = '';
let datosOriginales = [];
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
    
    // Configurar título y placeholder según el tipo
    switch(tipoFiltro) {
        case 'numero_pedido':
            titulo.textContent = 'Filtrar por Nº Pedido';
            buscador.placeholder = 'Buscar número de pedido...';
            break;
        case 'cliente':
            titulo.textContent = 'Filtrar por Cliente';
            buscador.placeholder = 'Buscar nombre del cliente...';
            break;
        case 'asesor':
            titulo.textContent = 'Filtrar por Asesor';
            buscador.placeholder = 'Buscar nombre del asesor...';
            break;
        case 'estado':
            titulo.textContent = 'Filtrar por Estado';
            buscador.placeholder = 'Buscar estado...';
            break;
        case 'fecha':
            titulo.textContent = 'Filtrar por Fecha de Creación';
            buscador.placeholder = 'Buscar fecha...';
            break;
    }
    
    // Limpiar buscador
    buscador.value = '';
    
    // Cargar datos iniciales
    cargarDatosFiltro();
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // Enfocar buscador
    setTimeout(() => buscador.focus(), 100);
}

async function cargarDatosFiltro() {
    try {
        // Obtener token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Obtener datos únicos según el tipo de filtro - usar ruta web en lugar de API
        const response = await fetch(`/gestion-bodega/filtro-datos/${tipoFiltroActual}?page=${paginaActual}&search=${terminoBusqueda}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            datosOriginales = data.datos;
            renderizarContenidoModal(data.datos);
            renderizarPaginacionModal(data.paginacion);
        } else {
            mostrarError('Error al cargar datos del filtro: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error al cargar datos del filtro:', error);
        mostrarError('Error de conexión al cargar datos: ' + error.message);
    }
}

function renderizarContenidoModal(datos) {
    const contenido = document.getElementById('contenidoModal');
    
    if (datos.length === 0) {
        contenido.innerHTML = `
            <div class="text-center py-8">
                <span class="material-symbols-rounded text-slate-300 text-4xl">search_off</span>
                <p class="text-slate-500 mt-2">No se encontraron resultados</p>
            </div>
        `;
        actualizarContadorSeleccionados();
        return;
    }
    
    let html = '<div class="space-y-2">';
    
    datos.forEach(item => {
        // Usar texto para visualización, valor para ID y selección
        const textoMostrar = item.texto || String(item.valor || item.nombre || item.texto || '');
        const valor = String(item.valor || item.nombre || item.texto || '');
        const cantidad = item.cantidad || 0;
        
        html += `
            <div class="flex items-center justify-between p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors" 
                 onclick="seleccionarValor('${valor}')">
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="valor_${valor.replace(/[^a-zA-Z0-9]/g, '_')}" class="rounded border-slate-300">
                    <label for="valor_${valor.replace(/[^a-zA-Z0-9]/g, '_')}" class="cursor-pointer">
                        <span class="text-sm font-medium text-slate-900">${textoMostrar}</span>
                    </label>
                </div>
                ${cantidad > 0 ? `<span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">${cantidad}</span>` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    contenido.innerHTML = html;
    
    // Actualizar contador y estado del botón
    actualizarContadorSeleccionados();
    actualizarBotonSeleccionarTodo();
}

function renderizarPaginacionModal(paginacion) {
    const contenedor = document.getElementById('paginacionModal');
    
    if (!paginacion || paginacion.total_pages <= 1) {
        contenedor.innerHTML = '';
        return;
    }
    
    let html = `
        <div class="flex items-center justify-between">
            <div class="text-sm text-slate-600">
                Mostrando ${paginacion.from}-${paginacion.to} de ${paginacion.total} resultados
            </div>
            <div class="flex gap-2">
    `;
    
    // Botón anterior
    if (paginacion.current_page > 1) {
        html += `
            <button onclick="cambiarPagina(${paginacion.current_page - 1})" 
                    class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm rounded transition-colors">
                ← Anterior
            </button>
        `;
    }
    
    // Página actual
    html += `
        <span class="px-3 py-1 text-sm text-slate-600">
            Página ${paginacion.current_page} de ${paginacion.total_pages}
        </span>
    `;
    
    // Botón siguiente
    if (paginacion.current_page < paginacion.total_pages) {
        html += `
            <button onclick="cambiarPagina(${paginacion.current_page + 1})" 
                    class="px-3 py-1 border border-slate-300 hover:border-slate-400 text-slate-600 hover:text-slate-900 text-sm rounded transition-colors">
                Siguiente →
            </button>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    contenedor.innerHTML = html;
}

function cambiarPagina(nuevaPagina) {
    paginaActual = nuevaPagina;
    cargarDatosFiltro();
}

function seleccionarValor(valor) {
    // Asegurar que valor sea string
    valor = String(valor || '');
    
    // Marcar/desmarcar checkbox
    const checkbox = document.getElementById(`valor_${valor.replace(/[^a-zA-Z0-9]/g, '_')}`);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
    }
    
    // Actualizar contador y botón
    actualizarContadorSeleccionados();
    actualizarBotonSeleccionarTodo();
}

function toggleSeleccionarTodo() {
    const checkboxes = document.querySelectorAll('#contenidoModal input[type="checkbox"]');
    const btnSeleccionarTodo = document.getElementById('btnSeleccionarTodo');
    const todosSeleccionados = Array.from(checkboxes).every(cb => cb.checked);
    
    if (todosSeleccionados) {
        // Deseleccionar todo
        checkboxes.forEach(checkbox => checkbox.checked = false);
        btnSeleccionarTodo.textContent = 'Seleccionar todo';
    } else {
        // Seleccionar todo
        checkboxes.forEach(checkbox => checkbox.checked = true);
        btnSeleccionarTodo.textContent = 'Deseleccionar todo';
    }
    
    actualizarContadorSeleccionados();
}

function actualizarContadorSeleccionados() {
    const checkboxes = document.querySelectorAll('#contenidoModal input[type="checkbox"]:checked');
    const contador = document.getElementById('contadorSeleccionados');
    if (contador) {
        contador.textContent = checkboxes.length;
    }
}

function actualizarBotonSeleccionarTodo() {
    const checkboxes = document.querySelectorAll('#contenidoModal input[type="checkbox"]');
    const btnSeleccionarTodo = document.getElementById('btnSeleccionarTodo');
    
    if (checkboxes.length === 0) {
        btnSeleccionarTodo.style.display = 'none';
        return;
    }
    
    btnSeleccionarTodo.style.display = 'block';
    
    const todosSeleccionados = Array.from(checkboxes).every(cb => cb.checked);
    btnSeleccionarTodo.textContent = todosSeleccionados ? 'Deseleccionar todo' : 'Seleccionar todo';
}

function buscarEnModal() {
    const buscador = document.getElementById('buscadorModal');
    terminoBusqueda = buscador.value;
    paginaActual = 1;
    cargarDatosFiltro();
}

function aplicarFiltroSeleccionado() {
    // Obtener valores seleccionados
    const checkboxes = document.querySelectorAll('#contenidoModal input[type="checkbox"]:checked');
    const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.id.replace('valor_', '').replace(/_/g, ' '));
    
    if (valoresSeleccionados.length === 0) {
        mostrarError('Por favor selecciona al menos un valor para filtrar');
        return;
    }
    
    // Aplicar filtro a la tabla principal
    aplicarFiltroATabla(valoresSeleccionados);
    
    // Cerrar modal
    cerrarModalFiltros();
}

function aplicarFiltroATabla(valores) {
    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    // Limpiar filtros anteriores del mismo tipo
    switch(tipoFiltroActual) {
        case 'numero_pedido':
            params.delete('filtro_numero_pedido');
            params.set('filtro_numero_pedido', valores.join(','));
            break;
        case 'cliente':
            params.delete('filtro_cliente');
            params.set('filtro_cliente', valores.join(','));
            break;
        case 'asesor':
            params.delete('filtro_asesor');
            params.set('filtro_asesor', valores.join(','));
            break;
        case 'estado':
            params.delete('filtro_estado');
            params.set('filtro_estado', valores.join(','));
            break;
    }
    
    // Mantener otros filtros y búsqueda
    const search = params.get('search');
    if (!search) {
        params.delete('search');
    }
    
    // Redirigir con nuevos filtros
    const newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

function mostrarError(mensaje) {
    const contenido = document.getElementById('contenidoModal');
    contenido.innerHTML = `
        <div class="text-center py-8">
            <span class="material-symbols-rounded text-red-500 text-4xl">error</span>
            <p class="text-red-600 mt-2">${mensaje}</p>
        </div>
    `;
}

function cerrarModalFiltros() {
    document.getElementById('modalFiltros').classList.add('hidden');
    document.getElementById('modalFiltros').classList.remove('flex');
    document.body.style.overflow = 'auto';
}

// Event listener para el buscador
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscadorModal');
    if (buscador) {
        let timeout;
        buscador.addEventListener('input', function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                terminoBusqueda = e.target.value;
                paginaActual = 1;
                cargarDatosFiltro();
            }, 300);
        });
    }
    
    // Verificar si hay filtros activos al cargar la página
    verificarFiltrosActivos();
});

function verificarFiltrosActivos() {
    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    // Verificar si hay algún parámetro de filtro activo
    const filtrosActivos = [
        'filtro_numero_pedido',
        'filtro_cliente', 
        'filtro_asesor',
        'filtro_estado',
        'filtro_fecha_desde',
        'filtro_fecha_hasta'
    ];
    
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

function limpiarTodosLosFiltros() {
    // Redirigir a la misma página sin parámetros de filtro
    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    // Eliminar todos los parámetros de filtro
    params.delete('filtro_numero_pedido');
    params.delete('filtro_cliente');
    params.delete('filtro_asesor');
    params.delete('filtro_estado');
    params.delete('filtro_fecha_desde');
    params.delete('filtro_fecha_hasta');
    
    // Mantener búsqueda si existe
    const search = params.get('search');
    params.delete('search');
    if (search) {
        params.set('search', search);
    }
    
    // Reconstruir URL sin parámetros de filtro
    const newUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

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
