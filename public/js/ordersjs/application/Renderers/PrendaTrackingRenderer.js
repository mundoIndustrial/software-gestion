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
   * Ordena: primero prendas con consecutivo (menor a mayor), luego sin consecutivo
   * 
   * @private
   */
  createPrendasTable(prendas, svgIcons) {
    // Ordenar prendas: con consecutivo primero (ascendente), luego sin consecutivo
    const prendasOrdenadas = [...prendas].sort((a, b) => {
      const consA = this.getConsecutivoCostura(a);
      const consB = this.getConsecutivoCostura(b);
      
      // Si ambos tienen consecutivo, ordenar por número
      if (consA && consB) {
        return parseInt(consA) - parseInt(consB);
      }
      
      // Con consecutivo va primero
      if (consA && !consB) return -1;
      if (!consA && consB) return 1;
      
      // Ambos sin consecutivo, mantener orden original
      return 0;
    });

    let tableHtml = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Consecutivo</th>
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

    prendasOrdenadas.forEach((prenda, index) => {
      const estadoBadge = this.getEstadoBadge(prenda);
      const areaBadge = this.getAreaBadge(prenda);
      const procesosCount = this.getProcesssCount(prenda);
      const procesosDisabled = procesosCount === 0;
      
      // Obtener consecutivo COSTURA
      const consecutivoCostura = this.getConsecutivoCostura(prenda);

      tableHtml += `
        <tr class="prendas-table-row" data-prenda-index="${index}">
          <td class="prendas-consecutivo-cell">
            ${consecutivoCostura || '-'}
          </td>
          <td class="prendas-name-cell">
            <div class="prendas-name">${prenda.nombre_prenda || 'Prenda ' + prenda.id}</div>
          </td>
          <td class="prendas-cantidad-cell">${prenda.cantidad || '-'}</td>
          <td class="prendas-procesos-cell">
            <button 
              class="btn-procesos btn-sm ${procesosDisabled ? 'btn-disabled' : 'btn-primary'}"
              data-prenda-index="${index}"
              ${procesosDisabled ? 'disabled' : ''}
              onclick="${procesosDisabled ? '' : `handleVerProcesos(${index})`}"
              title="${procesosDisabled ? 'Sin procesos registrados' : 'Ver procesos'}"
            >
              ${procesosDisabled ? `
                <svg class="btn-procesos-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                  <line x1="12" y1="9" x2="12" y2="13"></line>
                  <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Sin procesos
              ` : `
                <svg class="btn-procesos-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                ${procesosCount}
              `}
            </button>
          </td>
          <td class="prendas-area-cell">${areaBadge}</td>
          <td class="prendas-estado-cell">
            ${estadoBadge}
          </td>
          <td class="prendas-accion-cell">
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
    
    // Mapeo de estados a clases CSS del modal independiente
    let badgeClass = 'badge-pendiente';
    const estadoNormalizado = estado.toLowerCase().trim();
    
    if (estadoNormalizado === 'completado') {
      badgeClass = 'badge-completado';
    } else if (estadoNormalizado.includes('ejecución') || estadoNormalizado.includes('ejecucion')) {
      badgeClass = 'badge-en-ejecucion';
    } else if (estadoNormalizado === 'rechazado') {
      badgeClass = 'badge-rechazado';
    } else if (estadoNormalizado === 'sin procesos' || estadoNormalizado === 'no iniciado') {
      badgeClass = 'badge-gris';
    }
    
    return `<span class="badge ${badgeClass}">${estado}</span>`;
  }

  /**
   * Obtener badge de área actual
   * Muestra el área más reciente de la tabla consecutivos_recibos_pedidos
   * O "Se saca de bodega" si la prenda es de bodega
   * 
   * @private
   */
  getAreaBadge(prenda) {
    // Si la prenda es de bodega, mostrar mensaje específico
    if (prenda.de_bodega) {
      return `<span class="badge badge-bodega">Se saca de bodega</span>`;
    }

    // Usar area_mas_reciente si está disponible
    if (prenda.area_mas_reciente) {
      return `<span class="badge badge-area">${prenda.area_mas_reciente}</span>`;
    }

    // Fallback: si no hay area_mas_reciente, usar seguimientos_por_area
    const areas = prenda.seguimientos_por_area || {};
    const areaNombres = Object.keys(areas);

    if (areaNombres.length === 0) {
      return '<span class="badge badge-gris">-</span>';
    }

    if (areaNombres.length === 1) {
      return `<span class="badge badge-area">${areaNombres[0]}</span>`;
    }

    // Múltiples áreas
    return `<span class="badge badge-area">+${areaNombres.length} áreas</span>`;
  }

  /**
   * Obtener cantidad de procesos
   * Solo cuenta recibos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)
   * NO cuenta los procesos del seguimiento por área
   * 
   * @private
   */
  getProcesssCount(prenda) {
    // SOLO contar recibos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)
    const recibosEspeciales = prenda.recibos_especiales || [];
    
    if (Array.isArray(recibosEspeciales)) {
      return recibosEspeciales.length;
    }

    return 0;
  }

  /**
   * Obtener consecutivo COSTURA de la prenda
   * 
   * @private
   */
  getConsecutivoCostura(prenda) {
    if (!prenda.consecutivos || !Array.isArray(prenda.consecutivos)) {
      return null;
    }

    // Buscar el consecutivo de tipo COSTURA
    const costura = prenda.consecutivos.find(c => c.tipo_recibo === 'COSTURA');
    
    if (costura && costura.consecutivo_actual) {
      return costura.consecutivo_actual;
    }

    return null;
  }

  /**
   * Limpiar referencias internas
   */
  clear() {
    this.tableContainer = null;
  }
}
