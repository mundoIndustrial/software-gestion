/**
 * FILTER SYSTEM - Sistema de filtros para tabla de órdenes
 * Permite filtrar por cualquier columna con modal interactivo
 */



let currentFilterColumn = null;
let activeFilters = {};

/**
 * Guardar filtros en localStorage
 */
function saveFiltersToStorage() {
    try {
        localStorage.setItem('activeFilters', JSON.stringify(activeFilters));

    } catch (error) {

    }
}

/**
 * Cargar filtros desde localStorage
 */
function loadFiltersFromStorage() {
    try {
        const stored = localStorage.getItem('activeFilters');
        if (stored) {
            activeFilters = JSON.parse(stored);

            return true;
        }
    } catch (error) {

    }
    return false;
}

/**
 * Limpiar filtros de localStorage
 */
function clearFiltersFromStorage() {
    try {
        localStorage.removeItem('activeFilters');

    } catch (error) {

    }
}

/**
 * Opciones de áreas disponibles (sincronizadas con AreaOptions.php)
 */
const AREA_OPTIONS = [
    'Creación de Orden',
    'Control Calidad',
    'Entrega',
    'Despacho',
    'Insumos y Telas',
    'Costura',
    'Corte',
    'Bordado',
    'Estampado',
    'Lavandería',
    'Arreglos'
];

/**
 * Generar HTML de opciones de área
 */
function getAreaOptionsHtml(selectedArea) {
    return AREA_OPTIONS.map(area => 
        `<option value="${area}" ${selectedArea === area ? 'selected' : ''}>${area}</option>`
    ).join('');
}

/**
 * Obtener valores únicos de una columna
 */
function getColumnValues(columnIndex) {
    const rows = document.querySelectorAll('.table-row');
    const values = new Set();
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('.table-cell');
        if (cells[columnIndex]) {
            const text = cells[columnIndex].textContent.trim();
            if (text) values.add(text);
        }
    });
    
    return Array.from(values).sort();
}

/**
 * Mapeo de columnas - Índices basados en la estructura de la tabla
 */
const columnMap = {
    'acciones': 0,
    'estado': 1,
    'dia_entrega': 2,
    'total_dias': 3,
    'pedido': 4,
    'cliente': 5,
    'descripcion': 6,
    'cantidad': 7,
    'novedades': 8,
    'asesor': 9,
    'forma_pago': 10,
    'fecha_creacion': 11,
    'fecha_estimada': 12,
    'encargado': 13
};

/**
 * Obtener descripciones únicas (sin duplicados)
 */
function getDescripcionesWithPedidoIds() {
    const rows = document.querySelectorAll('.table-row');
    const descripcionesMap = new Map();
    
    rows.forEach(row => {
        const pedidoId = row.getAttribute('data-orden-id');
        const descCell = row.querySelector('.table-cell:nth-child(7)');
        
        if (pedidoId && descCell) {
            const descripcion = descCell.textContent.trim();
            if (descripcion && descripcion !== '-') {
                // Agrupar pedidos por descripción
                if (!descripcionesMap.has(descripcion)) {
                    descripcionesMap.set(descripcion, []);
                }
                descripcionesMap.get(descripcion).push(pedidoId);
            }
        }
    });
    
    // Convertir a array de opciones
    const descripciones = [];
    descripcionesMap.forEach((pedidos, descripcion) => {
        descripciones.push({
            display: descripcion,
            value: pedidos.join(',') // Guardar todos los pedidos con esa descripción
        });
    });
    
    return descripciones;
}

/**
 * Obtener valores únicos de una columna de forma más robusta
 */
