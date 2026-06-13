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
        const tipoMovimiento = m.tipoMovimiento || 'SALIDA';
        const tipoTexto = tipoMovimiento === 'ENTRADA' ? 'Llegaron' : 'Salieron';
        const tipoMovimientoBadgeClass = tipoMovimiento === 'ENTRADA' ? 'badge-entrada' : 'badge-salida';
        const tipoMovimientoIcon = tipoMovimiento === 'ENTRADA' ? 'arrow_downward' : 'arrow_upward';
        
        // Construir descripción de movimiento igual que en historial
        let descripcionDetalles = [];

        // Procesar recibos
        if (Array.isArray(m.recibos) && m.recibos.length > 0) {
            m.recibos.forEach(recibo => {
                const tallasPorGenero = recibo.tallasPorGenero || [];
                const cantidadTotal = tallasPorGenero.reduce((sum, g) => 
                    sum + g.tallas.reduce((s, t) => s + (t.cantidad_enviada || 0), 0), 0
                );
                const unidad = cantidadTotal === 1 ? 'unidad' : 'unidades';
                
                let tallasTexto = '';
                if (tallasPorGenero.length > 0) {
                    const tallasList = [];
                    tallasPorGenero.forEach(grupo => {
                        grupo.tallas.forEach(t => {
                            tallasList.push(`${t.talla}${t.cantidad_enviada ? ` (${t.cantidad_enviada})` : ''}`);
                        });
                    });
                    tallasTexto = tallasList.join(', ');
                }

                const reciboNumeroBadge = `<strong>#${recibo.numero_recibo}</strong>`;
                const tipoReciboBadge = recibo.tipo_recibo_mostrar ? ` (${recibo.tipo_recibo_mostrar})` : '';

                descripcionDetalles.push({
                    tipo: 'recibo',
                    principal: `${tipoTexto} ${cantidadTotal} ${unidad} de ${recibo.prenda} ${reciboNumeroBadge}${tipoReciboBadge}`,
                    tallas: tallasTexto ? `• Tallas: ${tallasTexto}` : null,
                    color: recibo.tipo_recibo_mostrar === 'BODEGA' ? '#059669' : '#2450ef',
                    bgColor: recibo.tipo_recibo_mostrar === 'BODEGA' ? '#f0fdf4' : '#f0f4ff',
                    tipoRecibo: recibo.tipo_recibo_mostrar
                });
            });
        }

        // Procesar prendas manuales
        if (Array.isArray(m.prendasManuales) && m.prendasManuales.length > 0) {
            m.prendasManuales.forEach(prenda => {
                const isSoloQuantidad = prenda.soloQuantidad || false;
                
                if (isSoloQuantidad) {
                    // Prenda con solo cantidad
                    const cantidad = prenda.cantidad || 0;
                    const unidad = cantidad === 1 ? 'unidad' : 'unidades';
                    descripcionDetalles.push({
                        tipo: 'manual_cantidad',
                        principal: `${tipoTexto} ${cantidad} ${unidad} de ${prenda.descripcion} (MANUAL)`,
                        tallas: null,
                        color: '#f59e0b',
                        bgColor: '#fef3c7',
                        tipoRecibo: 'MANUAL'
                    });
                } else {
                    // Prenda con tallas
                    const tallasPorGenero = [];
                    if (Array.isArray(prenda.tallas)) {
                        // Agrupar por género
                        const generoMap = {};
                        prenda.tallas.forEach(t => {
                            const genero = t.genero || prenda.genero || 'Sin género';
                            if (!generoMap[genero]) {
                                generoMap[genero] = [];
                            }
                            generoMap[genero].push(t);
                        });
                        
                        Object.entries(generoMap).forEach(([genero, tallas]) => {
                            tallasPorGenero.push({ genero, tallas });
                        });
                    }

                    const cantidadTotal = (prenda.tallas || []).reduce((sum, t) => sum + (t.cantidad_enviada || 0), 0);
                    const unidad = cantidadTotal === 1 ? 'unidad' : 'unidades';
                    
                    let tallasTexto = '';
                    if (prenda.tallas && prenda.tallas.length > 0) {
                        tallasTexto = prenda.tallas.map(t => 
                            `${t.talla}${t.cantidad_enviada ? ` (${t.cantidad_enviada})` : ''}`
                        ).join(', ');
                    }

                    descripcionDetalles.push({
                        tipo: 'manual_tallas',
                        principal: `${tipoTexto} ${cantidadTotal} ${unidad} de ${prenda.descripcion} (MANUAL)`,
                        tallas: tallasTexto ? `• Tallas: ${tallasTexto}` : null,
                        color: '#f59e0b',
                        bgColor: '#fef3c7',
                        tipoRecibo: 'MANUAL'
                    });
                }
            });
        }

        // Construir HTML de descripción similar al historial
        let descripcionHtml = '<div style="line-height: 1.6; color: #64748b; font-size: 13px;">';
        descripcionDetalles.forEach((item, idx) => {
            if (idx > 0) {
                descripcionHtml += '<br>';
            }
            descripcionHtml += `<span style="color: #1e293b; font-weight: 500;">${item.principal}</span>`;
            if (item.tallas) {
                descripcionHtml += `<br><span style="color: ${item.color}; font-size: 12px;">${item.tallas}</span>`;
            }
        });
        descripcionHtml += '</div>';

        const firmaBadgeClass = m.estadoFirma === 'PENDIENTE FIRMA' ? 'badge-pendiente' : 'badge-firmado';
        const firmaIcon = m.estadoFirma === 'PENDIENTE FIRMA' ? 'schedule' : 'check_circle';

        const firmaButtonText = tipoMovimiento === 'ENTRADA' ? 'Firmar entrada' : 'Firmar salida';
        const firmaButtonHtml = m.estadoFirma === 'PENDIENTE FIRMA' 
            ? `<button class="btn-firmar-salida-card btn-firmar-salida" data-movement-id="${m.id}" style="
                margin-top: 12px;
                width: 100%;
                padding: 8px 12px;
                background: #2450ef;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            ">
                <span class="material-symbols-rounded" style="font-size: 18px;">edit</span>
                ${firmaButtonText}
            </button>`
            : '';

        const novedadHtml = m.novedad 
            ? `<div style="margin-top: 12px; padding: 12px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px;">
                <div style="font-weight: 600; color: #92400e; font-size: 12px; margin-bottom: 4px;">
                    <span class="material-symbols-rounded" style="font-size: 14px; vertical-align: middle;">note</span>
                    Novedad
                </div>
                <p style="margin: 0; color: #1e293b; font-size: 13px; line-height: 1.5;">${m.novedad}</p>
              </div>`
            : '';

        return `
            <div style="
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 12px;
                transition: all 0.2s;
            " class="movement-card">
                <!-- Header -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; font-size: 15px; color: #1e293b;">
                            Mov #${m.numeroMovimiento || m.id}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="
                            background: ${tipoMovimiento === 'ENTRADA' ? '#d1fae5' : '#fef3c7'};
                            color: ${tipoMovimiento === 'ENTRADA' ? '#10b981' : '#f59e0b'};
                            padding: 4px 10px;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: 600;
                            display: flex;
                            align-items: center;
                            gap: 4px;
                        ">
                            <span class="material-symbols-rounded" style="font-size: 14px;">${tipoMovimientoIcon}</span>
                            ${tipoMovimiento}
                        </span>
                        <span style="font-size: 12px; color: #94a3b8; white-space: nowrap;">
                            ${m.fechaMovimiento}
                        </span>
                    </div>
                </div>

                <!-- Descripción detallada -->
                <div style="margin-bottom: 12px; padding: 12px; background: #f8fafc; border-radius: 6px;">
                    ${descripcionHtml}
                </div>

                <!-- Estado -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="
                            background: ${m.estadoFirma === 'PENDIENTE FIRMA' ? '#fef3c7' : '#d1fae5'};
                            color: ${m.estadoFirma === 'PENDIENTE FIRMA' ? '#92400e' : '#059669'};
                            padding: 4px 10px;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: 600;
                            display: flex;
                            align-items: center;
                            gap: 4px;
                        ">
                            <span class="material-symbols-rounded" style="font-size: 14px;">${firmaIcon}</span>
                            ${m.estadoFirma}
                        </span>
                    </div>
                    ${m.firmaMovimiento && m.firmaMovimiento !== 'pendiente' 
                        ? `<button class="btn-ver-firma" data-firma-url="${m.firmaMovimiento}" data-movement-id="${m.id}" style="
                            padding: 4px 10px;
                            background: #2450ef;
                            color: white;
                            border: none;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: 600;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            gap: 4px;
                        ">
                            <span class="material-symbols-rounded" style="font-size: 14px;">image</span>
                            Ver Firma
                        </button>` 
                        : ''
                    }
                </div>

                <!-- Novedades -->
                ${novedadHtml}

                <!-- Botón firmar -->
                ${firmaButtonHtml}
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
            // Buscar por ID o número de movimiento
            const idMatch = m.id.toString().includes(searchTerm) || 
                            (m.numeroMovimiento && m.numeroMovimiento.toString().includes(searchTerm));

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

