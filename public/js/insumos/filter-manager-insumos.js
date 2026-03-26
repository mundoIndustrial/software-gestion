/**
 * Filter Manager for Insumos/Materiales Module
 * Handles filter modal and filter operations
 */

let currentFilterColumn = null;
let currentFilterValues = [];
let selectedFilters = {};

// ===== FILTER MODAL & RENDERING =====

function showFilterModal(column, values) {
    // Crear modal si no existe
    let modal = document.getElementById('filterModalInsumos');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'filterModalInsumos';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        document.body.appendChild(modal);
    }
    
    const columnNames = {
        'pedido': 'Pedido',
        'numero_pedido': 'N° Pedido',
        'consecutivo_actual': 'N° Recibo',
        'cliente': 'Cliente',
        'estado': 'Estado',
        'area': 'Area',
        'fecha': 'Fecha',
        'created_at': 'Fecha de Inicio'
    };

    // Valores predefinidos para ciertos filtros
    const predefinedValues = {
        'area': ['Corte', 'Creacion de Orden'],
        'estado': {
            db: ['Pendiente', 'No iniciado', 'En Ejecucion', 'Entregado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'pendiente_cartera', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'],
            display: ['Pendiente', 'No iniciado', 'En Ejecucion', 'Entregado', 'Anulada', 'Pendiente Supervisor', 'Pendiente Insumos', 'Pendiente Cartera', 'Rechazado Cartera', 'Devuelto a Asesora']
        }
    };

    // Usar valores predefinidos si existen, sino usar los de la tabla
    let displayValues = values;
    if (column === 'estado' && predefinedValues[column]) {
        displayValues = predefinedValues[column].display;
        values = predefinedValues[column].db;
    } else if (predefinedValues[column]) {
        displayValues = predefinedValues[column];
    }
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: bold;">Filtrar Insumos por: ${columnNames[column] || column}</h3>
                <button data-insumos-action="filter-close-modal" style="background: none; border: none; font-size: 24px; cursor: pointer;">—</button>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center;">
                <input type="text" id="filterSearchInsumos" placeholder="Buscar valores..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                <button data-insumos-action="filter-apply" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap;"> Aplicar</button>
                <button data-insumos-action="filter-select-all" class="filter-btn-tooltip" data-tooltip="Marcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-check-double"></i>
                    </button>
                    <button data-insumos-action="filter-deselect-all" class="filter-btn-tooltip" data-tooltip="Desmarcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-times-circle"></i>
                    </button>
            </div>
            
            <div id="filterListInsumos" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                <p style="text-align: center; color: #999; padding: 20px;">Escribe para buscar valores...</p>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Agregar tooltips a los botones
    setTimeout(() => {
        document.querySelectorAll('.filter-btn-tooltip').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                const tooltip = this.getAttribute('data-tooltip');
                const rect = this.getBoundingClientRect();
                
                // Crear tooltip
                const tooltipEl = document.createElement('div');
                tooltipEl.textContent = tooltip;
                tooltipEl.style.cssText = `
                    position: fixed;
                    top: ${rect.top - 40}px;
                    left: ${rect.left + rect.width / 2}px;
                    transform: translateX(-50%);
                    background: #333;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    white-space: nowrap;
                    z-index: 10000;
                    pointer-events: none;
                `;
                document.body.appendChild(tooltipEl);
                
                // Remover tooltip al salir
                const removeTooltip = () => {
                    tooltipEl.remove();
                    this.removeEventListener('mouseleave', removeTooltip);
                };
                this.addEventListener('mouseleave', removeTooltip);
            });
        });
    }, 100);
    
    // Cargar valores al abrir el modal
    let allValuesLoaded = false;
    let allValues = [];
    // Mostrar mensaje de carga
    const filterList = document.getElementById('filterListInsumos');
    filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Cargando...</p>';
    
    // Obtener valores del backend
    fetch(`/insumos/api/filtros/${column}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allValues = data.valores;
                allValuesLoaded = true;
                // Renderizar primeros 15 valores
                renderFilterValues(allValues, '', column);
            } else {
                filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
            }
        })
        .catch(error => {
            filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
        });
    
    // Agregar busqueda
    document.getElementById('filterSearchInsumos').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        
        // Si ya tenemos los valores, filtrar
        if (allValuesLoaded) {
            renderFilterValues(allValues, searchTerm, column);
        }
    });
}

function renderFilterValues(values, searchTerm, column) {
    const filterList = document.getElementById('filterListInsumos');
    const urlParams = new URLSearchParams(window.location.search);
    const filterColumns = urlParams.getAll('filter_columns[]') || [];
    const filterValuesArray = urlParams.getAll('filter_values[]') || [];
    
    // Mapeo de estados para display
    const estadoMap = {
        'PENDIENTE_SUPERVISOR': 'Pendiente Supervisor',
        'PENDIENTE_INSUMOS': 'Pendiente Insumos',
        'pendiente_cartera': 'Pendiente Cartera',
        'RECHAZADO_CARTERA': 'Rechazado Cartera',
        'DEVUELTO_A_ASESORA': 'Devuelto a Asesora'
    };
    
    // Convertir valores a display si es estado
    const displayMappedValues = values.map(val => {
        if (column === 'estado' && estadoMap[val]) {
            return { db: val, display: estadoMap[val] };
        }
        return { db: val, display: val };
    });
    
    // Filtrar valores segun busqueda
    let filteredValues = displayMappedValues.filter(valObj => {
        // Convertir a string si no lo es
        const valStr = String(valObj.display || '').trim();
        return valStr.length > 0 && valStr.toLowerCase().includes(searchTerm.toLowerCase());
    });
    
    if (filteredValues.length === 0) {
        filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No se encontraron resultados</p>';
        return;
    }
    
    // Si no hay busqueda, mostrar solo los primeros 15
    const displayValues = searchTerm === '' ? filteredValues.slice(0, 15) : filteredValues;
    
    // Mostrar informacion de cuantos valores hay
    let totalText = '';
    if (searchTerm === '' && filteredValues.length > 15) {
        totalText = `<p style="text-align: center; color: #666; padding: 10px; font-size: 12px;">Mostrando ${Math.min(15, filteredValues.length)} de ${filteredValues.length} valores. Busca para ver mas.</p>`;
    }
    
    // Renderizar checkboxes
    filterList.innerHTML = totalText + displayValues.map(valObj => {
        // Usar el valor de la BD para el comparador
        const dbVal = String(valObj.db || '').trim();
        const displayVal = String(valObj.display || '').trim();
        
        // Buscar si este valor esta en los filtros del MISMO TIPO DE COLUMNA
        let isChecked = false;
        filterColumns.forEach((col, idx) => {
            if (col === column && filterValuesArray[idx] === dbVal) {
                isChecked = true;
            }
        });
        
        return `
            <label style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-radius: 4px; transition: background 0.2s; hover: background-color: #f3f4f6;">
                <input type="checkbox" value="${dbVal}" class="filter-checkbox" ${isChecked ? 'checked' : ''} style="margin-right: 10px; cursor: pointer;">
                <span style="flex: 1;">${displayVal}</span>
            </label>
        `;
    }).join('');
}

// ===== FILTER OPERATIONS =====

function selectAllFilters() {
    document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = true);
}

function deselectAllFilters() {
    document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
}

function clearAllFilters() {
    // Mostrar todas las filas
    document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
    document.getElementById('filterModalInsumos').style.display = 'none';
}

function clearAllTableFilters() {
    // Redirigir a la pagina sin filtros (asume que existe route)
    const baseUrl = window.location.pathname.split('?')[0];
    window.location.href = baseUrl;
}

function applyFilters() {
    const selected = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        // Si no hay seleccion, ir a la pagina sin filtros
        const baseUrl = window.location.pathname.split('?')[0];
        window.location.href = baseUrl;
    } else {
        // Obtener filtros existentes de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const existingFilters = {};
        
        // Recopilar filtros existentes
        const filterColumns = urlParams.getAll('filter_columns[]') || [];
        const filterValuesArray = urlParams.getAll('filter_values[]') || [];
        
        // Reconstruir objeto de filtros existentes
        filterColumns.forEach((col, idx) => {
            if (!existingFilters[col]) {
                existingFilters[col] = [];
            }
            if (filterValuesArray[idx]) {
                existingFilters[col].push(filterValuesArray[idx]);
            }
        });
        
        // Actualizar filtros con los nuevos valores seleccionados
        existingFilters[currentFilterColumn] = selected;
        
        // Construir nueva URL con los filtros
        const newUrl = new URL(window.location);
        newUrl.search = '';

        const columnas = Object.keys(existingFilters).filter(col => Array.isArray(existingFilters[col]) && existingFilters[col].length > 0);
        if (columnas.length === 1 && existingFilters[columnas[0]].length === 1) {
            // URL corta para caso simple de 1 columna / 1 valor
            newUrl.searchParams.set('filter_column', columnas[0]);
            newUrl.searchParams.set('filter_value', existingFilters[columnas[0]][0]);
        } else {
            // Formato multi-filtro
            columnas.forEach(col => {
                existingFilters[col].forEach(val => {
                    newUrl.searchParams.append('filter_columns[]', col);
                    newUrl.searchParams.append('filter_values[]', val);
                });
            });
        }
        
        // Navegar a la nueva URL
        window.location.href = newUrl.toString();
    }
}

// ===== INIT EVENT LISTENERS =====

function initFilterManagerInsumos() {
    // Setup filter button listeners
    document.querySelectorAll('.filter-btn-insumos').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const column = this.getAttribute('data-column');
            currentFilterColumn = column;
            currentFilterValues = [];
            showFilterModal(column, []);
        });
    });
    
    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.filterManager = {
        showFilterModal,
        renderFilterValues,
        selectAllFilters,
        deselectAllFilters,
        clearAllFilters,
        clearAllTableFilters,
        applyFilters,
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFilterManagerInsumos);
} else {
    initFilterManagerInsumos();
}
