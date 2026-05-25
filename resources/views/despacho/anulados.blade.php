@extends('layouts.despacho-standalone')

@section('title', 'Despacho - Anulados')
@section('page-title', 'Pedidos Anulados')

@push('styles')
<style>
.despacho-index {
    min-height: 100vh;
    background: white;
}

.despacho-index .max-w-6xl {
    max-width: 72rem;
    margin: 0 auto;
}

.despacho-index .border-b {
    border-bottom: 1px solid #e2e8f0;
}

.despacho-index .px-6 {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}

.despacho-index .py-6 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
}

.despacho-index .py-4 {
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.despacho-index .text-2xl {
    font-size: 1.5rem;
    line-height: 2rem;
}

.despacho-index .font-semibold {
    font-weight: 600;
}

.despacho-index .text-slate-900 {
    color: #0f172a;
}

.despacho-index .text-slate-500 {
    color: #64748b;
}

.despacho-index .text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.despacho-index .mt-1 {
    margin-top: 0.25rem;
}

.despacho-index .flex {
    display: flex;
}

.despacho-index .gap-2 {
    gap: 0.5rem;
}

.despacho-index .flex-1 {
    flex: 1;
}

.despacho-index .px-3 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

.despacho-index .py-2 {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.despacho-index .border {
    border: 1px solid #e2e8f0;
}

.despacho-index .border-slate-300 {
    border-color: #cbd5e1;
}

.despacho-index .rounded {
    border-radius: 0.375rem;
}

.despacho-index .text-xs {
    font-size: 0.75rem;
    line-height: 1rem;
}

.despacho-index .focus\:outline-none:focus {
    outline: none;
}

.despacho-index .focus\:border-slate-500:focus {
    border-color: #64748b;
}

.despacho-index .focus\:ring-1:focus {
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), 0 0 #0000;
}

.despacho-index .focus\:ring-slate-500:focus {
    --tw-ring-color: #64748b;
}

.despacho-index .bg-slate-900 {
    background-color: #0f172a;
}

.despacho-index .hover\:bg-slate-800:hover {
    background-color: #1e293b;
}

.despacho-index .text-white {
    color: white;
}

.despacho-index .font-medium {
    font-weight: 500;
}

.despacho-index .transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.despacho-index .gap-8 {
    gap: 2rem;
}

.despacho-index .block {
    display: block;
}

.despacho-index .text-slate-700 {
    color: #334155;
}

.despacho-index .bg-slate-50 {
    background-color: #f8fafc;
}

.despacho-index .divide-y {
    border-top: 1px solid #e2e8f0;
}

.despacho-index .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
    border-bottom: 1px solid #e2e8f0;
}

.despacho-index .overflow-x-auto {
    overflow-x: auto;
}

.despacho-index .w-full {
    width: 100%;
}

.despacho-index .text-center {
    text-align: center;
}

.despacho-index .w-32 {
    width: 8rem;
}

.despacho-index .text-left {
    text-align: left;
}

.despacho-index .hover\:bg-slate-50:hover {
    background-color: #f8fafc;
}

.despacho-index .bg-green-100 {
    background-color: #dcfce7;
}

.despacho-index .text-green-800 {
    color: #166534;
}

.despacho-index .bg-blue-100 {
    background-color: #dbeafe;
}

.despacho-index .text-blue-800 {
    color: #1e40af;
}

.despacho-index .bg-slate-100 {
    background-color: #f1f5f9;
}

.despacho-index .text-slate-800 {
    color: #1e293b;
}

.despacho-index .bg-blue-100 {
    background-color: #dbeafe;
}

.despacho-index .text-blue-800 {
    color: #1e40af;
}

.despacho-index .inline-block {
    display: inline-block;
}

