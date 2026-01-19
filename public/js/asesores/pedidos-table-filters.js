/**
 * Gestión de filtros de tabla en Modal para Asesores - Pedidos
 * Mapeo por nombre de columna para evitar problemas de índices
 */

let currentFilterColumn = null;
let currentFilterColumnName = null;
let selectedFilters = {};

/**
 * Guardar filtros en localStorage para persistencia
 */
function saveFiltersToLocalStorage() {
    localStorage.setItem('pedidosTableFilters', JSON.stringify(selectedFilters));
}

/**
 * Cargar filtros desde localStorage
 */
function loadFiltersFromLocalStorage() {
    const saved = localStorage.getItem('pedidosTableFilters');
    if (saved) {
        try {
            selectedFilters = JSON.parse(saved);
            return true;
        } catch (e) {
            console.error('❌ Error al cargar filtros:', e);
            return false;
        }
    }
    return false;
}

/**
 * Obtener el índice de una columna por su nombre
 */
function getColumnIndexByName(columnName) {
    const tableContainer = document.querySelector('.table-scroll-container');
    if (!tableContainer) return -1;
    
    const headerRow = tableContainer.querySelector('div[style*="background: linear-gradient"]');
    if (!headerRow) return -1;
    
    // Obtener solo los divs directos (children) del header
    const headerChildren = Array.from(headerRow.children);
    
    for (let i = 0; i < headerChildren.length; i++) {
        const child = headerChildren[i];
        
        // Si es un th-wrapper, buscar el span dentro
        if (child.classList.contains('th-wrapper')) {
            const labelSpan = child.querySelector('span');
            if (labelSpan && labelSpan.textContent.trim() === columnName) {
                return i;
            }
        }
    }
    
    console.warn(`❌ Columna "${columnName}" NO encontrada`);
    return -1;
}

/**
 * Obtener los valores únicos de una columna de la tabla por nombre
 */
function getColumnValuesFromTableByName(columnName) {
    const columnIndex = getColumnIndexByName(columnName);
    if (columnIndex === -1) {
        console.warn(`❌ Columna "${columnName}" no encontrada`);
        return [];
    }
    
    const values = new Set();
    const tableContainer = document.querySelector('.table-scroll-container');
    
    if (!tableContainer) return [];
    
    // Obtener todas las filas (excluyendo el header)
    // Las filas son divs con grid-template-columns pero NO son el header
    const allDivs = tableContainer.querySelectorAll('div[style*="grid-template-columns"]');
    const rows = Array.from(allDivs).filter((div, index) => {
        // Saltar el header (primer div con gradient)
        return !div.style.background?.includes('linear-gradient');
    });
    
    // IMPORTANTE: Solo considerar filas VISIBLES (no ocultas por otros filtros)
    const visibleRows = rows.filter(row => row.style.display !== 'none');
    
    visibleRows.forEach((row, rowIndex) => {
        // Obtener solo los divs directos (children) de la fila
        const cells = Array.from(row.children);
        
        if (cells && cells[columnIndex]) {
            let cellDiv = cells[columnIndex];
            let value = cellDiv.textContent.trim();
            
            // Limpiar valores especiales según la columna
            if (columnName === 'Estado') {
                const span = cellDiv.querySelector('span');
                if (span) {
                    value = span.textContent.trim();
                }
            } else if (columnName === 'Pedido') {
                value = value.replace('#', '').trim();
            } else if (columnName === 'Cantidad') {
                value = value.replace(/\s+und/i, '').trim();
            } else if (columnName === 'Fecha Estimada' || columnName === 'Fecha Creación') {
                // Limpiar fechas: solo DD/MM/YYYY
                value = value.replace(/\s*\d{2}:\d{2}:\d{2}.*$/i, '').trim();
            }
            
            // No agregar valores vacíos
            if (value && value.length > 0 && value !== '-') {
                values.add(value);
            }
        }
    });
    
    const result = Array.from(values).sort();
    return result;
}

/**
 * Abrir modal de filtro
 */
