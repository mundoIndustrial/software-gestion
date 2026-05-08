@extends('layouts.despacho-standalone')

@section('title', 'Despacho - Historial Pendientes')
@section('page-title', 'Historial de Pendientes')

@push('styles')
<style>
* {
    box-sizing: border-box;
}

.historial-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
    background: #ffffff;
}

/* FILTROS */
.filtros-bar {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    align-items: center;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
    width: 221px;
    margin-left: 95px;
}

.search-box::before {
    content: '';
    position: absolute;
    left: 12px;
    width: 18px;
    height: 18px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234b5563' stroke-width='2'%3Ecircle cx='11' cy='11' r='8'/%3Epath d='m21 21-4.35-4.35'/%3E/svg%3E");
    background-size: contain;
    opacity: 0.6;
    pointer-events: none;
}

.filtros-bar input {
    width: 100%;
    padding: 11px 14px 11px 40px;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    background: white;
    color: #1f2937;
    transition: all 0.2s ease;
}

.filtros-bar input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filtros-bar input::placeholder {
    color: #9ca3af;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-action.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-action.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.btn-action .material-symbols-rounded {
    font-size: 18px;
}

/* TABLA */
.historial-table {
    background: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 95px 85px 1fr 100px 140px;
    gap: 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    align-items: center;
    border-bottom: 2px solid #1e40af;
}

.table-header > div {
    display: flex;
    align-items: center;
    padding: 4px 0;
}

.table-row {
    display: grid;
    grid-template-columns: 95px 85px 1fr 100px 140px;
    gap: 16px;
    padding: 16px 20px;
    border-bottom: 1px solid #f0f1f3;
    align-items: center;
    transition: background-color 0.15s ease;
}

.table-row:hover {
    background-color: #f8fafc;
}

.table-row:last-child {
    border-bottom: none;
}

/* CONTENIDO DE TABLA */
.table-row > div:nth-child(1) {
    display: flex;
    justify-content: center;
}

.table-row > div:nth-child(2) {
    font-weight: 700;
    color: #1f2937;
    font-size: 0.95rem;
}

.table-row > div:nth-child(3) {
    font-weight: 500;
    color: #374151;
    font-size: 0.9375rem;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table-row > div:nth-child(4) {
    display: flex;
    justify-content: center;
}

.table-row > div:nth-child(5) {
    color: #4b5563;
    font-size: 0.875rem;
    font-weight: 500;
}

/* BADGES */
.estado-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

.estado-pendiente {
    background: #fef3c7;
    color: #92400e;
}

.estado-entregado {
    background: #dbeafe;
    color: #1e40af;
}

/* BOTONES EN TABLA */
.table-row .btn-action {
    padding: 7px 12px;
    font-size: 0.8rem;
    background: #3b82f6;
    color: white;
    border-radius: 6px;
    gap: 4px;
}

.table-row .btn-action:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.table-row .btn-action .material-symbols-rounded {
    font-size: 16px;
}

/* ESTADOS DE CARGA Y VACÍO */
.loading-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #9ca3af;
}

.empty-state .material-symbols-rounded {
    font-size: 56px;
    margin-bottom: 20px;
    opacity: 0.4;
}

.empty-state p {
    font-size: 1rem;
    margin: 0;
}

/* PAGINACIÓN */
.pagination-container {
    display: grid;
    grid-template-columns: 95px 85px 1fr 100px 140px;
    gap: 16px;
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    align-items: center;
}

.pagination-info {
    grid-column: 3;
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: right;
}

.pagination-controls {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.pagination-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.2rem;
}

