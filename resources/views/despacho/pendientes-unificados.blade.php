@extends('layouts.despacho-standalone')

@section('title', 'Despacho - Pendientes Unificados')
@section('page-title', 'Pendientes de Costura y EPP')

@push('styles')
<style>
.pendientes-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.filtros-bar {
    display: flex;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    align-items: center;
    position: relative;
    overflow: hidden;
    justify-content: center;
    max-width: 600px;
    margin: 0 auto;
}

.filtros-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
}

.search-box {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
}

.search-box::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23b82f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3Ecircle cx='11' cy='11' r='8'%3E/circle%3Epath d='m21 21-4.35-4.35-8-8-8'/%3E/svg%3E");
    background-size: 20px;
    background-repeat: no-repeat;
    opacity: 0.5;
    pointer-events: none;
}

.filtros-bar input {
    flex: 1;
    padding: 12px 16px 12px 44px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.95rem;
    background: white;
    color: #1e293b;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filtros-bar input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 4px 6px -1px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.filtros-bar input::placeholder {
    color: #94a3b8;
    font-style: italic;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 14px -2px rgba(0, 0, 0, 0.1);
}

.btn-action:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: 2px solid transparent;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border-color: #1d4ed8;
}

.btn-primary .material-symbols-rounded {
    font-size: 18px;
}

/* Modal de Filtros */
.filter-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.filter-modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filter-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.filter-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.close-btn {
    background: none;
    border: none;
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

.filter-modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.filter-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.btn-secondary {
    padding: 10px 20px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.btn-primary {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.filter-option {
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 8px;
}

.filter-option:hover {
    background: #f3f4f6;
}

.filter-option input[type="checkbox"] {
    margin-right: 12px;
}

.filter-option input[type="text"],
.filter-option input[type="date"] {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
}

.filter-option input[type="text"]:focus,
.filter-option input[type="date"]:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-option label {
    flex: 1;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
}

.filter-search {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.filter-search:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.pedidos-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    background: #3b82f6 !important;
    color: white !important;
    padding: 15px !important;
    font-weight: 600 !important;
    display: grid !important;
    grid-template-columns: 100px 120px 1fr 120px 120px !important;
    gap: 15px !important;
    align-items: center !important;
    position: relative;
}

.table-header > div {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    cursor: pointer !important;
    position: relative !important;
    padding: 5px 8px !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
}

.table-header > div:last-child {
    cursor: default !important;
}

.table-header > div:hover {
    background: rgba(255, 255, 255, 0.1) !important;
}

.table-header > div:last-child:hover {
    background: transparent !important;
}

.filter-icon {
    font-size: 16px !important;
    opacity: 0.7 !important;
    transition: all 0.2s ease !important;
}

.table-header > div:hover .filter-icon {
    opacity: 1 !important;
    grid-template-columns: 100px 120px 1fr 120px 120px !important;
    gap: 15px !important;
    align-items: center !important;
    position: relative;
}

.table-row {
    display: grid !important;
    grid-template-columns: 100px 120px 1fr 120px 120px !important;
    gap: 15px !important;
    padding: 15px !important;
    border-bottom: 1px solid #f3f4f6 !important;
    align-items: center !important;
    transition: background-color 0.2s !important;
}

.table-row:hover {
    background-color: #f9fafb !important;
}

.tipo-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
}

.tipo-costura {
    background: #fef3c7;
    color: #92400e;
}

.tipo-epp {
    background: #dbeafe;
    color: #1e40af;
}

.estado-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.estado-pendiente-insumos {
    background: #fed7aa;
    color: #9a3412;
}

.estado-no-iniciado {
    background: #e0e7ff;
    color: #3730a3;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.loading-state {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state .material-symbols-rounded {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.search-box {
    flex: 1;
    max-width: 300px;
}

@media (max-width: 768px) {
    .pendientes-container {
        padding: 10px;
    }
    
    .filtros-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        max-width: none;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 80px 1fr 80px 80px;
        gap: 10px;
    }
    
    .table-header > *:nth-child(4),
    .table-header > *:nth-child(5),
    .table-row > *:nth-child(4),
    .table-row > *:nth-child(5) {
        display: none;
    }
}
</style>
@endpush

@section('content')
<div class="pendientes-container">
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

    <!-- Tabla de Pedidos -->
    <div class="pedidos-table">
        <div class="table-header">
            <div>
                Acciones
            </div>
            <div onclick="toggleFilterModal('pedido')">
                N° Pedido
                <span class="material-symbols-rounded filter-icon">arrow_drop_down</span>
            </div>
            <div onclick="toggleFilterModal('cliente')">
                Cliente
                <span class="material-symbols-rounded filter-icon">arrow_drop_down</span>
            </div>
            <div onclick="toggleFilterModal('estado')">
                Estado
                <span class="material-symbols-rounded filter-icon">arrow_drop_down</span>
            </div>
            <div onclick="toggleFilterModal('fecha')">
                Fecha
                <span class="material-symbols-rounded filter-icon">arrow_drop_down</span>
            </div>
        </div>
        
        <div id="pedidosContainer">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Cargando pendientes...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Filtros -->
<div id="filterModal" class="filter-modal" style="display: none;">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3 id="filterTitle">Filtrar por Cliente</h3>
            <button onclick="closeFilterModal()" class="close-btn">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="filter-modal-body">
            <div id="filterContent">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
        <div class="filter-modal-footer">
            <button onclick="clearFilter()" class="btn-secondary">Limpiar</button>
            <button onclick="applyFilter()" class="btn-primary">Aplicar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let searchActual = '{{ $search }}' || '';

// Cargar pedidos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarPedidos();
    
    // Configurar búsqueda en tiempo real
    const searchInput = document.getElementById('searchInput');
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            searchActual = this.value;
            cargarPedidos();
        }, 500);
    });
});

