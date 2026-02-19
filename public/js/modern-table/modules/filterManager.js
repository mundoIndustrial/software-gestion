/**
 * FilterManager
 * Responsabilidad: Gestionar filtros de la tabla
 * SOLID: Single Responsibility
 */
const FilterManager = (() => {
    return {
        // Marcar filtros activos en URL
        markActiveFilters: () => {
            const url = new URL(window.location);
            document.querySelectorAll('.filter-btn').forEach(btn => {
                const columnName = btn.dataset.columnName;
                const filterParam = `filter_${columnName}`;
                if (url.searchParams.has(filterParam)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        },

        // Abrir modal de filtro
        openFilterModal: (columnIndex, columnName, baseRoute) => {
            const modal = document.getElementById('filterModal');
            const overlay = document.getElementById('modalOverlay');
            const filterList = document.getElementById('filterList');
            
            document.getElementById('filterColumnName').textContent = columnName;
            document.getElementById('filterSearch').value = '';
            
            filterList.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;"><i class="fas fa-spinner fa-spin"></i> Cargando valores...</div>';
            
            overlay.classList.add('active');
            modal.classList.add('active');

            fetch(`${baseRoute}?get_unique_values=1&column=${encodeURIComponent(columnName)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => FilterManager.generateFilterList(data.unique_values || [], columnIndex, columnName))
            .catch(error => {

                const values = [...new Set(
                    Array.from(document.querySelectorAll(`#tablaOrdenes tbody tr td:nth-child(${columnIndex + 1})`))
                        .map(td => td.querySelector('select')?.value || td.querySelector('.cell-text')?.textContent.trim() || td.textContent.trim())
                        .filter(v => v)
                )].sort();
                FilterManager.generateFilterList(values, columnIndex, columnName);
            });
        },

        // Generar lista de filtro
        generateFilterList: (values, columnIndex, columnName) => {
            const url = new URL(window.location);
            const currentFilter = url.searchParams.get(`filter_${columnName}`);
            const filteredValues = currentFilter ? currentFilter.split(',') : [];
            
            const filterList = document.getElementById('filterList');
            filterList.innerHTML = values.map(val => {
                const valStr = String(val);
                const isChecked = filteredValues.length === 0 || filteredValues.includes(valStr);
                return `
                    <div class="filter-item" data-value="${val}">
                        <input type="checkbox" id="filter_${columnIndex}_${val}" value="${val}" ${isChecked ? 'checked' : ''}>
                        <label for="filter_${columnIndex}_${val}">${val}</label>
                    </div>
                `;
            }).join('');

            filterList.querySelectorAll('.filter-item').forEach(item => {
                item.addEventListener('click', e => {
                    if (e.target.tagName !== 'INPUT') {
                        e.preventDefault();
                        item.querySelector('input').checked = !item.querySelector('input').checked;
                    }
                });
            });
        },

        // Filtrar items del modal
        filterModalItems: (term) => {
            document.querySelectorAll('.filter-item').forEach(item => {
                item.style.display = item.querySelector('label').textContent.toLowerCase().includes(term) ? 'flex' : 'none';
            });
        },

        // Seleccionar/deseleccionar todos
        selectAllFilterItems: (select) => {
            document.querySelectorAll('.filter-item:not([style*="none"]) input').forEach(cb => cb.checked = select);
        },

        // Aplicar filtro del servidor
        applyServerSideColumnFilter: (columnName, baseRoute) => {
            const selected = Array.from(document.querySelectorAll('#filterList input:checked')).map(cb => cb.value);
            const separator = '|||FILTER_SEPARATOR|||';
            const filterValue = selected.length ? selected.join(separator) : '';
            
            FilterManager.applyServerSideFilter(`filter_${columnName}`, filterValue, baseRoute);
            FilterManager.closeFilterModal();
        },

        // Aplicar filtro en servidor
        applyServerSideFilter: (key, value, baseRoute) => {
            const url = new URL(window.location);
            value ? url.searchParams.set(key, value) : url.searchParams.delete(key);
            url.searchParams.delete('page');
            
            FilterManager.loadTableWithAjax(url.toString());
        },

        // Cargar tabla con AJAX
        loadTableWithAjax: (url) => {
            const tableBody = document.getElementById('tablaOrdenesBody');
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');
            
            tableBody.style.transition = 'opacity 0.1s';
            tableBody.style.opacity = '0.3';
            tableBody.style.pointerEvents = 'none';
            
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newTableBody = doc.getElementById('tablaOrdenesBody');
                if (newTableBody) tableBody.innerHTML = newTableBody.innerHTML;
                
                const newPaginationControls = doc.getElementById('paginationControls');
                if (newPaginationControls && paginationControls) paginationControls.innerHTML = newPaginationControls.innerHTML;
                
                const newPaginationInfo = doc.getElementById('paginationInfo');
                if (newPaginationInfo && paginationInfo) paginationInfo.innerHTML = newPaginationInfo.innerHTML;
                
                window.history.pushState({}, '', url);
                
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
                
                FilterManager.markActiveFilters();
                
                if (typeof initializeStatusDropdowns === 'function') initializeStatusDropdowns();
                if (typeof actualizarDiasTabla === 'function') actualizarDiasTabla();
                
                document.querySelector('.table-container')?.scrollIntoView({ behavior: 'auto', block: 'start' });
            })
            .catch(error => {

                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            });
        },

        // Cerrar modal de filtro
        closeFilterModal: () => {
            document.getElementById('filterModal')?.classList.remove('active');
            document.getElementById('modalOverlay')?.classList.remove('active');
        },

        // Limpiar todos los filtros
        clearAllFilters: (baseRoute) => {
            const url = new URL(window.location);
            Array.from(url.searchParams.keys()).forEach(key => {
                if (key.startsWith('filter_') || key === 'search') url.searchParams.delete(key);
            });
            url.searchParams.delete('page');
            
            const searchInput = document.getElementById('buscarOrden');
            if (searchInput) searchInput.value = '';
            
            FilterManager.loadTableWithAjax(url.toString());
        }
    };
})();

globalThis.FilterManager = FilterManager;
