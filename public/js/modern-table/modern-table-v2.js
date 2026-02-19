/**
 * ModernTable v2 - Refactorizado con SOLID
 * Orchestrator que coordina todos los módulos
 * Responsabilidad: Orquestar y coordinar los módulos
 */
class ModernTableV2 {
    constructor() {

        this.baseRoute = this.getBaseRoute();
        this.storage = StorageManager.loadSettings();
        
        this.headers = [];
        this.currentOrderId = null;
        this.currentColumn = null;
        this.currentColumnName = null;
        this.isLoadingFilter = false;
        
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
        return globalThis.location.pathname.includes('/bodega') ? '/bodega' : '/registros';
    }

    init() {

        this.headers = ColumnManager.extractTableHeaders();
        
        StyleManager.applySavedSettings(this.storage);
        StyleManager.createResizers();
        ColumnManager.setupColumnResizing(this.storage);
        
        FilterManager.markActiveFilters();
        // NOTA: Los dropdowns de estado y área ahora son manejados por OrdersDropdownManager
        // ModernTableDropdownManager.initializeStatusDropdowns((dropdown) => this.updateOrderStatus(dropdown));
        
        this.setupEventListeners();
        this.setupUI();
        

    }

    setupUI() {
        StyleManager.setupCellTextWrapping();
    }

