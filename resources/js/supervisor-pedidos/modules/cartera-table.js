/**
 * Cartera Table Module - Gestor de tabla de pedidos
 *
 * Responsabilidades:
 * - Cargar datos de pedidos desde API
 * - Renderizar tabla con paginación
 * - Sorteo y filtrado
 * - Acciones en pedidos (cambiar estado, etc)
 *
 * Migrado de: public/js/cartera-pedidos/app.js
 */

export class CarteraTable {
    constructor(options = {}) {
        this.apiBase = options.apiBase || '/api/cartera/pedidos';
        this.perPage = options.perPage || 15;

        // Estado de tabla
        this.data = [];
        this.currentPage = 1;
        this.totalPages = 1;
        this.currentSort = 'fecha';
        this.currentSortOrder = 'desc';
        this.currentSearch = '';

        // Filtros
        this.filtroCliente = '';
        this.filtroFechaDesde = '';
        this.filtroFechaHasta = '';

        // WebSocket state
        this.wsNotificationsBootstrapped = false;
        this.wsDedupe = new Map();

        // Permisos
        this.tienePermisosAccion = window.userRole !== 'supervisor_gerencia';

        this.init();
    }

    async init() {
        try {
            // Cargar opciones de filtro
            await this.loadFilterOptions();

            // Cargar datos iniciales
            await this.loadPedidos();

            // Agregar listeners
            this.attachEventListeners();

            // Escuchar eventos de filtros
            document.addEventListener('filters:applied', (e) => {
                this.applyFilters(e.detail);
            });

            document.addEventListener('filters:cleared', () => {
                this.resetFilters();
            });

            console.log('[CarteraTable] ✅ Initialized');
        } catch (error) {
            console.error('[CarteraTable] Init error:', error);
        }
    }

    /**
     * Cargar opciones de filtro desde API
     */
    async loadFilterOptions() {
        try {
            const response = await fetch('/api/cartera/opciones-filtro');
            const data = await response.json();

            if (data.success) {
                // Cargar opciones en selects si existen
                if (data.clientes) {
                    const selectCliente = document.getElementById('filtroClienteSelect');
                    if (selectCliente) {
                        selectCliente.innerHTML = '<option value="">-- Todos --</option>';
                        data.clientes.forEach(cliente => {
                            const option = document.createElement('option');
                            option.value = cliente;
                            option.textContent = cliente;
                            selectCliente.appendChild(option);
                        });
                    }
                }
            }
        } catch (error) {
            console.error('[CarteraTable] Error loading filter options:', error);
        }
    }

    /**
     * Cargar pedidos desde API
     */
    async loadPedidos() {
        try {
            this.showLoading('Cargando pedidos...');

            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                sort: this.currentSort,
                order: this.currentSortOrder,
                search: this.currentSearch,
                filtro_cliente: this.filtroCliente,
                filtro_fecha_desde: this.filtroFechaDesde,
                filtro_fecha_hasta: this.filtroFechaHasta,
            });

            const response = await fetch(`${this.apiBase}?${params}`);
            const result = await response.json();