function openFilterModal(columnName) {
    currentFilterColumn = columnName;
    currentFilterColumnName = columnName;
    const modal = document.getElementById('filterModal');
    const title = document.getElementById('filterModalTitle');
    const optionsContainer = document.getElementById('filterOptions');
    const searchInput = document.getElementById('filterSearch');
    
    title.textContent = `Filtrar por ${columnName}`;
    optionsContainer.innerHTML = '';
    
    // CASO ESPECIAL: Descripción - búsqueda con opciones USANDO ENDPOINT
    if (columnName === 'Descripción') {
        // Ocultar el input de búsqueda genérico
        searchInput.style.display = 'none';
        
        // Obtener IDs de los pedidos de la tabla
        const tableContainer = document.querySelector('.table-scroll-container');
        const pedidoIds = [];
        
        if (tableContainer) {
            const allRows = tableContainer.querySelectorAll('div[data-pedido-row]');
            allRows.forEach(row => {
                // Buscar el botón con data-pedido-id
                const btnVer = row.querySelector('[data-pedido-id]');
                if (btnVer) {
                    const id = btnVer.getAttribute('data-pedido-id');
                    if (id) {
                        pedidoIds.push(parseInt(id));
                    }
                }
            });
        }
        
        console.log('Pedido IDs encontrados:', pedidoIds);
        
        // Input de búsqueda
        const searchWrapper = document.createElement('div');
        searchWrapper.style.cssText = 'display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.id = 'descripcion-search-input';
        input.placeholder = 'Buscar prendas, tela, color, proceso...';
        input.style.cssText = 'padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; width: 100%;';
        
        // Pre-llenar si hay filtro anterior
        if (selectedFilters[columnName] && selectedFilters[columnName][0]) {
            const stored = selectedFilters[columnName][0];
            if (stored.startsWith('field:todos:')) {
                input.value = stored.replace('field:todos:', '');
            }
        }
        
        searchWrapper.appendChild(input);
        optionsContainer.appendChild(searchWrapper);
        
        // Contenedor de opciones
        const optionsDiv = document.createElement('div');
        optionsDiv.id = 'prendas-options-container';
        optionsDiv.style.cssText = 'max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.5rem;';
        optionsContainer.appendChild(optionsDiv);
        
        // Función para mostrar prendas
        function renderPrendas() {
            optionsDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: #9ca3af;">Cargando prendas...</div>';
            const searchTerm = input.value.toLowerCase();
            
            console.log('=== RENDERIZAR PRENDAS (ENDPOINT) ===');
            console.log('Search term:', searchTerm);
            
            // Cargar datos de todos los pedidos
            Promise.all(pedidoIds.map(id => 
                fetch(`/asesores/pedidos/${id}/recibos-datos`)
                    .then(r => r.json())
                    .catch(e => {
                        console.error('Error fetching pedido', id, e);
                        return null;
                    })
            )).then(results => {
                let matchedPrendas = [];
                
                results.forEach((data, idx) => {
                    if (!data || !data.prendas) return;
                    
                    console.log(`\nPedido ${pedidoIds[idx]}:`, data.prendas);
                    
                    data.prendas.forEach(prenda => {
                        // Buscar en nombre, tela, color
                        const searchIn = `${prenda.nombre || ''} ${prenda.tela || ''} ${prenda.color || ''} ${prenda.descripcion || ''}`.toLowerCase();
                        console.log(`  Prenda: "${searchIn}"`);
                        
                        // También buscar en procesos
                        let hasProcess = false;
                        if (prenda.procesos && searchTerm) {
                            prenda.procesos.forEach(p => {
                                const processSearch = `${p.descripcion || ''} ${p.ubicacion || ''} ${p.observaciones || ''}`.toLowerCase();
                                if (processSearch.includes(searchTerm)) {
                                    console.log(`    ✓ Proceso encontrado: "${p.descripcion}"`);
                                    hasProcess = true;
                                }
                            });
                        }
                        
                        if (!searchTerm || searchIn.includes(searchTerm) || hasProcess) {
                            console.log(`  ✓ MATCH!`);
                            matchedPrendas.push(prenda);
                        }
                    });
                });
                
                console.log('Total matched prendas:', matchedPrendas.length);
                
                // Mostrar prendas
                optionsDiv.innerHTML = '';
                const seen = new Set();
                let count = 0;
                
                matchedPrendas.forEach((prenda) => {
                    if (count >= 5 && !searchTerm) return;
                    
                    const prendaNombre = prenda.nombre || prenda.descripcion || 'Prenda';
                    const key = `${prendaNombre}|${prenda.tela}|${prenda.color}`;
                    if (!seen.has(key)) {
                        seen.add(key);
                        count++;
                        
                        const div = document.createElement('div');
                        div.className = 'filter-option';
                        div.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 6px; transition: background 0.2s;';
                        
                        // Usar nombre o descripción como fallback
                        const prendaNombre = prenda.nombre || prenda.descripcion || 'Prenda';
                        const id = `filter-prenda-${prendaNombre}-${prenda.tela}-${prenda.color}`.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-]/g, '');
                        const displayText = `${prendaNombre}${prenda.tela ? ' - ' + prenda.tela : ''}${prenda.color ? ' (' + prenda.color + ')' : ''}`;
                        const filterValue = displayText;
                        const isChecked = selectedFilters[columnName] && selectedFilters[columnName][0] === `field:todos:${filterValue}`;
                        
                        console.log('Rendering:', displayText);
                        
                        div.innerHTML = `
                            <input type="checkbox" id="${id}" value="${filterValue}" ${isChecked ? 'checked' : ''} style="cursor: pointer;">
                            <label for="${id}" style="flex: 1; cursor: pointer; margin: 0; font-size: 0.9rem;">${displayText}</label>
                        `;
                        
                        div.addEventListener('mouseenter', () => {
                            div.style.background = '#f3f4f6';
                        });
                        div.addEventListener('mouseleave', () => {
                            div.style.background = 'transparent';
                        });
                        
                        optionsDiv.appendChild(div);
                    }
                });
                
                if (optionsDiv.children.length === 0) {
                    optionsDiv.innerHTML = '<div style="padding: 1rem; text-align: center; color: #9ca3af;">No hay prendas disponibles</div>';
                }
            });
        }
        
        // Renderizar opciones iniciales
        renderPrendas();
        
        // Evento de búsqueda en tiempo real
        input.addEventListener('keyup', renderPrendas);
        
        // Botón Buscar
        const buttonWrapper = document.createElement('div');
        buttonWrapper.style.cssText = 'display: flex; gap: 0.5rem; margin-top: 1rem;';
        
        const btnBuscar = document.createElement('button');
        btnBuscar.textContent = 'Buscar';
        btnBuscar.style.cssText = 'flex: 1; padding: 0.75rem; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;';
        btnBuscar.addEventListener('mouseover', () => btnBuscar.style.background = '#2563eb');
        btnBuscar.addEventListener('mouseout', () => btnBuscar.style.background = '#3b82f6');
        btnBuscar.addEventListener('click', () => {
            const checkbox = optionsDiv.querySelector('input[type="checkbox"]:checked');
            if (checkbox) {
                const prendaName = checkbox.value;
                selectedFilters['Descripción'] = [`field:todos:${prendaName}`];
            } else if (input.value.trim()) {
                selectedFilters['Descripción'] = [`field:todos:${input.value.trim()}`];
            } else {
                delete selectedFilters['Descripción'];
            }
            
            saveFiltersToLocalStorage();
            closeFilterModal();
            applyTableFilters();
            updateFilterBadges();
        });
        
        buttonWrapper.appendChild(btnBuscar);
        optionsContainer.appendChild(buttonWrapper);
        
        modal.classList.add('active');
        input.focus();
        return;
    }
    
    // RESTO DE COLUMNAS: Checkboxes normales - Mostrar el input de búsqueda genérico
    searchInput.style.display = 'block';
    
    // Obtener valores únicos de la tabla
    const values = getColumnValuesFromTableByName(columnName);
    
    // Construir opciones
    if (values.length === 0) {
        optionsContainer.innerHTML = '<div style="padding: 1rem; text-align: center; color: #9ca3af;">No hay datos disponibles</div>';
    } else {
        // Agregar opción "Seleccionar Todo"
        const selectAllDiv = document.createElement('div');
        selectAllDiv.className = 'filter-option select-all-option';
        selectAllDiv.style.borderBottom = '1px solid #e5e7eb';
        selectAllDiv.style.paddingBottom = '0.75rem';
        selectAllDiv.style.marginBottom = '0.75rem';
        
        const allChecked = selectedFilters[columnName] && selectedFilters[columnName].length === values.length;
        selectAllDiv.innerHTML = `
            <input type="checkbox" id="select-all-${columnName}" ${allChecked ? 'checked' : ''}>
            <label for="select-all-${columnName}" style="font-weight: 600; color: #1f2937;">Seleccionar Todo</label>
        `;
        optionsContainer.appendChild(selectAllDiv);
        
        // Agregar evento al checkbox "Seleccionar Todo"
        const selectAllCheckbox = selectAllDiv.querySelector('input[type="checkbox"]');
        selectAllCheckbox.addEventListener('change', function() {
            const allCheckboxes = optionsContainer.querySelectorAll('.filter-option:not(.select-all-option) input[type="checkbox"]');
            allCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
        
        // Agregar opciones individuales
        values.forEach(value => {
            if (!value || value === columnName) return;
            
            const div = document.createElement('div');
            div.className = 'filter-option';
            const id = `filter-${columnName}-${value}`.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-]/g, '');
            const isChecked = selectedFilters[columnName] && selectedFilters[columnName].includes(value);
            
            div.innerHTML = `
                <input type="checkbox" id="${id}" value="${value}" ${isChecked ? 'checked' : ''}>
                <label for="${id}">${value}</label>
            `;
            optionsContainer.appendChild(div);
        });
    }
    
    modal.classList.add('active');
    
    // Agregar funcionalidad al buscador
    searchInput.value = '';
    searchInput.focus();
    searchInput.onkeyup = filterSearchOptions;
}

