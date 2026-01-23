/**
 * NAV SEARCH - Búsqueda en tiempo real en la barra de navegación
 * Busca por número de pedido o cliente
 */

const NavSearch = {
    config: {
        debounceDelay: 500,
        minChars: 1,
        maxResults: 10,
        apiEndpoint: '/registros/search'
    },

    state: {
        debounceTimer: null,
        currentQuery: '',
        isLoading: false,
        isSearchActive: false,
        currentPage: 1,
        totalPages: 1,
        pagination: null
    },

    /**
     * Inicializar búsqueda en el nav
     */
    initialize() {


        const searchInput = document.getElementById('navSearchInput');
        const searchClear = document.getElementById('navSearchClear');
        const searchResults = document.getElementById('navSearchResults');

        if (!searchInput) {

            return;
        }

        // Detectar si hay búsqueda en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('search');
        
        if (searchParam) {

            searchInput.value = searchParam;
            this.state.isSearchActive = true;
            this.state.currentQuery = searchParam;
            
            // Mostrar botón de limpiar
            if (searchClear) {
                searchClear.style.display = 'flex';
            }
        }

        // Event listeners
        searchInput.addEventListener('input', (e) => this.handleInput(e));
        searchInput.addEventListener('keypress', (e) => this.handleKeyPress(e));
        searchInput.addEventListener('focus', () => this.showResults());
        searchInput.addEventListener('blur', () => {
            // Delay para permitir clicks en resultados
            setTimeout(() => this.hideResults(), 200);
        });

        if (searchClear) {
            searchClear.addEventListener('click', () => this.clearSearch());
        }

        // Cerrar resultados al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-search-container')) {
                this.hideResults();
            }
        });


    },

    /**
     * Manejar input de búsqueda (solo mostrar/ocultar botón limpiar)
     */
    handleInput(e) {
        const query = e.target.value.trim();

        // Mostrar/ocultar botón de limpiar
        const clearBtn = document.getElementById('navSearchClear');
        if (clearBtn) {
            clearBtn.style.display = query ? 'flex' : 'none';
        }
    },

    /**
     * Manejar presión de tecla (Enter para buscar)
     */
    handleKeyPress(e) {
        if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();
        
        const query = e.target.value.trim();

        // Si está vacío, restaurar tabla original
        if (!query) {
            if (this.state.isSearchActive) {
                this.restoreOriginalTable();
            }
            return;
        }

        // Si es muy corto, no buscar
        if (query.length < this.config.minChars) {
            return;
        }

        // Ejecutar búsqueda inmediatamente (sin debounce)

        this.performSearch(query);
    },

    /**
     * Debounce de búsqueda
     */
    debounceSearch(query) {
        clearTimeout(this.state.debounceTimer);

        this.state.debounceTimer = setTimeout(() => {
            this.performSearch(query);
        }, this.config.debounceDelay);
    },

    /**
     * Realizar búsqueda
     */
    async performSearch(query, page = 1) {


        this.state.isLoading = true;

        try {
            // Detectar si está en bodega o registros
            const isBodega = window.location.pathname.startsWith('/bodega');
            const searchEndpoint = isBodega ? '/bodega/search' : '/registros/search';
            


            // Hacer búsqueda con paginación
            const searchResponse = await fetch(searchEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    search: query,
                    limit: 25,
                    page: page,
                    isTableSearch: true
                })
            });

            if (searchResponse.ok) {
                const searchData = await searchResponse.json();
                const ordenes = searchData.data || searchData.ordenes || [];



                // Guardar estado de búsqueda
                this.state.isSearchActive = true;
                this.state.currentQuery = query;
                this.state.currentPage = page;
                this.state.pagination = searchData.pagination;
                this.state.totalPages = searchData.pagination?.last_page || 1;

                // Mantener el texto en el input de búsqueda
                const searchInput = document.getElementById('navSearchInput');
                if (searchInput && query) {
                    searchInput.value = query;
                }

                // Actualizar tabla dinámicamente (sin mostrar loading)
                this.updateTableDynamically(ordenes, searchData.pagination);

                // Actualizar URL sin recargar
                this.updateUrlWithoutReload(query, page);
            } else {
                throw new Error(`HTTP error! status: ${searchResponse.status}`);
            }
        } catch (error) {

            this.showError('Error al buscar');
        } finally {
            this.state.isLoading = false;
        }
    },

    /**
     * Actualizar tabla dinámicamente sin recargar
     */
    updateTableDynamically(ordenes, pagination) {


        const tableBody = document.querySelector('.table-body');
        if (!tableBody) {

            return;
        }

        // Limpiar tabla
        tableBody.innerHTML = '';

        // Si no hay resultados
        if (!ordenes || ordenes.length === 0) {
            tableBody.innerHTML = `
                <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #999;">
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                    <p>No se encontraron resultados para la búsqueda</p>
                </div>
            `;
            return;
        }

        // Renderizar filas
        ordenes.forEach(orden => {
            const row = this.createTableRow(orden);
            tableBody.appendChild(row);
        });

        // Aplicar colores condicionales
        if (typeof applyAllRowConditionalColors === 'function') {
            applyAllRowConditionalColors();
        }

        // Actualizar paginación
        if (pagination) {
            this.updatePaginationControls(pagination);
        }


    },

    /**
     * Crear fila de tabla
     */
    createTableRow(orden) {
        const row = document.createElement('div');
        row.className = 'table-row';
        row.setAttribute('data-orden-id', orden.numero_pedido);

        const totalDias = orden.total_dias_calculado || 0;
        const cantidad = this.getCantidadTotal(orden);

        row.innerHTML = `
            <!-- Acciones -->
            <div class="table-cell acciones-column" style="flex: 0 0 100px; justify-content: center; position: relative;">
                <button class="action-view-btn" title="Ver detalles" data-orden-id="${orden.numero_pedido}">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="action-menu" data-orden-id="${orden.numero_pedido}">
                    <a href="#" class="action-menu-item" data-action="detalle">
                        <i class="fas fa-eye"></i>
                        <span>Detalle</span>
                    </a>
                    <a href="#" class="action-menu-item" data-action="seguimiento">
                        <i class="fas fa-tasks"></i>
                        <span>Seguimiento</span>
                    </a>
                </div>
            </div>

            <!-- Estado (Dropdown) -->
            <div class="table-cell" style="flex: 0 0 auto;">
                <div class="cell-content">
                    <select class="estado-dropdown estado-${this.getStatusClass(orden.estado)}" data-orden-id="${orden.numero_pedido}">
                        <option value="En Ejecución" ${orden.estado === 'En Ejecución' ? 'selected' : ''}>En Ejecución</option>
                        <option value="Entregado" ${orden.estado === 'Entregado' ? 'selected' : ''}>Entregado</option>
                        <option value="No iniciado" ${orden.estado === 'No iniciado' ? 'selected' : ''}>No iniciado</option>
                        <option value="Anulada" ${orden.estado === 'Anulada' ? 'selected' : ''}>Anulada</option>
                    </select>
                </div>
            </div>

            <!-- Área (Dropdown) -->
            <div class="table-cell" style="flex: 0 0 auto;">
                <div class="cell-content">
                    <select class="area-dropdown" data-orden-id="${orden.numero_pedido}">
                        <option value="Corte" ${orden.area === 'Corte' ? 'selected' : ''}>Corte</option>
                        <option value="Costura" ${orden.area === 'Costura' ? 'selected' : ''}>Costura</option>
                        <option value="Estampado" ${orden.area === 'Estampado' ? 'selected' : ''}>Estampado</option>
                        <option value="Bordado" ${orden.area === 'Bordado' ? 'selected' : ''}>Bordado</option>
                        <option value="Confección" ${orden.area === 'Confección' ? 'selected' : ''}>Confección</option>
                        <option value="Empaque" ${orden.area === 'Empaque' ? 'selected' : ''}>Empaque</option>
                        <option value="Control de Calidad" ${orden.area === 'Control de Calidad' ? 'selected' : ''}>Control de Calidad</option>
                        <option value="Insumos y Telas" ${orden.area === 'Insumos y Telas' ? 'selected' : ''}>Insumos y Telas</option>
                        <option value="Armado" ${orden.area === 'Armado' ? 'selected' : ''}>Armado</option>
                        <option value="Confeccionando" ${orden.area === 'Confeccionando' ? 'selected' : ''}>Confeccionando</option>
                        <option value="Cortando" ${orden.area === 'Cortando' ? 'selected' : ''}>Cortando</option>
                    </select>
                </div>
            </div>

            <!-- Día de entrega (Dropdown) -->
            <div class="table-cell" style="flex: 0 0 auto;">
                <div class="cell-content">
                    <select class="dia-entrega-dropdown" data-orden-id="${orden.numero_pedido}">
                        <option value="">Seleccionar</option>
                        ${[1,2,3,4,5,6,7,8,9,10,15,20,25,30].map(dia => 
                            `<option value="${dia}" ${orden.dia_de_entrega == dia ? 'selected' : ''}>${dia} días</option>`
                        ).join('')}
                    </select>
                </div>
            </div>

            <!-- Total de días -->
            <div class="table-cell" style="flex: 0 0 120px;">
                <div class="cell-content" style="justify-content: center;">
                    <span class="total-dias">${totalDias}</span>
                </div>
            </div>

            <!-- Número de Pedido -->
            <div class="table-cell" style="flex: 0 0 120px;">
                <div class="cell-content" style="justify-content: center;">
                    <span class="numero-pedido" style="font-weight: 600;">${orden.numero_pedido}</span>
                </div>
            </div>

            <!-- Cliente -->
            <div class="table-cell" style="flex: 0 0 150px;">
                <div class="cell-content" style="justify-content: center;">
                    <span class="cliente">${orden.cliente || '-'}</span>
                </div>
            </div>

            <!-- Descripción de Prendas -->
            <div class="table-cell" style="flex: 10;">
                <div class="cell-content" style="justify-content: center;">
                    <span class="descripcion-prendas">${this.getDescripcionPrendas(orden)}</span>
                </div>
            </div>

            <!-- Cantidad -->
            <div class="table-cell" style="flex: 0 0 100px;">
                <div class="cell-content" style="margin-left: 50px;">
                    <span class="cantidad">${cantidad}</span>
                </div>
            </div>

            <!-- Novedades -->
            <div class="table-cell" style="flex: 0 0 120px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <span class="novedades">${orden.novedades || '-'}</span>
                </div>
            </div>

            <!-- Asesor -->
            <div class="table-cell" style="flex: 0 0 120px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <span class="asesor">${orden.asesor || '-'}</span>
                </div>
            </div>

            <!-- Forma de pago -->
            <div class="table-cell" style="flex: 0 0 150px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <span class="forma-pago">${orden.forma_de_pago || '-'}</span>
                </div>
            </div>

            <!-- Fecha de creación -->
            <div class="table-cell" style="flex: 0 0 150px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <span class="fecha-creacion">${this.formatDate(orden.fecha_de_creacion_de_orden || orden.created_at)}</span>
                </div>
            </div>

            <!-- Fecha estimada entrega -->
            <div class="table-cell" style="flex: 0 0 180px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <span class="fecha-estimada">${this.formatDate(orden.fecha_estimada_de_entrega)}</span>
                </div>
            </div>

            <!-- Encargado orden -->
            <div class="table-cell" style="flex: 0 0 150px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <span class="encargado">${orden.encargado || '-'}</span>
                </div>
            </div>
        `;

        return row;
    },

    /**
     * Obtener descripción de prendas
     */
    getDescripcionPrendas(orden) {
        if (!orden.prendas || orden.prendas.length === 0) {
            return '-';
        }

        const nombres = orden.prendas
            .map(p => p.nombre_prenda)
            .filter((v, i, a) => a.indexOf(v) === i)
            .join(', ');

        return this.truncateText(nombres, 100);
    },

    /**
     * Obtener cantidad total
     */
    getCantidadTotal(orden) {
        if (!orden.prendas || orden.prendas.length === 0) {
            return '-';
        }

        const total = orden.prendas.reduce((sum, prenda) => sum + (parseInt(prenda.cantidad) || 0), 0);
        return total > 0 ? String(total) : '-';
    },

    /**
     * Obtener clase CSS para estado
     */
    getStatusClass(estado) {
        return estado.toLowerCase().replace(/ /g, '-');
    },

    /**
     * Truncar texto
     */
    truncateText(text, length) {
        if (!text) return '-';
        if (text.length > length) {
            return text.substring(0, length) + '...';
        }
        return text;
    },

    /**
     * Formatear fecha
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit' });
    },

    /**
     * Actualizar controles de paginación
     */
    updatePaginationControls(pagination) {
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationControls = document.getElementById('paginationControls');

        if (!paginationInfo || !paginationControls) {

            return;
        }

        // Actualizar texto de información
        const total = pagination.total || 0;
        paginationInfo.textContent = `Mostrando ${total} de ${total} registros`;

        // Generar botones de paginación
        let html = '';
        const currentPage = pagination.current_page;
        const lastPage = pagination.last_page;

        // Primera página
        html += `<button class="pagination-btn" data-page="1" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-double-left"></i>
        </button>`;

        // Página anterior
        html += `<button class="pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-angle-left"></i>
        </button>`;

        // Números de página
        const start = Math.max(1, currentPage - 2);
        const end = Math.min(lastPage, currentPage + 2);

        for (let i = start; i <= end; i++) {
            html += `<button class="pagination-btn page-number ${i === currentPage ? 'active' : ''}" data-page="${i}">
                ${i}
            </button>`;
        }

        // Página siguiente
        html += `<button class="pagination-btn" data-page="${currentPage + 1}" ${currentPage === lastPage ? 'disabled' : ''}>
            <i class="fas fa-angle-right"></i>
        </button>`;

        // Última página
        html += `<button class="pagination-btn" data-page="${lastPage}" ${currentPage === lastPage ? 'disabled' : ''}>
            <i class="fas fa-angle-double-right"></i>
        </button>`;

        paginationControls.innerHTML = html;

        // Event listeners para paginación
        paginationControls.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const page = parseInt(btn.dataset.page);
                if (!isNaN(page) && !btn.disabled) {
                    this.performSearch(this.state.currentQuery, page);
                    // Scroll a la tabla
                    document.querySelector('.table-scroll-container')?.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });


    },

    /**
     * Actualizar URL sin recargar
     */
    updateUrlWithoutReload(query, page) {
        const url = new URL('/registros', window.location.origin);
        url.searchParams.append('search', query);
        url.searchParams.append('page', page);
        
        // Usar history.replaceState para actualizar URL sin recargar
        window.history.replaceState({ search: query, page: page }, '', url.toString());

    },

    /**
     * Renderizar resultados
     */
    renderResults(results) {
        const resultsContainer = document.getElementById('navSearchResults');

        if (!resultsContainer) return;

        if (results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="nav-search-empty">
                    <span class="material-symbols-rounded">search_off</span>
                    <p>No se encontraron resultados</p>
                </div>
            `;
            this.showResults();
            return;
        }

        const html = results.map(orden => `
            <div class="nav-search-result-item" data-numero-pedido="${orden.numero_pedido}">
                <span class="material-symbols-rounded nav-search-result-icon">receipt</span>
                <div class="nav-search-result-content">
                    <div class="nav-search-result-number">
                        Pedido #${orden.numero_pedido}
                    </div>
                    <div class="nav-search-result-client">
                        ${orden.cliente || 'Sin cliente'}
                    </div>
                </div>
            </div>
        `).join('');

        resultsContainer.innerHTML = html;

        // Agregar event listeners a los resultados
        resultsContainer.querySelectorAll('.nav-search-result-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const numeroPedido = item.dataset.numeroPedido;
                this.selectResult(numeroPedido);
            });
        });

        this.showResults();
    },

    /**
     * Seleccionar un resultado
     */
    selectResult(numeroPedido) {


        // Navegar a la vista del pedido
        window.location.href = `/registros/${numeroPedido}`;
    },

    /**
     * Navegar a página de búsqueda
     */
    goToSearchPage(page) {
        const url = new URL('/registros', window.location.origin);
        url.searchParams.append('search', this.state.currentQuery);
        url.searchParams.append('page', page);
        window.location.href = url.toString();
    },

    /**
     * Mostrar resultados
     */
    showResults() {
        const resultsContainer = document.getElementById('navSearchResults');
        if (resultsContainer) {
            resultsContainer.style.display = 'block';
        }
    },

    /**
     * Ocultar resultados
     */
    hideResults() {
        const resultsContainer = document.getElementById('navSearchResults');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    },

    /**
     * Mostrar estado de carga
     */
    showLoading() {
        const resultsContainer = document.getElementById('navSearchResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="nav-search-loading">
                </div>
            `;
            this.showResults();
        }
    },

    /**
     * Mostrar error
     */
    showError(message) {
        const resultsContainer = document.getElementById('navSearchResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="nav-search-empty">
                    <span class="material-symbols-rounded">error</span>
                    <p>${message}</p>
                </div>
            `;
            this.showResults();
        }
    },

    /**
     * Limpiar búsqueda
     */
    clearSearch() {

        
        const searchInput = document.getElementById('navSearchInput');
        const clearBtn = document.getElementById('navSearchClear');

        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }

        if (clearBtn) {
            clearBtn.style.display = 'none';
        }

        this.hideResults();
        this.state.currentQuery = '';
        this.state.isSearchActive = false;
        this.state.currentPage = 1;
        this.state.pagination = null;
        this.state.totalPages = 1;

        // Limpiar filtros de la tabla si existen
        const filterOptions = document.getElementById('filterOptions');
        if (filterOptions) {
            filterOptions.innerHTML = '';
            filterOptions.style.display = 'none';
        }

        // Restaurar tabla original sin recargar
        this.restoreOriginalTable();
    },

    /**
     * Restaurar tabla original sin recargar
     */
    restoreOriginalTable() {

        
        const tableBody = document.querySelector('.table-body');
        if (!tableBody) return;

        // Limpiar tabla
        tableBody.innerHTML = '';

        // Detectar si está en bodega o registros
        const isBodega = window.location.pathname.startsWith('/bodega');
        const baseUrl = isBodega ? '/bodega' : '/registros';
        
        // Actualizar URL sin recargar
        window.history.replaceState({}, '', baseUrl);

        // Recargar tabla original desde el servidor
        this.loadOriginalTable(baseUrl);
    },

    /**
     * Cargar tabla original
     */
    async loadOriginalTable(baseUrl = '/registros') {
        try {
            const response = await fetch(baseUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const html = await response.text();
            
            // Extraer el contenido de la tabla del HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const originalTableBody = doc.querySelector('.table-body');
            const originalPaginationInfo = doc.getElementById('paginationInfo');
            const originalPaginationControls = doc.getElementById('paginationControls');

            // Actualizar tabla
            const tableBody = document.querySelector('.table-body');
            if (tableBody && originalTableBody) {
                tableBody.innerHTML = originalTableBody.innerHTML;
                
                // Aplicar colores condicionales
                if (typeof applyAllRowConditionalColors === 'function') {
                    applyAllRowConditionalColors();
                }
            }

            // Actualizar paginación
            if (originalPaginationInfo) {
                const paginationInfo = document.getElementById('paginationInfo');
                if (paginationInfo) {
                    paginationInfo.innerHTML = originalPaginationInfo.innerHTML;
                }
            }

            if (originalPaginationControls) {
                const paginationControls = document.getElementById('paginationControls');
                if (paginationControls) {
                    paginationControls.innerHTML = originalPaginationControls.innerHTML;
                }
            }


        } catch (error) {

        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    NavSearch.initialize();
});

// Exponer globalmente
window.NavSearch = NavSearch;