function getColumnValuesByName(columnName) {
    const rows = document.querySelectorAll('.table-row');
    const values = new Set();
    
    rows.forEach(row => {
        let cellText = '';
        
        // Mapeo específico para cada columna
        switch(columnName) {
            case 'estado':
                const estadoSelect = row.querySelector('.estado-dropdown');
                cellText = estadoSelect ? estadoSelect.value : '';
                break;
            case 'area':
                const areaSelect = row.querySelector('.area-dropdown');
                cellText = areaSelect ? areaSelect.value : '';
                break;
            case 'dia_entrega':
                const diaSelect = row.querySelector('.dia-entrega-dropdown');
                cellText = diaSelect ? diaSelect.value : '';
                break;
            case 'pedido':
                const pedidoCell = row.querySelector('.table-cell:nth-child(5)');
                cellText = pedidoCell ? pedidoCell.textContent.trim() : '';
                break;
            case 'cliente':
                const clienteCell = row.querySelector('.table-cell:nth-child(6)');
                cellText = clienteCell ? clienteCell.textContent.trim() : '';
                break;
            case 'descripcion':
                const descCell = row.querySelector('.table-cell:nth-child(7)');
                cellText = descCell ? descCell.textContent.trim() : '';
                break;
            case 'cantidad':
                const cantCell = row.querySelector('.table-cell:nth-child(8)');
                cellText = cantCell ? cantCell.textContent.trim() : '';
                break;
            case 'novedades':
                const novCell = row.querySelector('.table-cell:nth-child(9)');
                cellText = novCell ? novCell.textContent.trim() : '';
                break;
            case 'asesor':
                const asesorCell = row.querySelector('.table-cell:nth-child(10)');
                cellText = asesorCell ? asesorCell.textContent.trim() : '';
                break;
            case 'forma_pago':
                const pagoCell = row.querySelector('.table-cell:nth-child(11)');
                cellText = pagoCell ? pagoCell.textContent.trim() : '';
                break;
            case 'fecha_creacion':
                const fcreacionCell = row.querySelector('.table-cell:nth-child(12)');
                cellText = fcreacionCell ? fcreacionCell.textContent.trim() : '';
                break;
            case 'fecha_estimada':
                const festimadaCell = row.querySelector('.table-cell:nth-child(13)');
                cellText = festimadaCell ? festimadaCell.textContent.trim() : '';
                break;
            case 'encargado':
                const encargadoCell = row.querySelector('.table-cell:nth-child(14)');
                cellText = encargadoCell ? encargadoCell.textContent.trim() : '';
                break;
            default:
                const cells = row.querySelectorAll('.table-cell');
                const columnIndex = columnMap[columnName];
                if (cells[columnIndex]) {
                    cellText = cells[columnIndex].textContent.trim();
                }
        }
        
        if (cellText) values.add(cellText);
    });
    
    return Array.from(values).sort();
}

/**
 * Obtener opciones de filtro del servidor
 */
async function getFilterOptionsFromServer() {
    try {
        const response = await fetch('/registros/filter-options');
        const data = await response.json();
        return data.options || {};
    } catch (error) {

        return {};
    }
}

/**
 * Obtener opciones de una columna específica (paginadas)
 */
async function getColumnOptionsFromServer(column, page = 1, limit = 25, search = '') {
    try {
        let url = `/registros/filter-column-options/${column}?page=${page}&limit=${limit}`;
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            return {
                options: data.options || [],
                total: data.total || 0,
                page: data.page || 1,
                limit: data.limit || 25
            };
        }
        return { options: [], total: 0, page: 1, limit: 25 };
    } catch (error) {

        return { options: [], total: 0, page: 1, limit: 25 };
    }
}

/**
 * Abrir modal de filtro
 */
