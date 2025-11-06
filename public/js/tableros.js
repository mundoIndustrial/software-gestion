function tablerosApp() {
    console.log('🚀 Inicializando tablerosApp...');
    return {
        activeTab: 'produccion',
        showRecords: false,

        setActiveTab(tab) {
            console.log('📑 Cambiando tab a:', tab, '- Reseteando showRecords a false');
            this.activeTab = tab;
            this.showRecords = false; // Reset when changing tabs
        },

        toggleRecords() {
            console.log('🔄 Toggle Records - Antes:', this.showRecords);
            this.showRecords = !this.showRecords;
            console.log('🔄 Toggle Records - Después:', this.showRecords);
            if (this.showRecords) {
                // Initialize filters when showing records
                const currentTab = this.activeTab;
                console.log('📊 Mostrando registros para tab:', currentTab);
                setTimeout(() => {
                    initializeTableFilters(currentTab);
                }, 100);
            } else {
                console.log('📈 Mostrando seguimiento/dashboard');
            }
        },

        openFormModal() {
            document.getElementById('activeSection').value = this.activeTab;
            const modalTitle = document.getElementById('modalTitle');
            if (this.activeTab === 'produccion') {
                modalTitle.textContent = 'Registro Control de Piso Producción';
            } else if (this.activeTab === 'polos') {
                modalTitle.textContent = 'Registro Control de Piso Polos';
            } else if (this.activeTab === 'corte') {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'piso-corte-form' }));
                return;
            }
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'tableros-form' }));
        }
    }
}

// Función para actualizar la tabla de registros después de guardar
function actualizarRegistros() {
    // Recargar la página para mostrar los nuevos registros
    location.reload();
}

