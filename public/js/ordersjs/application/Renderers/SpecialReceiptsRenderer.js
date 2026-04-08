'use strict';

/**
 * SPECIAL RECEIPTS RENDERER
 * Renderiza la tabla de recibos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)
 */

export class SpecialReceiptsRenderer {
  constructor() {
    this.overlayId = 'specialReceiptsOverlay';
  }

  /**
   * Mostrar modal con recibos especiales
   * @param {Object} prenda - Objeto de prenda con recibos_especiales
   */
  showReceipts(prenda) {
    const recibosEspeciales = prenda.recibos_especiales || [];
    
    if (!Array.isArray(recibosEspeciales) || recibosEspeciales.length === 0) {
      console.warn('[SpecialReceiptsRenderer] No hay recibos especiales para mostrar');
      return;
    }

    this.renderModal(prenda, recibosEspeciales);
    this.openModal();
  }

  /**
   * Renderizar contenido del modal
   */
  renderModal(prenda, recibos) {
    const overlay = document.getElementById(this.overlayId);

    if (!overlay) {
      console.error('[SpecialReceiptsRenderer] Overlay no encontrado en el DOM (#' + this.overlayId + ')');
      console.error('[SpecialReceiptsRenderer] Elementos disponibles:', document.querySelectorAll('*[id*="receipt"]'));
      return;
    }

    // Actualizar título
    const titleElement = overlay.querySelector('.special-receipts-title');
    const subtitleElement = overlay.querySelector('.special-receipts-subtitle');
    
    if (titleElement) {
      titleElement.textContent = 'Recibos Especiales';
    }
    
    if (subtitleElement) {
      subtitleElement.textContent = prenda.nombre || `Prenda ${prenda.id}`;
    }

    // Renderizar tabla
    const tableContainer = overlay.querySelector('.sr-table-container');
    if (tableContainer) {
      tableContainer.innerHTML = this.createReceiptsTable(recibos);
    }
  }

  /**
   * Crear tabla HTML de recibos
   */
  createReceiptsTable(recibos) {
    if (!recibos || recibos.length === 0) {
      return `
        <div class="special-receipts-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 12h6m-6 4h6M20.57 1.96a5 5 0 1 0 0 7.07 5 5 0 0 0 0-7.07zM10 4.07a4 4 0 0 1 5.66 5.66M3.02 12c1.82 1.99 4.23 3.15 6.98 3.15 3.79 0 7.06-2.28 8.61-5.58m-8.61 1.43c.33.63.75 1.21 1.25 1.71"></path>
          </svg>
          <p>No hay recibos especiales registrados</p>
        </div>
      `;
    }

    let html = `
      <table class="special-receipts-table">
        <thead>
          <tr>
            <th>Tipo de Recibo</th>
            <th>N° Recibo</th>
            <th>Área</th>
          </tr>
        </thead>
        <tbody>
    `;

    recibos.forEach((recibo, index) => {
      const tipo = (recibo.tipo_recibo || '').toLowerCase();
      const tipoLimpio = tipo.replace(/\s+/g, '-');

      html += `
        <tr>
          <td>
            <span class="receipt-type ${tipoLimpio}">
              ${recibo.tipo_recibo}
            </span>
          </td>
          <td>
            <span class="receipt-number">#${recibo.consecutivo || 'N/A'}</span>
          </td>
          <td>
            <span class="receipt-area">${recibo.area || '-'}</span>
          </td>
        </tr>
      `;
    });

    html += `
        </tbody>
      </table>
    `;

    return html;
  }

  /**
   * Abrir modal
   */
  openModal() {
    const overlay = document.getElementById(this.overlayId);
    if (overlay) {
      overlay.style.display = 'flex';
      // Prevenir scroll del body
      document.body.style.overflow = 'hidden';
    }
  }

  /**
   * Cerrar modal
   */
  closeModal() {
    const overlay = document.getElementById(this.overlayId);
    if (overlay) {
      overlay.style.display = 'none';
      // Permitir scroll del body
      document.body.style.overflow = 'auto';
    }
  }

  /**
   * Inicializar event listeners del modal
   */
  initializeEventListeners() {
    const overlay = document.getElementById(this.overlayId);
    
    if (!overlay) {
      console.warn('[SpecialReceiptsRenderer] Overlay no encontrado para inicializar listeners');
      return;
    }

    // Buscar botones dentro del overlay
    const closeBtn = overlay.querySelector('.special-receipts-close');
    const closeBtnFooter = overlay.querySelector('.btn-close-receipts');

    console.log('[SpecialReceiptsRenderer.initializeEventListeners] Inicializando listeners');
    console.log('[SpecialReceiptsRenderer] Close button (header):', closeBtn);
    console.log('[SpecialReceiptsRenderer] Close button (footer):', closeBtnFooter);

    // Click en overlay (fuera del modal)
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        console.log('[SpecialReceiptsRenderer] Click en overlay, cerrando...');
        this.closeModal();
      }
    });

    // Click en botón de cerrar (header)
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        console.log('[SpecialReceiptsRenderer] Click en close button (header)');
        this.closeModal();
      });
    }

    // Click en botón de cerrar (footer)
    if (closeBtnFooter) {
      closeBtnFooter.addEventListener('click', () => {
        console.log('[SpecialReceiptsRenderer] Click en close button (footer)');
        this.closeModal();
      });
    }

    // ESC key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.style.display === 'flex') {
        console.log('[SpecialReceiptsRenderer] Tecla ESC presionada, cerrando...');
        this.closeModal();
      }
    });
  }
}

export default SpecialReceiptsRenderer;