async function openFilterModal(column) {
    currentFilterColumn = column;
    
    // Actualizar título
    const titleMap = {
        'acciones': 'Acciones',
        'estado': 'Estado',
        'area': 'Área',
        'dia_entrega': 'Día de entrega',
        'total_dias': 'Total de días',
        'pedido': 'Pedido',
        'cliente': 'Cliente',
        'descripcion': 'Descripción',
        'cantidad': 'Cantidad',
        'novedades': 'Novedades',
        'asesor': 'Asesor',
        'forma_pago': 'Forma de pago',
        'fecha_creacion': 'Fecha de creación',
        'fecha_estimada': 'Fecha estimada',
        'encargado': 'Encargado'
    };
    
    document.getElementById('filterModalTitle').textContent = 'Filtrar por ' + (titleMap[column] || column);
    
    // Mostrar modal
    document.getElementById('filterModalOverlay').classList.add('active');
    
    // Mostrar loading
    const filterOptions = document.getElementById('filterOptions');
    filterOptions.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">Cargando opciones...</div>';
    
    // Obtener búsqueda actual del NavSearch si existe
    const currentSearch = window.NavSearch?.state?.currentQuery || '';
    
    // Obtener primeras 25 opciones del servidor
    const result = await getColumnOptionsFromServer(column, 1, 25, '');
    
    // Guardar estado globalmente
    window.currentFilterState = {
        column: column,
        total: result.total,
        currentPage: 1,
        limit: 25,
        searchTerm: ''
    };
    
    // Renderizar opciones
    renderFilterOptions(result.options, result.total);
    
    // Agregar listener al search
    const searchInput = document.getElementById('filterSearch');
    searchInput.value = '';
    searchInput.removeEventListener('input', filterSearchOptions);
    searchInput.addEventListener('input', filterSearchOptions);
    
    // Mostrar búsqueda actual en el input del filtro si existe
    if (currentSearch) {
        searchInput.value = currentSearch;
        searchInput.style.backgroundColor = '#fef3c7';
        searchInput.style.borderColor = '#f59e0b';
        
        // Mostrar indicador de búsqueda activa
        const indicator = document.createElement('div');
        indicator.style.cssText = 'padding: 8px 12px; background: #fef3c7; border-left: 4px solid #f59e0b; margin-bottom: 12px; border-radius: 4px; font-size: 13px; color: #92400e;';
        indicator.textContent = `📌 Búsqueda activa: "${currentSearch}"`;
        filterOptions.parentElement.insertBefore(indicator, filterOptions);
    }
    

}

/**
 * Renderizar opciones en el modal
 */
