/**
 * BadgeRenderer
 * 
 * Responsabilidad: Renderizar badges de estado (etiquetas pequeñas)
 * OCP: Fácil cambiar estilos sin tocar handlers
 * 
 * @class BadgeRenderer
 */
export class BadgeRenderer {
  /**
   * Renderizar badges genéricos
   * 
   * @param {Object} items - Items a renderizar {clave: {status, ...}}
   * @param {string} containerClass - Clase del contenedor
   * @param {string} statusField - Campo que indica estado (true=pendiente, false=completado)
   * @param {Function} textFormatter - Función para formatear texto
   * @returns {string} HTML
   */
  renderBadges(items, containerClass, statusField, textFormatter) {
    if (!items || Object.keys(items).length === 0) {
      return '';
    }

    let badgesHtml = `<div class="${containerClass}">`;

    Object.entries(items).forEach(([key, data]) => {
      const statusClass = data[statusField] ? 'pendiente' : 'completado';
      const text = textFormatter(key, data);

      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}" data-key="${key}">
          ${text}
        </span>
      `;
    });

    badgesHtml += '</div>';
    return badgesHtml;
  }

  /**
   * Renderizar badges de seguimientos por tipo de recibo
   * 
   * @param {Object} seguimientos - Seguimientos por tipo {tipo: {consecutivo_actual, ...}}
   * @returns {string} HTML
   */
  renderSeguimientosBadges(seguimientos) {
    return this.renderBadges(
      seguimientos,
      'tracking-prenda-seguimientos',
      'tiene_disponibles',
      (tipo, data) => `${tipo}: ${data.consecutivo_actual}/${data.consecutivo_inicial}`
    );
  }

  /**
   * Renderizar badges de áreas/procesos
   * 
   * @param {Object} areas - Áreas {area: {estado, esta_activo, ...}}
   * @returns {string} HTML
   */
  renderAreasBadges(areas) {
    return this.renderBadges(
      areas,
      'tracking-prenda-areas',
      'esta_activo',
      (area, data) => `${area}: ${data.estado || 'Pendiente'}`
    );
  }

  /**
   * Renderizar badges de estado genéricos
   * 
   * @param {Object} statusMap - Mapa de estados {clave: boolean}
   * @param {Object} options - Opciones
   * @param {string} options.containerClass - Clase del contenedor
   * @param {Function} options.labelFactory - Función para generar labels
   * @param {string} options.pendienteClass - Clase para pendiente (default: 'badge-warning')
   * @param {string} options.completadoClass - Clase para completado (default: 'badge-success')
   * @returns {string} HTML
   */
  renderStatusBadges(statusMap, options = {}) {
    const {
      containerClass = 'status-badges',
      labelFactory = (key) => key,
      pendienteClass = 'badge-warning',
      completadoClass = 'badge-success'
    } = options;

    if (!statusMap || Object.keys(statusMap).length === 0) {
      return '';
    }

    let html = `<div class="${containerClass}">`;

    Object.entries(statusMap).forEach(([key, isPendiente]) => {
      const badgeClass = isPendiente ? pendienteClass : completadoClass;
      const label = labelFactory(key);

      html += `
        <span class="badge ${badgeClass}" data-key="${key}">
          ${label}
        </span>
      `;
    });

    html += '</div>';
    return html;
  }

  /**
   * Renderizar badge único de estado
   * 
   * @param {string} text - Texto del badge
   * @param {boolean} isPendiente - Si está pendiente
   * @param {string} customClass - Clase adicional (opcional)
   * @returns {string} HTML
   */
  renderSingleBadge(text, isPendiente = false, customClass = '') {
    const statusClass = isPendiente ? 'badge-warning' : 'badge-success';
    return `<span class="badge ${statusClass} ${customClass}">${text}</span>`;
  }

  /**
   * Renderizar badges en línea (horizontales)
   * 
   * @param {Array<string>} items - Items a renderizar
   * @param {Object} options - Opciones
   * @returns {string} HTML
   */
  renderInlineBadges(items, options = {}) {
    const {
      containerClass = 'inline-badges',
      badgeClass = 'badge-info',
      separator = ' '
    } = options;

    if (!items || items.length === 0) {
      return '';
    }

    let html = `<div class="${containerClass}">`;

    items.forEach((item, index) => {
      if (index > 0) html += separator;
      html += `<span class="badge ${badgeClass}">${item}</span>`;
    });

    html += '</div>';
    return html;
  }

  /**
   * Renderizar progress badge (con progreso)
   * 
   * @param {number} completed - Cantidad completada
   * @param {number} total - Total
   * @param {Object} options - Opciones
   * @returns {string} HTML
   */
  renderProgressBadge(completed, total, options = {}) {
    const {
      label = 'Progreso',
      containerClass = 'progress-badge',
      percentageThreshold = 75
    } = options;

    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    const progressClass = percentage >= percentageThreshold ? 'badge-success' : 'badge-info';

    return `
      <div class="${containerClass}">
        <span class="progress-label">${label}:</span>
        <span class="badge ${progressClass}">
          ${completed}/${total} (${percentage}%)
        </span>
      </div>
    `;
  }

  /**
   * Renderizar badge con icono (si está disponible)
   * 
   * @param {string} text - Texto
   * @param {string} icon - HTML del icono (opcional)
   * @param {string} badgeClass - Clase del badge
   * @returns {string} HTML
   */
  renderBadgeWithIcon(text, icon = '', badgeClass = 'badge-info') {
    return `
      <span class="badge ${badgeClass}">
        ${icon ? `<span class="badge-icon">${icon}</span>` : ''}
        <span class="badge-text">${text}</span>
      </span>
    `;
  }
}