            if (result.success) {
                this.data = result.data || [];
                this.totalPages = result.total_pages || 1;
                this.renderTable();
                this.renderPagination();
            }
        } catch (error) {
            console.error('[CarteraTable] Error loading pedidos:', error);
            this.mostrarNotificacion('Error cargando pedidos', 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Renderizar tabla
     */
    renderTable() {
        const tableBody = document.getElementById('tableBody');
        if (!tableBody) return;

        if (this.data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 2rem;">Sin resultados</td></tr>';
            return;
        }

        tableBody.innerHTML = this.data.map((pedido, index) => `
            <tr class="table-row-cartera" data-pedido-id="${pedido.id}">
                <td>${pedido.numero_pedido || ''}</td>
                <td>${pedido.cliente || ''}</td>
                <td>${this.formatDate(pedido.fecha_creacion)}</td>
                <td>${pedido.estado || 'Pendiente'}</td>
                <td>${pedido.monto || '$0'}</td>
                <td>
                    <button class="btn-action" onclick="window.carteraTable.verDetalles(${pedido.id})">Ver</button>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Renderizar paginación
     */
    renderPagination() {
        const pagination = document.getElementById('pagination');
        if (!pagination) return;

        let html = '';

        // Botón anterior
        if (this.currentPage > 1) {
            html += `<button onclick="window.carteraTable.goToPage(${this.currentPage - 1})">← Anterior</button>`;
        }

        // Números de página
        for (let i = 1; i <= Math.min(this.totalPages, 5); i++) {
            if (i === this.currentPage) {
                html += `<button class="active">${i}</button>`;
            } else {
                html += `<button onclick="window.carteraTable.goToPage(${i})">${i}</button>`;
            }
        }

        // Botón siguiente
        if (this.currentPage < this.totalPages) {
            html += `<button onclick="window.carteraTable.goToPage(${this.currentPage + 1})">Siguiente →</button>`;
        }

        pagination.innerHTML = html;
    }

    /**
     * Ir a página específica
     */
    goToPage(page) {
        this.currentPage = page;
        this.loadPedidos();
    }

    /**
     * Aplicar filtros
     */
    applyFilters(filters = {}) {
        if (filters.fecha) {
            this.filtroFechaDesde = filters.fecha;
        }
        if (filters.cliente) {
            this.filtroCliente = filters.cliente;
        }
        if (filters.numero) {
            this.currentSearch = filters.numero;
        }

        this.currentPage = 1;
        this.loadPedidos();
    }

    /**
     * Resetear filtros
     */
    resetFilters() {
        this.filtroCliente = '';
        this.filtroFechaDesde = '';
        this.filtroFechaHasta = '';
        this.currentSearch = '';
        this.currentPage = 1;
        this.loadPedidos();
    }

    /**
     * Ver detalles de pedido
     */
    verDetalles(pedidoId) {
        console.log('[CarteraTable] Ver detalles pedido:', pedidoId);
        // Implementar apertura de modal con detalles
    }

    /**
     * Mostrar notificación
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        if (window.carteraFilters?.mostrarNotificacion) {
            window.carteraFilters.mostrarNotificacion(mensaje, tipo);
        } else {
            console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
        }
    }

    /**
     * Mostrar spinner de carga
     */
    showLoading(mensaje = 'Cargando...') {
        let spinner = document.getElementById('loadingSpinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loadingSpinner';
            spinner.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                    <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center;">
                        <div style="border: 4px solid #f3f4f6; border-top: 4px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                        <p style="margin: 0; color: #6b7280;">${mensaje}</p>
                    </div>
                    <style>
                        @keyframes spin {
                            from { transform: rotate(0deg); }
                            to { transform: rotate(360deg); }
                        }
                    </style>
                </div>
            `;
            document.body.appendChild(spinner);
        }
    }

    /**
     * Ocultar spinner
     */
    hideLoading() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.remove();
        }
    }

    /**
     * Formatear fecha
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
        } catch {
            return dateString;
        }
    }

    /**
     * Adjuntar event listeners
     */
    attachEventListeners() {
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentSearch = e.target.value;
                    this.currentPage = 1;
                    this.loadPedidos();
                }, 300);
            });
        }

        // Sort headers
        document.querySelectorAll('[data-sort]').forEach(header => {
            header.addEventListener('click', (e) => {
                if (e.target.closest('.btn-filter-column')) return;

                const sortBy = header.dataset.sort;
                if (this.currentSort === sortBy) {
                    this.currentSortOrder = this.currentSortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    this.currentSort = sortBy;
                    this.currentSortOrder = 'asc';
                }

                this.currentPage = 1;
                this.loadPedidos();
            });
        });
    }
}

/**
 * Inicializar tabla cuando DOM esté listo
 */
export async function initializeCarteraTable() {
    return new Promise((resolve) => {
        const checkDOM = () => {
            const tableBody = document.getElementById('tableBody');
            if (tableBody) {
                const table = new CarteraTable();
                window.carteraTable = table;
                console.log('[initializeCarteraTable] ✅ Table ready');
                resolve(table);
            } else {
                setTimeout(checkDOM, 100);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkDOM);
        } else {
            checkDOM();
        }
    });
}
