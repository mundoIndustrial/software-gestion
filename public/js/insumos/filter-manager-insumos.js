/**
 * Filter Manager for Insumos/Materiales Module (LOCAL STATE - WITHOUT URL)
 * Handles filter modal and filter operations in-memory
 * Similar to RecibosModule FilterModule - filters applied to table locally
 */

let currentFilterColumn = null;
let activeFilters = {}; // Estado local de filtros activos: { column: [value1, value2, ...] }
let allTableRows = []; // Cache de todas las filas de la tabla para referencia
let allValuesLoaded = false; // Estado de carga de valores para modal
let allValues = []; // Cache de valores para el modal actual
let isGlobalFilter = false; // Indica si el filtro actual es global (area/estado)

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

const columnMap = {
    'consecutivo_actual': 1,
    'numero_pedido': 2,
    'cliente': 3,
    'estado': 4,
    'area': 5,
    'created_at': 6
};

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

    // Usar siempre los valores que vienen del backend (no usar predefinidos)
    let displayValues = values;
    
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
    
    // Mostrar mensaje de carga
    const filterList = document.getElementById('filterListInsumos');
    filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Cargando...</p>';
    
    // Reset valores globales
    allValuesLoaded = false;
    allValues = [];
    isGlobalFilter = false;
    
    // TODOS los filtros cargan desde backend (permite buscar en todas las páginas)
    // Cargar desde backend para poder buscar en todas las páginas
    fetch(`/insumos/api/filtros/${column}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allValues = data.valores;
                allValuesLoaded = true;
                renderFilterValues(allValues, '', column);
            } else {
                filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
            }
        })
        .catch(error => {
            filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
        });
    
    // Agregar busqueda
    const searchInput = document.getElementById('filterSearchInsumos');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Filtrar localmente en los valores cargados
            if (allValuesLoaded) {
                renderFilterValues(allValues, searchTerm, column);
            }
        });
    }
}

/**
 * Busca valores en todas las páginas cuando el usuario escribe en columnas locales
 */
function searchFilterInBackend(column, searchTerm) {
    if (searchTerm.length < 1) {
        return;
    }
    
    const filterList = document.getElementById('filterListInsumos');
    filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Buscando en todas las páginas...</p>';
    
    // Hacer fetch para buscar en backend
    fetch(`/insumos/api/filtros/${column}?search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allValues = data.valores || [];
                renderFilterValues(allValues, '', column);
            } else {
                filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al buscar valores</p>';
            }
        })
        .catch(error => {
            filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al buscar valores</p>';
        });
}

/**
 * Obtiene opciones de filtro dinámicamente desde la tabla O backend
 * Para area/estado: obtiene TODOS los valores del backend
 * Para otras columnas: obtiene solo los 5 primeros de la página actual
 */
function getDynamicFilterOptions(column) {
    // Columnas que cargan todos los valores globales
    const globalFilterColumns = ['area', 'estado'];
    
    // Si es área o estado, cargar desde backend
    if (globalFilterColumns.includes(column)) {
        return { isGlobal: true, column: column };
    }
    
    // Para otras columnas: cargar solo los 5 primeros de la tabla actual
    const tbody = document.querySelector('table tbody');
    if (!tbody) {
        console.warn('[Filter] No se encontró tbody');
        return [];
    }
    
    const options = new Set();
    const columnIndex = columnMap[column];
    
    if (columnIndex === undefined) {
        console.warn('[Filter] Columna no mapeada:', column);
        return [];
    }
    
    const rows = tbody.querySelectorAll('tr');
    let count = 0;
    
    // Obtener solo los primeros 5 valores
    rows.forEach(row => {
        if (count >= 5) return;
        
        const cells = row.querySelectorAll('td');
        
        if (cells.length > columnIndex) {
            let cellValue = cells[columnIndex].textContent.trim();
            
            // Limpiar el valor
            cellValue = cellValue.replace(/\s+/g, ' ').trim();
            
            // No agregar valores vacíos, "N/A" o "-"
            if (cellValue && cellValue !== 'N/A' && cellValue !== '-' && cellValue.length > 0) {
                options.add(cellValue);
                count++;
            }
        }
    });
    
    // Retornar como array ordenado
    return Array.from(options).sort();
}

