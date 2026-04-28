/**
 * Ordenes Loader - Carga ordenes desde API y renderiza tabla Blade completa.
 *
 * Esto mantiene la compatibilidad con handlers legacy (acciones/filtros/modales)
 * que dependen del markup original de la vista.
 */

export class OrdenesLoader {
    constructor(options = {}) {
        console.log('[OrdenesLoader] Constructor llamado');
        this.apiBase = '/api/supervisor-pedidos/ordenes';
        this.perPage = options.perPage || 15;
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
        this.contentSelector = '#supervisorPedidosIndexContent';
        this.paginationSelector = '[data-ordenes-pagination]';

        const content = document.querySelector(this.contentSelector);
        console.log('[OrdenesLoader] Contenedor:', content ? 'encontrado' : 'NO ENCONTRADO');

        this.init();
    }

    async init() {
        console.log('%c[OrdenesLoader] Inicializando...', 'color: #3b82f6; font-weight: bold;');
        const startTime = performance.now();
        let success = false;

        try {
            success = await this.cargarPagina(1);
            const duration = performance.now() - startTime;
            console.log(`%cTabla lista en ${duration.toFixed(2)}ms`, 'color: #10b981; font-weight: bold;');
        } catch (error) {
            console.error('[OrdenesLoader] Error:', error);
        } finally {
            document.dispatchEvent(new CustomEvent('supervisor-pedidos:ordenes-loader-ready', {
                detail: { success },
            }));
        }
    }

    async cargarPagina(page = 1) {
        const apiStartTime = performance.now();

        try {
            const currentParams = new URLSearchParams(window.location.search || '');
            currentParams.set('page', String(page));
            currentParams.set('perPage', String(this.perPage));

            console.log(`%cCargando pagina ${page}...`, 'color: #6366f1;');

            const response = await fetch(`${this.apiBase}?${currentParams.toString()}`);
            const data = await response.json();

            const apiDuration = performance.now() - apiStartTime;
            console.log(`%cAPI respondio en ${apiDuration.toFixed(2)}ms`, 'color: #f97316;');

            if (!(data?.success && data?.data?.ordenes)) {
                console.warn('[OrdenesLoader] Respuesta invalida del API:', data);
                return false;
            }

            const pagination = data.data.ordenes;
            this.currentPage = pagination.current_page;
            this.totalPages = pagination.last_page;
            this.totalRecords = pagination.total;

            const renderStartTime = performance.now();
            this.renderTablaDesdeHtml(data?.data?.html || '');
            this.renderPaginacion();
            const renderDuration = performance.now() - renderStartTime;

            console.log(
                `%cRenderizado: ${(pagination.data || []).length} filas en ${renderDuration.toFixed(2)}ms`,
                renderDuration > 200 ? 'color: #f97316;' : 'color: #10b981;'
            );

            return true;
        } catch (error) {
            console.error('[OrdenesLoader] Error cargando pagina:', error);
            return false;
        }
    }

    renderTablaDesdeHtml(html) {
        if (!html) return;
        const content = document.querySelector(this.contentSelector);
        if (!content) return;

        content.innerHTML = html;
        document.dispatchEvent(new CustomEvent('supervisor-pedidos:tabla-actualizada'));
    }

    renderPaginacion() {
        const paginationContainer = document.querySelector(this.paginationSelector);
        if (!paginationContainer) return;

        let html = `
            <button class="pagination-btn" onclick="window.ordenesLoader.cargarPagina(1)" ${this.currentPage === 1 ? 'disabled' : ''}>
                Primera
            </button>
            <button class="pagination-btn" onclick="window.ordenesLoader.cargarPagina(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}>
                ← Anterior
            </button>
        `;

        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            if (i === this.currentPage) {
                html += `<button class="pagination-btn active" disabled>${i}</button>`;
            } else {
                html += `<button class="pagination-btn" onclick="window.ordenesLoader.cargarPagina(${i})">${i}</button>`;
            }
        }

        html += `
            <button class="pagination-btn" onclick="window.ordenesLoader.cargarPagina(${this.currentPage + 1})" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
                Siguiente →
            </button>
            <button class="pagination-btn" onclick="window.ordenesLoader.cargarPagina(${this.totalPages})" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
                Última
            </button>
            <span style="margin-left: 1rem; color: #666; font-size: 14px; font-weight: 500;">
                Página ${this.currentPage} de ${this.totalPages} | Total: ${this.totalRecords} registros
            </span>
        `;

        paginationContainer.innerHTML = html;
    }
}