function renderFilterOptions(options, total) {
    const filterOptions = document.getElementById('filterOptions');
    filterOptions.innerHTML = '';
    
    if (!options || options.length === 0) {
        filterOptions.innerHTML = '<div style="padding: 20px; text-align: center; color: #9ca3af;">No hay opciones disponibles</div>';
        return;
    }
    
    // Agregar botón "Seleccionar todas"
    const selectAllContainer = document.createElement('div');
    selectAllContainer.style.cssText = 'padding: 12px; border-bottom: 1px solid #e5e7eb; margin-bottom: 8px;';
    
    const selectAllBtn = document.createElement('button');
    selectAllBtn.type = 'button';
    selectAllBtn.className = 'btn-select-all';
    selectAllBtn.textContent = 'Seleccionar todas';
    selectAllBtn.style.cssText = `
        width: 100%;
        padding: 8px 12px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    `;
    
    selectAllBtn.addEventListener('mouseover', () => {
        selectAllBtn.style.boxShadow = '0 4px 8px rgba(59, 130, 246, 0.3)';
        selectAllBtn.style.transform = 'translateY(-1px)';
    });
    
    selectAllBtn.addEventListener('mouseout', () => {
        selectAllBtn.style.boxShadow = '0 2px 4px rgba(59, 130, 246, 0.2)';
        selectAllBtn.style.transform = 'translateY(0)';
    });
    
    selectAllBtn.addEventListener('click', () => {
        const checkboxes = filterOptions.querySelectorAll('.filter-option input[type="checkbox"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        // Toggle: si todas están seleccionadas, deseleccionar; si no, seleccionar todas
        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        
        // Cambiar texto del botón
        selectAllBtn.textContent = allChecked ? 'Seleccionar todas' : 'Deseleccionar todas';
    });
    
    selectAllContainer.appendChild(selectAllBtn);
    filterOptions.appendChild(selectAllContainer);
    
    options.forEach(item => {
        // Manejar tanto strings como objetos con display/value
        const displayText = typeof item === 'object' ? item.display : item;
        const checkboxValue = typeof item === 'object' ? item.value : item;
        
        const option = document.createElement('div');
        option.className = 'filter-option';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = `filter-${checkboxValue}`;
        checkbox.value = checkboxValue;
        checkbox.checked = activeFilters[currentFilterColumn]?.includes(checkboxValue) || false;
        
        // Listener para actualizar estado del botón "Seleccionar todas"
        checkbox.addEventListener('change', () => {
            const allCheckboxes = filterOptions.querySelectorAll('.filter-option input[type="checkbox"]');
            const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
            selectAllBtn.textContent = allChecked ? 'Deseleccionar todas' : 'Seleccionar todas';
        });
        
        const label = document.createElement('label');
        label.htmlFor = `filter-${checkboxValue}`;
        label.textContent = displayText;
        
        option.appendChild(checkbox);
        option.appendChild(label);
        filterOptions.appendChild(option);
    });
    
    // Mostrar información de opciones
    if (total > 25) {
        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'padding: 10px; text-align: center; color: #6b7280; font-size: 0.85rem; margin-top: 8px;';
        infoDiv.textContent = `Mostrando 25 de ${total} opciones. Usa la búsqueda para filtrar.`;
        filterOptions.appendChild(infoDiv);
    }
}

/**
 * Filtrar opciones de búsqueda en tiempo real
 */
async function filterSearchOptions(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const filterOptions = document.getElementById('filterOptions');
    
    // Actualizar estado
    if (window.currentFilterState) {
        window.currentFilterState.searchTerm = searchTerm;
        window.currentFilterState.currentPage = 1;
    }
    
    // Si no hay término de búsqueda, mostrar 25 opciones iniciales
    if (!searchTerm) {
        const result = await getColumnOptionsFromServer(currentFilterColumn, 1, 25, '');
        renderFilterOptions(result.options, result.total);
        return;
    }
    
    // Buscar en el servidor
    const result = await getColumnOptionsFromServer(currentFilterColumn, 1, 100, searchTerm);
    
    if (result.options.length === 0) {
        filterOptions.innerHTML = '<div style="padding: 20px; text-align: center; color: #9ca3af;">No hay opciones que coincidan con la búsqueda</div>';
    } else {
        renderFilterOptions(result.options, result.total);
        
        // Mostrar contador de resultados
        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'padding: 10px; text-align: center; color: #6b7280; font-size: 0.85rem;';
        infoDiv.textContent = `${result.options.length} opciones encontradas`;
        filterOptions.appendChild(infoDiv);
    }
}

/**
 * Cerrar modal de filtro
 */
function closeFilterModal() {
    document.getElementById('filterModalOverlay').classList.remove('active');
    currentFilterColumn = null;

}

/**
 * Aplicar filtros
 */
async function applyFilters() {
    const selectedValues = Array.from(document.querySelectorAll('#filterOptions input[type="checkbox"]:checked'))
        .map(cb => cb.value);
    
    if (selectedValues.length > 0) {
        activeFilters[currentFilterColumn] = selectedValues;

    } else {
        delete activeFilters[currentFilterColumn];
    }
    
    // Guardar filtros en localStorage
    saveFiltersToStorage();
    
    // Actualizar badge
    updateFilterBadges();
    
    // Actualizar visibilidad del botón flotante
    updateClearButtonVisibility();
    
    // Enviar filtros al backend
    await applyFiltersToBackend();
    
    closeFilterModal();
}

/**
 * Enviar filtros al backend y obtener resultados paginados
 */
async function applyFiltersToBackend(page = 1) {
    try {
        console.log(' Enviando al backend:', {
            filters: activeFilters,
            page: page,
            filtrosActivos: Object.keys(activeFilters).length > 0
        });
        
        const response = await fetch('/registros/filter-orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                filters: activeFilters,
                page: page
            })
        });

        const result = await response.json();

        if (result.success) {
            // Renderizar tabla con resultados
            renderFilteredTable(result.data);
            
            // Actualizar paginación


            updatePaginationInfo(result.pagination);
            
            // Actualizar URL con filtros
            updateUrlWithFilters(page);
            

        } else {

        }
    } catch (error) {

    }
}

