/**
 * AreaCardRenderer
 * 
 * Responsabilidad: Renderizar tarjetas de áreas con procesos
 * OCP: Fácil cambiar estilo de área sin tocar handlers
 * 
 * @class AreaCardRenderer
 */
export class AreaCardRenderer {
  constructor() {
    this.areaCards = new Map();
  }

  /**
   * Crear tarjeta HTML de área (Corte, Costura, etc.)
   * 
   * @param {Object} params - Parámetros
   * @param {string} params.areaName - Nombre del área
   * @param {Object} params.areaData - Datos del área (procesos, fechas, etc.)
   * @param {Object} params.prenda - Datos de prenda asociada
   * @param {boolean} params.readonly - Si es solo lectura
   * @param {Object} params.svgIcons - Helper de iconos
   * @param {Object} params.dateFormatter - Formateador de fechas
   * @returns {HTMLElement}
   */
  createAreaCard({
    areaName,
    areaData,
    prenda,
    readonly = false,
    svgIcons,
    dateFormatter
  }) {
    console.log(`[AreaCardRenderer] Creando tarjeta para área: ${areaName}`);

    const card = document.createElement('div');
    card.className = 'area-card';
    card.dataset.areaName = areaName;

    // Header del área
    const header = this.createAreaHeader({
      areaName,
      readonly,
      svgIcons
    });

    // Información del área
    const info = this.createAreaInfo({
      areaData,
      areaName,
      dateFormatter
    });

    // Procesos
    const processesSection = this.createProcessesSection({
      areaData,
      areaName,
      readonly,
      prenda,
      svgIcons
    });

    card.appendChild(header);
    card.appendChild(info);
    card.appendChild(processesSection);

    this.areaCards.set(areaName, card);

    return card;
  }

  /**
   * Crear header del área
   * 
   * @private
   */
  createAreaHeader({ areaName, readonly, svgIcons }) {
    const header = document.createElement('div');
    header.className = 'area-card-header';

    const title = document.createElement('div');
    title.className = 'area-card-title';
    title.innerHTML = `<h3>${areaName}</h3>`;

    const actions = document.createElement('div');
    actions.className = 'area-card-actions';

    if (!readonly) {
      const btnAgregarProceso = document.createElement('button');
      btnAgregarProceso.className = 'btn btn-sm btn-outline-primary';
      btnAgregarProceso.innerHTML = `${svgIcons.plus()} Agregar`;
      btnAgregarProceso.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        window.handleCrearProcesoDesdeArea(areaName);
      };

      actions.appendChild(btnAgregarProceso);
    }

    header.appendChild(title);
    header.appendChild(actions);

