class ModernTable {
    constructor() {
        console.log('ModernTable: Constructor called');
        this.headers = [];
        this.baseRoute = this.getBaseRoute();
        this.isLoadingFilter = false; // Prevenir doble clic en filtros
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
        this.initializeStatusDropdowns();
        this.initializeAreaDropdowns();

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
            select.dataset.id = orden.pedido || orden.id;
            select.dataset.value = value || '';

            const options = key === 'estado' 
                ? ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada']
                : window.areaOptions || [];

            // Normalizar el valor actual para comparación
            const normalizedValue = value ? String(value).trim() : '';

            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.textContent = opt;
                
                // Establecer selected durante la creación de la opción
                if (normalizedValue && opt.trim() === normalizedValue) {
                    option.setAttribute('selected', 'selected');
                    option.defaultSelected = true;
                    option.selected = true;
                }
                
                select.appendChild(option);
            });

            // Forzar el valor del select después de agregar todas las opciones
            if (normalizedValue) {
                setTimeout(() => {
                    select.value = normalizedValue;
                }, 0);
            }

            content.appendChild(select);
        } else if (key === 'dia_de_entrega' && window.modalContext === 'orden') {
            // CRÍTICO: Crear dropdown de día de entrega
            const select = document.createElement('select');
            select.className = 'dia-entrega-dropdown';
            select.dataset.id = orden.pedido || orden.id;
            
            // Normalizar el valor (null, undefined, '' → '')
            const diasValue = (value === null || value === undefined || value === '') ? '' : String(value);
            select.dataset.value = diasValue;

            // Opción "Seleccionar" por defecto
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Seleccionar';
            if (diasValue === '') {
                defaultOption.selected = true;
            }
            select.appendChild(defaultOption);

            // Opciones de días
            [15, 20, 25, 30].forEach(dias => {
                const option = document.createElement('option');
                option.value = dias;
                option.textContent = `${dias} días`;
                if (String(dias) === diasValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            
            content.appendChild(select);
        } else {
            const span = document.createElement('span');
            span.className = 'cell-text';
            
            // Columnas de fecha que deben formatearse
            const dateColumns = [
                'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 'insumos_y_telas', 'corte',
                'bordado', 'estampado', 'costura', 'reflectivo', 'lavanderia',
                'arreglos', 'marras', 'control_de_calidad', 'entrega', 'despacho'
            ];
            
            let displayValue;
            if (key === 'total_de_dias_') {
                displayValue = this.virtual.totalDiasCalculados[orden.pedido || orden.id] ?? 'N/A';
            } else if (dateColumns.includes(key) && value) {
                // Formatear fecha a d/m/Y - IMPORTANTE: Usar split en lugar de new Date() para evitar problemas de zona horaria
                console.log(`[modern-table.js] Formateando fecha - Columna: ${key}, Valor original: "${value}", Tipo: ${typeof value}`);
                try {
                    // Si es formato YYYY-MM-DD, convertir directamente sin usar new Date()
                    if (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        const partes = value.split('-');
                        displayValue = `${partes[2]}/${partes[1]}/${partes[0]}`;
                        console.log(`[modern-table.js] ✅ Fecha formateada (YYYY-MM-DD → DD/MM/YYYY): ${value} → ${displayValue}`);
                    } else if (typeof value === 'string' && value.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
                        // Ya está en formato DD/MM/YYYY
                        displayValue = value;
                        console.log(`[modern-table.js] ✅ Fecha ya formateada (DD/MM/YYYY): ${value}`);
                    } else if (typeof value === 'string' && value.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
                        // Formato YYYY/MM/DD (incorrecto) - convertir a DD/MM/YYYY
                        const partes = value.split('/');
                        displayValue = `${partes[2]}/${partes[1]}/${partes[0]}`;
                        console.log(`[modern-table.js] ⚠️ Fecha en formato YYYY/MM/DD (incorrecto): ${value} → ${displayValue}`);
                    } else {
                        // Fallback: intentar con new Date()
                        console.log(`[modern-table.js] ⚠️ Formato no reconocido, intentando con new Date(): ${value}`);
                        const date = new Date(value);
                        if (!isNaN(date.getTime())) {
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            displayValue = `${day}/${month}/${year}`;
                            console.log(`[modern-table.js] ✅ Fecha formateada (new Date): ${value} → ${displayValue}`);
                        } else {
                            displayValue = value ?? '';
                            console.log(`[modern-table.js] ❌ Fecha inválida (NaN): ${value}`);
                        }
                    }
                } catch (e) {
                    displayValue = value ?? '';
                    console.log(`[modern-table.js] ❌ Error formateando fecha: ${e.message}`);
                }
            } else {
                displayValue = value ?? '';
            }
            
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
        console.log('ModernTable: setupEventListeners called');
        
        // Búsqueda en tiempo real con debounce y AbortController
        const searchInput = document.getElementById('buscarOrden');
        if (searchInput) {
            let searchTimeout;
            this.searchAbortController = null; // Para cancelar requests anteriores
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performAjaxSearch(e.target.value);
                }, 500); // 500ms de delay para búsqueda en tiempo real (más tiempo para evitar race conditions)
            });
        }

        document.addEventListener('change', e => {
            if (e.target.classList.contains('estado-dropdown')) this.updateOrderStatus(e.target);
            if (e.target.classList.contains('area-dropdown')) this.updateOrderArea(e.target);
        });

        document.addEventListener('click', e => {
            // Buscar el botón de filtro, ya sea que se haga clic en el botón o en el icono dentro
            const filterBtn = e.target.closest('.filter-btn');
            if (filterBtn) {
                console.log('Filter button clicked:', filterBtn.dataset.column, filterBtn.dataset.columnName);
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

        // Soporte para doble click en desktop
        document.addEventListener('dblclick', e => {
            const cell = e.target.closest('.cell-content');
            if (cell && !cell.querySelector('select')) {
                console.log('Double click detected on cell');
                const cellText = cell.querySelector('.cell-text');
                if (cellText) {
                    const td = cell.closest('td');
                    const row = td.closest('tr');
                    this.openCellModal(cellText.textContent, row.dataset.orderId, td.dataset.column);
                }
            }
        });

        // Soporte para doble toque en tablets y móviles
        this.setupTouchDoubleTap();

        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'c') {
                const selected = document.querySelector('.table-cell.selected .cell-text');
                if (selected) navigator.clipboard.writeText(selected.textContent);
            }
        });

        this.setupModalEvents();
        
        // Reinicializar eventos táctiles cuando cambia la orientación
        window.addEventListener('orientationchange', () => {
            console.log('Orientation changed, reinitializing touch events');
            setTimeout(() => {
                this.setupTouchDoubleTap();
            }, 300);
        });
        
        // También manejar resize para tablets que no disparan orientationchange
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                console.log('Window resized, checking for orientation change');
                this.setupTouchDoubleTap();
            }, 300);
        });
    }

    setupTouchDoubleTap() {
        // Remover listeners anteriores si existen
        if (this.touchHandler) {
            document.removeEventListener('touchend', this.touchHandler);
        }

        let lastTap = 0;
        let lastTapTarget = null;
        const doubleTapDelay = 300; // ms entre toques

        this.touchHandler = (e) => {
            const cell = e.target.closest('.cell-content');
            if (!cell || cell.querySelector('select')) {
                return;
            }

            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;

            // Verificar si es el mismo elemento y dentro del tiempo límite
            if (lastTapTarget === cell && tapLength < doubleTapDelay && tapLength > 0) {
                console.log('Double tap detected on cell');
                e.preventDefault(); // Prevenir zoom en iOS
                
                const cellText = cell.querySelector('.cell-text');
                if (cellText) {
                    const td = cell.closest('td');
                    const row = td.closest('tr');
                    this.openCellModal(cellText.textContent, row.dataset.orderId, td.dataset.column);
                }
                
                // Reset para evitar triple tap
                lastTap = 0;
                lastTapTarget = null;
            } else {
                // Primer tap o tap en diferente elemento
                lastTap = currentTime;
                lastTapTarget = cell;
            }
        };

        document.addEventListener('touchend', this.touchHandler, { passive: false });
        console.log('Touch double tap handler initialized');
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
        console.log('openFilterModal called with columnIndex:', columnIndex, 'columnName:', columnName);
        
        // Prevenir apertura múltiple
        if (this.isLoadingFilter) {
            console.log('Ya se está cargando un filtro, ignorando...');
            return;
        }
        
        this.isLoadingFilter = true;
        this.currentColumn = columnIndex;
        this.currentColumnName = columnName;
        const modal = document.getElementById('filterModal');
        const overlay = document.getElementById('modalOverlay');
        const filterList = document.getElementById('filterList');
        
        document.getElementById('filterColumnName').textContent = columnName;
        document.getElementById('filterSearch').value = '';
        
        // Mostrar indicador de carga
        filterList.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;"><i class="fas fa-spinner fa-spin"></i> Cargando valores...</div>';
        
        // Abrir modal inmediatamente para mostrar el loading
        overlay.classList.add('active');
        modal.classList.add('active');

        try {
            console.log(`📡 Fetching unique values para columna: ${columnName}`);
            const response = await fetch(`${this.baseRoute}?get_unique_values=1&column=${encodeURIComponent(columnName)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            console.log(`✅ Valores únicos recibidos del servidor:`, data.unique_values);
            console.log(`📊 Total de valores únicos: ${data.unique_values?.length || 0}`);
            
            // Guardar los IDs asociados si es descripción
            if (columnName === 'descripcion' && data.value_ids) {
                this.valueIdsMap = {};
                data.value_ids.forEach(item => {
                    this.valueIdsMap[item.value] = item.ids;
                });
                console.log(`🆔 Mapa de IDs cargado:`, this.valueIdsMap);
            }
            
            this.generateFilterList(data.unique_values || [], columnIndex);
        } catch (error) {
            console.error('❌ Error fetching values:', error);
            console.log(`⚠️ Usando fallback: extrayendo valores de la tabla`);
            const values = [...new Set(
                Array.from(document.querySelectorAll(`#tablaOrdenes tbody tr td:nth-child(${columnIndex + 1})`))
                    .map(td => td.querySelector('select')?.value || td.querySelector('.cell-text')?.textContent.trim() || td.textContent.trim())
                    .filter(v => v)
            )].sort();
            console.log(`✅ Valores extraídos de la tabla:`, values);
            console.log(`📊 Total de valores del fallback: ${values.length}`);
            this.generateFilterList(values, columnIndex);
        } finally {
            this.isLoadingFilter = false;
        }
    }

    generateFilterList(values, columnIndex) {
        console.log(`🎯 generateFilterList llamado con ${values.length} valores`);
        console.log(`📋 Valores a mostrar:`, values);
        
        const url = new URL(window.location);
        const currentFilter = url.searchParams.get(`filter_${this.currentColumnName}`);
        const filteredValues = currentFilter ? currentFilter.split(',') : [];
        
        console.log(`🔗 Filtro actual en URL:`, currentFilter);
        console.log(`✅ Valores ya filtrados:`, filteredValues);

        const filterList = document.getElementById('filterList');
        filterList.innerHTML = values.map(val => {
            // Convertir ambos a string para comparación consistente
            const valStr = String(val);
            const isChecked = filteredValues.length === 0 || filteredValues.includes(valStr);
            console.log(`  ☑️ ${val} - Checked: ${isChecked}`);
            return `
                <div class="filter-item" data-value="${val}">
                    <input type="checkbox" id="filter_${columnIndex}_${val}" value="${val}" ${isChecked ? 'checked' : ''}>
                    <label for="filter_${columnIndex}_${val}">${val}</label>
                </div>
            `;
        }).join('');

        console.log(`✨ Filtro renderizado con ${values.length} items`);

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
        console.log(`🔘 Valores seleccionados en el filtro:`, selected);
        console.log(`📊 Total seleccionados: ${selected.length}`);
        
        // Si es la columna "descripcion", usar los IDs del servidor
        if (this.currentColumnName === 'descripcion' && this.valueIdsMap) {
            const selectedIds = [];
            
            console.log(`🔍 Obteniendo IDs del mapa del servidor...`);
            console.log(`📊 Mapa disponible:`, Object.keys(this.valueIdsMap).length, 'claves');
            
            selected.forEach(sel => {
                // Buscar coincidencia exacta primero
                let ids = this.valueIdsMap[sel];
                let normalizedSel = null;
                
                // Si no encuentra coincidencia exacta, buscar por normalización
                if (!ids) {
                    normalizedSel = this.normalizeText(sel);
                    for (const [key, value] of Object.entries(this.valueIdsMap)) {
                        if (this.normalizeText(key) === normalizedSel) {
                            ids = value;
                            console.log(`  🔄 Coincidencia normalizada exacta encontrada`);
                            break;
                        }
                    }
                }
                
                // Si aún no encuentra, buscar por similitud (primeras 30 caracteres normalizados)
                if (!ids) {
                    if (!normalizedSel) {
                        normalizedSel = this.normalizeText(sel);
                    }
                    const selPrefix = normalizedSel.substring(0, 30);
                    
                    for (const [key, value] of Object.entries(this.valueIdsMap)) {
                        const keyPrefix = this.normalizeText(key).substring(0, 30);
                        if (keyPrefix === selPrefix) {
                            ids = value;
                            console.log(`  🔄 Coincidencia por similitud encontrada`);
                            break;
                        }
                    }
                }
                
                if (ids && Array.isArray(ids)) {
                    console.log(`  📝 "${sel.substring(0, 30)}..." → IDs: ${ids.join(', ')}`);
                    selectedIds.push(...ids);
                } else {
                    if (!normalizedSel) {
                        normalizedSel = this.normalizeText(sel);
                    }
                    console.log(`  ⚠️ No se encontraron IDs para: "${sel.substring(0, 30)}..."`);
                    console.log(`     Normalizado: "${normalizedSel.substring(0, 30)}..."`);
                }
            });
            
            // Eliminar duplicados
            const uniqueIds = [...new Set(selectedIds)];
            
            console.log(`🆔 IDs únicos de pedidos a filtrar:`, uniqueIds);
            console.log(`📊 Total de IDs: ${uniqueIds.length}`);
            
            // Enviar como parámetro especial
            this.applyServerSideFilter(`filter_pedido_ids`, uniqueIds.join(','));
        } else {
            // Para otras columnas, usar separador especial
            const separator = '|||FILTER_SEPARATOR|||';
            const filterValue = selected.length ? selected.join(separator) : '';
            
            console.log(`🔗 Valores con separador especial:`, filterValue);
            
            this.applyServerSideFilter(`filter_${this.currentColumnName}`, filterValue);
        }
        
        this.closeFilterModal();
    }
    
    normalizeText(text) {
        return text.toLowerCase().trim().replace(/\s+/g, ' ');
    }

    applyServerSideFilter(key, value) {
        console.log(`🚀 Aplicando filtro:`, { key, value });
        const url = new URL(window.location);
        value ? url.searchParams.set(key, value) : url.searchParams.delete(key);
        url.searchParams.delete('page');
        
        console.log(`📍 URL con filtro:`, url.toString());
        
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
                const rowCount = newTableBody.querySelectorAll('tr').length;
                console.log(`✅ Tabla actualizada con ${rowCount} filas`);
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
            
            // CRÍTICO: Reinicializar todos los dropdowns después de aplicar filtros
            if (typeof initializeStatusDropdowns === 'function') {
                initializeStatusDropdowns();
            }
            if (typeof initializeAreaDropdowns === 'function') {
                initializeAreaDropdowns();
            }
            if (typeof initializeDiaEntregaDropdowns === 'function') {
                initializeDiaEntregaDropdowns();
                console.log('✅ Dropdowns reinicializados después de aplicar filtros');
            }
            
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
        console.log('openCellModal called with content:', content, 'orderId:', orderId, 'column:', column);
        this.currentOrderId = orderId;
        this.currentColumn = column;
        const input = document.getElementById('cellEditInput');
        const hint = document.getElementById('cellEditHint');
        console.log('cellEditInput element:', input);
        if (input) {
            input.value = content.split('\n').map(line => line.trimStart()).join('\n');
            input.focus();
            input.select();
        }
        
        // Columnas que permiten saltos de línea
        const multilineColumns = ['descripcion', 'novedades', 'cliente', 'encargado_orden', 'asesora', 'forma_de_pago'];
        const isMultilineColumn = multilineColumns.includes(column);
        
        // Mostrar mensaje de ayuda según la columna
        if (hint) {
            if (isMultilineColumn) {
                hint.textContent = 'Presiona Enter para salto de línea. Ctrl+Enter o clic en Guardar para guardar cambios.';
            } else {
                hint.textContent = 'Presiona Enter o clic en Guardar para guardar cambios.';
            }
        }

        const save = () => this.saveCellEdit();
        const cancel = () => this.closeCellModal();
        const keyHandler = e => {
            // Para columnas que permiten múltiples líneas, permitir Enter para saltos de línea
            // Solo guardar con Ctrl+Enter
            if (isMultilineColumn) {
                if (e.key === 'Enter' && e.ctrlKey) { 
                    e.preventDefault(); 
                    save(); 
                } else if (e.key === 'Escape') {
                    cancel();
                }
                // Enter sin Ctrl permite salto de línea (comportamiento por defecto)
            } else {
                // Para otras columnas, mantener comportamiento original
                if (e.key === 'Enter') { 
                    e.preventDefault(); 
                    save(); 
                } else if (e.key === 'Escape') {
                    cancel();
                }
            }
        };

        const saveBtn = document.getElementById('saveCellEdit');
        const cancelBtn = document.getElementById('cancelCellEdit');
        console.log('saveBtn:', saveBtn, 'cancelBtn:', cancelBtn);
        if (saveBtn) saveBtn.onclick = save;
        if (cancelBtn) cancelBtn.onclick = cancel;
        if (input) input.onkeydown = keyHandler;

        const overlay = document.getElementById('modalOverlay');
        const modal = document.getElementById('cellModal');
        console.log('modalOverlay:', overlay, 'cellModal:', modal);
        if (overlay) {
            overlay.classList.add('active');
            console.log('Added active class to modalOverlay');
        }
        if (modal) {
            modal.classList.add('active');
            console.log('Added active class to cellModal');
        }
    }

    async saveCellEdit() {
        const newValue = document.getElementById('cellEditInput').value;
        const oldValue = document.querySelector('.table-cell.selected .cell-text')?.textContent || '';

        try {
            // Si estamos editando el campo descripcion, usar endpoint especial para actualizar prendas
            if (this.currentColumn === 'descripcion') {
                const response = await fetch(`${this.baseRoute}/update-descripcion-prendas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ 
                        pedido: this.currentOrderId,
                        descripcion: newValue 
                    })
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

                    // Mostrar notificación moderna de éxito
                    this.showModernNotification(data.message, 'success', {
                        prendas: data.prendas_procesadas,
                        registrosRegenerados: data.registros_regenerados
                    });

                    this.closeCellModal();
                } else {
                    this.showModernNotification(data.message || 'Error al actualizar la descripción y prendas', 'error');
                }
                return;
            }

            // Si estamos editando el campo pedido, usar endpoint especial
            if (this.currentColumn === 'pedido') {
                const response = await fetch(`${this.baseRoute}/update-pedido`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ 
                        old_pedido: this.currentOrderId,
                        new_pedido: newValue 
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Actualizar la fila sin recargar la página
                    const row = document.querySelector(`tr[data-order-id="${this.currentOrderId}"]`);
                    if (row) {
                        // Actualizar el data-order-id de la fila
                        row.dataset.orderId = newValue;
                        
                        // Actualizar el texto de la celda de pedido
                        const selected = document.querySelector('.table-cell.selected');
                        if (selected) {
                            const cellText = selected.querySelector('.cell-text');
                            if (cellText) {
                                cellText.textContent = newValue;
                                selected.querySelector('.cell-content').title = newValue;
                            }
                        }
                        
                        // Actualizar los botones de acción con el nuevo pedido
                        const deleteBtn = row.querySelector('.delete-btn');
                        const detailBtn = row.querySelector('.detail-btn');
                        if (deleteBtn) {
                            deleteBtn.setAttribute('onclick', `deleteOrder(${newValue})`);
                        }
                        if (detailBtn) {
                            detailBtn.setAttribute('onclick', `viewDetail(${newValue})`);
                        }
                        
                        // Actualizar el currentOrderId para futuras ediciones
                        this.currentOrderId = newValue;
                        
                        // Efecto visual de confirmación
                        row.style.backgroundColor = 'rgba(34, 197, 94, 0.2)';
                        setTimeout(() => {
                            row.style.transition = 'background-color 0.5s ease';
                            row.style.backgroundColor = '';
                        }, 100);
                    }
                    
                    this.closeCellModal();
                } else {
                    this.showModernNotification(data.message || 'Error al actualizar el número de pedido', 'error');
                }
                return;
            }

            // Para otros campos, usar el endpoint normal
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
                this.showModernNotification('Error al guardar los cambios', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showModernNotification('Error de conexión al guardar los cambios', 'error');
        }
    }

    closeCellModal() {
        document.getElementById('cellModal')?.classList.remove('active');
        document.getElementById('modalOverlay')?.classList.remove('active');
    }

    /**
     * Mostrar notificación moderna y dinámica
     */
    showModernNotification(message, type = 'info', extraData = null) {
        // Crear el contenedor de notificaciones si no existe
        let container = document.getElementById('modern-notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'modern-notifications-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }

        // Crear la notificación
        const notification = document.createElement('div');
        const notificationId = 'notification-' + Date.now();
        notification.id = notificationId;
        
        // Estilos según el tipo
        const typeStyles = {
            success: {
                bg: 'linear-gradient(135deg, #10b981, #059669)',
                icon: '✅',
                border: '#10b981'
            },
            error: {
                bg: 'linear-gradient(135deg, #ef4444, #dc2626)',
                icon: '❌',
                border: '#ef4444'
            },
            warning: {
                bg: 'linear-gradient(135deg, #f59e0b, #d97706)',
                icon: '⚠️',
                border: '#f59e0b'
            },
            info: {
                bg: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                icon: 'ℹ️',
                border: '#3b82f6'
            }
        };

        const style = typeStyles[type] || typeStyles.info;
        
        // Construir contenido adicional
        let extraContent = '';
        if (extraData) {
            if (extraData.prendas !== undefined) {
                extraContent += `<div style="font-size: 0.8em; opacity: 0.9; margin-top: 4px;">
                    📦 ${extraData.prendas} prenda(s) procesada(s)
                </div>`;
            }
            if (extraData.registrosRegenerados) {
                extraContent += `<div style="font-size: 0.8em; opacity: 0.9;">
                    🔄 Registros regenerados automáticamente
                </div>`;
            }
        }

        notification.style.cssText = `
            background: ${style.bg};
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            margin-bottom: 12px;
            max-width: 400px;
            pointer-events: auto;
            cursor: pointer;
            transform: translateX(100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid ${style.border};
            backdrop-filter: blur(10px);
        `;

        notification.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 1.2em; flex-shrink: 0;">${style.icon}</span>
                <div style="flex: 1;">
                    <div style="font-weight: 600; line-height: 1.4; white-space: pre-line;">${message}</div>
                    ${extraContent}
                </div>
                <button style="
                    background: none; 
                    border: none; 
                    color: white; 
                    font-size: 1.2em; 
                    cursor: pointer; 
                    opacity: 0.7;
                    padding: 0;
                    margin-left: 8px;
                    flex-shrink: 0;
                " onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        // Agregar al contenedor
        container.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Auto-remover después de un tiempo (más tiempo para mensajes largos)
        const autoRemoveTime = type === 'error' ? 8000 : (message.length > 100 ? 6000 : 4000);
        setTimeout(() => {
            if (document.getElementById(notificationId)) {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, autoRemoveTime);

        // Remover al hacer clic
        notification.addEventListener('click', () => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }

    updateRowColor(orderId, status) {
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (!row) return;

        // Remover clases de color anteriores
        row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary');

        // Obtener el total de días para este pedido
        const totalDias = parseInt(this.virtual.totalDiasCalculados[orderId] || 0);
        let conditionalClass = '';

        // Aplicar clase según estado y días
        if (status === 'Entregado') {
            conditionalClass = 'row-delivered';
        } else if (status === 'Anulada') {
            conditionalClass = 'row-anulada';
        } else if (totalDias > 14 && totalDias < 20) {
            conditionalClass = 'row-warning';
        } else if (totalDias === 20) {
            conditionalClass = 'row-danger-light';
        } else if (totalDias > 20) {
            conditionalClass = 'row-secondary';
        }

        if (conditionalClass) {
            row.classList.add(conditionalClass);
        }
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

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error HTTP:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 200)}`);
            }

            const data = await response.json();
            if (data.success) {
                // Actualizar data-value del dropdown
                dropdown.dataset.value = newStatus;
                // Actualizar color de la fila dinámicamente
                this.updateRowColor(orderId, newStatus);
            } else {
                console.error('Error actualizando:', data.message);
                alert(`Error al actualizar: ${data.message}`);
                // Revertir cambio en caso de error
                dropdown.value = oldStatus;
            }
        } catch (error) {
            console.error('Error completo:', error);
            alert(`Error al actualizar el estado: ${error.message}`);
            // Revertir cambio en caso de error
            dropdown.value = oldStatus;
        }
    }

    async performAjaxSearch(term) {
        // Cancelar request anterior si existe
        if (this.searchAbortController) {
            this.searchAbortController.abort();
        }
        this.searchAbortController = new AbortController();

        const url = new URL(window.location);
        const params = new URLSearchParams(url.search);
        term ? params.set('search', term) : params.delete('search');
        params.set('page', 1);

        try {
            const response = await fetch(`${this.baseRoute}?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.searchAbortController.signal
            });

            const data = await response.json();
            
            // 🔍 LOGGING: Mostrar en console los días calculados para cada pedido
            if (data.totalDiasCalculados && Object.keys(data.totalDiasCalculados).length > 0) {
                console.log('%c📊 BÚSQUEDA DE PEDIDO - DÍAS CALCULADOS', 'color: #2563eb; font-weight: bold; font-size: 14px;');
                console.log('%cTérmino de búsqueda:', 'color: #7c3aed; font-weight: bold;', term);
                console.log('%cResultados encontrados:', 'color: #059669; font-weight: bold;', data.orders.length);
                console.log('%c' + '='.repeat(60), 'color: #6b7280;');
                
                data.orders.forEach(order => {
                    const dias = data.totalDiasCalculados[order.pedido] || 0;
                    const estado = order.estado || 'N/A';
                    const area = order.area || 'N/A';
                    const cliente = order.cliente || 'N/A';
                    
                    console.log(
                        `%c📦 Pedido: ${order.pedido}%c | Días: ${dias}%c | Estado: ${estado}%c | Área: ${area}%c | Cliente: ${cliente}`,
                        'color: #dc2626; font-weight: bold;',
                        'color: #2563eb; font-weight: bold;',
                        'color: #7c3aed;',
                        'color: #059669;',
                        'color: #6b7280;'
                    );
                });
                console.log('%c' + '='.repeat(60), 'color: #6b7280;');
            }
            
            this.updateTableWithData(data.orders, data.totalDiasCalculados);
            this.updatePaginationInfo(data.pagination);
            this.updatePaginationControls(data.pagination_html, data.pagination);
            this.updateUrl(params.toString());
            this.initializeStatusDropdowns();
            this.initializeAreaDropdowns();
            
            // CRÍTICO: Reinicializar dropdown de día de entrega después de búsqueda
            if (typeof initializeDiaEntregaDropdowns === 'function') {
                initializeDiaEntregaDropdowns();
                console.log('✅ Dropdowns de día de entrega reinicializados después de búsqueda');
            }
        } catch (error) {
            // Si fue cancelado por AbortController, no hacer nada
            if (error.name === 'AbortError') {
                console.log('⚠️ Búsqueda anterior cancelada (nueva búsqueda en progreso)');
                return;
            }
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
        
        // Aplicar clases condicionales basadas en días y estado
        const pedidoKey = orden.pedido || orden.id;
        const totalDias = parseInt(totalDiasCalculados[pedidoKey] || 0);
        const estado = orden.estado || '';
        let conditionalClass = '';
        
        if (estado === 'Entregado') {
            conditionalClass = 'row-delivered';
        } else if (estado === 'Anulada') {
            conditionalClass = 'row-anulada';
        } else if (totalDias > 14 && totalDias < 20) {
            conditionalClass = 'row-warning';
        } else if (totalDias === 20) {
            conditionalClass = 'row-danger-light';
        } else if (totalDias > 20) {
            conditionalClass = 'row-secondary';
        }
        
        row.className = `table-row ${conditionalClass}`.trim();
        row.dataset.orderId = pedidoKey;

        // PRIMERO: Crear la columna de acciones
        const accionesTd = document.createElement('td');
        accionesTd.className = 'table-cell acciones-column';
        accionesTd.style.minWidth = '200px';
        const accionesDiv = document.createElement('div');
        accionesDiv.className = 'cell-content';
        accionesDiv.style.cssText = 'display: flex; gap: 4px; flex-wrap: wrap;';
        accionesDiv.innerHTML = `
            <button class="action-btn edit-btn" onclick="openEditModal(${pedidoKey})"
                title="Editar orden"
                style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                Editar
            </button>
            <button class="action-btn detail-btn" onclick="createViewButtonDropdown(${pedidoKey})" 
                title="Ver opciones"
                style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                Ver
            </button>
            <button class="action-btn delete-btn" onclick="deleteOrder(${pedidoKey})" 
                title="Eliminar orden"
                style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                Borrar
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
            
            // Debug temporal para estado y área
            if (column === 'estado' || column === 'area') {
                console.log(`Columna ${column} para orden ${orden.pedido}: valor="${val}"`);
            }
            
            row.appendChild(this.createCellElement(column, val, orden));
        }

        tbody.appendChild(row);
    });
    
    this.setupCellTextWrapping();
    this.initializeStatusDropdowns();
    this.initializeAreaDropdowns();
}

    updatePaginationInfo(pagination) {
        // Buscar por ID primero, luego por clase
        let info = document.getElementById('paginationInfo');
        if (!info) {
            info = document.querySelector('.pagination-info span');
        }
        if (info) {
            const newText = `Mostrando ${pagination.from}-${pagination.to} de ${pagination.total} registros`;
            info.textContent = newText;
            console.log(`✅ Paginación actualizada: ${newText}`);
        } else {
            console.warn('⚠️ Elemento de paginación no encontrado');
        }
    }

    updatePaginationControls(html, pagination) {
        const controls = document.querySelector('.pagination-controls');
        if (!controls) return;

        // Si no hay datos de paginación, no hacer nada
        if (!pagination) {
            console.warn('⚠️ Datos de paginación no disponibles');
            return;
        }

        const currentPage = pagination.current_page || 1;
        const lastPage = pagination.last_page || 1;
        const total = pagination.total || 0;

        console.log(`📊 Actualizando paginación: Página ${currentPage} de ${lastPage} (Total: ${total})`);

        // Usar el HTML del backend si existe, de lo contrario generar uno simple
        if (html && html.trim().length > 0) {
            // El HTML del backend ya tiene el diseño correcto, solo usarlo
            controls.innerHTML = html;
            console.log(`✅ Paginación del backend utilizada`);
        } else {
            // Si no hay HTML del backend, generar uno simple
            console.warn('⚠️ HTML de paginación del backend no disponible, generando simple');
            
            let paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination">';

            // Botón anterior
            if (currentPage > 1) {
                const prevUrl = this.getPaginationUrl(currentPage - 1);
                paginationHtml += `<li class="page-item"><a class="page-link" href="${prevUrl}">← Anterior</a></li>`;
            } else {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">← Anterior</span></li>';
            }

            // Generar botones de página (máximo 10 páginas visibles)
            let startPage = Math.max(1, currentPage - 4);
            let endPage = Math.min(lastPage, currentPage + 5);

            // Si hay muchas páginas, mostrar puntos suspensivos
            if (startPage > 1) {
                paginationHtml += '<li class="page-item"><a class="page-link" href="' + this.getPaginationUrl(1) + '">1</a></li>';
                if (startPage > 2) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    const pageUrl = this.getPaginationUrl(i);
                    paginationHtml += `<li class="page-item"><a class="page-link" href="${pageUrl}">${i}</a></li>`;
                }
            }

            // Si hay más páginas, mostrar puntos suspensivos y última página
            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                paginationHtml += '<li class="page-item"><a class="page-link" href="' + this.getPaginationUrl(lastPage) + '">' + lastPage + '</a></li>';
            }

            // Botón siguiente
            if (currentPage < lastPage) {
                const nextUrl = this.getPaginationUrl(currentPage + 1);
                paginationHtml += `<li class="page-item"><a class="page-link" href="${nextUrl}">Siguiente →</a></li>`;
            } else {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">Siguiente →</span></li>';
            }

            paginationHtml += '</ul></nav>';

            controls.innerHTML = paginationHtml;
            console.log(`✅ Paginación simple generada: ${lastPage} página(s)`);
        }
    }

    getPaginationUrl(page) {
        const url = new URL(window.location);
        const params = new URLSearchParams(url.search);
        params.set('page', page);
        return `${this.baseRoute}?${params}`;
    }

    updateUrl(queryString) {
        window.history.pushState(null, '', `${window.location.pathname}?${queryString}`);
    }

