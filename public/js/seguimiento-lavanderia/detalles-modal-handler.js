/**
 * DETALLES MODAL HANDLER - Seguimiento Lavandería
 * Maneja la apertura, carga y renderizado del modal de detalles
 */

import { escapeHtml } from './utilities.js';

class DetallesModalHandler {
    constructor() {
        this.modal = document.getElementById('detallesModal');
        this.modalTitle = document.getElementById('detallesModalTitle');
        this.modalBody = document.getElementById('detallesModalBody');
        this.currentReciboId = null;
        this.currentTab = 'movimientos';
    }

    /**
     * Abre el modal y carga los movimientos del recibo
     */
    async abrirModal(reciboId, numeroRecibo) {
        if (!this.modal || !this.modalBody) return;

        this.currentReciboId = reciboId;
        this.currentTab = 'movimientos';

        // Mostrar modal con estado de carga
        this.modal.classList.add('show');
        this.modalTitle.textContent = `Detalles del Recibo ${numeroRecibo}`;
        this.modalBody.innerHTML = `
            <div style="text-align: center; padding: 32px;">
                <div class="loading-spinner"></div>
                <p style="margin-top: 16px; color: #64748b;">Cargando detalles...</p>
            </div>
        `;

        try {
            // Cargar movimientos
            const movimientosRes = await fetch(`/seguimiento-lavanderia/api/movimientos-recibo/${reciboId}`, {
                headers: { 'Accept': 'application/json' }
            });

            const movimientosData = await movimientosRes.json();

            if (!movimientosData.success) {
                throw new Error(movimientosData.message || 'Error al cargar los movimientos');
            }

            this.renderModal(movimientosData.data);
        } catch (error) {
            console.error('[DetallesModalHandler] Error cargando detalles:', error);
            this.modalBody.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-rounded empty-state-icon">error</span>
                    <p>${escapeHtml(error.message)}</p>
                </div>
            `;
        }
    }

    /**
     * Renderiza el modal con los movimientos
     */
    renderModal(movimientosAgrupados) {
        if (!this.modalBody) return;

        // Renderizar contenido de movimientos
        const movimientosHtml = this.renderMovimientos(movimientosAgrupados);

        this.modalBody.innerHTML = movimientosHtml;
    }

    /**
     * Renderiza la sección de movimientos
     */
    renderMovimientos(movimientosAgrupados) {
        const { entradas = [], salidas = [] } = movimientosAgrupados;

        let html = '';

        // Sección de Entradas
        if (entradas.length > 0) {
            html += `
                <div class="movimientos-section">
                    <div class="section-title entradas">
                        <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle; margin-right: 8px;">arrow_downward</span>
                        Entradas (${entradas.length})
                    </div>
                    ${entradas.map(mov => this.renderMovimientoItem(mov)).join('')}
                </div>
            `;
        }

        // Sección de Salidas
        if (salidas.length > 0) {
            html += `
                <div class="movimientos-section">
                    <div class="section-title salidas">
                        <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle; margin-right: 8px;">arrow_upward</span>
                        Salidas (${salidas.length})
                    </div>
                    ${salidas.map(mov => this.renderMovimientoItem(mov)).join('')}
                </div>
            `;
        }

        // Si no hay movimientos
        if (entradas.length === 0 && salidas.length === 0) {
            html = `
                <div class="empty-state">
                    <span class="material-symbols-rounded empty-state-icon">inbox</span>
                    <p>No hay movimientos registrados para este recibo</p>
                </div>
            `;
        }

        return html;
    }

    /**
     * Renderiza un item de movimiento
     */
    renderMovimientoItem(movimiento) {
        const { tipo_movimiento, fecha_movimiento, prenda, tallas = [] } = movimiento;

        // Calcular cantidad total de tallas enviadas
        const cantidadTotal = tallas.reduce((sum, talla) => sum + (talla.cantidad_enviada || 0), 0);

        // Crear texto descriptivo según el tipo de movimiento
        let textoDescriptivo = '';
        if (tipo_movimiento === 'SALIDA') {
            textoDescriptivo = `Salieron ${cantidadTotal} ${cantidadTotal === 1 ? 'unidad' : 'unidades'} de ${escapeHtml(prenda)} a lavandería`;
        } else if (tipo_movimiento === 'ENTRADA') {
            textoDescriptivo = `Llegaron ${cantidadTotal} ${cantidadTotal === 1 ? 'unidad' : 'unidades'} de ${escapeHtml(prenda)} de lavandería`;
        }

        let tallasHtml = '';
        if (tallas.length > 0) {
            tallasHtml = `
                <div class="tallas-container">
                    <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px;">Tallas:</div>
                    <div class="tallas-grid">
                        ${tallas.map(talla => {
                            let cantidadTexto = '';
                            if (talla.cantidad_enviada && talla.cantidad_recibida) {
                                cantidadTexto = `Env: ${talla.cantidad_enviada}<br>Rec: ${talla.cantidad_recibida}`;
                            } else if (talla.cantidad_enviada) {
                                cantidadTexto = `Env: ${talla.cantidad_enviada}`;
                            } else if (talla.cantidad_recibida) {
                                cantidadTexto = `Rec: ${talla.cantidad_recibida}`;
                            }
                            
                            return `
                                <div class="talla-badge">
                                    <span class="talla-badge-label">${escapeHtml(talla.talla)}</span>
                                    <span class="talla-badge-value">${cantidadTexto}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        }

        return `
            <div class="movimiento-item">
                <div class="movimiento-header">
                    <div class="movimiento-info">
                        <div class="movimiento-fecha">
                            <span class="material-symbols-rounded" style="font-size: 14px; vertical-align: middle; margin-right: 4px;">schedule</span>
                            ${escapeHtml(fecha_movimiento)}
                        </div>
                        <div class="movimiento-descripcion">${textoDescriptivo}</div>
                    </div>
                </div>
                ${tallasHtml}
            </div>
        `;
    }

    /**
     * Cierra el modal
     */
    cerrarModal() {
        if (this.modal) {
            this.modal.classList.remove('show');
        }
    }

    /**
     * Configura los event listeners del modal
     */
    setupEventListeners() {
        if (!this.modal) return;

        // Cerrar al hacer clic en el overlay
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.cerrarModal();
            }
        });

        // Cerrar al hacer clic en el botón X
        const closeBtn = this.modal.querySelector('.modal-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.cerrarModal();
            });
        }
    }
}

export { DetallesModalHandler };
