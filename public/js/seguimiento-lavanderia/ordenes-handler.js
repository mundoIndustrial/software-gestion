/**
 * ORDENES HANDLER - Seguimiento Lavandería
 * Maneja la carga, búsqueda y renderizado de órdenes
 */

import { escapeHtml, debounce } from './utilities.js';

class OrdenesHandler {
    constructor(apiOrdenes) {
        this.apiOrdenes = apiOrdenes;
        this.currentPage = 1;
        this.perPage = 25;
        this.currentSearch = '';
        this.ordenesLoaded = false;

        this.elements = {
            ordenesTableBody: document.getElementById('ordenesTableBody'),
            ordenesPagination: document.getElementById('ordenesPagination'),
            ordenesLoadingState: document.getElementById('ordenesLoadingState'),
            ordenesEmptyState: document.getElementById('ordenesEmptyState'),
            ordenesSearchInput: document.getElementById('ordenesSearchInput'),
            ordenesSearchClear: document.getElementById('ordenesSearchClear'),
        };
    }

    /**
     * Carga las órdenes desde la API
     */
    async loadOrdenes(page = 1) {
        if (!this.apiOrdenes) {
            return;
        }

        this.currentPage = page;
        this.toggleLoading(true);

        try {
            const url = new URL(this.apiOrdenes, window.location.origin);
            url.searchParams.set('page', String(page));
            url.searchParams.set('per_page', String(this.perPage));
            
            if (this.currentSearch) {
                url.searchParams.set('search', this.currentSearch);
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'No fue posible cargar las órdenes');
            }

            this.ordenesLoaded = true;
            this.renderOrdenes(data.data || []);
            this.renderPagination(data.pagination || {});
        } catch (error) {
            console.error('[OrdenesHandler] Error cargando órdenes:', error);
            this.renderError(error.message || 'Error al cargar órdenes');
        } finally {
            this.toggleLoading(false);
        }
    }

    /**
     * Maneja el input de búsqueda
     */
    handleSearchInput(searchTerm) {
        const { ordenesSearchClear } = this.elements;

        if (ordenesSearchClear) {
            ordenesSearchClear.style.display = searchTerm.trim().length > 0 ? 'flex' : 'none';
        }

        this.currentSearch = searchTerm.trim();
        this.currentPage = 1;
        this.loadOrdenes(1);
    }

    /**
     * Limpia la búsqueda
     */
    clearSearch() {
        const { ordenesSearchInput, ordenesSearchClear } = this.elements;

        if (ordenesSearchInput) {
            ordenesSearchInput.value = '';
            ordenesSearchInput.focus();
        }

        if (ordenesSearchClear) {
            ordenesSearchClear.style.display = 'none';
        }

        this.currentSearch = '';
        this.currentPage = 1;
        this.loadOrdenes(1);
    }

    /**
     * Renderiza las órdenes en la tabla
     */
    renderOrdenes(ordenes) {
        const { ordenesTableBody, ordenesEmptyState } = this.elements;

        if (!ordenesTableBody) return;

        if (!Array.isArray(ordenes) || ordenes.length === 0) {
            ordenesTableBody.innerHTML = '';
            if (ordenesEmptyState) {
                const message = this.currentSearch 
                    ? `No se encontraron órdenes que coincidan con "${escapeHtml(this.currentSearch)}"` 
                    : 'No hay órdenes para mostrar';
                ordenesEmptyState.textContent = message;
                ordenesEmptyState.style.display = 'block';
            }
            return;
        }

        if (ordenesEmptyState) {
            ordenesEmptyState.style.display = 'none';
        }

        ordenesTableBody.innerHTML = ordenes.map(item => `
            <tr>
                <td style="font-weight: 700; color: #1e293b;">
                    ${escapeHtml(item.numero_recibo_tipo || `#${item.numero_recibo}-${item.tipo_recibo}`)}
                </td>
                <td>${escapeHtml(item.cliente || 'Sin cliente')}</td>
                <td>${escapeHtml(item.prenda || 'Sin prenda')}</td>
                <td>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn-ver-detalles" onclick="window.abrirDetallesModal(${item.recibo_id}, '${escapeHtml(item.numero_recibo_tipo)}')">
                            <span class="material-symbols-rounded" style="font-size: 16px;">info</span>
                            Ver Detalles
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Renderiza la paginación
     */
    renderPagination(pagination) {
        const container = this.elements.ordenesPagination;
        if (!container) return;

        const currentPage = Number(pagination.current_page || 1);
        const lastPage = Number(pagination.last_page || 1);
        const total = Number(pagination.total || 0);
        const from = Number(pagination.from || 0);
        const to = Number(pagination.to || 0);

        if (lastPage <= 1) {
            container.innerHTML = '';
            return;
        }

        const buttons = [];
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(lastPage, currentPage + 2);

        buttons.push(`
            <button class="btn-pagination" data-page="${Math.max(1, currentPage - 1)}" ${currentPage === 1 ? 'disabled' : ''}>
                <span class="material-symbols-rounded" style="font-size: 18px;">chevron_left</span>
            </button>
        `);

        if (startPage > 1) {
            buttons.push(this.pageButton(1, currentPage));
            if (startPage > 2) {
                buttons.push('<span style="color:#94a3b8; padding:0 4px;">...</span>');
            }
        }

        for (let page = startPage; page <= endPage; page++) {
            buttons.push(this.pageButton(page, currentPage));
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                buttons.push('<span style="color:#94a3b8; padding:0 4px;">...</span>');
            }
            buttons.push(this.pageButton(lastPage, currentPage));
        }

        buttons.push(`
            <button class="btn-pagination" data-page="${Math.min(lastPage, currentPage + 1)}" ${currentPage === lastPage ? 'disabled' : ''}>
                <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
            </button>
        `);

        container.innerHTML = `
            <div style="display:flex; flex-direction:column; gap:12px; align-items:center; width:100%;">
                <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:center; align-items:center;">
                    ${buttons.join('')}
                </div>
                <div style="font-size:12px; color:#64748b; text-align:center;">
                    Mostrando ${from || 0}-${to || 0} de ${total} órdenes
                </div>
            </div>
        `;

        container.querySelectorAll('.btn-pagination[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = Number(btn.dataset.page || 1);
                if (page >= 1 && page <= lastPage && page !== currentPage) {
                    this.loadOrdenes(page);
                }
            });
        });
    }

    /**
     * Crea un botón de página
     */
    pageButton(page, currentPage) {
        const active = page === currentPage;
        return `
            <button class="btn-pagination ${active ? 'active' : ''}" data-page="${page}" ${active ? 'disabled' : ''} style="
                min-width: 40px;
                padding: 8px 12px;
                background: ${active ? '#2450ef' : '#e2e8f0'};
                color: ${active ? '#fff' : '#1e293b'};
                border: none;
                border-radius: 6px;
                cursor: ${active ? 'default' : 'pointer'};
                font-weight: 600;
            ">
                ${page}
            </button>
        `;
    }

    /**
     * Muestra/oculta el estado de carga
     */
    toggleLoading(isLoading) {
        const { ordenesLoadingState, ordenesTableBody, ordenesEmptyState, ordenesPagination } = this.elements;

        if (ordenesLoadingState) {
            ordenesLoadingState.style.display = isLoading ? 'block' : 'none';
        }

        if (ordenesTableBody) {
            ordenesTableBody.style.opacity = isLoading ? '0.5' : '1';
        }

        if (ordenesEmptyState && !isLoading) {
            ordenesEmptyState.style.display = 'none';
        }

        if (ordenesPagination && isLoading) {
            ordenesPagination.innerHTML = '';
        }
    }

    /**
     * Renderiza un error
     */
    renderError(message) {
        const { ordenesTableBody, ordenesEmptyState } = this.elements;

        if (ordenesTableBody) {
            ordenesTableBody.innerHTML = `
                <tr>
                    <td colspan="4" style="padding: 20px; text-align: center; color: #ef4444;">
                        ${escapeHtml(message)}
                    </td>
                </tr>
            `;
        }

        if (ordenesEmptyState) {
            ordenesEmptyState.style.display = 'none';
        }
    }
}

export { OrdenesHandler };