function renderFilterValues(values, searchTerm, column) {
    const filterList = document.getElementById('filterListInsumos');
    
    // Convertir valores a objetos con propiedades db y display
    const displayMappedValues = values.map(val => {
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
    
    // Renderizar checkboxes - marcar si está en el estado local de filtros activos
    filterList.innerHTML = totalText + displayValues.map(valObj => {
        const dbVal = String(valObj.db || '').trim();
        const displayVal = String(valObj.display || '').trim();
        
        // Verificar si este valor está en los filtros activos locales
        const isChecked = activeFilters[column] && activeFilters[column].includes(dbVal);
        
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

function clearAllTableFilters() {
    console.log('[Filter] Limpiando todos los filtros');
    
    // Limpiar todos los filtros locales
    activeFilters = {};
    
    // Construir URL sin filtros, reseteando a página 1
    const baseUrl = window.location.pathname;
    const url = new URL(baseUrl, window.location.origin);
    
    // Siempre empezar en página 1 al limpiar filtros
    url.searchParams.set('page', '1');
    
    // Conservar parámetro de búsqueda si existe
    const urlParams = new URLSearchParams(window.location.search);
    const search = urlParams.get('search');
    if (search) {
        url.searchParams.set('search', search);
    }
    
    console.log('[Filter] URL después de limpiar:', url.toString());
    
    // Aplicar cambios por AJAX (sin filtros)
    applyFiltersViaAjax(url.toString());
    
    // Actualizar badges
    updateFilterBadges();
}

function applyFilters() {
    // Obtener valores seleccionados del modal
    const selected = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(cb => cb.value);
    
    // Actualizar estado local de filtros
    if (selected.length === 0) {
        // Si no hay seleccionados, eliminar este filtro
        delete activeFilters[currentFilterColumn];
    } else {
        // Guardar en estado local
        activeFilters[currentFilterColumn] = selected;
    }
    
    // Construir URL con los filtros activos
    const url = buildFilterUrl();
    
    // Aplicar filtros por AJAX (traer datos del backend)
    applyFiltersViaAjax(url);
    
    // Actualizar badges en los botones de filtro
    updateFilterBadges();
    
    // Cerrar modal
    const modal = document.getElementById('filterModalInsumos');
    if (modal) {
        modal.style.display = 'none';
    }
    
    console.log('[Filter] Filtros aplicados:', activeFilters);
}

/**
 * Construye la URL con los parámetros de filtro
 */
function buildFilterUrl() {
    const baseUrl = window.location.pathname;
    const url = new URL(baseUrl, window.location.origin);
    
    // Conservar parámetro de búsqueda si existe
    const urlParams = new URLSearchParams(window.location.search);
    const search = urlParams.get('search');
    if (search) {
        url.searchParams.set('search', search);
    }
    
    // Agregar filtros
    Object.entries(activeFilters).forEach(([column, values]) => {
        if (values.length > 0) {
            values.forEach(val => {
                url.searchParams.append('filter_columns[]', column);
                url.searchParams.append('filter_values[]', val);
            });
        }
    });
    
    return url.toString();
}

/**
 * Aplica filtros por AJAX trayendo datos del backend
 */
async function applyFiltersViaAjax(url) {
    try {
        console.log('[Filter] Aplicando filtros por AJAX:', url);
        
        // Enviar petición AJAX
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        // Parsear HTML
        const html = await response.text();
        
        // Actualizar tabla con los resultados
        updateTableFromHtml(html);
        
        // Actualizar URL sin recargar
        window.history.pushState({ filter: url }, '', url);
        
        console.log('[Filter] Filtros aplicados exitosamente');
        
    } catch (error) {
        console.error('[Filter] Error aplicando filtros:', error);
        alert('Error al aplicar filtros: ' + error.message);
    }
}

/**
 * Actualiza tabla desde HTML (respuesta AJAX)
 */
function updateTableFromHtml(html) {
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Actualizar tabla
        const newTable = doc.querySelector('table');
        const currentTable = document.querySelector('table');
        
        if (newTable && currentTable) {
            const newTbody = newTable.querySelector('tbody');
            const currentTbody = currentTable.querySelector('tbody');
            
            if (newTbody && currentTbody) {
                currentTbody.innerHTML = newTbody.innerHTML;
                console.log('[Filter] Tabla actualizada');
            }
        }
        
        // Actualizar paginación (puede estar en #tablePagination o #paginationControls)
        let currentPagination = document.querySelector('#tablePagination');
        if (!currentPagination) {
            currentPagination = document.querySelector('#paginationControls');
        }
        
        if (currentPagination) {
            let newPagination = doc.querySelector('#tablePagination');
            if (!newPagination) {
                newPagination = doc.querySelector('#paginationControls');
            }
            
            if (newPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
                console.log('[Filter] Paginación actualizada');
            }
        }
        
        // Disparar evento para reinicializar eventos
        const event = new CustomEvent('insumosTableUpdated', {
            detail: { action: 'filter' }
        });
        document.dispatchEvent(event);
        
    } catch (error) {
        console.error('[Filter] Error actualizando tabla:', error);
        throw error;
    }
}

/**
 * Aplica los filtros activos a la tabla filtrando filas localmente
 */
function applyTableFilters() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) {
        console.warn('[Filter] No se encontró tbody de tabla');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let isVisible = true;
        
        // Verificar cada filtro activo
        for (const [column, selectedValues] of Object.entries(activeFilters)) {
            if (selectedValues.length === 0) continue;
            
            const columnIndex = columnMap[column];
            if (columnIndex === undefined || cells.length <= columnIndex) {
                console.warn(`[Filter] Columna ${column} no encontrada o índice inválido`);
                continue;
            }
            
            const cellText = cells[columnIndex].textContent.trim();
            
            // Si el valor de esta celda no coincide con ninguno de los valores seleccionados, ocultar fila
            const matches = selectedValues.some(val => {
                return cellText.includes(val) || cellText === val;
            });
            
            if (!matches) {
                isVisible = false;
                break;
            }
        }
        
        // Mostrar u ocultar fila
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
    
    console.log(`[Filter] Filtrado: ${visibleCount}/${rows.length} filas visibles`);
}

/**
 * Actualiza los badges de filtro en los botones de filtro de las columnas
 */
function updateFilterBadges() {
    // Buscar todos los botones de filtro
    const filterButtons = document.querySelectorAll('.filter-btn-insumos');
    
    let hasAnyFilters = false;
    
    filterButtons.forEach(btn => {
        const column = btn.getAttribute('data-column');
        
        // Remover badge anterior si existe
        let existingBadge = btn.querySelector('.filter-badge');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // Si hay filtros activos para esta columna, agregar badge
        if (activeFilters[column] && activeFilters[column].length > 0) {
            hasAnyFilters = true;
            btn.classList.add('has-filter');
            
            const badge = document.createElement('span');
            badge.className = 'filter-badge';
            badge.textContent = activeFilters[column].length;
            badge.style.cssText = `
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: -8px;
                right: -8px;
                background: #ef4444;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            `;
            btn.style.position = 'relative';
            btn.appendChild(badge);
        } else {
            btn.classList.remove('has-filter');
        }
    });
    
    // Mostrar/ocultar botón flotante de "Limpiar Filtros"
    const floatingBtn = document.getElementById('btnClearAllFiltersFloating');
    if (floatingBtn) {
        if (hasAnyFilters) {
            floatingBtn.style.display = 'flex';
        } else {
            floatingBtn.style.display = 'none';
        }
    }
}

// ===== INIT EVENT LISTENERS =====

function initFilterManagerInsumos() {
    // Cache de todas las filas de la tabla para referencia
    const tbody = document.querySelector('table tbody');
    if (tbody) {
        allTableRows = Array.from(tbody.querySelectorAll('tr'));
    }
    
    // Setup filter button listeners usando Event Delegation
    document.addEventListener('click', function(e) {
        // Filtro: verificar si el click fue en un botón de filtro
        let filterBtn = e.target.closest('.filter-btn-insumos');
        if (filterBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const column = filterBtn.getAttribute('data-column');
            currentFilterColumn = column;
            showFilterModal(column, []);
            return;
        }
        
        // Modal: cerrar modal si se hace click al botón de cerrar
        if (e.target.closest('[data-insumos-action="filter-close-modal"]')) {
            e.preventDefault();
            const modal = document.getElementById('filterModalInsumos');
            if (modal) modal.style.display = 'none';
            return;
        }
        
        // Modal: aplicar filtros si se hace click al botón "Aplicar"
        if (e.target.closest('[data-insumos-action="filter-apply"]')) {
            e.preventDefault();
            applyFilters();
            return;
        }
        
        // Modal: seleccionar todos
        if (e.target.closest('[data-insumos-action="filter-select-all"]')) {
            e.preventDefault();
            selectAllFilters();
            return;
        }
        
        // Modal: deseleccionar todos
        if (e.target.closest('[data-insumos-action="filter-deselect-all"]')) {
            e.preventDefault();
            deselectAllFilters();
            return;
        }
    }, true);
    
    // Actualizar badges inicialmente
    updateFilterBadges();
    
    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.filterManager = {
        showFilterModal,
        renderFilterValues,
        selectAllFilters,
        deselectAllFilters,
        clearAllTableFilters,
        applyFilters,
        applyTableFilters,
        updateFilterBadges,
        activeFilters
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFilterManagerInsumos);
} else {
    initFilterManagerInsumos();
}
