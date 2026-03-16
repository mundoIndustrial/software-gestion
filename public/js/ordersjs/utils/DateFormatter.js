/**
 * DateFormatter - Utilidad para formateo centralizado de fechas
 * Sigue patrón: Single Responsibility Principle (SRP)
 * 
 * Responsabilidad única: Formatear fechas en diferentes formatos
 * según el locale configurado (español)
 */

class DateFormatter {
  /**
   * Opciones estándar para formato de fecha en español
   * @type {Object}
   */
  static LOCALE = 'es-ES';

  static DATE_OPTIONS = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  };

  static DATETIME_OPTIONS = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  };

  /**
   * Convierte múltiples tipos de fecha a objeto Date válido
   * Maneja: strings, Date objects, objetos Carbon/Laravel
   * 
   * @param {string|Date|Object} dateInput - Entrada de fecha
   * @returns {Date|null} - Objeto Date o null
   */
  static parseDate(dateInput) {
    if (!dateInput) return null;

    try {
      // Si ya es un Date válido
      if (dateInput instanceof Date && !isNaN(dateInput.getTime())) {
        return dateInput;
      }

      // Si es un string, parsearlo
      if (typeof dateInput === 'string' && dateInput.trim()) {
        const date = new Date(dateInput);
        if (!isNaN(date.getTime())) {
          return date;
        }
      }

      // Si es un objeto Carbon con propiedad date
      if (dateInput && typeof dateInput === 'object' && dateInput.date) {
        const date = new Date(dateInput.date);
        if (!isNaN(date.getTime())) {
          return date;
        }
      }

      return null;
    } catch (error) {
      console.warn('[DateFormatter.parseDate] Error parsing date:', error);
      return null;
    }
  }

  /**
   * Formatea una fecha al formato estándar (dd/mm/yyyy)
   * Con manejadel errores robusto
   * 
   * @param {string|Date|Object} dateInput - Entrada de fecha
   * @param {Object} options - Opciones de formateo (opcional)
   * @returns {string} - Fecha formateada o "-" si es inválida
   */
  static format(dateInput, options = null) {
    try {
      const date = this.parseDate(dateInput);
      if (!date) return '-';

      const formatOptions = options || this.DATE_OPTIONS;
      return date.toLocaleDateString(this.LOCALE, formatOptions);
    } catch (error) {
      console.warn('[DateFormatter.format] Error formatting date:', error);
      return '-';
    }
  }

  /**
   * Formatea una fecha con hora (dd/mm/yyyy hh:mm)
   * 
   * @param {string|Date|Object} dateInput - Entrada de fecha
   * @returns {string} - Fecha con hora formateada o "-"
   */
  static formatWithTime(dateInput) {
    return this.format(dateInput, this.DATETIME_OPTIONS);
  }

  /**
   * Obtiene la primera fecha válida de un array de posibles fechas
   * Útil para priorizar diferentes campos de fecha
   * 
   * @param {...any} dateCandidates - Múltiples candidatos de fecha
   * @returns {string} - Primera fecha válida formateada o "-"
   */
  static getFirstValid(...dateCandidates) {
    for (const candidate of dateCandidates) {
      if (candidate) {
        const formatted = this.format(candidate);
        if (formatted !== '-') {
          return formatted;
        }
      }
    }
    return '-';
  }

  /**
   * Calcula diferencia de días entre dos fechas
   * 
   * @param {string|Date|Object} startDate - Fecha de inicio
   * @param {string|Date|Object} endDate - Fecha de fin
   * @returns {number} - Diferencia en días (0 si error)
   */
  static getDaysDifference(startDate, endDate) {
    try {
      const start = this.parseDate(startDate);
      const end = this.parseDate(endDate);

      if (!start || !end) return 0;

      const diffTime = Math.abs(end - start);
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    } catch (error) {
      console.warn('[DateFormatter.getDaysDifference] Error:', error);
      return 0;
    }
  }

  /**
   * Retorna si una fecha es válida (parseable)
   * 
   * @param {any} dateInput - Entrada de fecha
   * @returns {boolean} - true si es válida
   */
  static isValid(dateInput) {
    return this.parseDate(dateInput) !== null;
  }
}

// Exportar para uso como módulo o global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = DateFormatter;
} else if (typeof window !== 'undefined') {
  window.DateFormatter = DateFormatter;
}