// Table filtering functionality
function initializeTableFilters(section) {
    const table = document.querySelector(`table[data-section="${section}"]`);
    if (!table) return;

    // Remove existing filter dropdowns
    document.querySelectorAll('.filter-dropdown').forEach(dropdown => dropdown.remove());

    // Restaurar filtros guardados si existen
    restoreSavedFilters(section);

    // Add event listeners to filter icons
    table.querySelectorAll('.filter-icon').forEach(icon => {
        icon.addEventListener('click', (e) => {
            e.stopPropagation();
            showFilterDropdown(icon.dataset.column, section);
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.filter-dropdown') && !e.target.closest('.filter-icon')) {
            document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
}

function showFilterDropdown(column, section) {
    const table = document.querySelector(`table[data-section="${section}"]`);
    const headerCell = table.querySelector(`th[data-column="${column}"]`);
    const icon = headerCell.querySelector('.filter-icon');

    // Close any other open dropdowns first
    document.querySelectorAll('.filter-dropdown.show').forEach(openDropdown => {
        if (openDropdown !== document.querySelector(`.filter-dropdown[data-column="${column}"][data-section="${section}"]`)) {
            openDropdown.classList.remove('show');
        }
    });

    // Check if dropdown already exists for this column
    let dropdown = document.querySelector(`.filter-dropdown[data-column="${column}"][data-section="${section}"]`);
    if (dropdown) {
        // Toggle visibility
        dropdown.classList.toggle('show');
        return;
    }

    // Create new dropdown
    dropdown = document.createElement('div');
    dropdown.className = 'filter-dropdown';
    dropdown.setAttribute('data-column', column);
    dropdown.setAttribute('data-section', section);

    // Get unique values for this column from backend
    fetchUniqueValues(column, section).then(uniqueValues => {
        buildDropdownContent(dropdown, column, section, uniqueValues, headerCell, icon);
    }).catch(error => {
        console.error('Error fetching unique values:', error);
        // Fallback to local values
        const uniqueValues = collectUniqueValues(column, section);
        buildDropdownContent(dropdown, column, section, uniqueValues, headerCell, icon);
    });
}

function fetchUniqueValues(column, section) {
    return fetch(`/tableros/unique-values?section=${section}&column=${column}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => data.values || []);
}

function buildDropdownContent(dropdown, column, section, uniqueValues, headerCell, icon) {

    // Header
    const headerText = headerCell.querySelector('.header-content').firstChild.textContent.trim();
    dropdown.innerHTML = `
        <div class="filter-dropdown-header">${headerText}</div>
        <div class="filter-search">
            <input type="text" placeholder="Buscar..." class="filter-search-input">
        </div>
        <div class="filter-dropdown-body">
            ${uniqueValues.map(value => `
                <div class="filter-option" data-value="${value}">
                    <input type="checkbox" value="${value}" checked>
                    <span>${value || '(Vacío)'}</span>
                </div>
            `).join('')}
        </div>
        <div class="filter-dropdown-footer">
            <button class="filter-btn select-all" title="Seleccionar todos los valores">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </button>
            <button class="filter-btn clear-all" title="Limpiar todos los valores">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
            <button class="filter-btn apply" title="Aplicar filtro">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </button>
        </div>
    `;

    // Position dropdown
    const rect = icon.getBoundingClientRect();
    dropdown.style.left = `${rect.left}px`;
    dropdown.style.top = `${rect.bottom + 5}px`;

    document.body.appendChild(dropdown);

    // Show dropdown
    setTimeout(() => dropdown.classList.add('show'), 10);

    // Event listeners
    const selectAllBtn = dropdown.querySelector('.select-all');
    const clearAllBtn = dropdown.querySelector('.clear-all');
    const applyBtn = dropdown.querySelector('.apply');
    const searchInput = dropdown.querySelector('.filter-search-input');
    const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const options = dropdown.querySelectorAll('.filter-option');

        options.forEach(option => {
            const text = option.textContent.toLowerCase();
            const value = option.dataset.value.toLowerCase();
            if (text.includes(searchTerm) || value.includes(searchTerm)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    });

    selectAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
    });

    clearAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
        // Limpiar filtros guardados para esta sección
        clearSavedFilters(section);
        // Reset all filters and show all rows
        applyFilters(section);
    });

    applyBtn.addEventListener('click', () => {
        // Collect all active filters and send to backend
        applyFiltersBackend(section);
        dropdown.classList.remove('show');
    });

    // Don't apply on checkbox change, only on button click
}

function collectUniqueValues(column, section) {
    const table = document.querySelector(`table[data-section="${section}"]`);
    const values = new Set();

    table.querySelectorAll(`tbody tr td[data-column="${column}"]`).forEach(cell => {
        let value = cell.dataset.value || cell.textContent.trim();
        // For display values, try to get the raw value
        if (cell.dataset.value) {
            value = cell.dataset.value;
        }
        values.add(value);
    });

    return Array.from(values).sort();
}

function applyFiltersBackend(section) {
    // Collect all active filters from all dropdowns for this section
    const filters = {};
    document.querySelectorAll(`.filter-dropdown[data-section="${section}"]`).forEach(dropdown => {
        const column = dropdown.dataset.column;
        const checkedValues = Array.from(dropdown.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
        
        if (checkedValues.length > 0) {
            filters[column] = checkedValues;
        }
    });

    // Guardar filtros en localStorage para mantenerlos después de editar
    localStorage.setItem(`tableros_filters_${section}`, JSON.stringify(filters));

    // Build URL with filters
    const url = new URL(window.location.href);
    url.searchParams.set('section', section);
    url.searchParams.set('page', '1'); // Reset to page 1 when filtering
    
    // Send filters as JSON
    if (Object.keys(filters).length > 0) {
        url.searchParams.set('filters', JSON.stringify(filters));
    } else {
        url.searchParams.delete('filters');
    }

    // Show loading state
    const tableBody = document.querySelector(`table[data-section="${section}"] tbody`);
    if (tableBody) {
        tableBody.style.opacity = '0.3';
        tableBody.style.pointerEvents = 'none';
    }

    // Fetch filtered data
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error || !data.pagination) {
            throw new Error(data.error || 'Respuesta inválida del servidor');
        }
        
        // Update table with new data
        if (data.table_html && tableBody) {
            tableBody.innerHTML = data.table_html;
            // Re-attach editable cell listeners después de actualizar la tabla
            if (window.attachEditableCellListeners) {
                window.attachEditableCellListeners();
            }
        }
        
        // Update pagination controls
        const paginationControls = document.getElementById(`paginationControls-${section}`);
        if (data.pagination && data.pagination.links_html && paginationControls) {
            paginationControls.innerHTML = data.pagination.links_html;
        }
        
        // Update pagination info
        const paginationInfo = document.getElementById(`paginationInfo-${section}`);
        if (data.pagination && paginationInfo) {
            paginationInfo.textContent = `Mostrando ${data.pagination.first_item || 0}-${data.pagination.last_item || 0} de ${data.pagination.total} registros`;
        }
        
        // Update progress bar
        const progressFill = document.querySelector(`#pagination-${section} .progress-fill`);
        if (data.pagination && progressFill) {
            const progressPercent = data.pagination.last_page > 0 ? (data.pagination.current_page / data.pagination.last_page) * 100 : 0;
            progressFill.style.width = progressPercent + '%';
        }
        
        // Update URL
        window.history.pushState({}, '', url.toString());
        
        // Restore table
        if (tableBody) {
            tableBody.style.opacity = '1';
            tableBody.style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('Error applying filters:', error);
        if (tableBody) {
            tableBody.style.opacity = '1';
            tableBody.style.pointerEvents = 'auto';
        }
    });
}

function applyFilters(section) {
    // Legacy function - now redirects to backend version
    applyFiltersBackend(section);
}

function updatePaginationInfo(section, visible, total) {
    const container = document.querySelector(`.records-table-container:has(table[data-section="${section}"])`);
    if (!container) return;

    const paginationInfo = container.querySelector('.pagination-info span');
    if (paginationInfo) {
        paginationInfo.textContent = `Mostrando ${visible} de ${total} registros`;
    }
}

// Restaurar filtros guardados desde localStorage
function restoreSavedFilters(section) {
    const savedFilters = localStorage.getItem(`tableros_filters_${section}`);
    if (!savedFilters) return;

    try {
        const filters = JSON.parse(savedFilters);
        if (Object.keys(filters).length === 0) return;

        // Construir URL con filtros guardados
        const url = new URL(window.location.href);
        url.searchParams.set('section', section);
        url.searchParams.set('filters', JSON.stringify(filters));

        // Aplicar filtros sin recargar la página
        const tableBody = document.querySelector(`table[data-section="${section}"] tbody`);
        if (tableBody) {
            tableBody.style.opacity = '0.3';
            tableBody.style.pointerEvents = 'none';
        }

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error || !data.pagination) {
                throw new Error(data.error || 'Respuesta inválida del servidor');
            }
            
            // Update table with filtered data
            if (data.table_html && tableBody) {
                tableBody.innerHTML = data.table_html;
                // Re-attach editable cell listeners
                if (window.attachEditableCellListeners) {
                    window.attachEditableCellListeners();
                }
            }
            
            // Update pagination
            const paginationControls = document.getElementById(`paginationControls-${section}`);
            if (data.pagination && data.pagination.links_html && paginationControls) {
                paginationControls.innerHTML = data.pagination.links_html;
            }
            
            const paginationInfo = document.getElementById(`paginationInfo-${section}`);
            if (data.pagination && paginationInfo) {
                paginationInfo.textContent = `Mostrando ${data.pagination.first_item || 0}-${data.pagination.last_item || 0} de ${data.pagination.total} registros`;
            }
            
            // Restore table
            if (tableBody) {
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
        })
        .catch(error => {
            console.error('Error restoring filters:', error);
            if (tableBody) {
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
        });
    } catch (error) {
        console.error('Error parsing saved filters:', error);
        localStorage.removeItem(`tableros_filters_${section}`);
    }
}

// Limpiar filtros guardados
function clearSavedFilters(section) {
    localStorage.removeItem(`tableros_filters_${section}`);
}