/**
 * Filtrar opciones del buscador
 */
function filterSearchOptions() {
    const searchTerm = document.getElementById('filterSearch').value.toLowerCase();
    const options = document.querySelectorAll('.filter-option');
    
    options.forEach(option => {
        const label = option.querySelector('label');
        if (label) {
            const labelText = label.textContent.toLowerCase();
            option.style.display = labelText.includes(searchTerm) ? 'flex' : 'none';
        }
    });
}

/**
 * Cerrar modal de filtro
 */
function closeFilterModal(event) {
    if (event && event.target && event.target.id !== 'filterModal') return;
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Aplicar filtros seleccionados
 */
function applyFilters() {
    // CASO ESPECIAL: Descripción - búsqueda simple
    if (currentFilterColumnName === 'Descripción') {
        const input = document.getElementById('descripcion-search-input');
        
        if (input && input.value.trim()) {
            const text = input.value.trim();
            // Guardar con formato simplificado: field:todos:napoles
            selectedFilters['Descripción'] = [`field:todos:${text}`];
        } else {
            delete selectedFilters['Descripción'];
        }
    } else {
        // Resto de columnas con checkboxes
        const checkboxes = document.querySelectorAll('.filter-options input[type="checkbox"]:checked');
        const selectedValues = Array.from(checkboxes).map(cb => cb.value);
        
        if (selectedValues.length > 0) {
            selectedFilters[currentFilterColumnName] = selectedValues;
        } else {
            delete selectedFilters[currentFilterColumnName];
        }
    }
    
    saveFiltersToLocalStorage();  // 💾 Guardar en localStorage
    closeFilterModal();
    
    // Redirigir con parámetros de filtro en la URL para mantener con paginación
    const filterParams = new URLSearchParams();
    Object.entries(selectedFilters).forEach(([column, values]) => {
        values.forEach(value => {
            filterParams.append(`filter[${column}][]`, value);
        });
    });
    
    const newUrl = `${window.location.pathname}?${filterParams.toString()}`;
    window.location.href = newUrl;
}

/**
 * Limpiar filtros
 */
function resetFilters() {
    selectedFilters = {};
    localStorage.removeItem('pedidosTableFilters');  // 🗑️ Limpiar localStorage
    document.querySelectorAll('.filter-options input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    closeFilterModal();
    
    // Redirigir sin filtros
    window.location.href = window.location.pathname;
}

/**
 * Aplicar filtros a la tabla
 */
function applyTableFilters() {
    const tableContainer = document.querySelector('.table-scroll-container');
    if (!tableContainer) return;
    
    // Obtener todas las filas (excluyendo el header)
    const allDivs = tableContainer.querySelectorAll('div[style*="grid-template-columns"]');
    const rows = Array.from(allDivs).filter((div) => {
        // Saltar el header (primer div con gradient)
        return !div.style.background?.includes('linear-gradient');
    });
    
    let visibleCount = 0;
    
    rows.forEach((row) => {
        let shouldShow = true;
        // Obtener solo los divs directos (children) de la fila
        const cells = Array.from(row.children);
        
        // Verificar cada filtro aplicado
        for (const [columnName, filterValues] of Object.entries(selectedFilters)) {
            const columnIndex = getColumnIndexByName(columnName);
            
            if (columnIndex !== -1 && cells && cells[columnIndex]) {
                let cellDiv = cells[columnIndex];
                let cellValue = cellDiv.textContent.trim();
                
                // Limpiar valor para comparación
                if (columnName === 'Estado') {
                    const span = cellDiv.querySelector('span');
                    if (span) {
                        cellValue = span.textContent.trim();
                    }
                } else if (columnName === 'Pedido') {
                    cellValue = cellValue.replace('#', '').trim();
                } else if (columnName === 'Cantidad') {
                    cellValue = cellValue.replace(/\s+und/i, '').trim();
                } else if (columnName === 'Fecha Estimada' || columnName === 'Fecha Creación') {
                    // Limpiar fechas: solo DD/MM/YYYY
                    cellValue = cellValue.replace(/\s*\d{2}:\d{2}:\d{2}.*$/i, '').trim();
                }
                
                // Verificar si el valor está en los filtros seleccionados
                let matches = false;
                
                if (columnName === 'Descripción') {
                    // Búsqueda inteligente por campo específico
                    matches = filterValues.some(filter => {
                        // Formato: field:nombre_prenda:napoles
                        if (filter.startsWith('field:')) {
                            const [, field, searchText] = filter.match(/^field:(\w+):(.+)$/) || [];
                            if (!field || !searchText) return false;
                            
                            // Extraer los datos del atributo data-prenda-info de la fila si existe
                            const prenda = row.getAttribute('data-prenda-info');
                            if (!prenda) return false;
                            
                            try {
                                const prendaData = JSON.parse(prenda);
                                let searchIn = '';
                                
                                if (field === 'nombre_prenda') {
                                    searchIn = (prendaData.nombre_prenda || '').toLowerCase();
                                } else if (field === 'tela') {
                                    searchIn = (prendaData.tela || '').toLowerCase();
                                } else if (field === 'color') {
                                    searchIn = (prendaData.color || '').toLowerCase();
                                } else if (field === 'descripcion') {
                                    searchIn = (prendaData.descripcion || '').toLowerCase();
                                } else if (field === 'todos') {
                                    // Buscar en todos los campos
                                    const todos = [
                                        prendaData.nombre_prenda || '',
                                        prendaData.tela || '',
                                        prendaData.color || '',
                                        prendaData.descripcion || ''
                                    ].join(' ').toLowerCase();
                                    searchIn = todos;
                                }
                                
                                return searchIn.includes(searchText.toLowerCase());
                            } catch (e) {
                                // Si no hay data-prenda-info, hacer búsqueda en el texto visible
                                return cellValue.toLowerCase().includes(searchText.toLowerCase());
                            }
                        } else {
                            // Búsqueda parcial antigua (compatibilidad)
                            return cellValue.toLowerCase().includes(filter.toLowerCase());
                        }
                    });
                } else {
                    // Búsqueda exacta para otras columnas
                    matches = filterValues.includes(cellValue);
                }
                
                if (!matches) {
                    shouldShow = false;
                    break;
                }
            }
        }
        
        row.style.display = shouldShow ? 'grid' : 'none';
        if (shouldShow) visibleCount++;
    });
}

/**
 * Cargar filtros desde URL (query parameters)
 */
function loadFiltersFromURL() {
    const params = new URLSearchParams(window.location.search);
    const urlFilters = {};
    
    // Procesar parámetros filter[columna][]
    params.forEach((value, key) => {
        const match = key.match(/filter\[(.+?)\]/);
        if (match) {
            const columnName = match[1];
            if (!urlFilters[columnName]) {
                urlFilters[columnName] = [];
            }
            urlFilters[columnName].push(value);
        }
    });
    
    if (Object.keys(urlFilters).length > 0) {
        selectedFilters = urlFilters;
        return true;
    }
    return false;
}

/**
 * Inicializar los botones de filtro (reutilizable)
 */
function initializeFilterButtons() {
    // 📂 Cargar filtros: primero desde URL, luego desde localStorage
    const hasURLFilters = loadFiltersFromURL();
    if (!hasURLFilters) {
        loadFiltersFromLocalStorage();
    }
    
    // Obtener el header de la tabla para mapear correctamente
    const tableContainer = document.querySelector('.table-scroll-container');
    if (!tableContainer) return;
    
    const headerRow = tableContainer.querySelector('div[style*="background: linear-gradient"]');
    if (!headerRow) return;
    
    // Obtener todos los th-wrapper (que contienen span + button)
    const headerWrappers = headerRow.querySelectorAll('.th-wrapper');
    
    // Mapear botones a nombres de columna
    headerWrappers.forEach((wrapper, wrapperIndex) => {
        const button = wrapper.querySelector('.btn-filter-column');
        const labelSpan = wrapper.querySelector('span');
        
        if (!button || !labelSpan) return;
        
        const columnName = labelSpan.textContent.trim();
        
        // No agregar filtro a Acciones
        if (columnName === 'Acciones') {
            button.style.display = 'none';
            return;
        }
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openFilterModal(columnName);
        });
    });
    
    // ✅ Aplicar filtros a la tabla
    if (Object.keys(selectedFilters).length > 0) {
        applyTableFilters();
        updateFilterBadges();
        updateClearButtonVisibility();
    }
    
    // Actualizar links de paginación para mantener filtros
    updatePaginationLinks();
    
    // Cerrar modal al hacer clic fuera
    const filterModal = document.getElementById('filterModal');
    if (filterModal) {
        filterModal.addEventListener('click', closeFilterModal);
    }
    
    // Permitir cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeFilterModal();
        }
    });
}

