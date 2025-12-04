/**
 * ModernTable v2 - Refactorizado con SOLID
 * Orchestrator que coordina todos los módulos
 * Responsabilidad: Orquestar y coordinar los módulos
 */
class ModernTableV2 {
    constructor() {
        console.log('🚀 ModernTableV2: Constructor iniciado');
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
        console.log('🔧 ModernTableV2.init() - Inicializando tabla...');
        this.headers = ColumnManager.extractTableHeaders();
        
        StyleManager.applySavedSettings(this.storage);
        StyleManager.createResizers();
        ColumnManager.setupColumnResizing(this.storage);
        
        FilterManager.markActiveFilters();
        // NOTA: Los dropdowns de estado y área ahora son manejados por OrdersDropdownManager
        // ModernTableDropdownManager.initializeStatusDropdowns((dropdown) => this.updateOrderStatus(dropdown));
        
        this.setupEventListeners();
        this.setupUI();
        
        console.log('✅ ModernTableV2.init() - Tabla inicializada completamente');
    }

    setupUI() {
        StyleManager.setupCellTextWrapping();
    }

    setupEventListeners() {
        console.log('ModernTableV2: setupEventListeners called');
        
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
                            console.error('Error en búsqueda:', error);
                            const url = new URL(globalThis.location);
                            const params = new URLSearchParams(url.search);
                            globalThis.location.href = `${this.baseRoute}?${params}`;
                        });
                }, 300);
            });
        }

        document.addEventListener('change', e => {
            if (e.target.classList.contains('estado-dropdown')) this.updateOrderStatus(e.target);
            if (e.target.classList.contains('area-dropdown')) this.updateOrderArea(e.target);
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
            console.log('🖱️ DBLCLICK DETECTADO en:', e.target);
            const cell = e.target.closest('.cell-content');
            console.log('📍 Cell encontrada:', cell ? 'SÍ' : 'NO');
            
            if (cell) {
                const hasSelect = cell.querySelector('select');
                console.log('📍 Tiene select:', hasSelect ? 'SÍ' : 'NO');
                
                if (!hasSelect) {
                    const row = cell.closest('tr');
                    console.log('📍 Row encontrada:', row ? 'SÍ' : 'NO');
                    
                    const orderId = row?.dataset.orderId;
                    console.log('📍 OrderId:', orderId);
                    
                    // 🔧 Obtener column desde la celda td más cercana
                    const td = cell.closest('.table-cell');
                    const column = td?.dataset.column;
                    console.log('📍 TD encontrado:', td ? 'SÍ' : 'NO');
                    console.log('📍 Column:', column);
                    
                    let content = cell.textContent;
                    console.log('📍 Content inicial:', content.substring(0, 50));
                    
                    // 🔧 CORREGIR: Para descripcion_prendas, obtener contenido desde data-full-content del div .descripcion-preview
                    if (column === 'descripcion_prendas') {
                        console.log('🎯 Detectado descripcion_prendas - buscando .descripcion-preview...');
                        
                        // Buscar el div .descripcion-preview dentro del cell-content
                        const descripcionDiv = cell.querySelector('.descripcion-preview');
                        console.log('🎯 descripcionDiv encontrado en cell-content:', descripcionDiv ? 'SÍ' : 'NO');
                        
                        if (descripcionDiv && descripcionDiv.dataset.fullContent) {
                            try {
                                content = atob(descripcionDiv.dataset.fullContent);
                                console.log('✅ Contenido decodificado desde data-full-content:', content.substring(0, 50));
                            } catch (e) {
                                console.error('❌ Error decodificando base64:', e);
                            }
                        } else {
                            console.warn('⚠️ No se encontró .descripcion-preview o data-full-content');
                        }
                    }
                    
                    if (orderId && column) {
                        console.log('✅ Abriendo modal con orderId:', orderId, 'column:', column);
                        this.openCellModal(content, orderId, column);
                    } else {
                        console.log('❌ Falta orderId o column - NO se abre modal');
                    }
                }
            }
        });

        this.setupTouchDoubleTap();
        this.setupModalEvents();

        globalThis.addEventListener('orientationchange', () => {
            console.log('Orientation changed, reinitializing touch events');
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
                
                // 🔧 CORREGIR: Para descripcion_prendas, obtener contenido desde data-full-content
                if (column === 'descripcion_prendas') {
                    const descripcionDiv = cell.querySelector('.descripcion-preview');
                    if (descripcionDiv && descripcionDiv.dataset.fullContent) {
                        try {
                            content = atob(descripcionDiv.dataset.fullContent);
                            console.log('✅ Contenido decodificado desde data-full-content en touch');
                        } catch (e) {
                            console.error('❌ Error decodificando base64 en touch:', e);
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
        console.log('⚠️ updateOrderStatus called on ModernTableV2 - should be handled by OrdersDropdownManager');
    }

    async updateOrderArea(dropdown) {
        // Area updates now handled by OrdersDropdownManager in orders-table-v2.js
        console.log('⚠️ updateOrderArea called on ModernTableV2 - should be handled by OrdersDropdownManager');
    }

    /**
     * Maneja actualizaciones de órdenes en tiempo real desde Echo/WebSocket
     */
    handleOrdenUpdate(ordenData, action, changedFields) {
        console.log('📡 [ModernTableV2] handleOrdenUpdate recibido', {
            orderId: ordenData.id,
            numeroPedido: ordenData.numero_pedido,
            action: action,
            changedFields: changedFields
        });

        try {
            // Encontrar la fila en la tabla
            const row = document.querySelector(`tr[data-numero-pedido="${ordenData.numero_pedido}"]`);
            
            if (!row) {
                console.warn(`⚠️ Fila no encontrada para pedido ${ordenData.numero_pedido}`);
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
                console.log(`✅ Fila ${ordenData.numero_pedido} actualizada desde tiempo real`);
            }

            // Actualizar color de fila si el estado cambió
            if (changedFields && changedFields.estado) {
                if (typeof RowManager !== 'undefined' && RowManager.updateRowColor) {
                    RowManager.updateRowColor(ordenData.numero_pedido, changedFields.estado);
                }
            }
        } catch (error) {
            console.error('❌ Error al manejar actualización de orden:', error);
        }
    }

    openCellModal(content, orderId, column) {
        console.log('🔓 openCellModal LLAMADO con:', { content: content.substring(0, 50), orderId, column });
        
        this.currentOrderId = orderId;
        this.currentColumn = column;
        
        // Rellenar el modal inmediatamente con el contenido
        this._populateCellModal(content, column);
    }

    _populateCellModal(content, column) {
        console.log('📝 Rellenando modal con contenido, longitud:', content.length);
        
        const input = document.getElementById('cellEditInput');
        console.log('📝 Input encontrado:', input ? 'SÍ' : 'NO');
        
        if (input) {
            input.value = content.split('\n').map(line => line.trimStart()).join('\n');
            input.focus();
            input.select();
            console.log('📝 Input value asignado y enfocado');
        }

        const multilineColumns = ['descripcion', 'descripcion_prendas', 'novedades', 'cliente', 'encargado_orden', 'asesora', 'forma_de_pago'];
        const isMultilineColumn = multilineColumns.includes(column);
        console.log('📝 Es columna multilínea:', isMultilineColumn);
        
        const hint = document.getElementById('cellEditHint');
        console.log('💡 Hint encontrado:', hint ? 'SÍ' : 'NO');
        
        if (hint) {
            hint.textContent = isMultilineColumn ? 'Presiona Ctrl+Enter para guardar' : 'Presiona Enter para guardar';
            console.log('💡 Hint actualizado');
        }

        const saveBtn = document.getElementById('saveCellEdit');
        const cancelBtn = document.getElementById('cancelCellEdit');
        console.log('🔘 Save btn encontrado:', saveBtn ? 'SÍ' : 'NO');
        console.log('🔘 Cancel btn encontrado:', cancelBtn ? 'SÍ' : 'NO');
        
        // 🔧 Usar onclick en lugar de addEventListener para evitar múltiples listeners
        if (saveBtn) {
            saveBtn.onclick = () => {
                console.log('💾 Save button clickeado');
                this.saveCellEdit();
            };
            console.log('💾 Save onclick asignado');
        }
        if (cancelBtn) {
            cancelBtn.onclick = () => {
                console.log('❌ Cancel button clickeado');
                this.closeCellModal();
            };
            console.log('❌ Cancel onclick asignado');
        }

        if (input) {
            input.onkeydown = (e) => {
                console.log('⌨️ Tecla presionada:', e.key);
                if (isMultilineColumn) {
                    if (e.ctrlKey && e.key === 'Enter') {
                        console.log('⌨️ Ctrl+Enter detectado');
                        this.saveCellEdit();
                    }
                } else {
                    if (e.key === 'Enter') {
                        console.log('⌨️ Enter detectado');
                        this.saveCellEdit();
                    }
                    if (e.key === 'Escape') {
                        console.log('⌨️ Escape detectado');
                        this.closeCellModal();
                    }
                }
            };
            console.log('⌨️ Keydown handler asignado');
        }

        const overlay = document.getElementById('modalOverlay');
        const modal = document.getElementById('cellModal');
        console.log('🎭 Modal overlay encontrado:', overlay ? 'SÍ' : 'NO');
        console.log('🎭 Modal encontrado:', modal ? 'SÍ' : 'NO');
        
        if (overlay) {
            overlay.classList.add('active');
            console.log('🎭 Clase active agregada a overlay');
        }
        if (modal) {
            modal.classList.add('active');
            console.log('🎭 Clase active agregada a modal');
        }
        
        console.log('✅ openCellModal COMPLETADO');
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
                NotificationManager.show('✅ Cambio guardado exitosamente', 'success');
                this.closeCellModal();
            } else {
                NotificationManager.show('❌ Error al guardar: ' + (data.message || 'Desconocido'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            NotificationManager.show('Error de conexión al guardar los cambios', 'error');
        }
    }

    closeCellModal() {
        console.log('🔒 closeCellModal LLAMADO');
        const modal = document.getElementById('cellModal');
        const overlay = document.getElementById('modalOverlay');
        
        console.log('🎭 Modal encontrado:', modal ? 'SÍ' : 'NO');
        console.log('🎭 Overlay encontrado:', overlay ? 'SÍ' : 'NO');
        
        if (modal) {
            modal.classList.remove('active');
            console.log('🎭 Clase active removida de modal');
        }
        if (overlay) {
            overlay.classList.remove('active');
            console.log('🎭 Clase active removida de overlay');
        }
        
        console.log('✅ closeCellModal COMPLETADO');
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
        console.warn('⚠️ ModernTableV2 ya está inicializada');
        return globalThis.modernTableInstance;
    }

    console.log('%c🔍 Inicializando ModernTableV2...', 'color: #00aa00; font-weight: bold; font-size: 14px;');
    
    // Verificar que todos los módulos están disponibles
    const requiredModules = [
        'StorageManager', 'TableRenderer', 'StyleManager', 'FilterManager',
        'DragManager', 'ColumnManager', 'ModernTableDropdownManager', 'NotificationManager',
        'PaginationManager', 'SearchManager'
    ];

    const missingModules = requiredModules.filter(mod => typeof globalThis[mod] === 'undefined');
    
    if (missingModules.length > 0) {
        console.error('❌ ERROR: Módulos faltantes:', missingModules.join(', '));
        console.error('❌ Asegúrate de que todos los scripts se cargaron en orden correcto');
        return null;
    }

    // Verificar tabla en DOM
    const tabla = document.getElementById('tablaOrdenes');
    if (!tabla) {
        console.warn('⚠️ Tabla #tablaOrdenes no encontrada');
        return null;
    }

    console.log('%c✅ Todos los módulos disponibles - Creando instancia', 'color: #00aa00; font-weight: bold; font-size: 14px;');
    
    try {
        globalThis.modernTableInstance = new ModernTableV2();
        // Exponer también como window.modernTable para compatibilidad con realtime-listeners
        window.modernTable = globalThis.modernTableInstance;
        console.log('%c✅ ModernTableV2 instancia lista', 'color: #00aa00; font-weight: bold; font-size: 14px;');

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
            console.log('✅ Botón "Limpiar Filtros" agregado');
        }

        return globalThis.modernTableInstance;
    } catch (error) {
        console.error('❌ Error al inicializar ModernTableV2:', error);
        return null;
    }
};

// Intentar inicializar cuando DOM esté listo
if (document.readyState === 'loading') {
    // DOM aún se está cargando
    document.addEventListener('DOMContentLoaded', () => {
        console.log('📋 DOMContentLoaded disparado');
        setTimeout(() => globalThis.initializeModernTable(), 100);
    });
} else {
    // DOM ya está listo
    console.log('📋 DOM ya está listo');
    setTimeout(() => globalThis.initializeModernTable(), 100);
}
