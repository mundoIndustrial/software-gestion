/**
 * Filter Module
 * 
 * Sistema completo de filtrado de tabla con:
 * - Modal de filtro dinámico
 * - Opciones extraídas de la tabla
 * - Filtrado en tiempo real
 * - Persistencia de filtros activos
 * 
 * USO:
 * ====
 * FilterModule.openFilterModal('estado');
 * FilterModule.applyFilters();
 * FilterModule.resetFilters();
 * FilterModule.getActiveFilters();
 */

class FilterModule {
    constructor() {
        this.activeFilters = {};
        this.columnMap = {
            'estado': 1,
            'area': 2,
            'total_dias': 3,
            'numero_recibo': 4,
            'cliente': 5,
            'descripcion': 6,
            'cantidad': 7,
            'novedades': 8,
            'fecha_creacion': 9,
            'fecha_estimada': 10,
            'encargado': 11
        };
        this.filterTitles = {
            'descripcion': 'Filtrar por Descripción',
            'cliente': 'Filtrar por Cliente',
            'estado': 'Filtrar por Estado',
            'area': 'Filtrar por Área',
            'total_dias': 'Filtrar por Total de Días',
            'numero_recibo': 'Filtrar por N° Recibo',
            'cantidad': 'Filtrar por Cantidad',
            'novedades': 'Filtrar por Novedades',
            'fecha_creacion': 'Filtrar por Fecha de Creación',
            'fecha_estimada': 'Filtrar por Fecha Estimada Entrega',
            'encargado': 'Filtrar por Encargado'
        };
    }

    /**
     * Obtener índice de columna según tipo de filtro
     * @param {string} filterType - Tipo de filtro
     * @returns {number} Índice de columna o -1 si no existe
     */
    getColumnIndex(filterType) {
        return this.columnMap[filterType] ?? -1;
    }

    /**
     * Obtener opciones dinámicas desde la tabla
     * @param {string} filterType - Tipo de filtro
     * @returns {array} Array de opciones únicas ordenadas
     */
    getDynamicFilterOptions(filterType) {
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) {
            console.warn('[FilterModule] No se encontró tablaRecibosBody');
            return [];
        }

        const options = new Set();
        const columnIndex = this.getColumnIndex(filterType);