/**
 * Inicializar los botones de filtro en carga
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeFilterButtons();
});

/**
 * Actualizar links de paginación para mantener parámetros de filtro
 */
function updatePaginationLinks() {
    const filterParams = new URLSearchParams();
    Object.entries(selectedFilters).forEach(([column, values]) => {
        values.forEach(value => {
            filterParams.append(`filter[${column}][]`, value);
        });
    });
    
    if (filterParams.toString() === '') return;
    
    // Buscar todos los links de paginación y agregar los parámetros de filtro
    const paginationLinks = document.querySelectorAll('a[href*="page="]');
    paginationLinks.forEach(link => {
        const url = new URL(link.href);
        const filterStr = filterParams.toString();
        
        // Mantener el parámetro page= existente y agregar filtros
        if (filterStr) {
            // Separar query string existente de los filtros
            const existingParams = new URLSearchParams(url.search);
            const pageNum = existingParams.get('page');
            
            // Crear nuevo query string con page y filtros
            const newParams = new URLSearchParams();
            if (pageNum) newParams.set('page', pageNum);
            filterParams.forEach((value, key) => {
                newParams.append(key, value);
            });
            
            url.search = '?' + newParams.toString();
            link.href = url.toString();
        }
    });
}

/**
 * Actualizar badges de filtros activos
 */