/**
 * Actualizar URL con los filtros actuales
 */
function updateUrlWithFilters(page = 1) {
    const params = new URLSearchParams();
    
    // Agregar filtros a los parámetros
    Object.entries(activeFilters).forEach(([column, values]) => {
        if (values.length > 0) {
            let filterValue;
            
            // Para descripción, usar IDs de pedido separados por comas
            if (column === 'descripcion') {
                // Obtener los IDs de pedido de la tabla actual
                const pedidoIds = [];
                document.querySelectorAll('.table-row').forEach(row => {
                    const pedidoId = row.getAttribute('data-orden-id');
                    if (pedidoId) {
                        pedidoIds.push(pedidoId);
                    }
                });
                filterValue = pedidoIds.join(',');
                params.append('filter_pedido_ids', filterValue);
            } else {
                // Para otros filtros, usar separador |||FILTER_SEPARATOR|||
                filterValue = values.join('|||FILTER_SEPARATOR|||');
                params.append(`filter_${column}`, filterValue);
            }
        }
    });
    
    // Agregar página
    if (page > 1) {
        params.append('page', page);
    }
    
    // Actualizar URL sin recargar
    const newUrl = params.toString() ? `/registros?${params.toString()}` : '/registros';
    window.history.replaceState({}, '', newUrl);
}

/**
 * Renderizar tabla con datos filtrados
 */