    setupEventListeners() {

        
        // Búsqueda en tiempo real
        const searchInput = document.getElementById('buscarOrden');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const term = e.target.value.trim();
                    SearchManager.performAjaxSearch(term, this.baseRoute)
                        .then(data => {
                            if (data) {
                                this.updateTableWithData(data.orders, data.totalDiasCalculados, data.areaOptions, data.context, data.userRole);
                                this.updatePaginationInfo(data.pagination);
                                this.updatePaginationControls(data.pagination_html, data.pagination);
                                PaginationManager.updateUrl(new URLSearchParams(new URL(globalThis.location).search).toString());
                                // Los dropdowns se inicializan en orders-table-v2.js
                                if (typeof initializeDiaEntregaDropdowns === 'function') initializeDiaEntregaDropdowns();
                            }
                        })
                        .catch(error => {

                            const url = new URL(globalThis.location);
                            const params = new URLSearchParams(url.search);
                            globalThis.location.href = `${this.baseRoute}?${params}`;
                        });
                }, 300);
            });
        }

        document.addEventListener('change', e => {
            if (e.target.classList.contains('estado-dropdown')) this.updateOrderStatus(e.target);
        });

        document.addEventListener('click', e => {
            const filterBtn = e.target.closest('.filter-btn');
            if (filterBtn) {
                if (!this.isLoadingFilter) {
                    this.isLoadingFilter = true;
                    const columnIndex = Array.from(document.querySelectorAll('.filter-btn')).indexOf(filterBtn);
                    const columnName = filterBtn.dataset.columnName;
                    FilterManager.openFilterModal(columnIndex, columnName, this.baseRoute);
                    setTimeout(() => { this.isLoadingFilter = false; }, 500);
                }
            }
        });

        // Doble clic
        document.addEventListener('dblclick', e => {

            const cell = e.target.closest('.cell-content');

            
            if (cell) {
                const hasSelect = cell.querySelector('select');

                
                if (!hasSelect) {
                    const row = cell.closest('tr');

                    
                    const orderId = row?.dataset.orderId;

                    
                    //  Obtener column desde la celda td más cercana
                    const td = cell.closest('.table-cell');
                    const column = td?.dataset.column;


                    
                    let content = cell.textContent;

                    
                    //  CORREGIR: Para descripcion_prendas, obtener contenido desde data-full-content del div .descripcion-preview
                    if (column === 'descripcion_prendas') {

                        
                        // Buscar el div .descripcion-preview dentro del cell-content
                        const descripcionDiv = cell.querySelector('.descripcion-preview');

                        
                        if (descripcionDiv && descripcionDiv.dataset.fullContent) {
                            try {
                                content = atob(descripcionDiv.dataset.fullContent);

                            } catch (e) {

                            }
                        } else {

                        }
                    }
                    
                    if (orderId && column) {

                        this.openCellModal(content, orderId, column);
                    } else {

                    }
                }
            }
        });

        this.setupTouchDoubleTap();
        this.setupModalEvents();

        globalThis.addEventListener('orientationchange', () => {

            setTimeout(() => this.setupTouchDoubleTap(), 300);
        });

        let resizeTimer;
        globalThis.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => this.setupTouchDoubleTap(), 300);
        });
    }

    setupTouchDoubleTap() {
        let lastTap = 0;
        let lastTapTarget = null;
        const doubleTapDelay = 300;

        const touchHandler = (e) => {
            const cell = e.target.closest('.cell-content');
            if (!cell || cell.querySelector('select')) return;

            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;

            if (lastTapTarget === cell && tapLength < doubleTapDelay && tapLength > 0) {
                const row = cell.closest('tr');
                const orderId = row?.dataset.orderId;
                const td = row?.querySelector('.table-cell:has(> .cell-content)');
                const column = td?.dataset.column;
                let content = cell.textContent;
                
                //  CORREGIR: Para descripcion_prendas, obtener contenido desde data-full-content
                if (column === 'descripcion_prendas') {
                    const descripcionDiv = cell.querySelector('.descripcion-preview');
                    if (descripcionDiv && descripcionDiv.dataset.fullContent) {
                        try {
                            content = atob(descripcionDiv.dataset.fullContent);

                        } catch (e) {

                        }
                    }
                }
                
                if (orderId && column) {
                    this.openCellModal(content, orderId, column);
                }
                lastTap = 0;
            } else {
                lastTap = currentTime;
                lastTapTarget = cell;
            }
        };

        document.addEventListener('touchend', touchHandler, { passive: false });
    }

    setupModalEvents() {
        ['#closeModal', '#cancelFilter', '#closeCellModal'].forEach(sel => {
            document.querySelector(sel)?.addEventListener('click', () => {
                FilterManager.closeFilterModal();
                this.closeCellModal();
            });
        });

        document.getElementById('modalOverlay')?.addEventListener('click', () => {
            FilterManager.closeFilterModal();
            this.closeCellModal();
        });

        document.getElementById('applyFilter')?.addEventListener('click', () => {
            FilterManager.applyServerSideColumnFilter(this.currentColumnName, this.baseRoute);
        });

        document.getElementById('selectAll')?.addEventListener('click', () => FilterManager.selectAllFilterItems(true));
        document.getElementById('deselectAll')?.addEventListener('click', () => FilterManager.selectAllFilterItems(false));
        document.getElementById('filterSearch')?.addEventListener('input', e => FilterManager.filterModalItems(e.target.value.toLowerCase()));

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                FilterManager.closeFilterModal();
                this.closeCellModal();
            }
        });
    }

    updateTableWithData(orders, totalDiasCalculados, areaOptions = [], context = 'registros', userRole = null) {
        this.virtual.allData = orders;
        this.virtual.totalDiasCalculados = totalDiasCalculados || {};
        this.virtual.totalRows = orders.length;
        
        TableRenderer.updateTableWithData(orders, totalDiasCalculados, areaOptions, context, userRole);
        StyleManager.setupCellTextWrapping();
        // Dropdown handling now managed by OrdersDropdownManager in orders-table-v2.js
    }

    updatePaginationInfo(pagination) {
        PaginationManager.updateInfo(pagination);
    }

    updatePaginationControls(html, pagination) {
        PaginationManager.updateControls(html, pagination, this.baseRoute);
    }

    async updateOrderStatus(dropdown) {
        // Status updates now handled by OrdersDropdownManager in orders-table-v2.js

    }

    /**
     * Maneja actualizaciones de órdenes en tiempo real desde Echo/WebSocket
     */
    handleOrdenUpdate(ordenData, action, changedFields) {
        try {
            // Encontrar la fila en la tabla
            const row = document.querySelector(`tr[data-numero-pedido="${ordenData.numero_pedido}"]`);
            
            if (!row) {

                return;
            }

            // Actualizar solo los campos que cambiaron
            if (changedFields && typeof changedFields === 'object') {
                Object.keys(changedFields).forEach(field => {
                    const cell = row.querySelector(`[data-column="${field}"]`);
                    if (cell) {
                        const cellContent = cell.querySelector('.cell-content') || cell;
                        
                        // Actualizar valor según el campo
                        if (field === 'estado') {
                            const dropdown = cell.querySelector('.estado-dropdown');
                            if (dropdown) {
                                dropdown.value = changedFields[field];
                                dropdown.setAttribute('data-value', changedFields[field]);
                            }
                        } else if (field === 'area') {
                            const dropdown = cell.querySelector('.area-dropdown');
                            if (dropdown) {
                                dropdown.value = changedFields[field];
                                dropdown.setAttribute('data-value', changedFields[field]);
                            }
                        } else if (field === 'dia_de_entrega') {
                            const dropdown = cell.querySelector('.dia-entrega-dropdown');
                            if (dropdown) {
                                dropdown.value = changedFields[field];
                                dropdown.setAttribute('data-value', changedFields[field]);
                            }
                        } else {
                            // Campos de texto normales
                            cellContent.textContent = changedFields[field];
                        }
                    }
                });

            }

            // Actualizar color de fila si el estado cambió
            if (changedFields && changedFields.estado) {
                if (typeof RowManager !== 'undefined' && RowManager.updateRowColor) {
                    RowManager.updateRowColor(ordenData.numero_pedido, changedFields.estado);
                }
            }
        } catch (error) {

        }
    }

    openCellModal(content, orderId, column) {

        
        this.currentOrderId = orderId;
        this.currentColumn = column;
        
        // Rellenar el modal inmediatamente con el contenido
        this._populateCellModal(content, column);
    }

    _populateCellModal(content, column) {

        
        const input = document.getElementById('cellEditInput');

        
        if (input) {
            input.value = content.split('\n').map(line => line.trimStart()).join('\n');
            input.focus();
            input.select();

        }

        const multilineColumns = ['descripcion', 'descripcion_prendas', 'novedades', 'cliente', 'encargado_orden', 'asesora', 'forma_de_pago'];
        const isMultilineColumn = multilineColumns.includes(column);

        
        const hint = document.getElementById('cellEditHint');

        
        if (hint) {
            hint.textContent = isMultilineColumn ? 'Presiona Ctrl+Enter para guardar' : 'Presiona Enter para guardar';

        }

        const saveBtn = document.getElementById('saveCellEdit');
        const cancelBtn = document.getElementById('cancelCellEdit');


        
        //  Usar onclick en lugar de addEventListener para evitar múltiples listeners
        if (saveBtn) {
            saveBtn.onclick = () => {

                this.saveCellEdit();
            };

        }
        if (cancelBtn) {
            cancelBtn.onclick = () => {

                this.closeCellModal();
            };

        }

        if (input) {
            input.onkeydown = (e) => {

                if (isMultilineColumn) {
                    if (e.ctrlKey && e.key === 'Enter') {

                        this.saveCellEdit();
                    }
                } else {
                    if (e.key === 'Enter') {

                        this.saveCellEdit();
                    }
                    if (e.key === 'Escape') {

                        this.closeCellModal();
                    }
                }
            };

        }

        const overlay = document.getElementById('modalOverlay');
        const modal = document.getElementById('cellModal');


        
        if (overlay) {
            overlay.classList.add('active');

        }
        if (modal) {
            modal.classList.add('active');

        }
        

    }

    async saveCellEdit() {
        const newValue = document.getElementById('cellEditInput').value;

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
                NotificationManager.show(' Cambio guardado exitosamente', 'success');
                this.closeCellModal();
            } else {
                NotificationManager.show(' Error al guardar: ' + (data.message || 'Desconocido'), 'error');
            }
        } catch (error) {

            NotificationManager.show('Error de conexión al guardar los cambios', 'error');
        }
    }

    closeCellModal() {

        const modal = document.getElementById('cellModal');
        const overlay = document.getElementById('modalOverlay');
        


        
        if (modal) {
            modal.classList.remove('active');

        }
        if (overlay) {
            overlay.classList.remove('active');

        }
        

    }

    clearAllFilters() {
        FilterManager.clearAllFilters(this.baseRoute);
    }

    enableTableDragging() {
        DragManager.enableTableDragging(this.storage);
    }

    disableTableDragging() {
        DragManager.disableTableDragging();
    }

    enableHeaderDragging() {
        DragManager.enableHeaderDragging(this.storage);
    }

    disableHeaderDragging() {
        DragManager.disableHeaderDragging();
    }
}