appendRowsToTable(orders, totalDiasCalculados) {
    const tbody = document.querySelector('#tablaOrdenes tbody');
    
    orders.forEach(orden => {
        const row = document.createElement('tr');
        
        // Aplicar clases condicionales basadas en días y estado
        const pedidoKey = orden.pedido || orden.id;
        const totalDias = parseInt(totalDiasCalculados[pedidoKey] || 0);
        const estado = orden.estado || '';
        let conditionalClass = '';
        
        if (estado === 'Entregado') {
            conditionalClass = 'row-delivered';
        } else if (estado === 'Anulada') {
            conditionalClass = 'row-anulada';
        } else if (totalDias > 14 && totalDias < 20) {
            conditionalClass = 'row-warning';
        } else if (totalDias === 20) {
            conditionalClass = 'row-danger-light';
        } else if (totalDias > 20) {
            conditionalClass = 'row-secondary';
        }
        
        row.className = `table-row ${conditionalClass}`.trim();
        row.dataset.orderId = pedidoKey;

        // PRIMERO: Crear la columna de acciones
        const accionesTd = document.createElement('td');
        accionesTd.className = 'table-cell acciones-column';
        accionesTd.style.minWidth = '200px';
        const accionesDiv = document.createElement('div');
        accionesDiv.className = 'cell-content';
        accionesDiv.style.cssText = 'display: flex; gap: 4px; flex-wrap: wrap;';
        accionesDiv.innerHTML = `
            <button class="action-btn edit-btn" onclick="openEditModal(${pedidoKey})"
                title="Editar orden"
                style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                Editar
            </button>
            <button class="action-btn detail-btn" onclick="createViewButtonDropdown(${pedidoKey})" 
                title="Ver opciones"
                style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                Ver
            </button>
            <button class="action-btn delete-btn" onclick="deleteOrder(${pedidoKey})" 
                title="Eliminar orden"
                style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                Borrar
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
    this.initializeAreaDropdowns();
    
    // Formatear todas las fechas en la tabla después de renderizar
    this.formatearTodasLasFechas();
}

/**
 * Formatea todas las fechas en la tabla a DD/MM/YYYY
 */
formatearTodasLasFechas() {
    const dateColumns = [
        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
        'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
        'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega', 'despacho'
    ];
    
    // Buscar todas las celdas de fecha
    dateColumns.forEach(column => {
        document.querySelectorAll(`td[data-column="${column}"] .cell-text`).forEach(cell => {
            const fechaActual = cell.textContent.trim();
            
            // Si está en YYYY-MM-DD, convertir a DD/MM/YYYY
            if (fechaActual && fechaActual.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const partes = fechaActual.split('-');
                const fechaFormateada = `${partes[2]}/${partes[1]}/${partes[0]}`;
                cell.textContent = fechaFormateada;
                console.log(`✅ [formatearTodasLasFechas] ${column}: ${fechaActual} → ${fechaFormateada}`);
            }
        });
    });
}

initializeStatusDropdowns() {
        document.querySelectorAll('.estado-dropdown').forEach(dropdown => {
            // Guardar el valor actual antes de clonar
            const currentValue = dropdown.value;
            
            // Remover listener anterior si existe para evitar duplicados
            const newDropdown = dropdown.cloneNode(true);
            dropdown.parentNode.replaceChild(newDropdown, dropdown);
            
            // Restaurar el valor después de reemplazar
            newDropdown.value = currentValue;
            
            newDropdown.addEventListener('change', e => this.updateOrderStatus(e.target));
        });
    }

    initializeAreaDropdowns() {
        document.querySelectorAll('.area-dropdown').forEach(dropdown => {
            // Guardar el valor actual antes de clonar
            const currentValue = dropdown.value;
            
            // Remover listener anterior si existe para evitar duplicados
            const newDropdown = dropdown.cloneNode(true);
            dropdown.parentNode.replaceChild(newDropdown, dropdown);
            
            // Restaurar el valor después de reemplazar
            newDropdown.value = currentValue;
            
            newDropdown.addEventListener('change', e => this.updateOrderArea(e.target));
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

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error HTTP:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 200)}`);
            }

            const data = await response.json();
            if (data.success) {
                // Actualizar data-value del dropdown
                dropdown.dataset.value = newArea;
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
                alert(`Error al actualizar: ${data.message}`);
                // Revertir cambio en caso de error
                dropdown.value = oldArea;
            }
        } catch (error) {
            console.error('Error completo:', error);
            alert(`Error al actualizar el área: ${error.message}`);
            // Revertir cambio en caso de error
            dropdown.value = oldArea;
        }
    }

    async loadPageFromUrl(href) {
        const url = new URL(href);
        const params = new URLSearchParams(url.search);

        try {
            // Usar el href completo para preservar todos los parámetros (búsqueda, filtros, página)
            const response = await fetch(href, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await response.json();
            this.updateTableWithData(data.orders, data.totalDiasCalculados);
            this.updatePaginationInfo(data.pagination);
            this.updatePaginationControls(data.pagination_html, data.pagination);
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

    /**
     * Actualizar una orden existente en la tabla (para WebSocket updates)
     */
    actualizarOrdenEnTabla(orden) {
        const row = document.querySelector(`tr[data-order-id="${orden.pedido}"]`);
        if (!row) {
            console.log(`Orden ${orden.pedido} no encontrada en la tabla actual`);
            return;
        }

        let hasChanges = false;

        // Actualizar cada celda
        Object.keys(orden).forEach(column => {
            if (column === 'id' || column === 'tiempo') return;

            const cell = row.querySelector(`td[data-column="${column}"]`);
            if (!cell) return;

            const value = orden[column];
            // Para dia_de_entrega, null/undefined es válido (significa "Seleccionar")
            if (value === null || value === undefined) {
                if (column !== 'dia_de_entrega') return;
            }

            const cellContent = cell.querySelector('.cell-content');
            if (!cellContent) return;

            // Manejar dropdowns de estado, área y día de entrega
            if (column === 'estado') {
                const select = cellContent.querySelector('.estado-dropdown');
                if (select && select.value !== value) {
                    select.value = value;
                    select.setAttribute('data-value', value);
                    hasChanges = true;
                    cell.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
                    setTimeout(() => {
                        cell.style.transition = 'background-color 0.3s ease';
                        cell.style.backgroundColor = '';
                    }, 30);
                }
            } else if (column === 'area') {
                const select = cellContent.querySelector('.area-dropdown');
                if (select && select.value !== value) {
                    select.value = value;
                    select.setAttribute('data-value', value);
                    hasChanges = true;
                    cell.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
                    setTimeout(() => {
                        cell.style.transition = 'background-color 0.3s ease';
                        cell.style.backgroundColor = '';
                    }, 30);
                }
            } else if (column === 'dia_de_entrega') {
                // CRÍTICO: Actualizar dropdown de día de entrega
                const select = cellContent.querySelector('.dia-entrega-dropdown');
                if (select) {
                    const valorFinal = (value === null || value === undefined || value === '') ? '' : String(value);
                    if (select.value !== valorFinal) {
                        select.value = valorFinal;
                        select.setAttribute('data-value', valorFinal);
                        hasChanges = true;
                        cell.style.backgroundColor = 'rgba(249, 115, 22, 0.3)'; // Naranja
                        setTimeout(() => {
                            cell.style.transition = 'background-color 0.3s ease';
                            cell.style.backgroundColor = '';
                        }, 30);
                        console.log(`✅ Día de entrega actualizado vía WebSocket (modern-table): ${valorFinal || 'Seleccionar'} para orden ${orden.pedido}`);
                    }
                }
            } else {
                const span = cellContent.querySelector('.cell-text');
                if (span && span.textContent.trim() !== String(value).trim()) {
                    // Formatear fechas si es columna de fecha
                    const dateColumns = [
                        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
                        'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
                        'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega', 'despacho'
                    ];
                    
                    let displayValue = value;
                    
                    // Si es columna de fecha
                    if (dateColumns.includes(column)) {
                        // Si no hay valor (null, undefined, vacío), mostrar guión
                        if (!value || value === null || value === undefined || value === '') {
                            displayValue = '-';
                            console.log(`✅ [WebSocket] Fecha limpiada ${column}: mostrar "-"`);
                        } else if (String(value).match(/^\d{4}-\d{2}-\d{2}$/)) {
                            // Si está en YYYY-MM-DD, convertir a DD/MM/YYYY
                            const partes = String(value).split('-');
                            displayValue = `${partes[2]}/${partes[1]}/${partes[0]}`;
                            console.log(`✅ [WebSocket] Fecha formateada ${column}: ${value} → ${displayValue}`);
                        }
                    }
                    
                    span.textContent = displayValue;
                    hasChanges = true;
                    cell.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
                    setTimeout(() => {
                        cell.style.transition = 'background-color 0.3s ease';
                        cell.style.backgroundColor = '';
                    }, 30);
                }
            }
        });

        // Actualizar clases condicionales de la fila
        const estado = orden.estado || '';
        let totalDias = parseInt(orden.total_de_dias_) || 0;
        
        if (!totalDias) {
            const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
            if (totalDiasCell) {
                totalDias = parseInt(totalDiasCell.textContent) || 0;
            }
        }

        // Obtener día de entrega
        let diaDeEntrega = null;
        if (orden.dia_de_entrega !== null && orden.dia_de_entrega !== undefined && orden.dia_de_entrega !== '') {
            diaDeEntrega = parseInt(orden.dia_de_entrega);
        } else {
            const diaEntregaDropdown = row.querySelector('.dia-entrega-dropdown');
            if (diaEntregaDropdown && diaEntregaDropdown.value !== '') {
                diaDeEntrega = parseInt(diaEntregaDropdown.value);
            }
        }

        row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary', 'row-dia-entrega-warning', 'row-dia-entrega-danger', 'row-dia-entrega-critical');
        row.style.backgroundColor = '';

        // PRIORIDAD 1: Estados especiales
        if (estado === 'Entregado') {
            row.classList.add('row-delivered');
        } else if (estado === 'Anulada') {
            row.classList.add('row-anulada');
        }
        // PRIORIDAD 2: Día de entrega (si existe)
        else if (diaDeEntrega !== null && diaDeEntrega > 0) {
            if (totalDias >= 15) {
                row.classList.add('row-dia-entrega-critical');
            } else if (totalDias >= 10 && totalDias <= 14) {
                row.classList.add('row-dia-entrega-danger');
            } else if (totalDias >= 5 && totalDias <= 9) {
                row.classList.add('row-dia-entrega-warning');
            }
        }
        // PRIORIDAD 3: Lógica original
        else {
            if (totalDias > 20) {
                row.classList.add('row-secondary');
            } else if (totalDias === 20) {
                row.classList.add('row-danger-light');
            } else if (totalDias > 14 && totalDias < 20) {
                row.classList.add('row-warning');
            }
        }

        if (hasChanges) {
            console.log(`✅ Orden ${orden.pedido} actualizada en tiempo real`);
        }
    }

    /**
     * Manejar actualizaciones de órdenes desde WebSocket
     */
    handleOrdenUpdate(orden, action) {
        const pedido = orden.pedido;
        console.log(`📡 Procesando acción: ${action} para orden ${pedido}`);

        if (action === 'deleted') {
            const row = document.querySelector(`tr[data-order-id="${pedido}"]`);
            if (row) {
                row.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
                setTimeout(() => {
                    row.remove();
                    console.log(`✅ Orden ${pedido} eliminada de la tabla`);
                }, 500);
            }
            return;
        }

        if (action === 'created') {
            // Recargar la tabla para mostrar la nueva orden
            window.location.reload();
            return;
        }

        if (action === 'updated') {
            this.actualizarOrdenEnTabla(orden);
            return;
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('ModernTable: DOMContentLoaded fired, checking for tablaOrdenes...');
    if (document.getElementById('tablaOrdenes')) {
        console.log('ModernTable: tablaOrdenes found, initializing...');
        const modernTable = new ModernTable();
        window.modernTable = modernTable;

        // Add clear filters button
        const clearBtn = Object.assign(document.createElement('button'), {
            textContent: 'Limpiar Filtros',
            className: 'btn btn-secondary ml-2',
            style: 'font-size:12px;'
        });
        clearBtn.addEventListener('click', () => modernTable.clearAllFilters());

        // Add register orders button (solo para no supervisores)
        const isSupervisor = document.body.dataset.userRole === 'supervisor';
        if (!isSupervisor) {
            const registerBtn = Object.assign(document.createElement('button'), {
                textContent: 'Registrar Órdenes',
                className: 'btn btn-primary ml-2',
                style: 'font-size:12px; background-color: #ff9d58; border-color: #ff9d58; color: #fff;'
            });
            registerBtn.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-registration' }));
            });

            document.querySelector('.table-actions')?.appendChild(registerBtn);
        }

        document.querySelector('.table-actions')?.appendChild(clearBtn);
    }
})