function renderFilteredTable(ordenes) {
    const tableBody = document.getElementById('tablaOrdenesBody');
    
    if (!tableBody) {

        return;
    }

    // Limpiar tabla
    tableBody.innerHTML = '';

    if (ordenes.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.style.textAlign = 'center';
        emptyRow.style.padding = '20px';
        emptyRow.innerHTML = '<td colspan="14" style="padding: 20px;">No hay registros que coincidan con los filtros</td>';
        tableBody.appendChild(emptyRow);
        return;
    }

    // Renderizar cada orden con estructura HTML correcta
    ordenes.forEach(orden => {
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.setAttribute('data-orden-id', orden.numero_pedido);

        row.innerHTML = `
            <!-- Acciones -->
            <td class="table-cell acciones-column">
                <div class="cell-content">
                    <button class="action-view-btn" title="Ver detalles" data-orden-id="${orden.numero_pedido}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="action-menu" data-orden-id="${orden.numero_pedido}">
                        <a href="#" class="action-menu-item" data-action="detalle">
                            <i class="fas fa-eye"></i>
                            <span>Detalle</span>
                        </a>
                        <a href="#" class="action-menu-item" data-action="seguimiento">
                            <i class="fas fa-tasks"></i>
                            <span>Seguimiento</span>
                        </a>
                    </div>
                </div>
            </td>
            
            <!-- Estado -->
            <td class="table-cell">
                <div class="cell-content">
                    <select class="estado-dropdown estado-${orden.estado.toLowerCase().replace(/\s+/g, '-')}" data-orden-id="${orden.numero_pedido}">
                        <option value="Pendiente" ${orden.estado === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                        <option value="No iniciado" ${orden.estado === 'No iniciado' ? 'selected' : ''}>No iniciado</option>
                        <option value="En Ejecución" ${orden.estado === 'En Ejecución' ? 'selected' : ''}>En Ejecución</option>
                        <option value="Entregado" ${orden.estado === 'Entregado' ? 'selected' : ''}>Entregado</option>
                        <option value="Anulada" ${orden.estado === 'Anulada' ? 'selected' : ''}>Anulada</option>
                    </select>
                </div>
            </td>
            
            <!-- Día de entrega -->
            <td class="table-cell">
                <div class="cell-content">
                    <select class="dia-entrega-dropdown" data-orden-id="${orden.numero_pedido}">
                        <option value="">Seleccionar</option>
                        <option value="15" ${orden.dia_de_entrega == 15 ? 'selected' : ''}>15 días</option>
                        <option value="20" ${orden.dia_de_entrega == 20 ? 'selected' : ''}>20 días</option>
                        <option value="25" ${orden.dia_de_entrega == 25 ? 'selected' : ''}>25 días</option>
                        <option value="30" ${orden.dia_de_entrega == 30 ? 'selected' : ''}>30 días</option>
                    </select>
                </div>
            </td>
            
            <!-- Total de días -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.dias_habiles}</span>
                </div>
            </td>
            
            <!-- Pedido -->
            <td class="table-cell">
                <div class="cell-content">
                    <span style="font-weight: 600;">${orden.numero_pedido}</span>
                </div>
            </td>
            
            <!-- Cliente -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.cliente || '-'}</span>
                </div>
            </td>
            
            <!-- Descripción -->
            <td class="table-cell descripcion-cell">
                <div class="cell-content" style="cursor: pointer;" onclick="console.log('[ONCLICK TABLE CELL] 📌 Click en descripción'); event.stopPropagation(); abrirModalCeldaConFormato('Descripción', ${JSON.stringify(orden.prendas || [])})">
                    <span style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="Click para ver completo">
                        ${orden.descripcion ? orden.descripcion + ' <span style="color: #3b82f6; font-weight: 600;">...</span>' : '-'}
                    </span>
                </div>
            </td>
            
            <!-- Cantidad -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.cantidad || '-'}</span>
                </div>
            </td>
            
            <!-- Novedades -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.novedades || '-'}</span>
                </div>
            </td>
            
            <!-- Asesor -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.asesor}</span>
                </div>
            </td>
            
            <!-- Forma de pago -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.forma_de_pago || '-'}</span>
                </div>
            </td>
            
            <!-- Fecha de creación -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.fecha_creacion}</span>
                </div>
            </td>
            
            <!-- Fecha estimada -->
            <td class="table-cell fecha-estimada-cell" data-fecha-estimada="${orden.fecha_estimada}">
                <div class="cell-content">
                    <span class="fecha-estimada-span">${orden.fecha_estimada}</span>
                </div>
            </td>
            
            <!-- Encargado -->
            <td class="table-cell">
                <div class="cell-content">
                    <span>${orden.encargado}</span>
                </div>
            </td>
        `;

        tableBody.appendChild(row);
    });

    // Reinicializar event listeners
    initializeTableEventListeners();
    
    // Aplicar colores condicionales a las filas
    if (typeof updateRowConditionalColors === 'function') {
        updateRowConditionalColors();
    }
}

/**
 * Actualizar información de paginación
 */
function updatePaginationInfo(pagination) {
    const paginationContainer = document.querySelector('.pagination-info');
    if (paginationContainer) {
        paginationContainer.innerHTML = `
            Mostrando ${pagination.total} de ${pagination.total} registros
        `;
    }
    
    // Guardar información de paginación actual para referencia
    window.currentPagination = pagination;
    // Actualizar botones de paginación
    updatePaginationButtons(pagination);
}

/**
 * Actualizar botones de paginación
 */