        if (columnIndex === -1) return [];

        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > columnIndex) {
                let cellText = '';

                // Para descripción, leer atributo data-descripcion-detallada
                if (filterType === 'descripcion') {
                    cellText = cells[columnIndex].getAttribute('data-descripcion-detallada') || '';
                } else {
                    cellText = cells[columnIndex].textContent.trim();
                }

                if (cellText && cellText !== '-' && cellText !== 'N/A' && cellText !== '') {
                    options.add(cellText);
                }
            }
        });

        return Array.from(options).sort();
    }

    /**
     * Cargar opciones en el modal de filtro
     * @param {string} filterType - Tipo de filtro a cargar
     */
    loadFilterOptions(filterType) {
        const optionsContainer = document.getElementById('filterOptions');
        if (!optionsContainer) {
            console.error('[FilterModule] No se encontró #filterOptions');
            return;
        }

        const options = this.getDynamicFilterOptions(filterType);

        if (options.length === 0) {
            optionsContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos disponibles para filtrar</div>';
            return;
        }

        // Construir HTML con checkboxes
        let html = `
            <div style="padding: 12px; border-bottom: 1px solid rgb(229, 231, 235); margin-bottom: 8px;">
                <button type="button" class="btn-select-all" onclick="FilterModule.getInstance().selectAllCheckboxes('${filterType}')" style="width: 100%; padding: 8px 12px; background: linear-gradient(135deg, rgb(59, 130, 246), rgb(37, 99, 235)); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; transition: 0.2s; box-shadow: rgba(59, 130, 246, 0.2) 0px 2px 4px;">
                    Seleccionar todas
                </button>
            </div>
        `;

        options.forEach(option => {
            const safeValue = option.replace(/[^a-zA-Z0-9\s]/g, '_');
            html += `
                <div class="filter-option">
                    <input type="checkbox" id="filter-${filterType}-${safeValue}" value="${option}">
                    <label for="filter-${filterType}-${safeValue}">${option}</label>
                </div>
            `;
        });

        optionsContainer.innerHTML = html;
        console.log(`[FilterModule] Cargadas ${options.length} opciones para ${filterType}`);
    }

    /**
     * Abrir modal de filtro
     * @param {string} filterType - Tipo de filtro a mostrar
     */
    openFilterModal(filterType) {
        console.log('[FilterModule] Abriendo filtro:', filterType);

        const modal = document.getElementById('filterModal');
        if (!modal) {
            console.error('[FilterModule] No se encontró #filterModal');
            return;
        }

        // Mostrar modal
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';

        // Guardar tipo de filtro
        modal.setAttribute('data-filter-type', filterType);

        // Actualizar título
        const title = document.getElementById('filterModalTitle');
        if (title) {
            title.textContent = this.filterTitles[filterType] || 'Filtrar';
        }

        // Cargar opciones
        this.loadFilterOptions(filterType);
    }

    /**
     * Cerrar modal de filtro
     */
    closeFilterModal() {
        console.log('[FilterModule] Cerrando modal de filtro');
        const modal = document.getElementById('filterModal');
        if (modal) {
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
        }
    }

    /**
     * Seleccionar/deseleccionar todos los checkboxes
     * @param {string} filterType - Tipo de filtro
     */
    selectAllCheckboxes(filterType) {
        const checkboxes = document.querySelectorAll('#filterOptions input[type="checkbox"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
    }

    /**
     * Filtrar tabla de búsqueda en opciones
     * @param {string} filterType - Tipo de filtro
     */
    filterCheckboxOptions(filterType) {
        const searchTerm = document.querySelector('.filter-search')?.value.toLowerCase() || '';
        const options = document.querySelectorAll('#filterOptions .filter-option');

        options.forEach(option => {
            const label = option.querySelector('label').textContent.toLowerCase();
            option.style.display = label.includes(searchTerm) ? 'block' : 'none';
        });
    }

    /**
     * Aplicar filtros seleccionados
     */
    applyFilters() {
        console.log('[FilterModule] Aplicando filtros...');

        const modal = document.getElementById('filterModal');
        const filterType = modal?.getAttribute('data-filter-type');

        if (!filterType) {
            console.warn('[FilterModule] No se encontró tipo de filtro');
            this.closeFilterModal();
            return;
        }

        // Obtener checkboxes seleccionados
        const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
        const selectedValues = Array.from(checkboxes).map(cb => cb.value);

        console.log('[FilterModule] Filtro:', filterType, 'valores:', selectedValues);

        if (selectedValues.length === 0) {
            console.log('[FilterModule] Sin valores, reiniciando filtros');
            this.resetFilters();
            return;
        }

        // Filtrar tabla
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) {
            console.warn('[FilterModule] No se encontró tablaRecibosBody');
            this.closeFilterModal();
            return;
        }

        const columnIndex = this.getColumnIndex(filterType);
        if (columnIndex === -1) {
            console.warn('[FilterModule] Índice de columna no válido:', filterType);
            this.closeFilterModal();
            return;
        }

        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > columnIndex) {
                let cellText = '';

                if (filterType === 'descripcion') {
                    cellText = cells[columnIndex].getAttribute('data-descripcion-detallada') || '';
                } else {
                    cellText = cells[columnIndex].textContent.trim();
                }

                // Verificar si el valor está en selectedValues
                const isVisible = selectedValues.some(selectedValue => {
                    if (filterType === 'descripcion') {
                        return cellText === selectedValue;
                    } else {
                        return cellText.includes(selectedValue);
                    }
                });

                if (isVisible) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        console.log(`[FilterModule] Filtrado: ${visibleCount}/${rows.length} filas visibles`);

        // Guardar filtros activos
        this.activeFilters[filterType] = selectedValues;

        this.closeFilterModal();
    }

    /**
     * Reiniciar todos los filtros
     */
    resetFilters() {
        console.log('[FilterModule] Reiniciando filtros');

        // Limpiar checkboxes
        const checkboxes = document.querySelectorAll('#filterOptions input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);

        // Mostrar todas las filas
        const tbody = document.getElementById('tablaRecibosBody');
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                row.style.display = '';
            });
            console.log(`[FilterModule] Mostrando todas las ${rows.length} filas`);
        }

        // Limpiar filtros activos
        this.activeFilters = {};

        console.log('[FilterModule] Filtros reiniciados');
    }

    /**
     * Obtener filtros activos
     * @returns {object} Objeto con filtros activos
     */
    getActiveFilters() {
        return { ...this.activeFilters };
    }

    /**
     * Obtener instancia singleton
     */
    static getInstance() {
        if (!window.filterModuleInstance) {
            window.filterModuleInstance = new FilterModule();
        }
        return window.filterModuleInstance;
    }
}

// Crear instancia global
const FilterModule_Instance = FilterModule.getInstance();

// Hacer disponible globalmente
window.FilterModule = FilterModule;