    return header;
  }

  /**
   * Crear sección de información del área
   * 
   * @private
   */
  createAreaInfo({ areaData, areaName, dateFormatter }) {
    const info = document.createElement('div');
    info.className = 'area-card-info';

    const { procesos = [] } = areaData || {};

    // Estado del área
    const estadoDiv = document.createElement('div');
    estadoDiv.className = 'info-row';
    estadoDiv.innerHTML = `
      <span class="label">Estado:</span>
      <span class="value ${areaData?.esta_activo ? 'activo' : 'completado'}">
        ${areaData?.esta_activo ? 'En progreso' : 'Completado'}
      </span>
    `;

    // Duración del área
    const duracionDiv = document.createElement('div');
    duracionDiv.className = 'info-row';
    const duracion = this.calculateDuration(areaData);
    duracionDiv.innerHTML = `
      <span class="label">Duración:</span>
      <span class="value">${duracion}</span>
    `;

    // Procesos completados
    const completedCount = procesos.filter(p => p.fecha_fin).length;
    const procesosDiv = document.createElement('div');
    procesosDiv.className = 'info-row';
    procesosDiv.innerHTML = `
      <span class="label">Procesos:</span>
      <span class="value">${completedCount}/${procesos.length}</span>
    `;

    info.appendChild(estadoDiv);
    info.appendChild(duracionDiv);
    info.appendChild(procesosDiv);

    return info;
  }

  /**
   * Crear sección de procesos del área
   * 
   * @private
   */
  createProcessesSection({ areaData, areaName, readonly, prenda, svgIcons }) {
    const section = document.createElement('div');
    section.className = 'area-card-processes';

    const { procesos = [] } = areaData || {};

    if (procesos.length === 0) {
      section.innerHTML = '<p class="text-muted">Sin procesos registrados</p>';
      return section;
    }

    const processList = document.createElement('ul');
    processList.className = 'processes-list';

    procesos.forEach((proceso) => {
      const item = document.createElement('li');
      item.className = 'process-item';

      const estado = proceso.fecha_fin ? 'completado' : 'pendiente';
      const checkbox = `<input type="checkbox" ${proceso.fecha_fin ? 'checked' : ''} disabled>`;
      const procesName = proceso.nombre || `Proceso ${proceso.id}`;
      const fechaInicio = proceso.fecha_inicio ? new Date(proceso.fecha_inicio).toLocaleDateString() : '-';
      const fechaFin = proceso.fecha_fin ? new Date(proceso.fecha_fin).toLocaleDateString() : '-';

      item.innerHTML = `
        <div class="process-item-header">
          ${checkbox}
          <span class="process-name ${estado}">${procesName}</span>
          <div class="process-actions">
            ${!readonly ? `
              <button class="btn-editar-proceso btn-sm" data-id="${proceso.id}" data-area="${areaName}" title="Editar">
                ${svgIcons.edit()}
              </button>
              <button class="btn-eliminar-proceso btn-sm" data-id="${proceso.id}" data-area="${areaName}" title="Eliminar">
                ${svgIcons.trash()}
              </button>
            ` : ''}
          </div>
        </div>
        <div class="process-item-dates">
          <span><strong>Inicio:</strong> ${fechaInicio}</span>
          <span><strong>Fin:</strong> ${fechaFin}</span>
        </div>
      `;

      // Listeners para editar/eliminar
      const btnEditar = item.querySelector('.btn-editar-proceso');
      if (btnEditar) {
        btnEditar.onclick = (e) => {
          e.preventDefault();
          e.stopPropagation();
          window.handleEditarProceso(
            proceso.id,
            areaName,
            proceso,
            e
          );
        };
      }

      const btnEliminar = item.querySelector('.btn-eliminar-proceso');
      if (btnEliminar) {
        btnEliminar.onclick = (e) => {
          e.preventDefault();
          e.stopPropagation();
          window.showConfirmDeleteProcess(proceso.id, areaName, e);
        };
      }

      processList.appendChild(item);
    });

    section.appendChild(processList);

    return section;
  }

  /**
   * Calcular duración del área
   * 
   * @private
   */
  calculateDuration(areaData) {
    const { fecha_inicio, fecha_fin } = areaData || {};

    if (!fecha_inicio) return '-';

    const start = new Date(fecha_inicio);
    const end = fecha_fin ? new Date(fecha_fin) : new Date();

    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return `${diffDays} ${diffDays === 1 ? 'día' : 'días'}`;
  }

  /**
   * Actualizar estado de area
   * 
   * @param {string} areaName - Nombre del área
   * @param {Object} newData - Nuevos datos del área
   */
  updateAreaCard(areaName, newData) {
    const card = this.areaCards.get(areaName);
    if (!card) return;

    console.log(`[AreaCardRenderer] Actualizando tarjeta: ${areaName}`);

    // Actualizar estado
    const stateSpan = card.querySelector('.info-row .value.activo, .info-row .value.completado');
    if (stateSpan) {
      const nuevoEstado = newData?.esta_activo ? 'En progreso' : 'Completado';
      stateSpan.textContent = nuevoEstado;
      stateSpan.className = newData?.esta_activo ? 'value activo' : 'value completado';
    }
  }

  /**
   * Limpiar todos los cards
   */
  clear() {
    this.areaCards.clear();
  }
}