function updatePaginationButtons(pagination) {
    // Buscar contenedor de paginación (usar #paginationControls que es el ID en la vista)
    const paginationContainer = document.querySelector('#paginationControls');
    if (!paginationContainer) return;

    // Limpiar paginación anterior
    paginationContainer.innerHTML = '';

    // Si solo hay 1 página, mostrar solo el número de página sin navegación
    if (pagination.last_page === 1) {
        const pageBtn = document.createElement('a');
        pageBtn.href = '#';
        pageBtn.className = 'pagination-btn page-number active';
        pageBtn.textContent = '1';
        pageBtn.style.pointerEvents = 'none'; // Deshabilitar click
        pageBtn.style.cursor = 'default';
        paginationContainer.appendChild(pageBtn);
        return;
    }

    // Botón Primera página
    if (pagination.current_page > 1) {
        const firstBtn = document.createElement('a');
        firstBtn.href = '#';
        firstBtn.className = 'pagination-btn';
        firstBtn.textContent = '<<';
        firstBtn.onclick = (e) => {
            e.preventDefault();
            applyFiltersToBackend(1);
        };
        paginationContainer.appendChild(firstBtn);
    }

    // Botón Página anterior
    if (pagination.current_page > 1) {
        const prevBtn = document.createElement('a');
        prevBtn.href = '#';
        prevBtn.className = 'pagination-btn';
        prevBtn.textContent = '<';
        prevBtn.onclick = (e) => {
            e.preventDefault();
            applyFiltersToBackend(pagination.current_page - 1);
        };
        paginationContainer.appendChild(prevBtn);
    }

    // Números de página
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('a');
        pageBtn.href = '#';
        pageBtn.className = i === pagination.current_page ? 'pagination-btn page-number active' : 'pagination-btn page-number';
        pageBtn.textContent = i;
        pageBtn.onclick = (e) => {
            e.preventDefault();
            if (i !== pagination.current_page) {
                applyFiltersToBackend(i);
            }
        };
        paginationContainer.appendChild(pageBtn);
    }

    // Botón Página siguiente
    if (pagination.current_page < pagination.last_page) {
        const nextBtn = document.createElement('a');
        nextBtn.href = '#';
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = '>';
        nextBtn.onclick = (e) => {
            e.preventDefault();
            applyFiltersToBackend(pagination.current_page + 1);
        };
        paginationContainer.appendChild(nextBtn);
    }

    // Botón Última página
    if (pagination.current_page < pagination.last_page) {
        const lastBtn = document.createElement('a');
        lastBtn.href = '#';
        lastBtn.className = 'pagination-btn';
        lastBtn.textContent = '>>';
        lastBtn.onclick = (e) => {
            e.preventDefault();

            applyFiltersToBackend(pagination.last_page);
        };
        paginationContainer.appendChild(lastBtn);

    }
}

/**
 * Inicializar event listeners de la tabla
 */
function initializeTableEventListeners() {
    // Aquí van los event listeners para botones de acción, dropdowns, etc.
    // Por ahora, solo un placeholder

}

/**
 * Limpiar filtros
 */
function resetFilters() {
    document.querySelectorAll('#filterOptions input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });

}

/**
 * Actualizar badges
 */
function updateFilterBadges() {
    // Primero, remover todos los badges y la clase has-filter
    document.querySelectorAll('.btn-filter-column').forEach(btn => {
        btn.classList.remove('has-filter');
        const badge = btn.querySelector('.filter-badge');
        if (badge) {
            badge.remove();
        }
    });
    
    // Luego, agregar badges solo para los filtros activos
    Object.entries(activeFilters).forEach(([column, values]) => {
        if (values.length > 0) {
            // Buscar el botón de filtro que corresponde a esta columna
            // usando el atributo onclick que contiene el nombre de la columna
            const btn = document.querySelector(`.btn-filter-column[onclick*="'${column}'"]`);
            
            if (btn) {
                btn.classList.add('has-filter');
                
                // Remover badge anterior si existe
                const oldBadge = btn.querySelector('.filter-badge');
                if (oldBadge) {
                    oldBadge.remove();
                }
                
                // Crear nuevo badge
                const badge = document.createElement('div');
                badge.className = 'filter-badge';
                badge.textContent = values.length;
                btn.appendChild(badge);
                

            } else {

            }
        }
    });
    

}

/**
 * Filtrar tabla
 */
function filterTable() {
    const rows = document.querySelectorAll('.table-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let showRow = true;
        
        Object.entries(activeFilters).forEach(([column, filterValues]) => {
            let cellText = '';
            
            // Obtener el valor de la celda según la columna
            switch(column) {
                case 'estado':
                    const estadoSelect = row.querySelector('.estado-dropdown');
                    cellText = estadoSelect ? estadoSelect.value : '';
                    break;
                case 'area':
                    const areaSelect = row.querySelector('.area-dropdown');
                    cellText = areaSelect ? areaSelect.value : '';
                    break;
                case 'dia_entrega':
                    const diaSelect = row.querySelector('.dia-entrega-dropdown');
                    cellText = diaSelect ? diaSelect.value : '';
                    break;
                default:
                    const columnIndex = columnMap[column];
                    const cell = row.querySelectorAll('.table-cell')[columnIndex];
                    cellText = cell ? cell.textContent.trim() : '';
            }
            
            // Verificar si el valor está en los filtros seleccionados
            if (cellText && !filterValues.some(v => cellText.includes(v))) {
                showRow = false;
            }
        });
        
        row.style.display = showRow ? '' : 'none';
        if (showRow) visibleCount++;
    });
    

}

