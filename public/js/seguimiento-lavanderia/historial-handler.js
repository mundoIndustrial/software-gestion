/**
 * HISTORIAL HANDLER - Seguimiento Lavandería
 * Maneja la carga, renderizado y paginación del historial de movimientos
 */

import { escapeHtml, debounce } from './utilities.js';

class HistorialHandler {
    constructor(apiHistorial) {
        this.apiHistorial = apiHistorial;
        this.currentPage = 1;
        this.perPage = 25;
        this.currentSearch = '';
        this.currentTab = 'todos';
        this.historialLoaded = false;

        this.elements = {
            historialTableBody: document.getElementById('historialTableBody'),
            historialPagination: document.getElementById('historialPagination'),
            historialLoadingState: document.getElementById('historialLoadingState'),
            historialEmptyState: document.getElementById('historialEmptyState'),
            historialSearchInput: document.getElementById('historialSearchInput'),
            historialSearchClear: document.getElementById('historialSearchClear'),
        };

        this.setupTabListeners();
    }

    /**
     * Configura los event listeners de los tabs
     */
    setupTabListeners() {
        const tabButtons = document.querySelectorAll('.historial-tab');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabName = btn.dataset.tab;
                this.setTab(tabName);
            });
        });
    }

    /**
     * Cambia el tab activo y recarga datos
     */
    setTab(tabName) {
        if (!['todos', 'entrada', 'salida'].includes(tabName)) return;

        this.currentTab = tabName;
        this.currentPage = 1;
        this.currentSearch = '';

        // Actualizar interfaz de tabs
        const tabButtons = document.querySelectorAll('.historial-tab');
        tabButtons.forEach(btn => {
            const isActive = btn.dataset.tab === tabName;
            btn.classList.toggle('active', isActive);
            btn.style.borderBottomColor = isActive ? '#2450ef' : 'transparent';
            btn.style.color = isActive ? '#2450ef' : '#64748b';
        });

        // Limpiar búsqueda
        const { historialSearchInput, historialSearchClear } = this.elements;
        if (historialSearchInput) {
            historialSearchInput.value = '';
        }
        if (historialSearchClear) {
            historialSearchClear.style.display = 'none';
        }

        // Cargar movimientos del nuevo tab
        this.loadMovimientos(1);
    }

    /**
     * Carga los movimientos desde la API
     */
    async loadMovimientos(page = 1) {
        if (!this.apiHistorial) {
            return;
        }

        this.currentPage = page;
        this.toggleLoading(true);

        try {
            const url = new URL(this.apiHistorial, window.location.origin);
            url.searchParams.set('page', String(page));
            url.searchParams.set('per_page', String(this.perPage));
            
            if (this.currentSearch) {
                url.searchParams.set('search', this.currentSearch);
            }

            // Agregar filtro de tipo si no es 'todos'
            if (this.currentTab !== 'todos') {
                const tipoMap = {
                    'entrada': 'ENTRADA',
                    'salida': 'SALIDA'
                };
                if (tipoMap[this.currentTab]) {
                    url.searchParams.set('tipo', tipoMap[this.currentTab]);
                }
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'No fue posible cargar los movimientos');
            }

            this.historialLoaded = true;
            this.renderMovimientos(data.data || []);
            this.renderPagination(data.pagination || {});
        } catch (error) {
            console.error('[HistorialHandler] Error cargando movimientos:', error);
            this.renderError(error.message || 'Error al cargar movimientos');
        } finally {
            this.toggleLoading(false);
        }
    }

    /**
     * Maneja el input de búsqueda
     */
    handleSearchInput(searchTerm) {
        const { historialSearchClear } = this.elements;

        if (historialSearchClear) {
            historialSearchClear.style.display = searchTerm.trim().length > 0 ? 'flex' : 'none';
        }

        this.currentSearch = searchTerm.trim();
        this.currentPage = 1;
        this.loadMovimientos(1);
    }

    /**
     * Limpia la búsqueda
     */
    clearSearch() {
        const { historialSearchInput, historialSearchClear } = this.elements;

        if (historialSearchInput) {
            historialSearchInput.value = '';
            historialSearchInput.focus();
        }

        if (historialSearchClear) {
            historialSearchClear.style.display = 'none';
        }

        this.currentSearch = '';
        this.currentPage = 1;
        this.loadMovimientos(1);
    }

    /**
     * Renderiza los movimientos en la tabla
     */
    renderMovimientos(movimientos) {
        const { historialTableBody, historialEmptyState } = this.elements;

        if (!historialTableBody) return;

        if (!Array.isArray(movimientos) || movimientos.length === 0) {
            historialTableBody.innerHTML = '';
            if (historialEmptyState) {
                const message = this.currentSearch 
                    ? `No se encontraron movimientos que coincidan con "${escapeHtml(this.currentSearch)}"` 
                    : 'No hay movimientos para mostrar';
                historialEmptyState.textContent = message;
                historialEmptyState.style.display = 'block';
            }
            return;
        }

        if (historialEmptyState) {
            historialEmptyState.style.display = 'none';
        }

        historialTableBody.innerHTML = movimientos.map(mov => {
            // DEBUG: Log para ver qué datos recibimos
            console.log('[HistorialHandler] Movimiento:', mov);
            
            // Determinar color según tipo de movimiento
            let colorTipo = '#f59e0b'; // Naranja para SALIDA
            let bgColorTipo = '#fef3c7';
            let tipoTexto = 'Salieron';
            
            if (mov.tipo_movimiento === 'ENTRADA') {
                colorTipo = '#10b981'; // Verde para ENTRADA
                bgColorTipo = '#d1fae5';
                tipoTexto = 'Llegaron';
            }

            // Agrupar tallas por recibo (que corresponde a una prenda)
            const tallasPorRecibo = {};
            if (Array.isArray(mov.tallas)) {
                mov.tallas.forEach(talla => {
                    const reciboId = talla.recibo_id || 'sin_recibo';
                    
                    if (!tallasPorRecibo[reciboId]) {
                        // Buscar información del recibo o prenda agregada
                        let prendaNombre = 'Prenda sin nombre';
                        
                        // Si es una prenda agregada (manual)
                        if (talla.prenda_agregada_id && Array.isArray(mov.prendas)) {
                            const prendaAgregada = mov.prendas.find(p => p.id === talla.prenda_agregada_id && p.tipo === 'agregada');
                            if (prendaAgregada) {
                                prendaNombre = prendaAgregada.nombre || 'Prenda sin nombre';
                            }
                        } else if (Array.isArray(mov.recibos)) {
                            // Si es un recibo normal
                            const recibo = mov.recibos.find(r => r.id === reciboId);
                            console.log('[HistorialHandler] Buscando recibo:', reciboId, 'Encontrado:', recibo);
                            if (recibo) {
                                prendaNombre = recibo.prenda || 'Prenda sin nombre';
                            }
                        }
                        
                        tallasPorRecibo[reciboId] = {
                            nombre: prendaNombre,
                            tallas: []
                        };
                    }
                    tallasPorRecibo[reciboId].tallas.push(talla);
                });
            }

            // Construir descripción con viñetas por prenda
            let descripcion = '';
            const reciboKeys = Object.keys(tallasPorRecibo);
            
            reciboKeys.forEach((reciboId, index) => {
                const reciboData = tallasPorRecibo[reciboId];
                const prendaNombre = reciboData.nombre;
                const tallas = reciboData.tallas;
                const cantidadPrenda = tallas.reduce((sum, t) => sum + (t.cantidad_enviada || 0), 0);
                const unidad = cantidadPrenda === 1 ? 'unidad' : 'unidades';
                const tallasTexto = tallas.map(t => `${t.talla}${t.cantidad_enviada ? ` (${t.cantidad_enviada})` : ''}`).join(', ');
                
                // Obtener número y tipo de recibo de la primera talla del grupo
                let numeroRecibo = '';
                let tipoRecibo = '';
                if (tallas.length > 0) {
                    const primeraTalla = tallas[0];
                    numeroRecibo = primeraTalla.recibo_numero ? `<strong>#${primeraTalla.recibo_numero}</strong>` : '';
                    // Solo mostrar tipo si existe y no es null
                    tipoRecibo = primeraTalla.recibo_tipo ? ` (${primeraTalla.recibo_tipo})` : '';
                }
                
                if (index === 0) {
                    descripcion += `${tipoTexto} ${cantidadPrenda} ${unidad} de ${prendaNombre} ${numeroRecibo}${tipoRecibo}\n`;
                } else {
                    descripcion += `\n${tipoTexto} ${cantidadPrenda} ${unidad} de ${prendaNombre} ${numeroRecibo}${tipoRecibo}\n`;
                }
                descripcion += `• Tallas: ${tallasTexto}`;
            });

            // Botón de novedades
            const tieneNovedades = mov.novedad && mov.novedad.trim().length > 0;
            const btnNovedades = tieneNovedades
                ? `<button class="btn-ver-novedades" onclick="window.abrirNovedadesModal(${mov.id}, '${escapeHtml(mov.novedad)}', '${escapeHtml(mov.fecha_movimiento)}', '${mov.numero_movimiento || mov.id}')">
                    <span class="material-symbols-rounded" style="font-size: 16px;">note</span>
                    Ver
                  </button>`
                : `<span style="color: #94a3b8; font-size: 12px;">Sin novedades</span>`;

            // Estado de firma
            const tieneFirma = mov.firma_movimiento && mov.firma_movimiento !== 'pendiente';
            const btnFirma = tieneFirma
                ? `<button class="btn-ver-firma" onclick="window.abrirFirmaModal(${mov.id}, '${mov.fecha_firma || mov.fecha_movimiento}', '${mov.numero_movimiento || mov.id}')">
                    <span class="material-symbols-rounded" style="font-size: 16px;">image</span>
                    Ver Firma
                  </button>`
                : `<span style="color: #94a3b8; font-size: 12px;">Sin firma</span>`;

            return `
                <tr>
                    <td style="font-weight: 700; color: #1e293b;">
                        #${mov.numero_movimiento || mov.id}
                    </td>
                    <td>
                        <span style="background: ${bgColorTipo}; color: ${colorTipo}; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                            ${mov.tipo_movimiento}
                        </span>
                    </td>
                    <td style="color: #64748b; font-size: 13px; max-width: 400px; word-wrap: break-word; white-space: normal; line-height: 1.5;">
                        ${descripcion.replace(/\n/g, '<br>')}
                    </td>
                    <td>
                        ${btnFirma}
                    </td>
                    <td>
                        ${btnNovedades}
                    </td>
                    <td style="color: #64748b; font-size: 13px; white-space: nowrap;">
                        ${escapeHtml(mov.fecha_movimiento)}
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * Renderiza la paginación
     */
    renderPagination(pagination) {
        const container = this.elements.historialPagination;
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
                    Mostrando ${from || 0}-${to || 0} de ${total} movimientos
                </div>
            </div>
        `;

        container.querySelectorAll('.btn-pagination[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = Number(btn.dataset.page || 1);
                if (page >= 1 && page <= lastPage && page !== currentPage) {
                    this.loadMovimientos(page);
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
        const { historialLoadingState, historialTableBody, historialEmptyState, historialPagination } = this.elements;

        if (historialLoadingState) {
            historialLoadingState.style.display = isLoading ? 'block' : 'none';
        }

        if (historialTableBody) {
            historialTableBody.style.opacity = isLoading ? '0.5' : '1';
        }

        if (historialEmptyState && !isLoading) {
            historialEmptyState.style.display = 'none';
        }

        if (historialPagination && isLoading) {
            historialPagination.innerHTML = '';
        }
    }

    /**
     * Renderiza un error
     */
    renderError(message) {
        const { historialTableBody, historialEmptyState } = this.elements;

        if (historialTableBody) {
            historialTableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #ef4444;">
                        ${escapeHtml(message)}
                    </td>
                </tr>
            `;
        }

        if (historialEmptyState) {
            historialEmptyState.style.display = 'none';
        }
    }
}

export { HistorialHandler };
