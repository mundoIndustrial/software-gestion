function tablerosApp() {
    return {
        activeTab: 'produccion',
        showRecords: false,

        setActiveTab(tab) {
            this.activeTab = tab;
            this.showRecords = false; // Reset when changing tabs
        },

        toggleRecords() {
            this.showRecords = !this.showRecords;
            if (this.showRecords) {
                // Initialize filters when showing records
                const currentTab = this.activeTab;
                setTimeout(() => {
                    initializeTableFilters(currentTab);
                }, 100);
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

    // Get unique values for this column
    const uniqueValues = collectUniqueValues(column, section);

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
        // Reset all filters and show all rows
        applyFilters(section);
    });

    applyBtn.addEventListener('click', () => {
        // Only apply filters if there are checked values, otherwise show all rows
        const checkedBoxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
        if (checkedBoxes.length > 0) {
            applyFilters(section);
        } else {
            // Show all rows if no filters are selected
            const table = document.querySelector(`table[data-section="${section}"]`);
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => row.style.display = '');
            updatePaginationInfo(section, rows.length, rows.length);
        }
        dropdown.classList.remove('show');
    });

    // Apply on checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            applyFilters(section);
        });
    });
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

function applyFilters(section) {
    const table = document.querySelector(`table[data-section="${section}"]`);
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;

    rows.forEach(row => {
        let showRow = true;

        // Check each column filter - now search in document.body since dropdowns are positioned there
        document.querySelectorAll(`.filter-dropdown[data-section="${section}"]`).forEach(dropdown => {
            const column = dropdown.dataset.column;
            const checkedValues = Array.from(dropdown.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);

            if (checkedValues.length === 0) return; // No filter applied

            const cell = row.querySelector(`td[data-column="${column}"]`);
            if (!cell) return;

            let cellValue = cell.dataset.value || cell.textContent.trim();
            if (cell.dataset.value) {
                cellValue = cell.dataset.value;
            }

            if (!checkedValues.includes(cellValue)) {
                showRow = false;
            }
        });

        row.style.display = showRow ? '' : 'none';
        if (showRow) visibleCount++;
    });

    // Update pagination info
    updatePaginationInfo(section, visibleCount, rows.length);
}

function updatePaginationInfo(section, visible, total) {
    const container = document.querySelector(`.records-table-container:has(table[data-section="${section}"])`);
    if (!container) return;

    const paginationInfo = container.querySelector('.pagination-info span');
    if (paginationInfo) {
        paginationInfo.textContent = `Mostrando ${visible} de ${total} registros`;
    }
}
