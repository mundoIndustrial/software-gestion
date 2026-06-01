/**
 * FIRMA MODAL HANDLER - Seguimiento Lavandería
 * Maneja la apertura y visualización de firmas de movimientos
 */

import { escapeHtml } from './utilities.js';

class FirmaModalHandler {
    constructor() {
        this.modal = document.getElementById('firmaModal');
        this.modalTitle = document.getElementById('firmaModalTitle');
        this.modalBody = document.getElementById('firmaModalBody');
    }

    /**
     * Abre el modal y carga la firma del movimiento
     */
    abrirModal(movimientoId, fecha) {
        if (!this.modal || !this.modalBody) return;

        // Mostrar modal con estado de carga
        this.modal.classList.add('show');
        this.modalTitle.textContent = `Firma del Movimiento #${movimientoId}`;
        this.modalBody.innerHTML = `
            <div style="text-align: center; padding: 32px;">
                <div class="loading-spinner"></div>
                <p style="margin-top: 16px; color: #64748b;">Cargando firma...</p>
            </div>
        `;

        // Obtener la URL de la firma desde el servidor
        fetch(`/seguimiento-lavanderia/api/firma-movimiento/${movimientoId}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.url) {
                this.renderFirma(movimientoId, fecha, data.data.url);
            } else {
                this.renderError('No se pudo obtener la firma');
            }
        })
        .catch(error => {
            console.error('[FirmaModalHandler] Error:', error);
            this.renderError('Error al cargar la firma');
        });
    }

    /**
     * Renderiza un error
     */
    renderError(mensaje) {
        if (!this.modalBody) return;

        this.modalBody.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-rounded empty-state-icon">error</span>
                <p>${escapeHtml(mensaje)}</p>
            </div>
        `;
    }

    /**
     * Renderiza la firma
     */
    renderFirma(movimientoId, fecha, firmaUrl) {
        if (!this.modalBody) return;

        this.modalBody.innerHTML = `
            <div style="text-align: center; padding: 24px;">
                <div style="margin-bottom: 16px; color: #64748b; font-size: 13px;">
                    <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">schedule</span>
                    ${escapeHtml(fecha)}
                </div>
                <div style="
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 16px;
                    background: #f8fafc;
                    max-height: 280px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                " id="firmaContainer">
                    <img 
                        src="${firmaUrl}" 
                        alt="Firma del movimiento" 
                        style="
                            max-width: 100%;
                            max-height: 250px;
                            border-radius: 4px;
                        "
                        id="firmaImage"
                    >
                </div>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="${firmaUrl}" download="firma_movimiento_${movimientoId}.webp" class="btn-descargar" style="
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        background: #2450ef;
                        color: white;
                        padding: 8px 16px;
                        border-radius: 6px;
                        text-decoration: none;
                        font-size: 12px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                    "
                    onmouseover="this.style.background='#1e40af'"
                    onmouseout="this.style.background='#2450ef'"
                    >
                        <span class="material-symbols-rounded" style="font-size: 16px;">download</span>
                        Descargar Firma
                    </a>
                </div>
            </div>
        `;

        // Agregar event listener para el error de carga de imagen
        const firmaImage = document.getElementById('firmaImage');
        if (firmaImage) {
            firmaImage.addEventListener('error', () => {
                const container = document.getElementById('firmaContainer');
                if (container) {
                    container.innerHTML = `
                        <div style="color: #94a3b8; text-align: center;">
                            <span class="material-symbols-rounded" style="font-size: 40px; display: block; margin-bottom: 8px;">image_not_supported</span>
                            <p>No se pudo cargar la firma</p>
                        </div>
                    `;
                }
            });
        }
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

export { FirmaModalHandler };
