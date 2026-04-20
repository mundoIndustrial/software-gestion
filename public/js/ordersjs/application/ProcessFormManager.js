/**
 * PROCESS FORM MANAGER
 * 
 * Responsabilidad: Encapsular toda la lógica de manipulación del formulario
 * de procesos (obtener elementos, establecer datos, validar, limpiar).
 * 
 * ANTES: Lógica dispersa en 4 funciones separadas
 * DESPUÉS: Interfaz única y reutilizable
 * 
 * Arquitectura: DIP (Dependency Inversion Principle)
 */

export class ProcessFormManager {
  /**
   * @param {Object} selectors - Mapa de selectores DOM
   * @example
   * {
   *   area: '#procesoArea',
   *   estado: '#procesoEstado',
   *   fechaInicio: '#procesoFechaInicio',
   *   observaciones: '#procesoObservaciones',
   *   encargadoInput: '#procesoEncargado',
   *   encargadoSelect: '#procesoEncargadoSelect'
   * }
   */
  constructor(selectors = {}) {
    this.selectors = {
      area: selectors.area || '#procesoArea',
      estado: selectors.estado || '#procesoEstado',
      fechaInicio: selectors.fechaInicio || '#procesoFechaInicio',
      observaciones: selectors.observaciones || '#procesoObservaciones',
      encargadoInput: selectors.encargadoInput || '#procesoEncargado',
      encargadoSelect: selectors.encargadoSelect || '#procesoEncargadoSelect'
    };
  }

  /**
   * Obtener todos los elementos del formulario
   * @returns {Object} Objeto con referencias a elementos DOM
   */
  getElements() {
    return {
      area: document.querySelector(this.selectors.area),
      estado: document.querySelector(this.selectors.estado),
      fechaInicio: document.querySelector(this.selectors.fechaInicio),
      observaciones: document.querySelector(this.selectors.observaciones),
      encargadoInput: document.querySelector(this.selectors.encargadoInput),
      encargadoSelect: document.querySelector(this.selectors.encargadoSelect)
    };
  }

  /**
   * Establecer datos en el formulario (para edición)
   * @param {Object} data - Datos del proceso a editar
   * @returns {boolean} true si se establecieron correctamente
   */
  setData(data) {
    try {
      const elements = this.getElements();

      // Establecer área (dispara evento change para selector dinámico)
      if (elements.area && data.area) {
        elements.area.value = data.area;
        const changeEvent = new Event('change', { bubbles: true });
        elements.area.dispatchEvent(changeEvent);
      }

      // Establecer estado
      if (elements.estado && data.estado) {
        elements.estado.value = data.estado;
      }

      // Establecer fecha de inicio (convertir a formato YYYY-MM-DD)
      if (elements.fechaInicio && data.fecha_inicio) {
        const date = new Date(data.fecha_inicio);
        if (!isNaN(date.getTime())) {
          const year = date.getFullYear();
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const day = String(date.getDate()).padStart(2, '0');
          elements.fechaInicio.value = `${year}-${month}-${day}`;
        }
      }

      // Establecer observaciones
      if (elements.observaciones && data.observaciones) {
        elements.observaciones.value = data.observaciones;
      }

      return true;
    } catch (error) {
      console.error('[ProcessFormManager.setData] Error:', error);
      return false;
    }
  }

  /**
   * Recopilar datos del formulario
   * @param {string} encargado - Nombre del encargado
   * @returns {Object} Objeto con datos del proceso listo para enviar a API
   */
  collectData(encargado = '') {
    const elements = this.getElements();

    return {
      area: elements.area?.value || '',
      estado: elements.estado?.value || 'Pendiente',
      fecha_inicio: elements.fechaInicio?.value || null,
      encargado: String(encargado || '').trim().toUpperCase(),
      observaciones: elements.observaciones?.value || ''
    };
  }

  /**
   * Obtener valor del encargado (desde select o input, según cuál sea visible)
   * @returns {string} Nombre del encargado
   */
  getEncargadoValue() {
    const elements = this.getElements();

    // Si el select está visible, usar su valor
    if (elements.encargadoSelect && elements.encargadoSelect.offsetParent !== null) {
      const selectedOption = elements.encargadoSelect.options[elements.encargadoSelect.selectedIndex];
      return selectedOption ? selectedOption.text : '';
    }

    // Compatibilidad: si el "input" en realidad es un SELECT (markup legacy),
    // devolver el texto mostrado y no el value (id).
    if (elements.encargadoInput && elements.encargadoInput.tagName === 'SELECT') {
      const selectedOption = elements.encargadoInput.options[elements.encargadoInput.selectedIndex];
      return selectedOption ? selectedOption.text : '';
    }

    // Si no, usar el input de texto
    return elements.encargadoInput?.value || '';
  }

