/**
 * PrendaTrackingRenderer
 * 
 * Responsabilidad: Renderizar tabla y tarjetas de prendas
 * OCP: Fácil agregar nuevos estilos sin modificar handler
 * 
 * @class PrendaTrackingRenderer
 */
export class PrendaTrackingRenderer {
  constructor() {
    this.tableContainer = null;
  }

  /**
   * Renderizar la tabla de prendas en el contenedor
   * 
   * @param {HTMLElement} container - Contenedor destino
   * @param {Array} prendas - Array de prendas
   * @param {Object} svgIcons - Helpers de iconos SVG
   * @param {Object} orderState - Estado centralizado
   */
  renderPrendasTable(container, prendas, svgIcons, orderState) {
    console.log('[PrendaTrackingRenderer] Renderizando tabla de prendas:', prendas.length);

    container.innerHTML = '';

    if (prendas.length === 0) {
      container.innerHTML = `
        <div class="tracking-no-prendas">
          <p>No hay prendas registradas para este pedido</p>
        </div>
      `;
      return;
    }

    const tableHtml = this.createPrendasTable(prendas, svgIcons);
    container.innerHTML = tableHtml;

    // Agregar event listeners a botones de ver
    this.setupTableListeners(container, prendas);

    this.tableContainer = container;
  }

  /**
   * Crear HTML de tabla con todas las prendas
   * 
   * @private
   */
  createPrendasTable(prendas, svgIcons) {
    let tableHtml = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Prenda</th>
              <th>Cantidad</th>
              <th>Procesos</th>
              <th>Área</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
    `;

    prendas.forEach((prenda, index) => {
      const estadoBadge = this.getEstadoBadge(prenda);
      const areaBadge = this.getAreaBadge(prenda);
      const procesosCount = this.getProcesssCount(prenda);

      tableHtml += `
        <tr data-prenda-index="${index}">
          <td>${prenda.nombre_prenda || 'Prenda ' + prenda.id}</td>
          <td>${prenda.cantidad || '-'}</td>
          <td>${procesosCount}</td>
          <td>${areaBadge}</td>
          <td>${estadoBadge}</td>
          <td>
            <button 
              class="btn-ver-prenda btn-sm btn-primary"
              data-prenda-index="${index}"
              onclick="showPrendaTrackingFromTable(${index})"
            >
              ${svgIcons.view()}
              Ver
            </button>
          </td>
        </tr>
      `;
    });

    tableHtml += `
          </tbody>
        </table>
      </div>
    `;

    return tableHtml;
  }

  /**
   * Configurar listeners de tabla
   * 
   * @private
   */
  setupTableListeners(container, prendas) {
    const buttons = container.querySelectorAll('.btn-ver-prenda');
    buttons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const index = parseInt(btn.getAttribute('data-prenda-index'));
        if (!Number.isFinite(index)) return;
        window.showPrendaTrackingFromTable(index);
      });
    });
  }

  /**
   * Obtener badge de estado
   * 
   * @private
   */
  getEstadoBadge(prenda) {
    const estado = prenda.estado || 'Pendiente';
    const stateClass = estado === 'Completado' ? 'badge-success' : 'badge-warning';
    return `<span class="badge ${stateClass}">${estado}</span>`;
  }

  /**
   * Obtener badge de área actual
   * 
   * @private
   */
  getAreaBadge(prenda) {
    const areas = prenda.seguimientos_por_area || {};
    const areaNombres = Object.keys(areas);

    if (areaNombres.length === 0) {
      return '<span class="badge badge-secondary">-</span>';
    }

    if (areaNombres.length === 1) {
      return `<span class="badge badge-info">${areaNombres[0]}</span>`;
    }

    // Múltiples áreas
    return `<span class="badge badge-info">+${areaNombres.length} áreas</span>`;
  }

  /**
   * Obtener cantidad de procesos
   * 
   * @private
   */
  getProcesssCount(prenda) {
    const areas = prenda.seguimientos_por_area || {};
    let totalProcesos = 0;

    Object.values(areas).forEach(area => {
      if (area.procesos && Array.isArray(area.procesos)) {
        totalProcesos += area.procesos.length;
      }
    });

    return totalProcesos;
  }

  /**
   * Limpiar referencias internas
   */
  clear() {
    this.tableContainer = null;
  }
}