/**
 * Cargar filtros desde URL
 */
function loadFiltersFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const page = parseInt(params.get('page')) || 1;
    
    // Extraer filtros de la URL
    const filters = {};
    
    // Manejar filter_pedido_ids (descripción)
    const pedidoIds = params.get('filter_pedido_ids');
    if (pedidoIds) {
        filters['descripcion'] = pedidoIds.split(',');
    }
    
    // Manejar otros filtros
    for (const [key, value] of params.entries()) {
        if (key.startsWith('filter_') && key !== 'filter_pedido_ids') {
            const column = key.replace('filter_', '');
            if (!filters[column]) {
                filters[column] = [];
            }
            
            // Soportar múltiples valores separados por |||FILTER_SEPARATOR|||
            const values = value.split('|||FILTER_SEPARATOR|||');
            filters[column] = filters[column].concat(values);
        }
    }
    
    // Si hay filtros en la URL, aplicarlos
    if (Object.keys(filters).length > 0) {
        activeFilters = filters;
        updateClearButtonVisibility();  // Mostrar botón flotante
        applyFiltersToBackend(page);
    }
}

/**
 * Mostrar/ocultar botón flotante de limpiar filtros
 */
function updateClearButtonVisibility() {
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (!clearBtn) return;
    
    const hasFilters = Object.keys(activeFilters).length > 0;
    
    if (hasFilters) {
        clearBtn.classList.add('visible');

    } else {
        clearBtn.classList.remove('visible');

    }
}

/**
 * Limpiar todos los filtros
 */
async function clearAllFilters() {
    // Limpiar objeto de filtros activos
    activeFilters = {};
    
    // Limpiar filtros de localStorage
    clearFiltersFromStorage();
    
    // Desmarcar todos los checkboxes del modal
    document.querySelectorAll('#filterOptions input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Actualizar badges
    updateFilterBadges();
    
    // Ocultar botón flotante
    updateClearButtonVisibility();
    
    // Cerrar modal si está abierto
    closeFilterModal();
    

    // Cargar tabla sin filtros (página 1)
    await applyFiltersToBackend(1);
    
    // Forzar actualización de URL para limpiar parámetros de filtro
    window.history.replaceState({}, '', '/registros');
}

/**
 * Inicializar sistema de filtros
 */
document.addEventListener('DOMContentLoaded', function() {
    const filterOverlay = document.getElementById('filterModalOverlay');
    
    if (filterOverlay) {
        // Cerrar modal al hacer click en overlay
        filterOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFilterModal();
            }
        });
        

    }
    
    // Cargar filtros desde localStorage
    if (loadFiltersFromStorage()) {

        // Actualizar badges con los filtros cargados
        updateFilterBadges();
        // Aplicar filtros al backend
        applyFiltersToBackend(1);
    }
    
    // Cargar filtros desde URL si existen
    loadFiltersFromUrl();
});