.despacho-index .px-2 {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.despacho-index .py-1 {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}

.despacho-index .text-slate-600 {
    color: #475569;
}

.despacho-index .bg-blue-600 {
    background-color: #2563eb;
}

.despacho-index .hover\:bg-blue-700:hover {
    background-color: #1d4ed8;
}

.despacho-index .relative {
    position: relative;
}

.despacho-index .title {
    cursor: help;
}

.despacho-index .w-56 {
    width: 14rem;
}

.despacho-index .resize-none {
    resize: none;
}

.despacho-index .bg-slate-50 {
    background-color: #f8fafc;
}

.despacho-index .items-start {
    align-items: flex-start;
}

.despacho-index .style {
    height: 40px;
}

.despacho-index .bg-green-100 {
    background-color: #dcfce7;
}

.despacho-index .text-green-800 {
    color: #166534;
}
</style>
@endpush

@section('content')
<div class="despacho-index">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="border-b border-slate-200 px-6 py-6">
            <h1 class="text-2xl font-semibold text-slate-900">Anulados</h1>
            <p class="text-sm text-slate-500 mt-1">Pedidos marcados como anulados</p>
        </div>

        <!-- Buscador -->
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex gap-2">
                <input type="text" id="searchInput" placeholder="Buscar por pedido o cliente..." value="{{ $search }}" class="flex-1 px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                <button type="button" onclick="buscarAnulados()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded transition-colors">
                    Buscar
                </button>
            </div>
        </div>

        <!-- Stats compactas -->
        <div class="px-6 py-4 border-b border-slate-200">
            <div class="flex gap-8">
                <div>
                    <span class="text-sm text-slate-500">Pedidos totales</span>
                    <span class="block text-2xl font-semibold text-slate-900" id="totalPedidos">-</span>
                </div>
                <div>
                    <span class="text-sm text-slate-500">En esta página</span>
                    <span class="block text-2xl font-semibold text-slate-900" id="paginaPedidos">-</span>
                </div>
                <div>
                    <span class="text-sm text-slate-500">Página</span>
                    <span class="block text-2xl font-semibold text-slate-900"><span id="paginaActual">1</span> / <span id="totalPaginas">1</span></span>
                </div>
            </div>
        </div>

        <!-- Tabla de pedidos -->
        <div class="bg-white overflow-hidden">
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
                    <tbody class="divide-y divide-slate-200" id="tablaPedidos">
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <span class="material-symbols-rounded text-4xl mb-2">hourglass_empty</span>
                                    <span>Cargando Pedidos Anulados...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between" id="paginationContainer" style="display: none;">
                <div class="text-sm text-slate-600">
                    Mostrando <span id="desde">0</span> a <span id="hasta">0</span> de <span id="totalResultados">0</span> resultados
                </div>
                <div class="flex gap-2">
                    <button onclick="paginaAnterior()" id="btnAnterior" class="px-3 py-2 border border-slate-300 rounded text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        ← Anterior
                    </button>
                    <button onclick="paginaSiguiente()" id="btnSiguiente" class="px-3 py-2 border border-slate-300 rounded text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Siguiente →
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let paginaActualAnulados = 1;
const itemsPorPagina = 10;

document.addEventListener('DOMContentLoaded', function() {
    cargarPedidosAnulados();

    // Búsqueda con Enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarAnulados();
        }
    });
});

function buscarAnulados() {
    paginaActualAnulados = 1;
    cargarPedidosAnulados();
}

