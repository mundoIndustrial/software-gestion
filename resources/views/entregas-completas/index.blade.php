@extends('layouts.app')

@section('title', 'Seguimiento de Entregas')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <span class="material-symbols-rounded me-1">list_alt</span>
                    Listado de Entregas
                </h5>
                <div class="search-box">
                    <span class="material-symbols-rounded search-icon">search</span>
                    <input type="text" id="buscadorGeneral" class="form-control form-control-sm" placeholder="Buscar...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                Pedido
                                <button class="btn-filter-column" onclick="openFilterModal('numero_pedido')" title="Filtrar por pedido">
                                    <span class="material-symbols-rounded">filter_alt</span>
                                </button>
                            </th>
                            <th>
                                Cliente
                                <button class="btn-filter-column" onclick="openFilterModal('cliente')" title="Filtrar por cliente">
                                    <span class="material-symbols-rounded">filter_alt</span>
                                </button>
                            </th>
                            <th>
                                Estado Pedido
                                <button class="btn-filter-column" onclick="openFilterModal('estado_pedido')" title="Filtrar por estado">
                                    <span class="material-symbols-rounded">filter_alt</span>
                                </button>
                            </th>
                            <th>Entrega Supervisor Pedidos</th>
                            <th>Entrega Despacho</th>
                            <th>Tiempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entregas as $entrega)
                            <tr>
                                <td><strong>#{{ $entrega->numero_pedido }}</strong></td>
                                <td>{{ $entrega->cliente }}</td>
                                <td>
                                    <span class="badge {{ getEstadoBadgeClass($entrega->estado_pedido) }}">
                                        {{ getEstadoNombre($entrega->estado_pedido) }}
                                    </span>
                                </td>
                                <td>
                                    @if($entrega->fecha_entrega_supervisor)
                                        <div>
                                            <small>{{ \Carbon\Carbon::parse($entrega->fecha_entrega_supervisor)->format('d/m/Y H:i') }}</small><br>
                                            <strong>{{ $entrega->nombre_supervisor_entrega }}</strong>
                                        </div>
                                    @else
                                        <span class="text-muted">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($entrega->fecha_entrega_despacho)
                                        <div>
                                            <small>{{ \Carbon\Carbon::parse($entrega->fecha_entrega_despacho)->format('d/m/Y H:i') }}</small><br>
                                            <strong>{{ $entrega->nombre_despacho_entrega }}</strong>
                                        </div>
                                    @else
                                        <span class="text-muted">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($entrega->dias_entre_entregas)
                                        <span class="badge bg-info">
                                            {{ $entrega->dias_entre_entregas }} día{{ $entrega->dias_entre_entregas != 1 ? 's' : '' }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">No se encontraron entregas</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Mostrando {{ $entregas->firstItem() }} a {{ $entregas->lastItem() }} 
                    de {{ $entregas->total() }} registros
                </div>
                {{ $entregas->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Botón flotante para limpiar filtros -->
<button class="btn-clear-filters" id="btnClearFilters" onclick="clearAllFilters()" title="Limpiar todos los filtros">
    <span class="material-symbols-rounded">filter_alt_off</span>
</button>

<!-- Modal de Filtrado -->
<div class="filter-modal-overlay" id="filterModalOverlay">
    <div class="filter-modal">
        <div class="filter-modal-header">
            <h3 id="filterModalTitle">Filtrar por Cliente</h3>
            <button type="button" class="filter-modal-close" onclick="closeFilterModal()">×</button>
        </div>
        <div class="filter-modal-body">
            <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar...">
            <div class="filter-options" id="filterOptions">
                <!-- Las opciones se cargarán dinámicamente -->
            </div>
        </div>
        <div class="filter-modal-footer">
            <button type="button" class="btn-filter-reset" onclick="resetFilters()">Limpiar</button>
            <button type="button" class="btn-filter-apply" onclick="applyFilters()">Aplicar</button>
        </div>
    </div>
</div>

@php
function getEstadoBadgeClass($estado) {
    $classes = [
        'Pendiente' => 'bg-secondary',
        'No iniciado' => 'bg-secondary',
        'En Ejecución' => 'bg-primary',
        'Entregado' => 'bg-success',
        'Anulada' => 'bg-danger',
        'PENDIENTE_SUPERVISOR' => 'bg-warning',
        'pendiente_cartera' => 'bg-info',
        'RECHAZADO_CARTERA' => 'bg-danger',
        'PENDIENTE_INSUMOS' => 'bg-purple',
        'DEVUELTO_A_ASESORA' => 'bg-orange',
    ];
    return $classes[$estado] ?? 'bg-secondary';
}

function getEstadoNombre($estado) {
    $nombres = [
        'Pendiente' => 'Pendiente',
        'No iniciado' => 'No iniciado',
        'En Ejecución' => 'En Ejecución',
        'Entregado' => 'Entregado',
        'Anulada' => 'Anulada',
        'PENDIENTE_SUPERVISOR' => 'Pendiente Supervisor',
        'pendiente_cartera' => 'Pendiente Cartera',
        'RECHAZADO_CARTERA' => 'Rechazado Cartera',
        'PENDIENTE_INSUMOS' => 'Pendiente Insumos',
        'DEVUELTO_A_ASESORA' => 'Devuelto a Asesora',
    ];
    return $nombres[$estado] ?? $estado;
}
@endphp

<script>
// Datos para filtrado
const entregasData = @json($entregas->items());
let currentFilterColumn = '';
let activeFilters = {};

// Función para abrir modal de filtrado
function openFilterModal(column) {
    currentFilterColumn = column;
    const modal = document.getElementById('filterModalOverlay');
    const title = document.getElementById('filterModalTitle');
    const options = document.getElementById('filterOptions');
    
    // Configurar título
    const titles = {
        'numero_pedido': 'Filtrar por Pedido',
        'cliente': 'Filtrar por Cliente',
        'estado_pedido': 'Filtrar por Estado'
    };
    title.textContent = titles[column] || 'Filtrar';
    
    // Obtener valores únicos para la columna
    const uniqueValues = [...new Set(entregasData.map(item => {
        const value = item[column];
        return column === 'numero_pedido' ? '#' + value : value;
    }))].sort();
    
    // Generar opciones
    options.innerHTML = `
        <div style="padding: 12px; border-bottom: 1px solid #e5e7eb; margin-bottom: 8px;">
            <button type="button" class="btn-select-all" style="width: 100%; padding: 8px 12px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px;">
                Seleccionar todas
            </button>
        </div>
        ${uniqueValues.map(value => `
            <div class="filter-option">
                <input type="checkbox" id="filter-${value.replace(/[^a-zA-Z0-9]/g, '')}" value="${value}">
                <label for="filter-${value.replace(/[^a-zA-Z0-9]/g, '')}">${value}</label>
            </div>
        `).join('')}
    `;
    
    // Restaurar selecciones previas
    if (activeFilters[column]) {
        activeFilters[column].forEach(value => {
            const checkbox = options.querySelector(`input[value="${value}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    modal.classList.add('active');
}

// Función para cerrar modal
function closeFilterModal() {
    document.getElementById('filterModalOverlay').classList.remove('active');
}

// Función para aplicar filtros
function applyFilters() {
    const options = document.getElementById('filterOptions');
    const checkboxes = options.querySelectorAll('input[type="checkbox"]:checked');
    
    if (checkboxes.length > 0) {
        activeFilters[currentFilterColumn] = Array.from(checkboxes).map(cb => cb.value);
    } else {
        delete activeFilters[currentFilterColumn];
    }
    
    filterTable();
    updateClearFiltersButton();
    closeFilterModal();
}

// Función para limpiar filtros
function resetFilters() {
    const options = document.getElementById('filterOptions');
    const checkboxes = options.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    delete activeFilters[currentFilterColumn];
    filterTable();
    updateClearFiltersButton();
}

// Función para filtrar tabla
function filterTable() {
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        let showRow = true;
        
        // Aplicar filtros activos
        Object.keys(activeFilters).forEach(column => {
            if (activeFilters[column].length > 0) {
                const cellIndex = getColumnIndex(column);
                const cellValue = row.cells[cellIndex]?.textContent.trim();
                
                if (!activeFilters[column].some(filter => cellValue.includes(filter))) {
                    showRow = false;
                }
            }
        });
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Función para obtener índice de columna
function getColumnIndex(column) {
    const indices = {
        'numero_pedido': 0,
        'cliente': 1,
        'estado_pedido': 2
    };
    return indices[column] || 0;
}

// Buscador general
document.getElementById('buscadorGeneral').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
    
    // Actualizar estado del botón flotante
    updateClearFiltersButton();
});

// Buscar en modal
document.getElementById('filterSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const options = document.querySelectorAll('.filter-option');
    
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        option.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Seleccionar todas
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-select-all')) {
        const options = document.getElementById('filterOptions');
        const checkboxes = options.querySelectorAll('input[type="checkbox"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => cb.checked = !allChecked);
    }
});

// Función para limpiar todos los filtros
function clearAllFilters() {
    // Limpiar filtros activos
    activeFilters = {};
    
    // Limpiar buscador general
    document.getElementById('buscadorGeneral').value = '';
    
    // Aplicar filtros limpios
    filterTable();
    
    // Actualizar estado del botón
    updateClearFiltersButton();
    
    // Mostrar notificación
    showNotification('Todos los filtros han sido limpiados', 'info');
}

// Función para mostrar notificaciones
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="material-symbols-rounded">
            ${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}
        </span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-eliminar después de 3 segundos
    setTimeout(() => {
        notification.classList.add('notification-hide');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Actualizar estado del botón flotante
function updateClearFiltersButton() {
    const btn = document.getElementById('btnClearFilters');
    const hasFilters = Object.keys(activeFilters).length > 0 || document.getElementById('buscadorGeneral').value.length > 0;
    
    if (hasFilters) {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
    }
}

// Cerrar modal al hacer clic fuera
document.getElementById('filterModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFilterModal();
    }
});
</script>

<style>
.btn-filter-column {
    background: none;
    border: none;
    padding: 2px 4px;
    margin-left: 8px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.btn-filter-column:hover {
    opacity: 1;
}

.btn-filter-column .material-symbols-rounded {
    font-size: 16px;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box .search-icon {
    position: absolute;
    left: 10px;
    font-size: 18px;
    opacity: 0.6;
}

.search-box input {
    padding-left: 35px !important;
    width: 200px;
}

.filter-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    z-index: 1050;
    align-items: center;
    justify-content: center;
}

.filter-modal-overlay.active {
    display: flex;
}

.filter-modal {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.filter-modal-header {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.filter-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.filter-modal-body {
    padding: 16px;
    max-height: 400px;
    overflow-y: auto;
}

.filter-search {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    margin-bottom: 12px;
}

.filter-options {
    max-height: 300px;
    overflow-y: auto;
}

.filter-option {
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
}

.filter-option input[type="checkbox"] {
    margin-right: 8px;
}

.filter-option label {
    cursor: pointer;
    flex: 1;
}

.filter-modal-footer {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.btn-filter-reset,
.btn-filter-apply {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.btn-filter-reset {
    background: #f3f4f6;
    color: #374151;
}

.btn-filter-apply {
    background: #3b82f6;
    color: white;
}

/* Botón flotante para limpiar filtros */
.btn-clear-filters {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #6b7280;
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    z-index: 1000;
    opacity: 0.7;
}

.btn-clear-filters:hover {
    background: #ef4444;
    transform: scale(1.1);
    opacity: 1;
}

.btn-clear-filters.active {
    background: #ef4444;
    opacity: 1;
    animation: pulse 2s infinite;
}

.btn-clear-filters .material-symbols-rounded {
    font-size: 24px;
}

@keyframes pulse {
    0% {
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
    }
    50% {
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
    }
    100% {
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
    }
}

/* Notificaciones */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 9999;
    min-width: 250px;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.notification.notification-show {
    transform: translateX(0);
}

.notification.notification-hide {
    transform: translateX(100%);
    opacity: 0;
}

.notification-success {
    border-left: 4px solid #22c55e;
}

.notification-error {
    border-left: 4px solid #ef4444;
}

.notification-info {
    border-left: 4px solid #3b82f6;
}

.notification .material-symbols-rounded {
    font-size: 20px;
}

.notification-success .material-symbols-rounded {
    color: #22c55e;
}

.notification-error .material-symbols-rounded {
    color: #ef4444;
}

.notification-info .material-symbols-rounded {
    color: #3b82f6;
}
</style>
@endsection