  /**
   * Establecer valor del encargado (en select o input, según cuál sea visible)
   * @param {string} encargado - Nombre del encargado
   * @returns {boolean} true si se estableció correctamente
   */
  setEncargadoValue(encargado = '') {
    try {
      const elements = this.getElements();
      const encargadoValue = String(encargado || '').trim();

      // Si el select está visible, buscar opción por nombre
      if (elements.encargadoSelect && elements.encargadoSelect.offsetParent !== null) {
        const options = elements.encargadoSelect.options;
        for (let i = 0; i < options.length; i++) {
          if (options[i].text.toLowerCase() === encargadoValue.toLowerCase()) {
            elements.encargadoSelect.value = options[i].value;
            return true;
          }
        }
        return false;
      }

      // Compatibilidad legacy: si el "input" es realmente un SELECT.
      if (elements.encargadoInput && elements.encargadoInput.tagName === 'SELECT') {
        const options = elements.encargadoInput.options;
        for (let i = 0; i < options.length; i++) {
          if (options[i].text.toLowerCase() === encargadoValue.toLowerCase()) {
            elements.encargadoInput.value = options[i].value;
            return true;
          }
        }
      }

      // Si no, establecer el input de texto
      if (elements.encargadoInput) {
        elements.encargadoInput.value = encargadoValue ? encargadoValue.toUpperCase() : '';
        return true;
      }

      return false;
    } catch (error) {
      console.error('[ProcessFormManager.setEncargadoValue] Error:', error);
      return false;
    }
  }

  /**
   * Limpiar/resetear el formulario
   * @returns {boolean} true si se limpió correctamente
   */
  clear() {
    try {
      const elements = this.getElements();

      if (elements.area) elements.area.value = '';
      if (elements.estado) elements.estado.value = 'Pendiente';
      if (elements.fechaInicio) elements.fechaInicio.value = '';
      if (elements.observaciones) elements.observaciones.value = '';
      if (elements.encargadoInput) elements.encargadoInput.value = '';
      if (elements.encargadoSelect) elements.encargadoSelect.value = '';

      return true;
    } catch (error) {
      console.error('[ProcessFormManager.clear] Error:', error);
      return false;
    }
  }

  /**
   * Validar que los campos obligatorios estén completos
   * @param {Object} options - Opciones de validación
   * @returns {Object} { isValid: boolean, errors: string[] }
   */
  validate(options = {}) {
    const errors = [];
    const elements = this.getElements();

    // Validar área (siempre obligatoria)
    if (!elements.area?.value?.trim()) {
      errors.push('El área/proceso es obligatorio');
    }

    // Validar encargado si es requerido (delegado a quien use esto)
    if (options.requireEncargado && !this.getEncargadoValue().trim()) {
      errors.push('El encargado es obligatorio para esta área');
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  }

  /**
   * Remover campos de encargado (input y select)
   * Útil cuando se necesita recriar dinámicamente
   * @returns {boolean} true si se removieron
   */
  removeEncargadoFields() {
    try {
      const elements = this.getElements();
      if (elements.encargadoInput) elements.encargadoInput.remove();
      if (elements.encargadoSelect) elements.encargadoSelect.remove();
      return true;
    } catch (error) {
      console.error('[ProcessFormManager.removeEncargadoFields] Error:', error);
      return false;
    }
  }

  /**
   * Crear nuevo campo de encargado (input o select)
   * @param {HTMLElement} container - Contenedor donde insertar el campo
   * @param {string} type - 'input' o 'select'
   * @param {string} id - ID del elemento
   * @param {Array} options - Opciones si es select
   * @returns {HTMLElement|null} Elemento creado o null si error
   */
  createEncargadoField(container, type = 'input', id = 'procesoEncargado', options = []) {
    try {
      // Remover campo anterior
      this.removeEncargadoFields();

      if (type === 'select') {
        const select = document.createElement('select');
        select.id = id;
        select.className = 'add-proceso-select';
        select.innerHTML = '<option value="">Seleccionar encargado...</option>';

        options.forEach(opt => {
          const option = document.createElement('option');
          option.value = opt.id || opt.value;
          option.textContent = opt.nombre || opt.label;
          select.appendChild(option);
        });

        container.appendChild(select);
        return select;
      } else {
        // type === 'input'
        const input = document.createElement('input');
        input.type = 'text';
        input.id = id;
        input.className = 'add-proceso-input';
        input.placeholder = 'Nombre del encargado';
        input.style.textTransform = 'uppercase';
        container.appendChild(input);
        return input;
      }
    } catch (error) {
      console.error('[ProcessFormManager.createEncargadoField] Error:', error);
      return null;
    }
  }
}

export default ProcessFormManager;