// Exponer a globalThis
globalThis.ModernTableV2 = ModernTableV2;

/**
 * Factory function para crear instancia de ModernTableV2
 * Se llama cuando todos los módulos están listos
 */
globalThis.initializeModernTable = () => {
    if (globalThis.modernTableInstance) {

        return globalThis.modernTableInstance;
    }
    // Verificar que todos los módulos están disponibles
    const requiredModules = [
        'StorageManager', 'TableRenderer', 'StyleManager', 'FilterManager',
        'DragManager', 'ColumnManager', 'ModernTableDropdownManager', 'NotificationManager',
        'PaginationManager', 'SearchManager'
    ];

    const missingModules = requiredModules.filter(mod => typeof globalThis[mod] === 'undefined');
    
    if (missingModules.length > 0) {


        return null;
    }

    // Verificar tabla en DOM
    const tabla = document.getElementById('tablaOrdenes');
    if (!tabla) {

        return null;
    }
    try {
        globalThis.modernTableInstance = new ModernTableV2();
        // Exponer también como window.modernTable para compatibilidad con realtime-listeners
        window.modernTable = globalThis.modernTableInstance;
        // Agregar botón de limpiar filtros
        const tableActions = document.querySelector('.table-actions');
        if (tableActions && !document.getElementById('clearFiltersBtn')) {
            const clearBtn = document.createElement('button');
            clearBtn.id = 'clearFiltersBtn';
            clearBtn.textContent = 'Limpiar Filtros';
            clearBtn.className = 'btn btn-secondary ml-2';
            clearBtn.style.fontSize = '12px';
            clearBtn.addEventListener('click', () => globalThis.modernTableInstance.clearAllFilters());
            tableActions.appendChild(clearBtn);

        }

        return globalThis.modernTableInstance;
    } catch (error) {

        return null;
    }
};

// Intentar inicializar cuando DOM esté listo
if (document.readyState === 'loading') {
    // DOM aún se está cargando
    document.addEventListener('DOMContentLoaded', () => {

        setTimeout(() => globalThis.initializeModernTable(), 100);
    });
} else {
    // DOM ya está listo

    setTimeout(() => globalThis.initializeModernTable(), 100);
}