function buscarPedidos() {
    searchActual = document.getElementById('searchInput').value;
    cargarPedidos();
}

async function cargarPedidos() {
    try {
        mostrarLoading();
        
        const params = new URLSearchParams();
        if (searchActual) params.append('search', searchActual);
        
        const response = await fetch(`/despacho/api/pendientes-todos?${params}`);
        const data = await response.json();
        
        if (data.success) {
            renderizarPedidos(data.data);
        } else {
            mostrarError(data.message);
        }
    } catch (error) {
        console.error('Error cargando pedidos:', error);
        mostrarError('Error al cargar los pedidos');
    }
}

function renderizarPedidos(pedidos) {
    const container = document.getElementById('pedidosContainer');
    
    if (pedidos.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-rounded">inbox</span>
                <p>No hay pedidos pendientes</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = pedidos.map(pedido => {
        // Determinar clase de color según estado de entrega
        let colorClass = '';
        if (pedido.estado_entrega === 'completo') {
            colorClass = 'bg-blue-100';
        } else if (pedido.estado_entrega === 'parcial') {
            colorClass = 'bg-yellow-100';
        }
        
        return `
        <div class="table-row ${colorClass}">
            <div>
                <a href="/despacho/pendientes/${pedido.id}" 
                   class="btn-action btn-primary">
                    <span class="material-symbols-rounded">visibility</span>
                    Ver
                </a>
            </div>
            <div>
                #${pedido.numero_pedido}
            </div>
            <div>
                <strong>${pedido.cliente}</strong>
            </div>
            <div>
                <span class="estado-badge ${getEstadoClass(pedido.estado)}">
                    ${formatEstado(pedido.estado)}
                </span>
            </div>
            <div>
                ${pedido.fecha_creacion}
            </div>
        </div>
    `;
    }).join('');
}

function getEstadoClass(estado) {
    const clases = {
        'PENDIENTE_INSUMOS': 'estado-pendiente-insumos',
        'No iniciado': 'estado-no-iniciado'
    };
    return clases[estado] || '';
}

function formatEstado(estado) {
    const estados = {
        'PENDIENTE_INSUMOS': 'Pendiente Insumos',
        'No iniciado': 'No Iniciado'
    };
    return estados[estado] || estado;
}

function mostrarLoading() {
    const container = document.getElementById('pedidosContainer');
    container.innerHTML = `
        <div class="loading-state">
            <div class="loading-spinner"></div>
            <p>Cargando pendientes...</p>
        </div>
    `;
}

function mostrarError(mensaje) {
    const container = document.getElementById('pedidosContainer');
    container.innerHTML = `
        <div class="empty-state">
            <span class="material-symbols-rounded">error</span>
            <p>Error: ${mensaje}</p>
        </div>
    `;
}

// Funciones para el Modal de Filtros
let currentFilterType = '';
let currentFilterValue = '';

function toggleFilterModal(type) {
    currentFilterType = type;
    const modal = document.getElementById('filterModal');
    const title = document.getElementById('filterTitle');
    const content = document.getElementById('filterContent');
    
    // Configurar título según el tipo
    const titles = {
        'cliente': 'Filtrar por Cliente',
        'pedido': 'Filtrar por N° Pedido',
        'estado': 'Filtrar por Estado',
        'fecha': 'Filtrar por Fecha'
    };
    
    title.textContent = titles[type] || 'Filtrar';
    
    // Obtener datos únicos de la tabla actual
    const tableData = getUniqueTableData(type);
    
    // Generar contenido del modal según el tipo y datos
    let contentHTML = generateFilterContent(type, tableData);
    
    content.innerHTML = contentHTML;
    
    // Mostrar modal
    modal.style.display = 'flex';
    
    // Configurar valores actuales si existen
    if (currentFilterValue) {
        setCurrentFilterValues(type, currentFilterValue);
    }
}

function getUniqueTableData(type) {
    const rows = document.querySelectorAll('.table-row');
    const uniqueValues = new Set();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('div');
        let value = '';
        
        switch(type) {
            case 'acciones':
                // Primera celda: Acciones (si hay botones)
                const hasActions = cells[0]?.querySelector('.btn-action');
                value = hasActions ? 'con_acciones' : 'sin_acciones';
                break;
            case 'pedido':
                // Segunda celda: N° Pedido
                value = cells[1]?.textContent?.trim() || '';
                break;
            case 'cliente':
                // Tercera celda: Cliente
                value = cells[2]?.textContent?.trim() || '';
                break;
            case 'estado':
                // Cuarta celda: Estado
                const estadoBadge = cells[3]?.querySelector('.estado-badge');
                value = estadoBadge?.textContent?.trim() || cells[3]?.textContent?.trim() || '';
                break;
            case 'fecha':
                // Quinta celda: Fecha
                value = cells[4]?.textContent?.trim() || '';
                break;
        }
        
        if (value && value !== '-') {
            uniqueValues.add(value);
        }
    });
    
    return Array.from(uniqueValues).sort();
}

