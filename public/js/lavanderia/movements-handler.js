/**
 * MOVEMENTS HANDLER - Lavandería
 * Maneja la carga, renderizado y filtrado de movimientos
 */

class MovementsHandler {
    constructor(apiSearchUrl) {
        this.apiSearchUrl = apiSearchUrl;
        this.allMovements = [];
        this.currentTab = 'salidas';
        this.currentPage = 1;
        this.itemsPerPage = 15;
    }

    /**
     * Carga los movimientos desde la API
     */
    loadMovements() {
        const apiUrl = this.apiSearchUrl.replace('search-recibos', 'movimientos');
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.allMovements = data.data;
                    this.currentPage = 1;
                    this.renderPaginatedMovements();
                } else {
                    console.error('Error al cargar movimientos:', data.message);
                    this.allMovements = [];
                    this.renderMovements([]);
                }
            })
            .catch(error => {
                console.error('Error en loadMovements:', error);
                this.allMovements = [];
                this.renderMovements([]);
            });
    }

    /**
     * Filtra movimientos por tab
     */
    filterMovementsByTab(tabType) {
        this.currentTab = tabType;

        // Actualizar botones de tab
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabType) {
                btn.classList.add('active');
            }
        });

        this.renderPaginatedMovements();
    }

    /**
     * Obtiene movimientos filtrados
     */
    getFilteredMovements() {
        let filteredMovements = this.allMovements;

        if (this.currentTab === 'salidas') {
            filteredMovements = this.allMovements.filter(m => m.tipoMovimiento === 'SALIDA');
        } else if (this.currentTab === 'entradas') {
            filteredMovements = this.allMovements.filter(m => m.tipoMovimiento === 'ENTRADA');
        }

        return filteredMovements;
    }

    /**
     * Obtiene el total de páginas
     */
    getTotalPages() {
        const filteredMovements = this.getFilteredMovements();
        return Math.ceil(filteredMovements.length / this.itemsPerPage);
    }

    /**
     * Obtiene movimientos paginados
     */
    getPaginatedMovements() {
        const filteredMovements = this.getFilteredMovements();
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        return filteredMovements.slice(startIndex, endIndex);
    }

    /**
     * Renderiza movimientos paginados
     */
    renderPaginatedMovements() {
        const paginatedMovements = this.getPaginatedMovements();
        this.renderMovements(paginatedMovements);
        this.renderPagination();
    }

    /**
     * Renderiza la paginación
     */
    renderPagination() {
        const totalPages = this.getTotalPages();
        const paginationContainer = document.getElementById('paginationContainer');
        const pageNumbers = document.getElementById('pageNumbers');

        if (totalPages <= 1) {
            paginationContainer.classList.remove('visible');
            return;
        }

        paginationContainer.classList.add('visible');
        pageNumbers.innerHTML = '';

        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(totalPages, this.currentPage + 2);

        if (startPage > 1) {
            pageNumbers.innerHTML += `<button class="page-number" data-page="1">1</button>`;
            if (startPage > 2) {
                pageNumbers.innerHTML += `<span style="color: #94a3b8; padding: 0 4px;">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === this.currentPage;
            pageNumbers.innerHTML += `<button class="page-number ${isActive ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageNumbers.innerHTML += `<span style="color: #94a3b8; padding: 0 4px;">...</span>`;
            }
            pageNumbers.innerHTML += `<button class="page-number" data-page="${totalPages}">${totalPages}</button>`;
        }

        document.querySelectorAll('.page-number').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentPage = parseInt(btn.dataset.page);
                this.renderPaginatedMovements();
                // Scroll al inicio de los movimientos
                const container = document.getElementById('movementsContainer');
                if (container) {
                    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    /**
     * Renderiza los movimientos
     */
    renderMovements(movements) {
        const container = document.getElementById('movementsContainer');
        if (!container) {
            console.warn('[MovementsHandler] movementsContainer no encontrado');
            return;
        }

        if (movements.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 60px 20px;">
                    <div class="empty-icon">📦</div>
                    <h3 class="empty-title">Sin movimientos</h3>
                    <p class="empty-text">No hay registros en esta categoría</p>
                </div>
            `;
            return;
        }

        container.innerHTML = movements.map(m => this.createMovementCard(m)).join('');

        document.querySelectorAll('.btn-firmar-salida').forEach(btn => {
            btn.addEventListener('click', (e) => {
                window.dispatchEvent(new CustomEvent('openFirmaModal', { 
                    detail: { movementId: e.target.closest('button').dataset.movementId }
                }));
            });
        });

        document.querySelectorAll('.btn-ver-firma').forEach(btn => {
            btn.addEventListener('click', (e) => {
                window.dispatchEvent(new CustomEvent('openVerFirmaModal', { 
                    detail: { firmaUrl: e.target.dataset.firmaUrl, movementId: e.target.dataset.movementId }
                }));
            });
        });
    }

    /**
     * Crea el HTML de una tarjeta de movimiento
     */
    createMovementCard(m) {
        const firmaBadgeClass = m.estadoFirma === 'FIRMADO' ? 'badge-firmado' : 'badge-pendiente';
        const firmaIcon = m.estadoFirma === 'FIRMADO' ? 'check_circle' : 'schedule';
        
        const tipoMovimiento = m.tipoMovimiento || 'SALIDA';
        const tipoMovimientoBadgeClass = tipoMovimiento === 'ENTRADA' ? 'badge-entrada' : 'badge-salida';
        const tipoMovimientoIcon = tipoMovimiento === 'ENTRADA' ? 'arrow_downward' : 'arrow_upward';

        const prendasManualesHtml = Array.isArray(m.prendasManuales) && m.prendasManuales.length > 0
            ? m.prendasManuales.map(prenda => {
                const generoLabel = prenda.genero || 'Sin género';
                const tallasPorGenero = Array.isArray(prenda.tallas) && prenda.tallas.length > 0
                    ? Object.values(prenda.tallas.reduce((acc, talla) => {
                        const genero = (talla.genero || generoLabel || 'Sin género').toString();
                        if (!acc[genero]) {
                            acc[genero] = {
                                genero,
                                tallas: []
                            };
                        }
                        acc[genero].tallas.push(talla);
                        return acc;
                    }, {}))
                    : [];

                return `
                    <div style="
                        background: #fffbeb;
                        border: 1px solid #fcd34d;
                        border-radius: 8px;
                        padding: 12px;
                        margin-bottom: 8px;
                    ">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span style="font-weight: 700; font-size: 14px; color: #1e293b;">
                                    Prenda Manual
                                </span>
                                <span style="font-size: 12px; color: #92400e; font-weight: 600;">
                                    ${generoLabel}
                                </span>
                            </div>
                            <span style="
                                background: #f59e0b;
                                color: white;
                                padding: 2px 8px;
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 600;
                            ">
                                MANUAL
                            </span>
                        </div>
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">
                            <strong>Descripción:</strong> ${prenda.descripcion}
                        </div>
                        ${tallasPorGenero.length > 0
                            ? tallasPorGenero.map(grupo => `
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #fcd34d;">
                                    <div style="font-size: 12px; font-weight: 700; color: #92400e; margin-bottom: 8px;">
                                        ${grupo.genero}
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                        ${grupo.tallas.map(t => `
                                            <span class="talla-badge" style="
                                                display: inline-block;
                                                background: #fef3c7;
                                                color: #92400e;
                                                border: 1px solid #f59e0b;
                                                padding: 4px 8px;
                                                border-radius: 4px;
                                                font-size: 12px;
                                                font-weight: 500;
                                            ">
                                                ${t.talla}: ${t.cantidad_enviada}
                                            </span>
                                        `).join('')}
                                    </div>
                                </div>
                            `).join('')
                            : '<span style="color: #94a3b8; font-size: 13px;">Sin tallas</span>'
                        }
                    </div>
                `;
            }).join('')
            : '';
        
        // Renderizar múltiples recibos con sus tallas agrupadas
        const recibosHtml = m.recibos.map(recibo => {
            let colorTipo = '#2450ef';
            let bgColorTipo = '#f0f4ff';
            
            if (recibo.tipo_recibo_mostrar === 'BODEGA') {
                colorTipo = '#059669';
                bgColorTipo = '#f0fdf4';
            }

            const clienteHtml = recibo.tipo_recibo_mostrar !== 'BODEGA'
                ? `<div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                    <strong>Cliente:</strong> ${recibo.cliente}
                  </div>`
                : '';

            // Usar las tallas específicas de este recibo
            const tallasPorGeneroHtml = recibo.tallasPorGenero && recibo.tallasPorGenero.length > 0 
                ? recibo.tallasPorGenero.map(grupo => {
                    return `
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid ${colorTipo}20;">
                            <div style="font-size: 12px; font-weight: 600; color: ${colorTipo}; margin-bottom: 8px;">
                                ${grupo.genero}
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                ${grupo.tallas.map(t => 
                                    `<span class="talla-badge" style="
                                        background: ${bgColorTipo};
                                        color: ${colorTipo};
                                        border: 1px solid ${colorTipo};
                                        padding: 4px 8px;
                                        border-radius: 4px;
                                        font-size: 12px;
                                        font-weight: 500;
                                    ">
                                        ${t.talla}: ${t.cantidad_enviada}
                                    </span>`
                                ).join('')}
                            </div>
                        </div>
                    `;
                }).join('')
                : '';

            return `
                <div style="
                    background: ${bgColorTipo};
                    border: 1px solid ${colorTipo};
                    border-radius: 8px;
                    padding: 12px;
                    margin-bottom: 8px;
                ">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-weight: 700; font-size: 14px; color: #1e293b;">
                            Recibo #${recibo.numero_recibo}
                        </span>
                        <span style="
                            background: ${colorTipo};
                            color: white;
                            padding: 2px 8px;
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 600;
                        ">
                            ${recibo.tipo_recibo_mostrar}
                        </span>
                    </div>
                    ${clienteHtml}
                    <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                        <strong>Prenda:</strong> ${recibo.prenda}
                    </div>
                    ${tallasPorGeneroHtml}
                </div>
            `;
        }).join('');
        
        const novedadHtml = m.novedad 
            ? `<div class="card-section">
                <div class="card-label">Novedad</div>
                <p style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">${m.novedad}</p>
              </div>`
            : '';

        const firmaButtonText = tipoMovimiento === 'ENTRADA' ? 'Firmar entrada' : 'Firmar salida';
        const firmaButtonHtml = m.estadoFirma === 'PENDIENTE FIRMA' 
            ? `<button class="btn-firmar-salida-card btn-firmar-salida" data-movement-id="${m.id}">
                <span class="material-symbols-rounded">edit</span>
                ${firmaButtonText}
              </button>`
            : '';

        return `
            <div class="movement-card">
                <div class="card-header-top">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; font-size: 14px; color: #1e293b;">
                            #${m.id}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                        <span class="badge ${tipoMovimientoBadgeClass}">
                            <span class="material-symbols-rounded badge-icon">${tipoMovimientoIcon}</span>
                            <span>${tipoMovimiento}</span>
                        </span>
                        <div class="card-fecha">
                            ${m.fechaMovimiento}
                        </div>
                    </div>
                </div>

                <div class="card-divider"></div>

                <div class="card-section">
                    <div class="card-label">Recibos (${m.recibos.length})</div>
                </div>

                <div class="card-section">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        ${recibosHtml}
                    </div>
                </div>

                ${prendasManualesHtml ? '<div class="card-divider"></div><div class="card-section"><div class="card-label">Prendas manuales (' + m.prendasManuales.length + ')</div><div style="display: flex; flex-direction: column; gap: 8px; margin-top: 8px;">' + prendasManualesHtml + '</div></div>' : ''}

                <div class="card-divider"></div>

                <div class="card-section">
                    <div class="card-label">Estado</div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge ${firmaBadgeClass}">
                            <span class="material-symbols-rounded badge-icon">${firmaIcon}</span>
                            <span>${m.estadoFirma}</span>
                        </span>
                        ${m.firmaMovimiento && m.firmaMovimiento !== 'pendiente' ? `<button class="btn-ver-firma" data-firma-url="${m.firmaMovimiento}" data-movement-id="${m.id}" style="padding: 4px 8px; font-size: 11px;">Ver Firma</button>` : ''}
                    </div>
                </div>

                ${novedadHtml ? '<div class="card-divider"></div>' + novedadHtml : ''}

                ${firmaButtonHtml ? '<div class="card-divider"></div>' + firmaButtonHtml : ''}
            </div>
        `;
    }

    /**
     * Busca movimientos
     */
    searchMovements(query) {
        if (query.length === 0) {
            this.currentPage = 1;
            this.renderPaginatedMovements();
            return;
        }

        const searchTerm = query.toLowerCase();
        const filteredMovements = this.getFilteredMovements().filter(m => {
            // Buscar por ID del movimiento
            const idMatch = m.id.toString().includes(searchTerm);

            // Buscar por nombre de prenda en recibos
            const prendasRecibosText = Array.isArray(m.recibos)
                ? m.recibos.map(recibo => recibo.prenda).filter(Boolean).join(' ')
                : '';

            const prendasRecibosMatch = prendasRecibosText.toLowerCase().includes(searchTerm);

            // Buscar por descripción de prendas manuales agregadas
            const prendasManualesText = Array.isArray(m.prendasManuales)
                ? m.prendasManuales.map(prenda => prenda.descripcion).filter(Boolean).join(' ')
                : '';

            const prendasManualesMatch = prendasManualesText.toLowerCase().includes(searchTerm);

            return idMatch || prendasRecibosMatch || prendasManualesMatch;
        });

        this.renderMovements(filteredMovements);
        
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.classList.toggle('visible', filteredMovements.length > 15);
        }
    }
}

export { MovementsHandler };