function updateFilterBadges() {
    const tableContainer = document.querySelector('.table-scroll-container');
    if (!tableContainer) return;
    
    const headerRow = tableContainer.querySelector('div[style*="background: linear-gradient"]');
    if (!headerRow) return;
    
    // Obtener todos los th-wrapper
    const headerChildren = Array.from(headerRow.children);
    
    headerChildren.forEach((child, index) => {
        if (child.classList.contains('th-wrapper')) {
            const button = child.querySelector('.btn-filter-column');
            const labelSpan = child.querySelector('span');
            
            if (!button || !labelSpan) return;
            
            const columnName = labelSpan.textContent.trim();
            
            // Remover badge anterior si existe
            const oldBadge = button.querySelector('.filter-badge');
            if (oldBadge) {
                oldBadge.remove();
            }
            
            // Si hay filtros para esta columna, agregar badge
            if (selectedFilters[columnName] && selectedFilters[columnName].length > 0) {
                button.classList.add('has-filter');
                
                const badge = document.createElement('div');
                badge.className = 'filter-badge';
                badge.textContent = selectedFilters[columnName].length;
                button.appendChild(badge);
            } else {
                button.classList.remove('has-filter');
            }
        }
    });
}

/**
 * Mostrar/ocultar botón flotante de limpiar filtros
 */
