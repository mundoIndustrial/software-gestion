/**
 * ProcessFormValidationService
 * 
 * Responsabilidad: Validar datos de formulario de procesos
 * 
 * SRP: Una sola razón para cambiar — reglas de validación de procesos
 * 
 * Parámetros inyectados:
 * - (ninguno - stateless service)
 * 
 * Métodos:
 * - validateArea(area)
 * - validateEstado(estado)
 * - validateFechaInicio(fechaInicio)
 * - validateEncargado(encargado)
 * - validateObservaciones(observaciones)
 * - validateAll(data) — valida todo junto
 */

export class ProcessFormValidationService {
  /**
   * Validar que el área sea válida
   * @param {string} area
   * @returns {object} { valid: boolean, errors: string[] }
   */
  validateArea(area) {
    const errors = [];

    if (!area || area.trim() === '') {
      errors.push('El área es requerida');
    }

    if (area && area.length > 100) {
      errors.push('El área no puede exceder 100 caracteres');
    }

    return {
      valid: errors.length === 0,
      errors
    };
  }

  /**
   * Validar que el estado sea válido
   * @param {string} estado
   * @returns {object} { valid: boolean, errors: string[] }
   */
  validateEstado(estado) {
    const errors = [];
    const validEstados = ['Pendiente', 'En Proceso', 'Completado'];

    if (!estado) {
      estado = 'Pendiente'; // default válido
    }

    if (!validEstados.includes(estado)) {
      errors.push(`Estado '${estado}' no es válido. Válidos: ${validEstados.join(', ')}`);
    }

    return {
      valid: errors.length === 0,
      errors
    };
  }

  /**
   * Validar que la fecha de inicio sea válida
   * @param {string} fechaInicio - Formato YYYY-MM-DD o vacío
   * @returns {object}  { valid: boolean, errors: string[] }
   */
  validateFechaInicio(fechaInicio) {
    const errors = [];

    if (fechaInicio && fechaInicio.trim() !== '') {
      // Validar formato YYYY-MM-DD
      const regex = /^\d{4}-\d{2}-\d{2}$/;
      if (!regex.test(fechaInicio)) {
        errors.push('Fecha de inicio debe estar en formato YYYY-MM-DD');
      } else {
        // Validar que sea fecha válida
        const date = new Date(fechaInicio);
        if (isNaN(date.getTime())) {
          errors.push('Fecha de inicio no es válida');
        }

        // Validar que no sea una fecha futura (opcional)
        if (date > new Date()) {
          errors.push('Fecha de inicio no puede ser en el futuro');
        }
      }
    }

    return {
      valid: errors.length === 0,
      errors
    };
  }

  /**
   * Validar que el encargado sea válido
   * @param {string} encargado
   * @returns {object} { valid: boolean, errors: string[] }
   */
  validateEncargado(encargado) {
    const errors = [];

    if (!encargado || encargado.trim() === '') {
      errors.push('El encargado es requerido');
    }

    if (encargado && encargado.length > 100) {
      errors.push('El encargado no puede exceder 100 caracteres');
    }

    return {
      valid: errors.length === 0,
      errors
    };
  }

  /**
   * Validar observaciones
   * @param {string} observaciones
   * @returns {object} { valid: boolean, errors: string[] }
   */
  validateObservaciones(observaciones) {
    const errors = [];

    // Observaciones son opcionales, pero si se proporcionan, validar largo
    if (observaciones && observaciones.length > 500) {
      errors.push('Las observaciones no pueden exceder 500 caracteres');
    }

    return {
      valid: errors.length === 0,
      errors
    };
  }

  /**
   * Validar todos los campos conjuntamente
   * @param {object} data - { area, estado, fechaInicio, encargado, observaciones }
   * @returns {object} { valid: boolean, errors: object }
   */
  validateAll(data = {}) {
    const validations = {
      area: this.validateArea(data.area),
      estado: this.validateEstado(data.estado),
      fechaInicio: this.validateFechaInicio(data.fechaInicio),
      encargado: this.validateEncargado(data.encargado),
      observaciones: this.validateObservaciones(data.observaciones)
    };

    // Agrupar todos los errores
    const allErrors = [];
    Object.entries(validations).forEach(([field, result]) => {
      if (!result.valid) {
        allErrors.push({
          field,
          errors: result.errors
        });
      }
    });

    return {
      valid: allErrors.length === 0,
      errors: allErrors
    };
  }

  /**
   * Obtener mensaje de error formateado para UI
   * @param {object} validationResult - Resultado de validateAll()
   * @returns {string} - Mensaje para mostrar
   */
  getErrorMessage(validationResult) {
    if (validationResult.valid) {
      return '';
    }

    const messages = validationResult.errors
      .flatMap(item => item.errors)
      .map(error => `• ${error}`);

    return messages.join('\n');
  }
}
