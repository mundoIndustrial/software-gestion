/**
 * Ordenes Loader - Carga órdenes desde API y renderiza tabla
 *
 * Optimizado para performance:
 * - Carga inicial: solo 15 registros (~200ms)
 * - Paginación bajo demanda
 * - Renderizado eficiente con DocumentFragment
 */

export class OrdenesLoader {
    constructor(options = {}) {
        console.log('[OrdenesLoader] Constructor llamado');
        this.apiBase = '/api/supervisor-pedidos/ordenes';
        this.perPage = options.perPage || 15;
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
        this.ordenes = [];

        this.tableBodySelector = '[data-ordenes-body]';
        this.paginationSelector = '[data-ordenes-pagination]';

        const tableBody = document.querySelector(this.tableBodySelector);
        console.log('[OrdenesLoader] Elemento [data-ordenes-body]:', tableBody ? 'encontrado' : 'NO ENCONTRADO');

        this.init();
    }

    async init() {
        console.log('%c[OrdenesLoader] Inicializando...', 'color: #3b82f6; font-weight: bold;');
        const startTime = performance.now();
        let success = false;

        try {
            // Cargar primera página
            success = await this.cargarPagina(1);

            const duration = performance.now() - startTime;
            console.log(`%c✅ Tabla lista en ${duration.toFixed(2)}ms`, 'color: #10b981; font-weight: bold;');

            if (!success) {
                console.warn('[OrdenesLoader] Primera carga completada con advertencias');
            }
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
            const params = new URLSearchParams({
                page: page,
                perPage: this.perPage
            });

            console.log(`%c📡 Cargando página ${page}...`, 'color: #6366f1;');

            const response = await fetch(`${this.apiBase}?${params}`);
            const data = await response.json();

            const apiDuration = performance.now() - apiStartTime;
            console.log(`%c🔌 API respondió en ${apiDuration.toFixed(2)}ms`, 'color: #f97316;');

            if (data.success && data.data && data.data.ordenes) {
                // data.data.ordenes es una colección paginada de Laravel
                const pagination = data.data.ordenes;
                this.ordenes = pagination.data || [];
                this.currentPage = pagination.current_page;
                this.totalPages = pagination.last_page;
                this.totalRecords = pagination.total;

                const renderStartTime = performance.now();
                this.renderTabla();
                this.renderPaginacion();
                const renderDuration = performance.now() - renderStartTime;

                console.log(`%c🎨 Renderizado: ${this.ordenes.length} filas en ${renderDuration.toFixed(2)}ms`,
                    renderDuration > 200 ? 'color: #f97316;' : 'color: #10b981;'
                );
                return true;
            } else {
                console.warn('[OrdenesLoader] Respuesta inválida del API:', data);
                return false;
            }
        } catch (error) {
            console.error('[OrdenesLoader] Error cargando página:', error);
            return false;
        }
    }

    renderTabla() {
        const tableBody = document.querySelector(this.tableBodySelector);
        if (!tableBody) return;

        // Usar DocumentFragment para mejor performance
        const fragment = document.createDocumentFragment();

        this.ordenes.forEach(orden => {
            const fila = this.crearFilaOrden(orden);
            fragment.appendChild(fila);
        });

        tableBody.innerHTML = '';
        tableBody.appendChild(fragment);
    }

    crearFilaOrden(orden) {
        const div = document.createElement('div');
        div.className = 'sp-orders-grid';
        div.style.cssText = 'padding: 1rem; border-bottom: 1px solid #e5e7eb; align-items: center; background: white;';
        div.dataset.ordenId = orden.id;

        const estadoColor = this.obtenerColorEstado(orden.estado);

        div.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center;">
                <input type="checkbox" class="orden-checkbox" data-orden-id="${orden.id}" style="width: 18px; height: 18px; cursor: pointer;">
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                <button class="btn-accion btn-ver" data-orden-id="${orden.id}" title="Ver Detalles" style="background: none; border: none; cursor: pointer; color: #3b82f6;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div>
                <span class="sp-date-cell">${this.formatearFecha(orden.fecha)}</span>
            </div>
            <div>
                <span style="font-weight: 600; color: #1e5ba8;">#${orden.numero}</span>
            </div>
            <div>
                <span>${orden.cliente || 'N/A'}</span>
            </div>
            <div>
                <span>${orden.asesora || 'N/A'}</span>
            </div>
            <div>
                <span style="background: ${estadoColor.bg}; color: ${estadoColor.text}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                    ${orden.estado}
                </span>
            </div>
            <div>
                ${orden.novedades_count > 0 ? `
                    <button class="btn-novedades" data-orden-id="${orden.id}" style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid #bfdbfe; cursor: pointer;">
                        ${orden.novedades_count} novedades
                    </button>
                ` : `
                    <span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">
                        Sin novedades
                    </span>
                `}
            </div>
            <div>
                <span>${orden.forma_de_pago || 'N/A'}</span>
            </div>
            <div>
                <span class="sp-date-cell">${orden.aprobado_por_cartera_en ? this.formatearFecha(orden.aprobado_por_cartera_en) : '-'}</span>
            </div>
            <div>
                <span class="sp-date-cell">${orden.aprobado_por_supervisor_en ? this.formatearFecha(orden.aprobado_por_supervisor_en) : '-'}</span>
            </div>
        `;

        return div;
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

        // Mostrar números de página (máximo 5)
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

    obtenerColorEstado(estado) {
        const colores = {
            'PENDIENTE_SUPERVISOR': { bg: '#fff3cd', text: '#856404' },
            'PENDIENTE_INSUMOS': { bg: '#d1ecf1', text: '#0c5460' },
            'En Ejecución': { bg: '#d4edda', text: '#155724' },
            'Entregado': { bg: '#d4edda', text: '#155724' },
            'Anulada': { bg: '#f8d7da', text: '#721c24' },
            'DEVUELTO_A_ASESORA': { bg: '#f8d7da', text: '#721c24' }
        };
        return colores[estado] || { bg: '#e2e3e5', text: '#383d41' };
    }

    formatearFecha(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        } catch {
            return dateString;
        }
    }
}

// No inicializar automáticamente - se inicializa desde index.js
