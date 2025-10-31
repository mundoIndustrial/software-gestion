class ModernTable {
    constructor() {
        this.headers = [];
        this.baseRoute = this.getBaseRoute();
        this.storage = {
            rowHeight: parseInt(this.getStorage('table_rowHeight')) || 50,
            columnWidths: JSON.parse(this.getStorage('table_columnWidths')) || {},
            tableWidth: this.getStorage('table_tableWidth') ? parseInt(this.getStorage('table_tableWidth')) : null,
            tableHeight: parseInt(this.getStorage('table_tableHeight')) || null,
            tablePosition: JSON.parse(this.getStorage('table_tablePosition')) || null,
            headerPosition: JSON.parse(this.getStorage('table_headerPosition')) || null,
            moveTableEnabled: this.getStorage('table_moveTableEnabled') === 'true',
            moveHeaderEnabled: this.getStorage('table_moveHeaderEnabled') === 'true'
        };

        this.virtual = {
            buffer: 10,
            visibleRows: 20,
            startIndex: 0,
            endIndex: 0,
            allData: [],
            totalRows: 0,
            totalDiasCalculados: {},
            enabled: true
        };

        this.init();
    }

    getBaseRoute() {
        return window.location.pathname.includes('/bodega') ? '/bodega' : '/registros';
    }

    getStorage(key) { return localStorage.getItem(key); }
    setStorage(key, val) { localStorage.setItem(key, val); }
    removeStorage(key) { localStorage.removeItem(key); }

    init() {
        this.extractTableData();
        this.applySavedSettings();
        this.setupEventListeners();
        this.setupUI();
        this.markActiveFilters();

        // Apply dragging settings based on saved preferences
        // Note: Dragging is disabled by default, user must enable it manually
    }

    applySavedSettings() {
        const { rowHeight, tableWidth, tableHeight, columnWidths, tablePosition, headerPosition } = this.storage;

        document.documentElement.style.setProperty('--row-height', `${rowHeight}px`);
        document.documentElement.style.setProperty('--table-width', tableWidth ? `${tableWidth}px` : '100%');
        document.documentElement.style.setProperty('--table-height', tableHeight ? `${tableHeight}px` : 'auto');

        Object.entries(columnWidths).forEach(([colIndex, width]) => {
            const th = document.querySelector(`#tablaOrdenes thead th:nth-child(${parseInt(colIndex) + 1})`);
            if (th) th.style.width = `${width}px`;
        });

        document.querySelectorAll('#tablaOrdenes tbody tr').forEach(row => {
            row.style.height = `${rowHeight}px`;
        });

        const wrapper = document.querySelector('.modern-table-wrapper');
        const container = document.querySelector('.table-scroll-container');
        const tableHeader = document.getElementById('tableHeader');

        if (wrapper) {
            wrapper.style.width = 'var(--table-width)';
            wrapper.style.maxWidth = 'var(--table-width)';
            wrapper.style.height = tableHeight ? 'var(--table-height)' : 'auto';
            if (tablePosition) {
                wrapper.style.position = 'absolute';
                wrapper.style.left = `${tablePosition.x}px`;
                wrapper.style.top = `${tablePosition.y}px`;
                if (this.storage.moveTableEnabled) {
                    wrapper.style.cursor = 'move';
                    wrapper.style.zIndex = '999';
                } else {
                    wrapper.style.cursor = '';
                    wrapper.style.zIndex = '';
                }
            } else {
                wrapper.style.position = '';
                wrapper.style.left = '';
                wrapper.style.top = '';
                wrapper.style.cursor = '';
                wrapper.style.zIndex = '';
            }
        }

        if (container) {
            container.style.width = 'var(--table-width)';
            container.style.height = tableHeight ? 'var(--table-height)' : `calc(${rowHeight}px * 14 + 60px)`;
        }

        if (tableHeader && headerPosition) {
            tableHeader.style.position = 'absolute';
            tableHeader.style.left = `${headerPosition.x}px`;
            tableHeader.style.top = `${headerPosition.y}px`;
            if (this.storage.moveHeaderEnabled) {
                tableHeader.style.cursor = 'move';
                tableHeader.style.zIndex = '998';
            } else {
                tableHeader.style.cursor = '';
                tableHeader.style.zIndex = '';
            }
        } else if (tableHeader) {
            tableHeader.style.position = '';
            tableHeader.style.left = '';
            tableHeader.style.top = '';
            tableHeader.style.cursor = '';
            tableHeader.style.zIndex = '';
        }
    }

    createResizers() {
        const thead = document.querySelector('#tablaOrdenes thead');
        if (!thead) {
            return;
        }

        thead.querySelectorAll('th').forEach((th, i) => {
            const resizer = document.createElement('div');
            resizer.className = 'column-resizer';
            resizer.dataset.column = i;
            th.style.position = 'relative';
            th.appendChild(resizer);
        });
    }

    createButton(id, className, icon, text, style = '') {
        const btn = document.createElement('button');
        Object.assign(btn, { id, className });
        btn.style.cssText = `margin-left:10px;font-size:12px;${style}`;
        btn.innerHTML = `<i class="fas ${icon}"></i><span>${text}</span>`;
        return btn;
    }



    setupColumnResizing() {
    let state = { isResizing: false, resizer: null, startX: 0, startWidth: 0, column: null };

    const handleMove = e => {
        if (!state.isResizing) return;
        const delta = e.clientX - state.startX;
        const newWidth = Math.max(50, state.startWidth + delta);
        const th = state.resizer.parentElement;
        const colIndex = state.column;

        // Aplica ancho al <th>
        th.style.width = `${newWidth}px`;
        th.style.setProperty('--col-width', `${newWidth}px`);

        // Aplica ancho a todas las <td> de esa columna
        document.querySelectorAll(`#tablaOrdenes tbody td:nth-child(${colIndex + 1})`).forEach(td => {
            td.style.width = `${newWidth}px`;
            td.style.setProperty('--col-width', `${newWidth}px`);
        });

        // Guarda ancho en localStorage
        this.storage.columnWidths[colIndex] = newWidth;
        this.setStorage('table_columnWidths', JSON.stringify(this.storage.columnWidths));
    };

    const handleUp = () => {
        if (!state.isResizing) return;
        state.isResizing = false;
        state.resizer?.classList.remove('dragging');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    };

    document.addEventListener('mousedown', e => {
        if (e.target.classList.contains('column-resizer')) {
            const th = e.target.parentElement;
            const colIndex = parseInt(e.target.dataset.column);
            state = {
                isResizing: true,
                resizer: e.target,
                column: colIndex,
                startX: e.clientX,
                startWidth: th.offsetWidth
            };
            e.target.classList.add('dragging');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        }
    });

    document.addEventListener('mousemove', handleMove);
    document.addEventListener('mouseup', handleUp);
}




    extractTableData() {
        const table = document.getElementById('tablaOrdenes');
        this.headers = Array.from(table.querySelectorAll('thead th')).map((th, i) => {
            const headerText = th.querySelector('.header-text').textContent.trim();
            const filterBtn = th.querySelector('.filter-btn');
            return {
                index: i,
                name: headerText,
                originalName: filterBtn ? filterBtn.dataset.columnName : headerText.toLowerCase().replace(/\s+/g, '_')
            };
        });
    }

    createCellElement(key, value, orden) {
        const td = document.createElement('td');
        td.className = 'table-cell';
        td.dataset.column = key;

        const content = document.createElement('div');
        content.className = 'cell-content';
        content.title = value;

        if (key === 'estado' || key === 'area') {
            const select = document.createElement('select');
            select.className = `${key}-dropdown`;
            select.dataset.id = orden.id;
            select.dataset.value = value;

            const options = key === 'estado' 
                ? ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada']
                : window.areaOptions || [];

            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = option.textContent = opt;
                if (value === opt) option.selected = true;
                select.appendChild(option);
            });

            content.appendChild(select);
        } else {
            const span = document.createElement('span');
            span.className = 'cell-text';
            const displayValue = key === 'total_de_dias_'
                ? this.virtual.totalDiasCalculados[orden.id] ?? 'N/A'
                : value ?? '';
            span.textContent = this.wrapText(displayValue, 20);
            span.style.whiteSpace = 'nowrap';
            span.style.overflow = 'visible';
            content.appendChild(span);
        }

        td.appendChild(content);
        return td;
    }

    createVirtualRow(orden, globalIndex) {
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.dataset.orderId = orden.id;
        row.dataset.globalIndex = globalIndex;

        Object.entries(orden).forEach(([key, value]) => {
            row.appendChild(this.createCellElement(key, value, orden));
        });

        return row;
    }

    setupUI() {
        this.setupCellTextWrapping();
        this.createResizers();
        this.setupColumnResizing();
    }

    markActiveFilters() {
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
    }

    setupEventListeners() {
        document.getElementById('buscarOrden')?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                this.performAjaxSearch(e.target.value);
            }
        });

        document.addEventListener('change', e => {
            if (e.target.classList.contains('estado-dropdown')) this.updateOrderStatus(e.target);
            if (e.target.classList.contains('area-dropdown')) this.updateOrderArea(e.target);
        });

        document.addEventListener('click', e => {
            // Buscar el botón de filtro, ya sea que se haga clic en el botón o en el icono dentro
            const filterBtn = e.target.closest('.filter-btn');
            if (filterBtn) {
                e.preventDefault();
                e.stopPropagation();
                this.openFilterModal(parseInt(filterBtn.dataset.column), filterBtn.dataset.columnName);
            } else if (e.target.classList.contains('page-link') && !e.target.classList.contains('disabled')) {
                e.preventDefault();
                const href = e.target.getAttribute('href');
                if (href) this.loadPageFromUrl(href);
            } else if (e.target.closest('.table-cell') && !e.target.closest('select')) {
                this.selectCell(e.target.closest('.table-cell'));
            }
        });

        document.addEventListener('dblclick', e => {
            const cell = e.target.closest('.cell-content');
            if (cell && !cell.querySelector('select')) {
                const cellText = cell.querySelector('.cell-text');
                if (cellText) {
                    const td = cell.closest('td');
                    const row = td.closest('tr');
                    this.openCellModal(cellText.textContent, row.dataset.orderId, td.dataset.column);
                }
            }
        });

        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'c') {
                const selected = document.querySelector('.table-cell.selected .cell-text');
                if (selected) navigator.clipboard.writeText(selected.textContent);
            }
        });

        this.setupModalEvents();
    }



    updateVirtualRows() {
        if (!this.virtual.enabled || !this.virtual.allData.length) return;

        const container = document.querySelector('.table-scroll-container');
        if (!container) return;

        const { scrollTop, clientHeight } = container;
        const { rowHeight } = this.storage;
        const { buffer, totalRows } = this.virtual;

        const startIndex = Math.max(0, Math.floor(scrollTop / rowHeight) - buffer);
        const endIndex = Math.min(totalRows - 1, Math.ceil((scrollTop + clientHeight) / rowHeight) + buffer);

        if (startIndex !== this.virtual.startIndex || endIndex !== this.virtual.endIndex) {
            this.virtual.startIndex = startIndex;
            this.virtual.endIndex = endIndex;
            this.renderVirtualRows();
        }
    }

    renderVirtualRows() {
        if (!this.virtual.enabled || !this.virtual.allData.length) return;

        const tbody = document.querySelector('#tablaOrdenes tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        const visibleData = this.virtual.allData.slice(this.virtual.startIndex, this.virtual.endIndex + 1);

        visibleData.forEach((orden, i) => {
            tbody.appendChild(this.createVirtualRow(orden, this.virtual.startIndex + i));
        });

        tbody.style.transform = `translateY(${this.virtual.startIndex * this.storage.rowHeight}px)`;
        tbody.style.height = `${this.virtual.totalRows * this.storage.rowHeight}px`;

        this.setupCellTextWrapping();
        this.initializeStatusDropdowns();
    }



    async loadNextPage() {
        const nextLink = document.querySelector('.pagination .page-link[rel="next"]');
        if (!nextLink) return;

        const url = new URL(window.location);
        const currentPage = parseInt(url.searchParams.get('page')) || 1;
        const params = new URLSearchParams(url.search);
        params.set('page', currentPage + 1);

        try {
            const response = await fetch(`${this.baseRoute}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.orders?.length) {
                this.appendRowsToTable(data.orders, data.totalDiasCalculados);
                this.updatePaginationInfo(data.pagination);
                this.updatePaginationControls(data.pagination_html);
                this.updateUrl(params.toString());
            }
        } catch (error) {
            console.error('Error cargando página:', error);
        }
    }

    setupCellTextWrapping() {
        document.querySelectorAll('.cell-text').forEach(cell => {
            cell.textContent = this.wrapText(cell.textContent, 20);
            cell.style.whiteSpace = 'nowrap';
            cell.style.overflow = 'visible';
        });
    }

    wrapText(text, maxChars) {
        // Para revelado gradual, devolver el texto completo sin wrapping
        return text || '';
    }

    setupModalEvents() {
        ['#closeModal', '#cancelFilter', '#closeCellModal'].forEach(sel => {
            document.querySelector(sel)?.addEventListener('click', () => {
                this.closeFilterModal();
                this.closeCellModal();
            });
        });

        document.getElementById('modalOverlay')?.addEventListener('click', () => {
            this.closeFilterModal();
            this.closeCellModal();
        });

        document.getElementById('applyFilter')?.addEventListener('click', () => this.applyServerSideColumnFilter());
        document.getElementById('selectAll')?.addEventListener('click', () => this.selectAllFilterItems(true));
        document.getElementById('deselectAll')?.addEventListener('click', () => this.selectAllFilterItems(false));
        document.getElementById('filterSearch')?.addEventListener('input', e => this.filterModalItems(e.target.value.toLowerCase()));

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                this.closeFilterModal();
                this.closeCellModal();
            }
        });
    }

    async openFilterModal(columnIndex, columnName) {
        this.currentColumn = columnIndex;
        this.currentColumnName = columnName;
        const modal = document.getElementById('filterModal');
        const overlay = document.getElementById('modalOverlay');
        const filterList = document.getElementById('filterList');
        
        document.getElementById('filterColumnName').textContent = columnName;
        document.getElementById('filterSearch').value = '';

        try {
            const response = await fetch(`${this.baseRoute}?get_unique_values=1&column=${encodeURIComponent(columnName)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            this.generateFilterList(data.unique_values || [], columnIndex);
        } catch (error) {
            console.error('Error fetching values:', error);
            const values = [...new Set(
                Array.from(document.querySelectorAll(`#tablaOrdenes tbody tr td:nth-child(${columnIndex + 1})`))
                    .map(td => td.querySelector('select')?.value || td.querySelector('.cell-text')?.textContent.trim() || td.textContent.trim())
                    .filter(v => v)
            )].sort();
            this.generateFilterList(values, columnIndex);
        }

        overlay.classList.add('active');
        modal.classList.add('active');
    }

    generateFilterList(values, columnIndex) {
        const url = new URL(window.location);
        const currentFilter = url.searchParams.get(`filter_${this.currentColumnName}`);
        const filteredValues = currentFilter ? currentFilter.split(',') : [];

        const filterList = document.getElementById('filterList');
        filterList.innerHTML = values.map(val => {
            const isChecked = filteredValues.length === 0 || filteredValues.includes(val);
            return `
                <div class="filter-item" data-value="${val}">
                    <input type="checkbox" id="filter_${columnIndex}_${val}" value="${val}" ${isChecked ? 'checked' : ''}>
                    <label for="filter_${columnIndex}_${val}">${val}</label>
                </div>
            `;
        }).join('');

        filterList.querySelectorAll('.filter-item').forEach(item => {
            item.addEventListener('click', e => {
                if (e.target.type !== 'checkbox') {
                    const cb = item.querySelector('input');
                    cb.checked = !cb.checked;
                }
            });
        });
    }

    filterModalItems(term) {
        document.querySelectorAll('.filter-item').forEach(item => {
            item.style.display = item.querySelector('label').textContent.toLowerCase().includes(term) ? 'flex' : 'none';
        });
    }

    selectAllFilterItems(select) {
        document.querySelectorAll('.filter-item:not([style*="none"]) input').forEach(cb => cb.checked = select);
    }

    applyServerSideColumnFilter() {
        const selected = Array.from(document.querySelectorAll('#filterList input:checked')).map(cb => cb.value);
        this.applyServerSideFilter(`filter_${this.currentColumnName}`, selected.length ? selected.join(',') : '');
        this.closeFilterModal();
    }

    applyServerSideFilter(key, value) {
        const url = new URL(window.location);
        value ? url.searchParams.set(key, value) : url.searchParams.delete(key);
        url.searchParams.delete('page');
        
        // Aplicar filtro con AJAX sin recargar
        this.loadTableWithAjax(url.toString());
    }

    loadTableWithAjax(url) {
        const tableBody = document.getElementById('tablaOrdenesBody');
        const paginationControls = document.getElementById('paginationControls');
        const paginationInfo = document.getElementById('paginationInfo');
        
        // Indicador de carga
        tableBody.style.transition = 'opacity 0.1s';
        tableBody.style.opacity = '0.3';
        tableBody.style.pointerEvents = 'none';
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Actualizar tabla
            const newTableBody = doc.getElementById('tablaOrdenesBody');
            if (newTableBody) {
                tableBody.innerHTML = newTableBody.innerHTML;
            }
            
            // Actualizar paginación
            const newPaginationControls = doc.getElementById('paginationControls');
            if (newPaginationControls && paginationControls) {
                paginationControls.innerHTML = newPaginationControls.innerHTML;
            }
            
            const newPaginationInfo = doc.getElementById('paginationInfo');
            if (newPaginationInfo && paginationInfo) {
                paginationInfo.innerHTML = newPaginationInfo.innerHTML;
            }
            
            // Actualizar URL sin recargar
            window.history.pushState({}, '', url);
            
            // Restaurar
            tableBody.style.opacity = '1';
            tableBody.style.pointerEvents = 'auto';
            
            // Actualizar marcadores de filtros activos
            this.markActiveFilters();
            
            // Scroll a la tabla
            document.querySelector('.table-container')?.scrollIntoView({ 
                behavior: 'auto', 
                block: 'start' 
            });
        })
        .catch(error => {
            console.error('Error al aplicar filtro:', error);
            tableBody.style.opacity = '1';
            tableBody.style.pointerEvents = 'auto';
        });
    }

    closeFilterModal() {
        document.getElementById('filterModal')?.classList.remove('active');
        document.getElementById('modalOverlay')?.classList.remove('active');
    }

    selectCell(cell) {
        document.querySelectorAll('.table-cell.selected').forEach(c => c.classList.remove('selected'));
        cell.classList.add('selected');
    }

    openCellModal(content, orderId, column) {
        this.currentOrderId = orderId;
        this.currentColumn = column;
        const input = document.getElementById('cellEditInput');
        input.value = content.split('\n').map(line => line.trimStart()).join('\n');
        input.focus();
        input.select();

        const save = () => this.saveCellEdit();
        const cancel = () => this.closeCellModal();
        const keyHandler = e => {
            if (e.key === 'Enter') { e.preventDefault(); save(); }
            else if (e.key === 'Escape') cancel();
        };

        document.getElementById('saveCellEdit').onclick = save;
        document.getElementById('cancelCellEdit').onclick = cancel;
        input.onkeydown = keyHandler;

        document.getElementById('modalOverlay').classList.add('active');
        document.getElementById('cellModal').classList.add('active');
    }

    async saveCellEdit() {
        const newValue = document.getElementById('cellEditInput').value;
        const oldValue = document.querySelector('.table-cell.selected .cell-text')?.textContent || '';

        try {
            const response = await fetch(`${this.baseRoute}/${this.currentOrderId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-HTTP-Method-Override': 'PATCH'
                },
                body: JSON.stringify({ [this.currentColumn]: newValue })
            });

            const data = await response.json();

            if (data.success) {
                const selected = document.querySelector('.table-cell.selected');
                if (selected) {
                    const cellText = selected.querySelector('.cell-text');
                    if (cellText) {
                        cellText.textContent = newValue;
                        cellText.innerHTML = this.wrapText(newValue, 20);
                        selected.querySelector('.cell-content').title = newValue;
                    }
                }

                this.closeCellModal();
            } else {
                alert('Error al guardar los cambios');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al guardar los cambios');
        }
    }

    closeCellModal() {
        document.getElementById('cellModal')?.classList.remove('active');
        document.getElementById('modalOverlay')?.classList.remove('active');
    }

    updateRowColor(orderId, status) {
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (!row) return;

        // Remover clases de color anteriores
        row.classList.remove('status-pendiente', 'status-proceso', 'status-completado', 'status-cancelado');

        // Agregar clase de color según el estado
        const statusClass = `status-${status.toLowerCase().replace(/\s+/g, '-')}`;
        row.classList.add(statusClass);
    }

    async updateOrderStatus(dropdown) {
        const orderId = dropdown.dataset.id;
        const newStatus = dropdown.value;
        const oldStatus = dropdown.dataset.value;

        try {
            const response = await fetch(`${this.baseRoute}/${orderId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ estado: newStatus })
            });

            const data = await response.json();
            if (data.success) {
                // Actualizar color de la fila dinámicamente
                this.updateRowColor(orderId, newStatus);
            } else {
                console.error('Error actualizando:', data.message);
                // Revertir cambio en caso de error
                dropdown.value = oldStatus;
            }
        } catch (error) {
            console.error('Error:', error);
            // Revertir cambio en caso de error
            dropdown.value = oldStatus;
        }
    }

    async performAjaxSearch(term) {
        const url = new URL(window.location);
        const params = new URLSearchParams(url.search);
        term ? params.set('search', term) : params.delete('search');
        params.set('page', 1);

        try {
            const response = await fetch(`${this.baseRoute}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();
            this.updateTableWithData(data.orders, data.totalDiasCalculados);
            this.updatePaginationInfo(data.pagination);
            this.updatePaginationControls(data.pagination_html);
            this.updateUrl(params.toString());
            this.initializeStatusDropdowns();
        } catch (error) {
            console.error('Error en búsqueda:', error);
            window.location.href = `${this.baseRoute}?${params}`;
        }
    }

    updateTableWithData(orders, totalDiasCalculados) {
    this.virtual.allData = orders;
    this.virtual.totalDiasCalculados = totalDiasCalculados || {};
    this.virtual.totalRows = orders.length;
    this.virtual.startIndex = this.virtual.endIndex = 0;

    const tbody = document.querySelector('#tablaOrdenes tbody');
    tbody.innerHTML = '';
    
    if (orders.length === 0) {
        tbody.innerHTML = `
            <tr class="table-row">
                <td colspan="51" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                    No hay resultados que coincidan con los filtros aplicados.
                </td>
            </tr>
        `;
        return;
    }

    orders.forEach(orden => {
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.dataset.orderId = orden.pedido || orden.id;

        // PRIMERO: Crear la columna de acciones
        const accionesTd = document.createElement('td');
        accionesTd.className = 'table-cell acciones-column';
        const accionesDiv = document.createElement('div');
        accionesDiv.className = 'cell-content';
        accionesDiv.innerHTML = `
            <button class="action-btn delete-btn" onclick="deleteOrder(${orden.pedido || orden.id})" 
                title="Eliminar orden"
                style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; margin-right: 5px; border-radius: 4px; cursor: pointer;">
                Borrar
            </button>
            <button class="action-btn detail-btn" onclick="viewDetail(${orden.pedido || orden.id})" 
                title="Ver detalle"
                style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                Ver
            </button>
        `;
        accionesTd.appendChild(accionesDiv);
        row.appendChild(accionesTd);

        // DESPUÉS: Crear las demás columnas basándose en el thead
        const theadRow = document.querySelector('#tablaOrdenes thead tr');
        const ths = Array.from(theadRow.querySelectorAll('th'));
        
        // Saltar el primer th (acciones) e iterar sobre los demás
        for (let i = 1; i < ths.length; i++) {
            const th = ths[i];
            const column = th.dataset.column;
            
            if (!column) continue;
            
            const val = orden[column];
            row.appendChild(this.createCellElement(column, val, orden));
        }

        tbody.appendChild(row);
    });
    
    this.setupCellTextWrapping();
    this.initializeStatusDropdowns();
}

    updatePaginationInfo(pagination) {
        const info = document.querySelector('.pagination-info span');
        if (info) info.textContent = `Mostrando ${pagination.from}-${pagination.to} de ${pagination.total} registros`;
    }

    updatePaginationControls(html) {
        const controls = document.querySelector('.pagination-controls');
        if (controls && html) controls.innerHTML = html;
    }

    updateUrl(queryString) {
        window.history.pushState(null, '', `${window.location.pathname}?${queryString}`);
    }

appendRowsToTable(orders, totalDiasCalculados) {
    const tbody = document.querySelector('#tablaOrdenes tbody');
    
    orders.forEach(orden => {
        const row = document.createElement('tr');
        row.className = 'table-row';
        row.dataset.orderId = orden.pedido || orden.id;

        // PRIMERO: Crear la columna de acciones
        const accionesTd = document.createElement('td');
        accionesTd.className = 'table-cell acciones-column';
        const accionesDiv = document.createElement('div');
        accionesDiv.className = 'cell-content';
        accionesDiv.innerHTML = `
            <button class="action-btn delete-btn" onclick="deleteOrder(${orden.pedido || orden.id})" 
                title="Eliminar orden"
                style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; margin-right: 5px; border-radius: 4px; cursor: pointer;">
                Borrar
            </button>
            <button class="action-btn detail-btn" onclick="viewDetail(${orden.pedido || orden.id})" 
                title="Ver detalle"
                style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                Ver
            </button>
        `;
        accionesTd.appendChild(accionesDiv);
        row.appendChild(accionesTd);

        // DESPUÉS: Crear las demás columnas
        const theadRow = document.querySelector('#tablaOrdenes thead tr');
        const ths = Array.from(theadRow.querySelectorAll('th'));
        
        for (let i = 1; i < ths.length; i++) {
            const th = ths[i];
            const column = th.dataset.column;
            
            if (!column) continue;
            
            const val = orden[column];
            row.appendChild(this.createCellElement(column, val, orden));
        }

        tbody.appendChild(row);
    });
    
    this.initializeStatusDropdowns();
}
    initializeStatusDropdowns() {
        document.querySelectorAll('.estado-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', e => this.updateOrderStatus(e.target));
        });
    }

    initializeAreaDropdowns() {
        document.querySelectorAll('.area-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', e => this.updateOrderArea(e.target));
        });
    }

    async updateOrderArea(dropdown) {
        const orderId = dropdown.dataset.id;
        const newArea = dropdown.value;
        const oldArea = dropdown.dataset.value;

        try {
            const response = await fetch(`${this.baseRoute}/${orderId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ area: newArea })
            });

            const data = await response.json();
            if (data.success) {
                // Actualizar las celdas con las fechas actualizadas según la respuesta del servidor
                if (data.updated_fields) {
                    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                    if (row) {
                        for (const [field, date] of Object.entries(data.updated_fields)) {
                            const cell = row.querySelector(`td[data-column="${field}"] .cell-text`);
                            if (cell) {
                                cell.textContent = date;
                            }
                        }
                    }
                }
            } else {
                console.error('Error actualizando área:', data.message);
                // Revertir cambio en caso de error
                dropdown.value = oldArea;
            }
        } catch (error) {
            console.error('Error:', error);
            // Revertir cambio en caso de error
            dropdown.value = oldArea;
        }
    }

    async loadPageFromUrl(href) {
        const url = new URL(href);
        const params = new URLSearchParams(url.search);

        try {
            const response = await fetch(`${this.baseRoute}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();
            this.updateTableWithData(data.orders, data.totalDiasCalculados);
            this.updatePaginationInfo(data.pagination);
            this.updatePaginationControls(data.pagination_html);
            this.updateUrl(params.toString());
            this.initializeStatusDropdowns();
        } catch (error) {
            console.error('Error:', error);
            window.location.href = href;
        }
    }

    clearAllFilters() {
        const url = new URL(window.location);
        Array.from(url.searchParams.keys()).forEach(key => {
            if (key.startsWith('filter_') || key === 'search') url.searchParams.delete(key);
        });
        url.searchParams.delete('page');
        
        // Limpiar campo de búsqueda si existe
        const searchInput = document.getElementById('buscarOrden');
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Usar AJAX en lugar de recargar
        this.loadTableWithAjax(url.toString());
    }

    exportFilteredData() {
        alert('Exportar datos filtrados - Funcionalidad por implementar en el servidor');
    }

    enableTableDragging() {
        const tableWrapper = document.querySelector('.modern-table-wrapper');
        if (!tableWrapper) return;

        tableWrapper.style.position = 'absolute';
        tableWrapper.style.cursor = 'move';
        tableWrapper.style.zIndex = '999';

        let isDragging = false;
        let startX, startY, initialX, initialY;

        const mouseDownHandler = (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialX = tableWrapper.offsetLeft;
            initialY = tableWrapper.offsetTop;

            document.addEventListener('mousemove', mouseMoveHandler);
            document.addEventListener('mouseup', mouseUpHandler);
        };

        const mouseMoveHandler = (e) => {
            if (!isDragging) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            let newX = initialX + dx;
            let newY = initialY + dy;

            // Prevent dragging over sidebar
            const sidebar = document.querySelector('.sidebar, #sidebar, .main-sidebar');
            if (sidebar) {
                const sidebarRect = sidebar.getBoundingClientRect();
                if (newX < sidebarRect.right) {
                    newX = sidebarRect.right;
                }
            }

            // Prevent dragging above top of viewport
            if (newY < 0) {
                newY = 0;
            }

            tableWrapper.style.left = `${newX}px`;
            tableWrapper.style.top = `${newY}px`;
        };

        const mouseUpHandler = () => {
            isDragging = false;
            // Save position to localStorage
            this.storage.tablePosition = { x: parseInt(tableWrapper.style.left || 0), y: parseInt(tableWrapper.style.top || 0) };
            this.setStorage('table_tablePosition', JSON.stringify(this.storage.tablePosition));
            document.removeEventListener('mousemove', mouseMoveHandler);
            document.removeEventListener('mouseup', mouseUpHandler);
        };

        tableWrapper.addEventListener('mousedown', mouseDownHandler);
        tableWrapper._dragHandler = mouseDownHandler;
    }

    disableTableDragging() {
        const tableWrapper = document.querySelector('.modern-table-wrapper');
        if (!tableWrapper) return;

        // Remove all dragging-related styles
        tableWrapper.style.position = '';
        tableWrapper.style.left = '';
        tableWrapper.style.top = '';
        tableWrapper.style.cursor = '';
        tableWrapper.style.zIndex = '';

        if (tableWrapper._dragHandler) {
            tableWrapper.removeEventListener('mousedown', tableWrapper._dragHandler);
            delete tableWrapper._dragHandler;
        }
    }

    enableHeaderDragging() {
        const tableHeader = document.getElementById('tableHeader');
        if (!tableHeader) return;

        tableHeader.style.position = 'absolute';
        tableHeader.style.cursor = 'move';
        tableHeader.style.zIndex = '998';

        let isDragging = false;
        let startX, startY, initialX, initialY;

        const mouseDownHandler = (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialX = tableHeader.offsetLeft;
            initialY = tableHeader.offsetTop;

            document.addEventListener('mousemove', mouseMoveHandler);
            document.addEventListener('mouseup', mouseUpHandler);
        };

        const mouseMoveHandler = (e) => {
            if (!isDragging) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            let newX = initialX + dx;
            let newY = initialY + dy;

            // Prevent dragging over sidebar
            const sidebar = document.querySelector('.sidebar, #sidebar, .main-sidebar');
            if (sidebar) {
                const sidebarRect = sidebar.getBoundingClientRect();
                if (newX < sidebarRect.right) {
                    newX = sidebarRect.right;
                }
            }

            // Prevent dragging above top of viewport
            if (newY < 0) {
                newY = 0;
            }

            tableHeader.style.left = `${newX}px`;
            tableHeader.style.top = `${newY}px`;
        };

        const mouseUpHandler = () => {
            isDragging = false;
            // Save position to localStorage
            this.storage.headerPosition = { x: parseInt(tableHeader.style.left || 0), y: parseInt(tableHeader.style.top || 0) };
            this.setStorage('table_headerPosition', JSON.stringify(this.storage.headerPosition));
            document.removeEventListener('mousemove', mouseMoveHandler);
            document.removeEventListener('mouseup', mouseUpHandler);
        };

        tableHeader.addEventListener('mousedown', mouseDownHandler);
        tableHeader._dragHandler = mouseDownHandler;
    }

    disableHeaderDragging() {
        const tableHeader = document.getElementById('tableHeader');
        if (!tableHeader) return;

        // Remove all dragging-related styles
        tableHeader.style.position = '';
        tableHeader.style.left = '';
        tableHeader.style.top = '';
        tableHeader.style.cursor = '';
        tableHeader.style.zIndex = '';

        if (tableHeader._dragHandler) {
            tableHeader.removeEventListener('mousedown', tableHeader._dragHandler);
            delete tableHeader._dragHandler;
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tablaOrdenes')) {
        const modernTable = new ModernTable();
        window.modernTable = modernTable;

        // Add clear filters button
        const clearBtn = Object.assign(document.createElement('button'), {
            textContent: 'Limpiar Filtros',
            className: 'btn btn-secondary ml-2',
            style: 'font-size:12px;'
        });
        clearBtn.addEventListener('click', () => modernTable.clearAllFilters());

        // Add register orders button
        const registerBtn = Object.assign(document.createElement('button'), {
            textContent: 'Registrar Órdenes',
            className: 'btn btn-primary ml-2',
            style: 'font-size:12px; background-color: #ff9d58; border-color: #ff9d58; color: #fff;'
        });
        registerBtn.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-registration' }));
        });

        document.querySelector('.table-actions')?.appendChild(clearBtn);
        document.querySelector('.table-actions')?.appendChild(registerBtn);
    }
})