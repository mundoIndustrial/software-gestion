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
    console.log('💾 Filtros guardados en localStorage:', selectedFilters);
}

/**
 * Cargar filtros desde localStorage
 */
function loadFiltersFromLocalStorage() {
    const saved = localStorage.getItem('pedidosTableFilters');
    if (saved) {
        try {
            selectedFilters = JSON.parse(saved);
            console.log('📂 Filtros cargados desde localStorage:', selectedFilters);
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
                console.log(`✅ Columna "${columnName}" encontrada en índice ${i}`);
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
    
    console.log(`📊 Extrayendo valores de "${columnName}" (índice ${columnIndex}) de ${rows.length} filas`);
    
    rows.forEach((row, rowIndex) => {
        // Obtener solo los divs directos (children) de la fila
        const cells = Array.from(row.children);
        
        if (cells && cells[columnIndex]) {
            let cellDiv = cells[columnIndex];
            let value = cellDiv.textContent.trim();
            
            console.log(`  [Fila ${rowIndex}] Valor bruto: "${value}"`);
            
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
                console.log(`  ✅ Agregado: "${value}"`);
            }
        }
    });
    
    const result = Array.from(values).sort();
    console.log(`📋 Valores únicos para "${columnName}":`, result);
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
    
    title.textContent = `Filtrar por ${columnName}`;
    
    // Obtener valores únicos de la tabla
    const values = getColumnValuesFromTableByName(columnName);
    
    console.log(`Columna "${columnName}":`, values);
    
    // Construir opciones
    optionsContainer.innerHTML = '';
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
    const searchInput = document.getElementById('filterSearch');
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
    const checkboxes = document.querySelectorAll('.filter-options input[type="checkbox"]:checked');
    const selectedValues = Array.from(checkboxes).map(cb => cb.value);
    
    if (selectedValues.length > 0) {
        selectedFilters[currentFilterColumnName] = selectedValues;
    } else {
        delete selectedFilters[currentFilterColumnName];
    }
    
    console.log('Filtros aplicados:', selectedFilters);
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
    console.log('🔗 Redirigiendo a:', newUrl);
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
    console.log('🔄 Limpiando filtros...');
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
                
                console.log(`🔍 Comparando "${columnName}" - Valor: "${cellValue}" - Filtros: ${JSON.stringify(filterValues)}`);
                
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
                // Para Descripción, usar búsqueda parcial (contiene)
                let matches = false;
                if (columnName === 'Descripción') {
                    // Búsqueda parcial: verificar si algún filtro está contenido en el valor
                    matches = filterValues.some(filter => 
                        cellValue.toLowerCase().includes(filter.toLowerCase())
                    );
                } else {
                    // Búsqueda exacta para otras columnas
                    matches = filterValues.includes(cellValue);
                }
                
                if (!matches) {
                    console.log(`  ❌ No coincide - Ocultando fila`);
                    shouldShow = false;
                    break;
                } else {
                    console.log(`  ✅ Coincide - Mostrando fila`);
                }
            }
        }
        
        row.style.display = shouldShow ? 'grid' : 'none';
        if (shouldShow) visibleCount++;
    });
    
    console.log(`✅ Mostrando ${visibleCount} de ${rows.length} filas`);
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
        console.log('📋 Filtros cargados desde URL:', selectedFilters);
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
        console.log('✅ Aplicando filtros:', selectedFilters);
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
    
    console.log('🔗 Links de paginación actualizados con filtros');
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
                
                console.log(`🔴 Badge agregado a "${columnName}": ${selectedFilters[columnName].length}`);
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
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (!clearBtn) return;
    
    const hasFilters = Object.keys(selectedFilters).length > 0;
    
    if (hasFilters) {
        clearBtn.classList.add('visible');
        console.log('✅ Botón flotante mostrado');
    } else {
        clearBtn.classList.remove('visible');
        console.log('❌ Botón flotante ocultado');
    }
}
