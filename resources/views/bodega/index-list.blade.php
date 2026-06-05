@extends('layouts.app')

@section('title', 'Gestión de Pedidos - Bodega')
@section('page-title', Route::currentRouteName() === 'gestion-bodega.pedidos-anulados' ? 'Pedidos Anulados' : 'Gestión de Bodega')

@push('styles')
<style>
    #bodega-loading-overlay {
        position: fixed;
        inset: 0;
        background: #ffffff;
        z-index: 9999999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    #bodega-loading-overlay.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: all;
    }
</style>
@endpush

{{-- Overlay de carga removido --}}

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-7xl mx-auto">
        @php
            $detalleRouteName = 'gestion-bodega.pedidos-show';
        @endphp
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

        
        <!-- Tabla de Pedidos -->
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
                                <th class="px-6 py-3 text-center font-medium text-slate-700 w-20">
                                    Novedades
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
                        <tbody id="tablaOrdenesBody" class="divide-y divide-slate-200">
                            @foreach($pedidosPorPagina as $pedidoData)
                                <tr class="hover:opacity-75 transition-all duration-300 @if($pedidoData['tiene_cambios_nuevos'] ?? false) bg-red-200 @elseif($pedidoData['todos_pendientes'] ?? false) bg-yellow-200 @elseif($pedidoData['todos_entregados'] ?? false) bg-blue-200 @else bg-white @endif" 
                                    data-pedido-id="{{ $pedidoData['id'] }}"
                                    data-numero-pedido="{{ $pedidoData['numero_pedido'] }}">
                                    <td class="px-6 py-4 text-center">
                                        <input type="checkbox"
                                               class="w-5 h-5 rounded cursor-pointer"
                                               @if(!($pedidoData['tiene_cambios_nuevos'] ?? false) && !empty($pedidoData['pedido_revisado'])) checked @endif
                                               onchange="guardarCheckPedido({{ $pedidoData['id'] }}, this.checked)"
                                               title="Marcar pedido como revisado">
                                    </td>
                                    <td class="px-6 py-4 text-center flex gap-2 justify-center items-center">
                                        <a href="{{ route($detalleRouteName, $pedidoData['id']) }}"
                                           class="inline-flex items-center justify-center p-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded transition-colors"
                                           title="Ver detalles del pedido">
                                            <span class="material-symbols-rounded text-base">visibility</span>
                                        </a>
                                        <button type="button"
                                                class="inline-flex items-center justify-center p-1.5 bg-red-100 hover:bg-red-200 text-red-600 hover:text-red-700 rounded-lg transition-colors"
                                                onclick="ocultarPedido({{ $pedidoData['id'] }})"
                                                title="Ocultar este pedido">
                                            <span class="material-symbols-rounded text-base">visibility_off</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $rawNovedades = $pedidoData['novedades'] ?? '';
                                            $novedades = [];
                                            if (!empty($rawNovedades)) {
                                                $decoded = json_decode($rawNovedades, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    // Si es JSON, filtrar por rol 'asesor' y por contenido (prenda o epp)
                                                    $novedades = array_filter($decoded, function($nov) {
                                                        $rol = strtolower($nov['rol'] ?? $nov['role'] ?? '');
                                                        $esAsesor = ($rol === 'asesor' || $rol === 'asesora');
                                                        
                                                        $texto = strtolower($nov['texto'] ?? $nov['text'] ?? $nov['description'] ?? '');
                                                        $esPrendaOEpp = str_contains($texto, 'prenda') || str_contains($texto, 'epp');
                                                        $esActualizacion = str_contains($texto, 'modific') || str_contains($texto, 'agreg') || str_contains($texto, 'homolog') || str_contains($texto, 'elimin');
                                                        
                                                        return $esAsesor && $esPrendaOEpp && $esActualizacion;
                                                    });
                                                } else {
                                                    // Es texto plano. El formato suele ser: Rol-Nombre-Fecha - Accion
                                                    $entries = explode("\n\n", $rawNovedades);
                                                    foreach ($entries as $entry) {
                                                        $trimmed = trim($entry);
                                                        if (empty($trimmed)) continue;
                                                        
                                                        $lowerEntry = strtolower($trimmed);
                                                        $esAsesor = str_contains($lowerEntry, 'asesor') || str_contains($lowerEntry, 'asesora');
                                                        $esPrendaOEpp = str_contains($lowerEntry, 'prenda') || str_contains($lowerEntry, 'epp');
                                                        $esActualizacion = str_contains($lowerEntry, 'modific') || str_contains($lowerEntry, 'agreg') || str_contains($lowerEntry, 'homolog') || str_contains($lowerEntry, 'elimin');
                                                        
                                                        if ($esAsesor && $esPrendaOEpp && $esActualizacion) {
                                                            $novedades[] = ['texto' => $trimmed];
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Agregar novedades de la tabla News
                                            $numeroPedido = $pedidoData['numero_pedido'] ?? null;
                                            if ($numeroPedido) {
                                                $newsNovedades = \App\Models\News::whereIn('event_type', ['epp_homologado', 'epp_eliminado', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado'])
                                                    ->where('pedido', $numeroPedido)
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
                                                
                                                foreach ($newsNovedades as $news) {
                                                    $novedades[] = [
                                                        'texto' => $news->description,
                                                        'fecha' => $news->created_at->format('d/m/Y H:i'),
                                                        'fechaObj' => $news->created_at,
                                                        'timestamp' => $news->created_at->getTimestamp(),
                                                        'tipo' => $news->event_type
                                                    ];
                                                }
                                            }
                                            
                                            // Parsear fechas de novedades antiguas y ordenar todo por fecha descendente
                                            foreach ($novedades as &$nov) {
                                                if (!isset($nov['timestamp'])) {
                                                    // Intentar extraer fecha del texto antiguo
                                                    // Formato: "...fecha, HH:MM:SS am/pm)" 
                                                    if (preg_match('/(\d{2}\/\d{2}\/\d{4}),?\s+(\d{1,2}):(\d{2}):(\d{2})\s*([apm\.]+)/i', $nov['texto'], $matches)) {
                                                        $fechaStr = $matches[1] . ' ' . $matches[2] . ':' . $matches[3];
                                                        try {
                                                            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $fechaStr);
                                                            $nov['timestamp'] = $fecha->getTimestamp();
                                                            $nov['fecha'] = $fecha->format('d/m/Y H:i');
                                                        } catch (\Exception $e) {
                                                            $nov['timestamp'] = 0; // Si no se puede parsear, enviar al final
                                                        }
                                                    } else {
                                                        $nov['timestamp'] = 0; // Si no se puede extraer fecha, enviar al final
                                                    }
                                                }
                                            }
                                            unset($nov);
                                            
                                            // Ordenar por timestamp descendente (más recientes primero)
                                            usort($novedades, function($a, $b) {
                                                return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
                                            });
                                            
                                            // Reindexar el array y remover campos internos antes de enviar al JSON
                                            $novedades = array_map(function($nov) {
                                                return ['texto' => $nov['texto'], 'fecha' => $nov['fecha'] ?? ''];
                                            }, $novedades);
                                            $novedades = array_values($novedades);
                                            $cantidadNovedades = count($novedades);
                                        @endphp
                                        <button type="button" 
                                                class="relative inline-flex items-center justify-center p-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-full transition-colors"
                                                onclick="abrirModalNovedades({{ $pedidoData['id'] }}, '{{ $pedidoData['numero_pedido'] }}', {{ json_encode($novedades) }})"
                                                title="Ver novedades">
                                            <span class="material-symbols-rounded">notifications</span>
                                            @if($cantidadNovedades > 0)
                                                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-medium text-white ring-2 ring-white">
                                                    {{ $cantidadNovedades }}
                                                </span>
                                            @endif
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
                            Mostrando <span class="font-medium">{{ count($pedidosPorPagina) }}</span> de <span class="font-medium">{{ $totalPedidos }}</span> pedidos
                        </div>
                        <div class="flex gap-2">
                            @php
                                $totalPaginas = ceil($totalPedidos / $porPagina);
                                $routeName = $routeName ?? 'gestion-bodega.pedidos';
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
                    <p class="text-slate-500 font-medium">No hay pedidos disponibles</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Drawer: Detalle de pedido inline (sin recargar la pestaña) -->
<div id="pedidoDetalleDrawer" class="fixed inset-0 z-[10050] hidden" aria-hidden="true">
    <div id="pedidoDetalleDrawerBackdrop" class="absolute inset-0 bg-black/40"></div>
    <aside class="absolute right-0 top-0 h-full w-full bg-white shadow-2xl flex flex-col">
        <div class="relative flex-1">
            <iframe id="pedidoDetalleIframe"
                    title="Detalle del pedido"
                    class="w-full h-full border-0"
                    loading="eager"></iframe>
        </div>
    </aside>
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

<!-- Modal de Novedades -->
<div id="modalNovedades" class="fixed inset-0 bg-black/50 z-[10000] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-lg w-full shadow-2xl overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Novedades del Pedido</h3>
                <p class="text-xs text-slate-500 font-medium">Nº Pedido: <span id="novedadesNumeroPedido">—</span></p>
            </div>
            <button type="button" onclick="cerrarModalNovedades()" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                <span class="material-symbols-rounded text-slate-400">close</span>
            </button>
        </div>
        
        <!-- Content -->
        <div id="novedadesLista" class="p-6 max-h-[60vh] overflow-y-auto bg-slate-50/30 space-y-4">
            <!-- Novedades dinámicas -->
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-slate-100 bg-white">
            <button type="button" onclick="cerrarModalNovedades()" 
                    class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition-colors shadow-sm">
                Cerrar
            </button>
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

@push('scripts')
<script>
(function() {
    let tipoFiltroActual = '';
    let datosOriginales = [];
    let paginaActual = 1;
    let terminoBusqueda = '';

    // --- FILTROS ---
    window.abrirModalFiltros = function(tipo) {
        tipoFiltroActual = tipo; paginaActual = 1; terminoBusqueda = '';
        const modal = document.getElementById('modalFiltros');
        const titulo = modal.querySelector('h3');
        const buscador = document.getElementById('buscadorModal');
        switch(tipo) {
            case 'numero_pedido': titulo.textContent = 'Filtrar por Nº Pedido'; break;
            case 'cliente': titulo.textContent = 'Filtrar por Cliente'; break;
            case 'asesor': titulo.textContent = 'Filtrar por Asesor'; break;
            case 'estado': titulo.textContent = 'Filtrar por Estado'; break;
            case 'fecha': titulo.textContent = 'Filtrar por Fecha'; break;
        }
        buscador.value = ''; window.cargarDatosFiltro();
        modal.classList.remove('hidden'); modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        setTimeout(() => buscador.focus(), 100);
    };

    window.cargarDatosFiltro = async function() {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch(`/gestion-bodega/filtro-datos/${tipoFiltroActual}?page=${paginaActual}&search=${terminoBusqueda}`, {
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                datosOriginales = data.datos;
                const cont = document.getElementById('contenidoModal');
                if (data.datos.length === 0) { cont.innerHTML = '<p class="text-center py-4 text-slate-500">No hay resultados</p>'; return; }
                let html = '<div class="space-y-2">';
                data.datos.forEach(item => {
                    const val = String(item.valor || item.nombre || item.texto || '');
                    html += `<div class="p-3 border rounded-lg hover:bg-slate-50 cursor-pointer flex items-center gap-3" onclick="seleccionarValor('${val}')">
                        <input type="checkbox" id="valor_${val.replace(/[^a-zA-Z0-9]/g, '_')}" class="rounded border-slate-300">
                        <span class="text-sm font-medium text-slate-900">${item.texto || val}</span></div>`;
                });
                cont.innerHTML = html + '</div>';
                window.actualizarContadorSeleccionados();
            }
        } catch (e) { console.error(e); }
    };

    window.seleccionarValor = function(v) {
        const cb = document.getElementById(`valor_${v.replace(/[^a-zA-Z0-9]/g, '_')}`);
        if (cb) { cb.checked = !cb.checked; window.actualizarContadorSeleccionados(); }
    };

    window.actualizarContadorSeleccionados = function() {
        const count = document.querySelectorAll('#contenidoModal input:checked').length;
        const el = document.getElementById('contadorSeleccionados');
        if (el) el.textContent = count;
    };

    window.toggleSeleccionarTodo = function() {
        const cbs = document.querySelectorAll('#contenidoModal input[type="checkbox"]');
        const all = Array.from(cbs).every(x => x.checked);
        cbs.forEach(x => x.checked = !all);
        window.actualizarContadorSeleccionados();
    };

    window.buscarEnModal = function() {
        terminoBusqueda = document.getElementById('buscadorModal').value;
        paginaActual = 1; window.cargarDatosFiltro();
    };

    window.cerrarModalFiltros = function() {
        document.getElementById('modalFiltros').classList.add('hidden');
        document.body.style.overflow = 'auto';
    };
    
    window.aplicarFiltroSeleccionado = function() {
        const sel = [];
        document.querySelectorAll('#contenidoModal input:checked').forEach(cb => {
            const id = cb.id.replace('valor_', '');
            const d = datosOriginales.find(x => String(x.valor || x.nombre || x.texto || '').replace(/[^a-zA-Z0-9]/g, '_') === id);
            if (d) sel.push(d.valor || d.nombre || d.texto);
        });
        const url = new URL(window.location);
        const paramMap = { 'numero_pedido': 'filtro_numero_pedido', 'cliente': 'filtro_cliente', 'asesor': 'filtro_asesor', 'estado': 'filtro_estado' };
        const pName = paramMap[tipoFiltroActual] || tipoFiltroActual;
        if (sel.length > 0) url.searchParams.set(pName, sel.join(','));
        else url.searchParams.delete(pName);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    };

    window.limpiarTodosLosFiltros = function() {
        const url = new URL(window.location.origin + window.location.pathname);
        window.location.href = url.toString();
    };

    function verificarFiltrosActivos() {
        const params = new URLSearchParams(window.location.search);
        const filtros = ['filtro_numero_pedido', 'filtro_cliente', 'filtro_asesor', 'filtro_estado', 'search'];
        const activo = filtros.some(f => params.get(f));
        const btn = document.getElementById('btnLimpiarFiltros');
        if (btn) btn.classList.toggle('hidden', !activo);
    }

    // --- ACCIONES ---
    window.abrirDetallePedidoInline = function(e, id) {
        if (e) e.preventDefault();
        const dr = document.getElementById('pedidoDetalleDrawer');
        const ifr = document.getElementById('pedidoDetalleIframe');
        ifr.src = `/gestion-bodega/pedidos/${id}?inline=1`;
        dr.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.cerrarDetallePedidoInline = function() {
        document.getElementById('pedidoDetalleDrawer').classList.add('hidden');
        document.body.style.overflow = 'auto';
        document.getElementById('pedidoDetalleIframe').src = 'about:blank';
    };

    window.guardarCheckPedido = async function(id, v) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch(`/gestion-bodega/pedidos/${id}/revisar`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ revisado: v })
        });
        const tr = document.querySelector(`tr[data-pedido-id="${id}"]`);
        if (tr && v) {
            tr.classList.remove('bg-red-200');
            if (!tr.classList.contains('bg-yellow-200') && !tr.classList.contains('bg-blue-200')) tr.classList.add('bg-white');
        }
    };

    window.ocultarPedido = async function(id) {
        if (!confirm('¿Ocultar pedido?')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch(`/gestion-bodega/pedidos/${id}/ocultar`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
        const data = await res.json();
        if (data.success) {
            const tr = document.querySelector(`tr[data-pedido-id="${id}"]`);
            if (tr) {
                tr.style.opacity = '0';
                tr.style.transform = 'translateX(20px)';
                setTimeout(() => tr.remove(), 300);
            }
        }
    };

    // --- NOVEDADES ---
    window.abrirModalNovedades = function(id, numero, novedades) {
        console.log('[abrirModalNovedades] Parámetros recibidos:', {
            id: id,
            numero: numero,
            novedades: novedades,
            tipo: typeof novedades,
            esArray: Array.isArray(novedades)
        });

        document.getElementById('novedadesNumeroPedido').textContent = numero;
        const lista = document.getElementById('novedadesLista');
        lista.innerHTML = '';

        // Asegurar que novedades es un array
        let novedadesArray = [];
        if (typeof novedades === 'string') {
            try {
                novedadesArray = JSON.parse(novedades);
                console.log('[abrirModalNovedades] Parseado desde string:', novedadesArray);
                if (!Array.isArray(novedadesArray)) {
                    console.warn('[abrirModalNovedades] El resultado del parse no es un array:', novedadesArray);
                    novedadesArray = [];
                }
            } catch (e) {
                console.error('[abrirModalNovedades] Error parseando novedades:', e, 'String:', novedades);
                novedadesArray = [];
            }
        } else if (Array.isArray(novedades)) {
            novedadesArray = novedades;
            console.log('[abrirModalNovedades] Ya es un array:', novedadesArray);
        } else {
            console.warn('[abrirModalNovedades] Tipo desconocido:', typeof novedades, novedades);
        }

        console.log('[abrirModalNovedades] Array final:', novedadesArray, 'Cantidad:', novedadesArray.length);

        if (!novedadesArray || novedadesArray.length === 0) {
            lista.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                    <span class="material-symbols-rounded text-5xl mb-2">notifications_off</span>
                    <p class="text-sm font-medium">No hay novedades registradas</p>
                </div>
            `;
        } else {
            novedadesArray.forEach((nov, index) => {
                const div = document.createElement('div');
                div.className = 'bg-white p-5 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden';
                
                const textoNovedad = nov.texto || nov;
                const fechaNovedad = nov.fecha ? `<div class="text-xs text-slate-500 mt-2">${nov.fecha}</div>` : '';
                
                div.innerHTML = `
                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                        ${textoNovedad}
                    </div>
                    ${fechaNovedad}
                `;
                lista.appendChild(div);
            });
        }

        const modal = document.getElementById('modalNovedades');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };

    window.cerrarModalNovedades = function() {
        document.getElementById('modalNovedades').classList.add('hidden');
        document.body.style.overflow = 'auto';
    };

    // --- TIEMPO REAL ---
    function formatFechaJS(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        const pad = (n) => String(n).padStart(2, '0');
        let h = d.getHours();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(h)}:${pad(d.getMinutes())}:${pad(d.getSeconds())} ${ampm}`;
    }

    // Función auxiliar para procesar novedades
    function procesarNovedades(rawNov) {
        let novedades = [];
        if (rawNov) {
            try {
                let decoded = typeof rawNov === 'string' ? JSON.parse(rawNov) : rawNov;
                if (Array.isArray(decoded)) {
                    // Si es JSON, filtrar por rol asesor y contenido
                    novedades = decoded.filter(nov => {
                        let rol = (nov.rol || nov.role || '').toLowerCase();
                        let esAsesor = (rol === 'asesor' || rol === 'asesora');
                        
                        let texto = (nov.texto || nov.text || nov.description || '').toLowerCase();
                        let esPrendaOEpp = texto.includes('prenda') || texto.includes('epp');
                        let esActualizacion = texto.includes('modific') || texto.includes('agreg') || texto.includes('homolog') || texto.includes('elimin');
                        
                        return esAsesor && esPrendaOEpp && esActualizacion;
                    });
                } else {
                    // Texto plano: separar por \n\n y filtrar
                    let entries = rawNov.split(/\n\n+/);
                    entries.forEach(entry => {
                        let trimmed = entry.trim();
                        if (trimmed.length === 0) return;
                        
                        let lowerEntry = trimmed.toLowerCase();
                        let esAsesor = lowerEntry.includes('asesor') || lowerEntry.includes('asesora');
                        let esPrendaOEpp = lowerEntry.includes('prenda') || lowerEntry.includes('epp');
                        let esActualizacion = lowerEntry.includes('modific') || lowerEntry.includes('agreg') || lowerEntry.includes('homolog') || lowerEntry.includes('elimin');
                        
                        if (esAsesor && esPrendaOEpp && esActualizacion) {
                            novedades.push({texto: trimmed});
                        }
                    });
                }
            } catch(e) { 
                // Si falla el parseo, tratar como texto plano
                let entries = rawNov.split(/\n\n+/);
                entries.forEach(entry => {
                    let trimmed = entry.trim();
                    if (trimmed.length === 0) return;
                    
                    let lowerEntry = trimmed.toLowerCase();
                    let esAsesor = lowerEntry.includes('asesor') || lowerEntry.includes('asesora');
                    let esPrendaOEpp = lowerEntry.includes('prenda') || lowerEntry.includes('epp');
                    let esActualizacion = lowerEntry.includes('modific') || lowerEntry.includes('agreg') || lowerEntry.includes('homolog') || lowerEntry.includes('elimin');
                    
                    if (esAsesor && esPrendaOEpp && esActualizacion) {
                        novedades.push({texto: trimmed});
                    }
                });
            }
        }
        return novedades;
    }

    // Función para actualizar el badge de novedades en una fila
    function actualizarBadgeNovedades(pedidoId, novedades) {
        const tr = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
        if (!tr) return;

        const notificacionBtn = tr.querySelector('button[title="Ver novedades"]');
        if (!notificacionBtn) return;

        const cantidadNovedades = novedades.length;
        const badgeExistente = notificacionBtn.querySelector('span.absolute');
        
        if (cantidadNovedades > 0) {
            if (badgeExistente) {
                // Actualizar el badge existente
                badgeExistente.textContent = cantidadNovedades;
            } else {
                // Crear nuevo badge
                const badgeHtml = `<span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-medium text-white ring-2 ring-white">${cantidadNovedades}</span>`;
                notificacionBtn.insertAdjacentHTML('beforeend', badgeHtml);
            }
        } else {
            // Remover badge si no hay novedades
            if (badgeExistente) {
                badgeExistente.remove();
            }
        }

        // Actualizar el onclick del botón con las nuevas novedades
        notificacionBtn.onclick = function() {
            abrirModalNovedades(pedidoId, tr.dataset.numeroPedido, novedades);
        };
    }

    async function insertarPedidoDinamico(id) {
        try {
            const res = await fetch(`/gestion-bodega/pedidos/${id}/fila`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success) {
                const p = data.fila;
                const tbody = document.getElementById('tablaOrdenesBody');
                if (!tbody || document.querySelector(`tr[data-pedido-id="${p.id}"]`)) return;

                const tr = document.createElement('tr');
                tr.className = `hover:opacity-75 transition-all duration-300 ${p.tiene_cambios_nuevos ? 'bg-red-200' : (p.todos_pendientes ? 'bg-yellow-200' : (p.todos_entregados ? 'bg-blue-200' : 'bg-white'))}`;
                tr.dataset.pedidoId = p.id;
                tr.dataset.numeroPedido = p.numero_pedido;
                tr.style.opacity = '0';
                tr.style.transform = 'translateY(-20px)';

                const novedades = procesarNovedades(p.novedades);
                const cantidadNovedades = novedades.length;
                const badgeHtml = cantidadNovedades > 0 ? `<span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-medium text-white ring-2 ring-white">${cantidadNovedades}</span>` : '';

                tr.innerHTML = `
                    <td class="px-6 py-4 text-center">
                        <input type="checkbox" class="w-5 h-5 rounded cursor-pointer" ${p.pedido_revisado ? 'checked' : ''} 
                               onchange="guardarCheckPedido(${p.id}, this.checked)" title="Marcar pedido como revisado">
                    </td>
                    <td class="px-6 py-4 text-center flex gap-2 justify-center items-center">
                        <a href="/gestion-bodega/pedidos/${p.id}" class="inline-flex items-center justify-center p-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded transition-colors"
                           title="Ver detalles del pedido">
                            <span class="material-symbols-rounded text-base">visibility</span>
                        </a>
                        <button type="button" class="inline-flex items-center justify-center p-1.5 bg-red-100 hover:bg-red-200 text-red-600 hover:text-red-700 rounded-lg transition-colors"
                                onclick="ocultarPedido(${p.id})" title="Ocultar este pedido">
                            <span class="material-symbols-rounded text-base">visibility_off</span>
                        </button>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button type="button" 
                                class="relative inline-flex items-center justify-center p-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-full transition-colors"
                                onclick='abrirModalNovedades(${p.id}, "${p.numero_pedido}", ${JSON.stringify(novedades)})'
                                title="Ver novedades">
                            <span class="material-symbols-rounded">notifications</span>
                            ${badgeHtml}
                        </button>
                    </td>
                    <td class="px-6 py-4 font-medium text-black">${p.numero_pedido}</td>
                    <td class="px-6 py-4 text-black">${p.cliente}</td>
                    <td class="px-6 py-4 text-black">${p.asesor}</td>
                    <td class="px-6 py-4 text-center text-black">${formatFechaJS(p.fecha_pedido)}</td>
                    <td class="px-6 py-4 text-center text-black">${formatFechaJS(p.fecha_actualizacion)}</td>
                `;

                tbody.prepend(tr);
                setTimeout(() => {
                    tr.style.opacity = '1';
                    tr.style.transform = 'translateY(0)';
                    tr.animate([{backgroundColor:'#fecaca'},{backgroundColor:'#fee2e2'},{backgroundColor:'#fecaca'}], {duration:2000});
                }, 50);
            }
        } catch (e) { console.error('[BODEGA-LIST] Error insertando pedido:', e); }
    }

    document.addEventListener('DOMContentLoaded', () => {
        verificarFiltrosActivos();
        const bd = document.getElementById('pedidoDetalleDrawerBackdrop');
        if (bd) bd.addEventListener('click', window.cerrarDetallePedidoInline);
        
        document.getElementById('buscadorModal')?.addEventListener('keypress', (e) => { if (e.key === 'Enter') window.buscarEnModal(); });

        if (typeof window.waitForEcho === 'function') {
            window.waitForEcho((echo) => {
                console.log('[BODEGA] Echo Activo');
                
                // Cache para evitar procesamiento duplicado de eventos en ráfaga (debouncing por pedido)
                const eventCache = {
                    processed: new Map(),
                    shouldProcess(pedidoId, eventType) {
                        const key = `${pedidoId}_${eventType}`;
                        const now = Date.now();
                        if (this.processed.has(key) && (now - this.processed.get(key)) < 2000) {
                            return false; // Ignorar si se procesó hace menos de 2 segundos
                        }
                        this.processed.set(key, now);
                        // Limpieza periódica del mapa
                        if (this.processed.size > 100) this.processed.clear();
                        return true;
                    }
                };

                function refrescarYSubirPedido(pedidoNumero, pedidoId = null) {
                    if (!pedidoNumero) return false;

                    const row = document.querySelector(`tr[data-numero-pedido="${pedidoNumero}"]`);
                    if (!row) return false;

                    console.log('[BODEGA-LIST] Resaltando pedido existente:', pedidoNumero);
                    row.classList.remove('bg-white', 'bg-yellow-200', 'bg-blue-200');
                    row.classList.add('bg-red-200');
                    const tbody = document.getElementById('tablaOrdenesBody');
                    if (tbody && tbody.firstChild !== row) {
                        row.style.transition = 'all 0.5s ease-out';
                        tbody.prepend(row);
                        row.animate([{backgroundColor:'#fecaca'},{backgroundColor:'#fee2e2'},{backgroundColor:'#fecaca'}], {duration:2000});
                    }

                    const filaPedidoId = row.dataset.pedidoId || pedidoId;
                    if (filaPedidoId) {
                        fetch(`/gestion-bodega/pedidos/${filaPedidoId}/fila`, { headers: { 'Accept': 'application/json' } })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success && data.fila) {
                                    const checkbox = row.querySelector('input[type="checkbox"]');
                                    if (checkbox) {
                                        const tieneChanges = data.fila.tiene_cambios_nuevos;
                                        const estaRevisado = data.fila.pedido_revisado;
                                        checkbox.checked = !tieneChanges && estaRevisado;
                                    }

                                    row.classList.remove('bg-white', 'bg-yellow-200', 'bg-blue-200', 'bg-red-200');
                                    if (data.fila.tiene_cambios_nuevos) {
                                        row.classList.add('bg-red-200');
                                    } else if (data.fila.todos_pendientes) {
                                        row.classList.add('bg-yellow-200');
                                    } else if (data.fila.todos_entregados) {
                                        row.classList.add('bg-blue-200');
                                    } else {
                                        row.classList.add('bg-white');
                                    }

                                    const novedades = procesarNovedades(data.fila.novedades);
                                    actualizarBadgeNovedades(filaPedidoId, novedades);
                                    console.log('[BODEGA-LIST] Fila actualizada para pedido:', filaPedidoId, 'Novedades:', novedades.length);
                                }
                            })
                            .catch(err => console.error('[BODEGA-LIST] Error actualizando fila:', err));
                    }

                    return true;
                }

                echo.channel('notifications').listen('.new-notification', (e) => {
                    console.log('[BODEGA-LIST] Evento recibido:', e.event_type, e);
                    const num = e.pedido; if (!num) return;
                    
                    // Deduplicar: Si es un pedido_approved, evitamos procesar ráfagas
                    if (['pedido_approved', 'pedido_aprobado', 'order_status_changed'].includes(e.event_type)) {
                        if (!eventCache.shouldProcess(num, 'update')) return;
                    }

                    const seRefrescoFila = refrescarYSubirPedido(num, e.record_id || e.id || null);
                    if (!seRefrescoFila && ['pedido_creado', 'order_created', 'pedido_approved', 'pedido_aprobado', 'order_status_changed', 'epp_homologado', 'epp_eliminado', 'epp_agregado', 'prenda_agregada', 'prenda_modificada'].includes(e.event_type)) {
                        console.log('[BODEGA-LIST] Nuevo pedido detectado, insertando dinámicamente...');
                        const params = new URLSearchParams(window.location.search);
                        if ((params.get('page') || '1') === '1' && !params.has('search')) {
                            insertarPedidoDinamico(e.record_id || e.id);
                        }
                    }
                });

                echo.channel('pedidos.general').listen('.pedido.actualizado', (e) => {
                    const pedidoNum = e.numero_pedido || e.pedido_id;
                    if (!pedidoNum) return;

                    if (eventCache.shouldProcess(pedidoNum, 'update')) {
                        const seRefrescoFila = refrescarYSubirPedido(pedidoNum, e.pedido_id || null);
                        if (!seRefrescoFila) {
                            insertarPedidoDinamico(e.pedido_id);
                        }
                    }
                });
            });
        }
    });

    window.addEventListener('message', (e) => { if (e.data.action === 'cerrarDrawerPedido') window.cerrarDetallePedidoInline(); });

    // REFRESH ON BACK: Forzar recarga si se vuelve atrás desde el navegador
    window.addEventListener('pageshow', (event) => {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
            console.log('[BODEGA-LIST] Detectada navegación atrás, recargando para actualizar estados...');
            window.location.reload();
        }
    });
})();
</script>
@endpush

@endsection
