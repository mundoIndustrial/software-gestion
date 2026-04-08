/**
 * Domain Value Object: DateFormatter
 * 
 * Representa el concepto de una "Fecha de Orden" en el dominio.
 * Un Value Object es un objeto sin identidad que es reemplazable por otro con los mismos valores.
 * 
 * Responsabilidades:
 * - Convertir múltiples formatos de fecha a Date nativo
 * - Formatear fechas de manera consistente
 * - Manejar distintas fuentes: string ISO, objeto Date, objeto Laravel/Carbon
 * 
 * Beneficios:
 * - Elimina duplicación de lógica de formateo (estaba en 3+ lugares)
 * - Point of change único para cambios de formato
 * - Testeable independientemente
 */

class DateFormatter {
  /**
   * Configuración de formato por defecto
   * @type {Object}
   */
  static FORMAT_OPTIONS = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  };

  /**
   * Locale por defecto (español)
   * @type {String}
   */
  static DEFAULT_LOCALE = 'es-ES';

  /**
   * Formatear una fecha desde cualquier fuente
   * 
   * @param {Date|String|Object|null} dateInput - Fecha en cualquier formato
   * @param {String} locale - Locale para formateo (default: 'es-ES')
   * @returns {String} Fecha formateada o '-' si no es válida
   * 
   * Soporta:
   * - Date nativo: new Date()
   * - String ISO: "2026-03-24T10:30:00"
   * - Objeto Laravel/Carbon: { date: "2026-03-24 10:30:00" }
   * - null/undefined: retorna '-'
   */
  static format(dateInput, locale = this.DEFAULT_LOCALE) {
    if (!dateInput) {
      return '-';
    }

    const dateObject = this.#toDateObject(dateInput);
    
    // Si no se puede convertir, retornar '-'
    if (!dateObject) {
      console.warn('[DateFormatter] No se pudo convertir fecha:', dateInput);
      return '-';
    }

    try {
      return dateObject.toLocaleDateString(locale, this.FORMAT_OPTIONS);
    } catch (error) {
      console.error('[DateFormatter] Error formateando fecha:', error);
      return '-';
    }
  }

  /**
   * Convertir entrada a objeto Date
   * 
   * @private
   * @param {Date|String|Object} input - Entrada en varios formatos
   * @returns {Date|null} Objeto Date o null si no puede convertir
   */
  static #toDateObject(input) {
    // Caso 1: Ya es un Date
    if (input instanceof Date) {
      return isNaN(input.getTime()) ? null : input;
    }

    // Caso 2: String ISO (formato estándar)
    if (typeof input === 'string') {
      // Intenta primero como ISO
      let date = new Date(input);
      if (!isNaN(date.getTime())) {
        return date;
      }

      // Caso 2b: Formato DD/MM/YYYY (del backend formateado)
      const ddmmyyyyMatch = input.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
      if (ddmmyyyyMatch) {
        const [, day, month, year] = ddmmyyyyMatch;
        date = new Date(year, parseInt(month) - 1, day);
        return isNaN(date.getTime()) ? null : date;
      }

      return null;
    }

    // Caso 3: Objeto Laravel/Carbon con propiedad .date
    // Viene del backend laravel como: { date: "2026-03-24 10:30:00", ... }
    if (input && typeof input === 'object' && input.date) {
      const date = new Date(input.date);
      return isNaN(date.getTime()) ? null : date;
    }

    // No puede convertirse
    return null;
  }

  /**
   * Obtener fecha de inicio de una orden
   * 
   * Las órdenes pueden tener varios campos de fecha según el backend.
   * Este método normaliza: ¿cuál es la fecha de inicio?
   * 
   * @param {Object} orderData - Datos de la orden
   * @returns {String} Fecha formateada o '-'
   */
  static getOrderStartDate(orderData) {
    if (!orderData) return '-';

    // Intentar en este orden de prioridad
    const dateInput = 
      orderData.fecha_creacion ||
      orderData.created_at ||
      orderData.created_at ||
      null;

    return this.format(dateInput);
  }

  /**
   * Obtener fecha estimada de entrega de una orden
   * 
   * Normaliza el campo de fecha estimada (puede venir con diferentes nombres).
   * 
   * @param {Object} orderData - Datos de la orden
   * @returns {String} Fecha formateada o '-'
   */
  static getOrderEstimatedDate(orderData) {
    if (!orderData) return '-';

    const dateInput = 
      orderData.fecha_estimada_de_entrega ||
      orderData.fecha_estimada_entrega ||
      null;

    return this.format(dateInput);
  }

  /**
   * Comparar dos fechas
   * 
   * @param {Date|String|Object} date1 - Primera fecha
   * @param {Date|String|Object} date2 - Segunda fecha
   * @returns {Number} -1 si date1 < date2, 0 si iguales, 1 si date1 > date2
   */
  static compare(date1, date2) {
    const d1 = this.#toDateObject(date1);
    const d2 = this.#toDateObject(date2);

    if (!d1 || !d2) {
      return 0;
    }

    if (d1 < d2) return -1;
    if (d1 > d2) return 1;
    return 0;
  }

  /**
   * Verificar si una fecha es válida
   * 
   * @param {Any} dateInput - Fecha en cualquier formato
   * @returns {Boolean}
   */
  static isValid(dateInput) {
    return this.#toDateObject(dateInput) !== null;
  }

  /**
   * Obtener fecha actual formateada
   * 
   * @returns {String} Fecha de hoy en formato dd/mm/yyyy
   */
  static getCurrentDate() {
    return this.format(new Date());
  }

  /**
   * Calcular diferencia en días entre dos fechas
   * 
   * @param {Date|String|Object} startDate - Fecha inicio
   * @param {Date|String|Object} endDate - Fecha fin
   * @returns {Number} Diferencia en días (números positivos/negativos)
   */
  static diffInDays(startDate, endDate) {
    const d1 = this.#toDateObject(startDate);
    const d2 = this.#toDateObject(endDate);

    if (!d1 || !d2) {
      return 0;
    }

    const diffTime = d2.getTime() - d1.getTime();
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  }
}

export default DateFormatter;