.pagination-btn:hover:not(:disabled) {
    background: white;
    border-color: #3b82f6;
    color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pagination-pages {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
    background: white;
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .table-header,
    .table-row {
        grid-template-columns: 80px 75px 100px 1fr 100px 100px 100px;
        gap: 12px;
        padding: 14px 16px;
    }

    .pagination-container {
        grid-template-columns: 80px 175px 1fr 100px 100px 100px 80px;
    }

    .filtros-bar {
        flex-wrap: wrap;
    }

    .search-box {
        width: 200px;
        margin-left: 80px;
    }
}

@media (max-width: 768px) {
    .historial-container {
        padding: 16px;
        gap: 16px;
    }

    .table-header,
    .table-row {
        grid-template-columns: 70px 70px 1fr 80px;
        gap: 8px;
        padding: 12px;
    }

    .table-header > div:nth-child(5),
    .table-header > div:nth-child(6),
    .table-header > div:nth-child(7),
    .table-row > div:nth-child(5),
    .table-row > div:nth-child(6),
    .table-row > div:nth-child(7) {
        display: none;
    }

    .filtros-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .search-box {
        width: 100%;
        margin-left: 0;
    }

    .pagination-container {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .pagination-info {
        grid-column: 1;
        text-align: center;
        margin-bottom: 8px;
    }

    .pagination-controls {
        grid-column: 1;
        justify-content: center;
        margin-top: 8px;
    }
}
</style>
@endpush

@section('content')
<div class="historial-container">
    <!-- Filtros -->
    <div class="filtros-bar">
        <div class="search-box">
            <input type="text"
                   id="searchInput"
                   placeholder="Buscar por cliente o número de pedido..."
                   value="{{ $search }}">
        </div>

        <button class="btn-action btn-primary" onclick="buscarPedidos()">
            <span class="material-symbols-rounded">search</span>
            Buscar
        </button>
    </div>

    <!-- Tabla de Historial -->
    <div class="historial-table">
        <div class="table-header">
            <div>Acciones</div>
            <div>N° Pedido</div>
            <div>Cliente</div>
            <div>Estado</div>
            <div>F. Pedido</div>
        </div>

        <div id="historialContainer">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Cargando historial...</p>
            </div>
        </div>

        <!-- Controles de paginación -->
        <div id="paginationContainer" class="pagination-container" style="display: none;">
            <div class="pagination-info">
                <span id="paginationText">Mostrando 0 de 0 resultados</span>
            </div>
            <div class="pagination-controls">
                <button id="btnFirstPage" class="pagination-btn" onclick="irAPrimera()" disabled title="Ir a primera página">
                    <span class="material-symbols-rounded">first_page</span>
                </button>
                <button id="btnPrevPage" class="pagination-btn" onclick="cambiarPagina(-1)" disabled title="Página anterior">
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>
                <div class="pagination-pages">
                    <span id="currentPage">1</span>
                    <span class="pagination-separator">de</span>
                    <span id="totalPages">1</span>
                </div>
                <button id="btnNextPage" class="pagination-btn" onclick="cambiarPagina(1)" disabled title="Siguiente página">
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>
                <button id="btnLastPage" class="pagination-btn" onclick="irAUltima()" disabled title="Ir a última página">
                    <span class="material-symbols-rounded">last_page</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let searchActual = '{{ $search }}' || '';
let tipoActual = '{{ $tipo ?? 'todos' }}' || 'todos';
let currentPage = 1;
let perPageActual = 10;
let paginationData = null;

function inicializarDesdeQuery() {
    const params = new URLSearchParams(window.location.search);
    const pageQuery = parseInt(params.get('page'), 10);
    const perPageQuery = parseInt(params.get('per_page'), 10);
    const searchQuery = params.get('search');
    const tipoQuery = params.get('tipo');

    if (!Number.isNaN(pageQuery) && pageQuery > 0) {
        currentPage = pageQuery;
    }

    if (!Number.isNaN(perPageQuery) && perPageQuery > 0) {
        perPageActual = perPageQuery;
    }

    if (searchQuery !== null) {
        searchActual = searchQuery;
    }

    if (tipoQuery !== null && tipoQuery !== '') {
        tipoActual = tipoQuery;
    }
}

function sincronizarUrl() {
    const params = new URLSearchParams();
    if (searchActual) params.set('search', searchActual);
    if (tipoActual) params.set('tipo', tipoActual);
    params.set('page', String(currentPage));
    params.set('per_page', String(perPageActual));
    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
}

// Cargar historial al iniciar
document.addEventListener('DOMContentLoaded', function() {
    inicializarDesdeQuery();
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = searchActual;
    }

    cargarHistorial();
    // Configurar búsqueda en tiempo real
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            searchActual = this.value;
            currentPage = 1;
            cargarHistorial();
        }, 500);
    });
});

function buscarPedidos() {
    searchActual = document.getElementById('searchInput').value;
    currentPage = 1;
    cargarHistorial();
}