function generateFilterContent(type, data) {
    let contentHTML = '';
    
    switch(type) {
        case 'cliente':
        case 'pedido':
            contentHTML = `
                <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar..." value="">
                <div class="filter-options" style="max-height: 300px; overflow-y: auto;">
                    ${data.length > 0 ? data.map(value => `
                        <div class="filter-option">
                            <input type="checkbox" id="filter_${value.replace(/[^a-zA-Z0-9]/g, '_')}" value="${value}">
                            <label for="filter_${value.replace(/[^a-zA-Z0-9]/g, '_')}">${value}</label>
                        </div>
                    `).join('') : '<p class="text-gray-500 text-center">No hay datos disponibles</p>'}
                </div>
            `;
            break;
            
        case 'estado':
            contentHTML = `
                <div class="filter-options" style="max-height: 300px; overflow-y: auto;">
                    ${data.length > 0 ? data.map(estado => {
                        const estadoId = estado.replace(/[^a-zA-Z0-9]/g, '_');
                        return `
                            <div class="filter-option">
                                <input type="checkbox" id="filter_${estadoId}" value="${estado}">
                                <label for="filter_${estadoId}">
                                    <span class="estado-badge ${getEstadoClass(estado)}">${formatEstado(estado)}</span>
                                </label>
                            </div>
                        `;
                    }).join('') : '<p class="text-gray-500 text-center">No hay datos disponibles</p>'}
                </div>
            `;
            break;
            
        case 'fecha':
            const fechas = data.filter(fecha => fecha && fecha !== '-');
            if (fechas.length > 0) {
                contentHTML = `
                    <div class="filter-options">
                        <div class="filter-option">
                            <label for="filterFechaDesde">Desde:</label>
                            <input type="date" id="filterFechaDesde" class="w-full">
                        </div>
                        <div class="filter-option">
                            <label for="filterFechaHasta">Hasta:</label>
                            <input type="date" id="filterFechaHasta" class="w-full">
                        </div>
                    </div>
                    <div class="filter-options">
                        <p class="text-sm text-gray-600 mb-2">Fechas disponibles:</p>
                        ${fechas.map(fecha => `
                            <div class="filter-option">
                                <input type="checkbox" id="filter_fecha_${fecha}" value="${fecha}">
                                <label for="filter_fecha_${fecha}">${fecha}</label>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                contentHTML = '<p class="text-gray-500 text-center">No hay fechas disponibles</p>';
            }
            break;
    }
    
    return contentHTML;
}

