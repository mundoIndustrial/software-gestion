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
            paginationContainer.style.display = 'none';
            return;
        }

        paginationContainer.style.display = 'flex';
        pageNumbers.innerHTML = '';

        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(totalPages, this.currentPage + 2);

        if (startPage > 1) {
            pageNumbers.innerHTML += `<button class="page-number" data-page="1" style="padding: 6px 10px; background: #f1f5f9; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; color: #1e293b;">1</button>`;
            if (startPage > 2) {
                pageNumbers.innerHTML += `<span style="color: #94a3b8; padding: 0 4px;">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === this.currentPage;
            pageNumbers.innerHTML += `<button class="page-number ${isActive ? 'active' : ''}" data-page="${i}" style="padding: 6px 10px; background: ${isActive ? '#2450ef' : '#f1f5f9'}; color: ${isActive ? '#fff' : '#1e293b'}; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageNumbers.innerHTML += `<span style="color: #94a3b8; padding: 0 4px;">...</span>`;
            }
            pageNumbers.innerHTML += `<button class="page-number" data-page="${totalPages}" style="padding: 6px 10px; background: #f1f5f9; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; color: #1e293b;">${totalPages}</button>`;
        }

        document.querySelectorAll('.page-number').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentPage = parseInt(btn.dataset.page);
                this.renderPaginatedMovements();
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
        const tallasHtml = m.tallas.map(t => 
            `<span class="talla-badge">Talla ${t.talla}: ${t.cantidad_enviada}</span>`
        ).join('');

        const firmaBadgeClass = m.estadoFirma === 'FIRMADO' ? 'badge-firmado' : 'badge-pendiente';
        const firmaIcon = m.estadoFirma === 'FIRMADO' ? 'check_circle' : 'schedule';
        
        const tipoMovimiento = m.tipoMovimiento || 'SALIDA';
        const tipoMovimientoBadgeClass = tipoMovimiento === 'ENTRADA' ? 'badge-entrada' : 'badge-salida';
        const tipoMovimientoIcon = tipoMovimiento === 'ENTRADA' ? 'arrow_downward' : 'arrow_upward';
        
        let colorTipo = '#2450ef';
        let bgColorTipo = '#f0f4ff';
        
        if (m.tipo_recibo_mostrar === 'BODEGA') {
            colorTipo = '#059669';
            bgColorTipo = '#f0fdf4';
        }
        
        const novedadHtml = m.novedad 
            ? `<div class="card-section">
                <div class="card-label">Novedad</div>
                <p style="margin: 8px 0 0 0; font-size: 14px; color: #1e293b;">${m.novedad}</p>
              </div>`
            : '';

        const clienteHtml = m.tipo_recibo_mostrar !== 'BODEGA'
            ? `<div class="card-section">
                <div class="card-label">Cliente</div>
                <div class="card-value">${m.cliente}</div>
              </div>
              <div class="card-divider"></div>`
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
                    <div class="card-section">
                        <div class="card-label">Recibo / Tipo</div>
                        <div class="card-value">
                            ${m.recibo}-<span style="color: ${colorTipo}; font-weight: 700;">${m.tipo_recibo_mostrar}</span>
                        </div>
                    </div>
                    <div class="card-fecha">
                        ${m.fechaMovimiento}
                    </div>
                </div>

                <div class="card-divider"></div>

                ${clienteHtml}

                <div class="card-section">
                    <div class="card-label">Prenda / Tallas</div>
                    <div class="card-value">${m.prenda}</div>
                    <div class="tallas-enviadas" style="margin-top: 8px;">
                        ${tallasHtml}
                    </div>
                </div>

                <div class="card-divider"></div>

                <div class="card-section-row">
                    <div class="card-section">
                        <div class="card-label">Tipo de Movimiento</div>
                        <span class="badge ${tipoMovimientoBadgeClass}">
                            <span class="material-symbols-rounded badge-icon">${tipoMovimientoIcon}</span>
                            <span>${tipoMovimiento}</span>
                        </span>
                    </div>

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

        const filteredMovements = this.getFilteredMovements().filter(m => 
            String(m.recibo).toLowerCase().includes(query.toLowerCase()) ||
            String(m.cliente).toLowerCase().includes(query.toLowerCase()) ||
            String(m.prenda).toLowerCase().includes(query.toLowerCase())
        );

        this.renderMovements(filteredMovements);
        
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.style.display = filteredMovements.length > 15 ? 'flex' : 'none';
        }
    }
}

export { MovementsHandler };