function updateClearButtonVisibility() {
    const clearBtn = document.getElementById('btnClearAllFilters');
    if (!clearBtn) {
        console.warn('❌ Botón flotante no encontrado');
        return;
    }
    
    const hasFilters = Object.keys(selectedFilters).some(key => selectedFilters[key] && selectedFilters[key].length > 0);
    
    if (hasFilters) {
        clearBtn.classList.add('visible');
    } else {
        clearBtn.classList.remove('visible');
    }
}

/**
 * Limpiar todos los filtros
 */
function clearAllFilters() {
    selectedFilters = {};
    saveFiltersToLocalStorage();
    
    // Aplicar cambios sin recargar
    applyTableFilters();
    updateFilterBadges();
    updateClearButtonVisibility();
    
    // Actualizar URL sin recargar la página
    const url = new URL(window.location.href);
    const params = new URLSearchParams();
    
    // Mantener solo los parámetros que no son filtros
    const currentParams = new URLSearchParams(url.search);
    currentParams.forEach((value, key) => {
        if (!key.startsWith('filter[')) {
            params.set(key, value);
        }
    });
    
    url.search = params.toString();
    window.history.pushState({}, '', url.toString());
}

/**
 * Cerrar modal de filtros
 */
function closeFilterModal(event) {
    if (event && event.target.id !== 'filterModal') return;
    
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Aplicar filtros desde el modal
 */
function applyFilters() {
    const optionsContainer = document.getElementById('filterOptions');
    const checkboxes = optionsContainer.querySelectorAll('input[type="checkbox"]:not(#select-all-' + currentFilterColumnName + ')');
    
    const values = [];
    checkboxes.forEach(cb => {
        if (cb.checked) {
            values.push(cb.value);
        }
    });
    
    if (values.length > 0) {
        selectedFilters[currentFilterColumnName] = values;
    } else {
        delete selectedFilters[currentFilterColumnName];
    }
    
    saveFiltersToLocalStorage();
    
    // Cerrar el modal
    closeFilterModal();
    
    // Aplicar filtros a la tabla sin recargar
    applyTableFilters();
    updateFilterBadges();
    updateClearButtonVisibility();
    
    // Actualizar URL sin recargar la página
    const url = new URL(window.location.href);
    const params = new URLSearchParams();
    
    // Mantener parámetros que no son filtros (como page, tipo, etc)
    const currentParams = new URLSearchParams(url.search);
    currentParams.forEach((value, key) => {
        if (!key.startsWith('filter[')) {
            params.set(key, value);
        }
    });
    
    // Agregar filtros actuales
    Object.entries(selectedFilters).forEach(([column, vals]) => {
        vals.forEach(val => {
            params.append(`filter[${column}][]`, val);
        });
    });
    
    url.search = params.toString();
    window.history.pushState({}, '', url.toString());
}

/**
 * Resetear filtros del modal actual
 */
function resetFilters() {
    delete selectedFilters[currentFilterColumnName];
    saveFiltersToLocalStorage();
    
    // Cerrar el modal
    closeFilterModal();
    
    // Aplicar cambios sin recargar
    applyTableFilters();
    updateFilterBadges();
    updateClearButtonVisibility();
    
    // Actualizar URL sin recargar la página
    const url = new URL(window.location.href);
    const params = new URLSearchParams();
    
    // Mantener parámetros que no son filtros
    const currentParams = new URLSearchParams(url.search);
    currentParams.forEach((value, key) => {
        if (!key.startsWith('filter[')) {
            params.set(key, value);
        }
    });
    
    // Agregar filtros restantes
    Object.entries(selectedFilters).forEach(([column, vals]) => {
        vals.forEach(val => {
            params.append(`filter[${column}][]`, val);
        });
    });
    
    url.search = params.toString();
    window.history.pushState({}, '', url.toString());
}