function setCurrentFilterValues(type, filterValue) {
    switch(type) {
        case 'cliente':
        case 'pedido':
            // Configurar búsqueda de texto
            const searchInput = document.getElementById('filterSearch');
            if (searchInput) searchInput.value = filterValue;
            
            // Marcar checkboxes si coinciden
            const values = filterValue.split(',').map(v => v.trim());
            values.forEach(value => {
                const checkbox = document.getElementById(`filter_${value.replace(/[^a-zA-Z0-9]/g, '_')}`);
                if (checkbox) checkbox.checked = true;
            });
            break;
            
        case 'estado':
            const estados = filterValue.split(',').map(v => v.trim());
            estados.forEach(estado => {
                const checkbox = document.getElementById(`filter_${estado.replace(/[^a-zA-Z0-9]/g, '_')}`);
                if (checkbox) checkbox.checked = true;
            });
            break;
            
        case 'fecha':
            if (filterValue.includes('-')) {
                const [desde, hasta] = filterValue.split('-');
                const desdeInput = document.getElementById('filterFechaDesde');
                const hastaInput = document.getElementById('filterFechaHasta');
                if (desdeInput && desde) desdeInput.value = desde;
                if (hastaInput && hasta) hastaInput.value = hasta;
            }
            break;
    }
}

function closeFilterModal() {
    const modal = document.getElementById('filterModal');
    modal.style.display = 'none';
}

function clearFilter() {
    currentFilterValue = '';
    
    // Limpiar búsqueda de texto
    const searchInput = document.getElementById('filterSearch');
    if (searchInput) searchInput.value = '';
    
    // Limpiar checkboxes
    const checkboxes = document.querySelectorAll('#filterContent input[type="checkbox"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    // Limpiar inputs de fecha
    const fechaDesde = document.getElementById('filterFechaDesde');
    const fechaHasta = document.getElementById('filterFechaHasta');
    if (fechaDesde) fechaDesde.value = '';
    if (fechaHasta) fechaHasta.value = '';
    
    // Aplicar filtro limpio
    applyFilter();
}

function getEstadoClass(estado) {
    const clases = {
        'PENDIENTE_INSUMOS': 'estado-pendiente-insumos',
        'No iniciado': 'estado-no-iniciado',
        'En Ejecución': 'estado-en-ejecucion',
        'Anulado': 'estado-anulado'
    };
    return clases[estado] || '';
}

function formatEstado(estado) {
    const estados = {
        'PENDIENTE_INSUMOS': 'Pendiente Insumos',
        'No iniciado': 'No iniciado',
        'En Ejecución': 'En Ejecución',
        'Anulado': 'Anulado'
    };
    return estados[estado] || estado;
}

function applyFilter() {
    let filterValue = '';
    
    switch (currentFilterType) {
        case 'cliente':
        case 'pedido':
            // Obtener valor del input de búsqueda
            const searchInput = document.getElementById('filterSearch');
            const searchValue = searchInput ? searchInput.value.trim() : '';
            
            // Obtener checkboxes seleccionados
            const checkboxes = document.querySelectorAll('#filterContent input[type="checkbox"]:checked');
            const selectedValues = Array.from(checkboxes).map(cb => cb.value);
            
            // Combinar búsqueda y selección
            if (searchValue && selectedValues.length > 0) {
                filterValue = searchValue + ',' + selectedValues.join(',');
            } else if (searchValue) {
                filterValue = searchValue;
            } else if (selectedValues.length > 0) {
                filterValue = selectedValues.join(',');
            }
            break;
            
        case 'estado':
            const estadoCheckboxes = document.querySelectorAll('#filterContent input[type="checkbox"]:checked');
            const estados = Array.from(estadoCheckboxes).map(cb => cb.value);
            filterValue = estados.join(',');
            break;
            
        case 'fecha':
            const desde = document.getElementById('filterFechaDesde');
            const hasta = document.getElementById('filterFechaHasta');
            const fechaCheckboxes = document.querySelectorAll('#filterContent input[type="checkbox"]:checked');
            const fechasSeleccionadas = Array.from(fechaCheckboxes).map(cb => cb.value);
            
            if (desde?.value || hasta?.value) {
                filterValue = `${desde?.value || ''}-${hasta?.value || ''}`;
            } else if (fechasSeleccionadas.length > 0) {
                filterValue = fechasSeleccionadas.join(',');
            }
            break;
    }
    
    currentFilterValue = filterValue;
    closeFilterModal();
    cargarPedidos();
}

function getEstadoClass(estado) {
    const clases = {
        'PENDIENTE_INSUMOS': 'estado-pendiente-insumos',
        'No iniciado': 'estado-no-iniciado',
        'En Ejecución': 'estado-en-ejecucion',
        'Anulado': 'estado-anulado'
    };
    return clases[estado] || '';
}

function formatEstado(estado) {
    const estados = {
        'PENDIENTE_INSUMOS': 'Pendiente Insumos',
        'No iniciado': 'No iniciado',
        'En Ejecución': 'En Ejecución',
        'Anulado': 'Anulado'
    };
    return estados[estado] || estado;
}
</script>

<style>
.loading-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #f3f4f6;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 10px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
@endpush