async function cargarPedidosAnulados(pagina = 1) {
    const search = document.getElementById('searchInput').value || '';
    const tablaBody = document.getElementById('tablaPedidos');
    const totalPedidos = document.getElementById('totalPedidos');
    const paginaPedidos = document.getElementById('paginaPedidos');
    const paginationContainer = document.getElementById('paginationContainer');

    try {
        const response = await fetch(`/despacho/api/anulados?search=${encodeURIComponent(search)}&page=${pagina}&per_page=${itemsPorPagina}`);
        const data = await response.json();

        if (data.success) {
            paginaActualAnulados = pagina;

            // Actualizar estadísticas
            totalPedidos.textContent = data.total;
            paginaPedidos.textContent = data.data.length;

            // Actualizar información de paginación
            const pagination = data.pagination;
            document.getElementById('paginaActual').textContent = pagination.current_page;
            document.getElementById('totalPaginas').textContent = pagination.last_page;
            document.getElementById('desde').textContent = pagination.from || 0;
            document.getElementById('hasta').textContent = pagination.to || 0;
            document.getElementById('totalResultados').textContent = pagination.total;

            // Actualizar estado de botones de paginación
            document.getElementById('btnAnterior').disabled = pagination.current_page <= 1;
            document.getElementById('btnSiguiente').disabled = !pagination.has_more;

            // Mostrar/ocultar controles de paginación
            paginationContainer.style.display = pagination.total > itemsPorPagina ? 'flex' : 'none';

            if (data.data.length === 0) {
                tablaBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <span class="material-symbols-rounded text-4xl mb-2">inventory_2</span>
                                <span>No hay Pedidos Anulados</span>
                                <span class="text-sm mt-1">No se encontraron pedidos con estado "Anulada"</span>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                tablaBody.innerHTML = data.data.map(pedido => `
                    <tr class="hover:bg-slate-50 transition-colors" data-pedido-id="${pedido.id}">
                        <td class="px-6 py-4 text-center">
                            <a href="/despacho/${pedido.id}" class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                                Ver
                            </a>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-900">
                            ${pedido.numero_pedido}
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            ${pedido.cliente || 'Sin cliente'}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2 items-start">
                                <textarea class="despacho-observaciones-preview w-56 px-2 py-1 border border-slate-300 rounded text-xs bg-slate-50 resize-none" rows="2" readonly data-pedido-id="${pedido.id}" style="height: 40px;"></textarea>
                                <button type="button" onclick="abrirModalObservacionesDespachoIndex(${pedido.id}, '${pedido.numero_pedido}')" class="despacho-obs-btn px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors" data-pedido-id="${pedido.id}" style="position:relative" title="Ver/agregar observaciones">
                                    💬
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                Anulada
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 text-xs">
                            ${pedido.fecha_creacion}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 text-xs">
                            ${pedido.fecha_anulacion}
                        </td>
                    </tr>
                `).join('');
            }
        } else {
            tablaBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                        <div class="flex flex-col items-center">
                            <span class="material-symbols-rounded text-4xl mb-2">error</span>
                            <span>Error al cargar los datos</span>
                            <span class="text-sm mt-1">${data.message || 'Intente nuevamente más tarde'}</span>
                        </div>
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error al cargar Anuladas:', error);
        tablaBody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                    <div class="flex flex-col items-center">
                        <span class="material-symbols-rounded text-4xl mb-2">wifi_off</span>
                        <span>Error de conexión</span>
                        <span class="text-sm mt-1">No se pudieron cargar los Pedidos Anulados</span>
                    </div>
                </td>
            </tr>
        `;
    }
}

function paginaAnterior() {
    if (paginaActualAnulados > 1) {
        cargarPedidosAnulados(paginaActualAnulados - 1);
        window.scrollTo(0, 0);
    }
}

function paginaSiguiente() {
    cargarPedidosAnulados(paginaActualAnulados + 1);
    window.scrollTo(0, 0);
}

// Función para abrir el modal de observaciones
function abrirModalObservacionesDespachoIndex(pedidoId, numeroPedido) {
    if (typeof abrirModalNovedadesAdvanced !== 'function') {
        console.error('[Despacho Anulados] abrirModalNovedadesAdvanced no está disponible');
        alert('No se pudo abrir el modal de notas en este momento.');
        return;
    }

    abrirModalNovedadesAdvanced(String(pedidoId));

    const el = document.getElementById('modalNovedadesNumeroPedido');
    if (el && numeroPedido) {
        el.textContent = numeroPedido;
    }
}
</script>
@include('components.modals.novedades-advanced-modal')
@endpush