async function cargarHistorial() {
    try {
        mostrarLoading();

        const params = new URLSearchParams();

        if (searchActual) params.append('search', searchActual);
        if (tipoActual) params.append('tipo', tipoActual);
        params.append('page', currentPage);
        params.append('per_page', perPageActual);

        const response = await fetch(`/despacho/api/historial-pendientes?${params}`);
        const data = await response.json();

        if (data.success) {
            const historialArray = Array.isArray(data.data) ? data.data : [];
            renderizarHistorial(historialArray);
            actualizarPaginacion(data.pagination);
            sincronizarUrl();
        } else {
            mostrarError(data.message);
        }
    } catch (error) {
        console.error('Error cargando historial:', error);
        mostrarError('Error al cargar el historial');
    }
}

function renderizarHistorial(registros) {
    const container = document.getElementById('historialContainer');

    const registrosArray = Array.isArray(registros) ? registros : [];

    if (registrosArray.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-rounded">inbox</span>
                <p>No hay registros en el historial</p>
            </div>
        `;

        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
    } else {
        container.innerHTML = registrosArray.map(registro => {
            const estadoEntrega = registro.estado_entrega === 'Entregado' ? 'estado-entregado' : 'estado-pendiente';
            const backParams = new URLSearchParams();
            backParams.append('page', String(currentPage));
            backParams.append('per_page', String(perPageActual));
            if (searchActual) backParams.append('search', searchActual);
            if (tipoActual) backParams.append('tipo', tipoActual);

            return `
                <div class="table-row">
                    <div>
                        <a href="/despacho/historial-pendientes/${registro.numero_pedido}?${backParams.toString()}" class="btn-action btn-primary">
                            <span class="material-symbols-rounded">visibility</span>
                            Ver
                        </a>
                    </div>
                    <div>
                        #${registro.numero_pedido || '-'}
                    </div>
                    <div>
                        <strong>${registro.cliente || '-'}</strong>
                    </div>
                    <div>
                        <span class="estado-badge ${estadoEntrega}">${registro.estado_entrega}</span>
                    </div>
                    <div>
                        ${registro.fecha_creacion_pedido || '-'}
                    </div>
                </div>
            `;
        }).join('');

        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
        }
    }
}

function mostrarLoading() {
    const container = document.getElementById('historialContainer');
    container.innerHTML = `
        <div class="loading-state">
            <div class="loading-spinner"></div>
            <p>Cargando historial...</p>
        </div>
    `;
}

function mostrarError(mensaje) {
    const container = document.getElementById('historialContainer');
    container.innerHTML = `
        <div class="empty-state">
            <span class="material-symbols-rounded">error</span>
            <p>Error: ${mensaje}</p>
        </div>
    `;
}

function cambiarPagina(direction) {
    const nuevaPagina = currentPage + direction;

    if (paginationData && nuevaPagina >= 1 && nuevaPagina <= paginationData.last_page) {
        currentPage = nuevaPagina;
        cargarHistorial();
    }
}

function irAPrimera() {
    if (currentPage !== 1) {
        currentPage = 1;
        cargarHistorial();
    }
}

function irAUltima() {
    if (paginationData && currentPage !== paginationData.last_page) {
        currentPage = paginationData.last_page;
        cargarHistorial();
    }
}

function actualizarPaginacion(pagination) {
    if (!pagination) return;

    paginationData = pagination;
    currentPage = Number(pagination.current_page) || currentPage;

    const paginationText = document.getElementById('paginationText');
    if (paginationText) {
        const from = pagination.from || 0;
        const to = pagination.to || 0;
        const total = pagination.total || 0;
        paginationText.textContent = `Mostrando ${from} a ${to} de ${total} resultados`;
    }

    const currentPageSpan = document.getElementById('currentPage');
    const totalPagesSpan = document.getElementById('totalPages');
    if (currentPageSpan) currentPageSpan.textContent = pagination.current_page;
    if (totalPagesSpan) totalPagesSpan.textContent = pagination.last_page;

    const btnFirst = document.getElementById('btnFirstPage');
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');
    const btnLast = document.getElementById('btnLastPage');

    if (btnFirst) {
        btnFirst.disabled = pagination.current_page <= 1;
    }

    if (btnPrev) {
        btnPrev.disabled = pagination.current_page <= 1;
    }

    if (btnNext) {
        btnNext.disabled = !pagination.has_more;
    }

    if (btnLast) {
        btnLast.disabled = !pagination.has_more;
    }
}
</script>
@endpush


