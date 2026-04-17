/**
 * Domain Value Object: DateFormatter
 */

class DateFormatter {
  static FORMAT_OPTIONS = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  };

  static DEFAULT_LOCALE = 'es-ES';

  static format(dateInput, locale = this.DEFAULT_LOCALE) {
    if (!dateInput) {
      return '-';
    }

    const dateObject = this.#toDateObject(dateInput);
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
   * Convierte entradas de fecha a Date evitando corrimientos por zona horaria
   * para valores YYYY-MM-DD.
   */
  static #toDateObject(input) {
    if (input instanceof Date) {
      return isNaN(input.getTime()) ? null : input;
    }

    if (typeof input === 'string') {
      // YYYY-MM-DD (solo fecha): interpretar como fecha local fija
      const ymdMatch = input.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (ymdMatch) {
        const [, year, month, day] = ymdMatch;
        const date = new Date(
          parseInt(year, 10),
          parseInt(month, 10) - 1,
          parseInt(day, 10),
          12,
          0,
          0
        );
        return isNaN(date.getTime()) ? null : date;
      }

      let date = new Date(input);
      if (!isNaN(date.getTime())) {
        return date;
      }

      const ddmmyyyyMatch = input.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
      if (ddmmyyyyMatch) {
        const [, day, month, year] = ddmmyyyyMatch;
        date = new Date(
          parseInt(year, 10),
          parseInt(month, 10) - 1,
          parseInt(day, 10),
          12,
          0,
          0
        );
        return isNaN(date.getTime()) ? null : date;
      }

      return null;
    }

    if (input && typeof input === 'object' && input.date) {
      if (typeof input.date === 'string') {
        const ymdMatch = input.date.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (ymdMatch) {
          const [, year, month, day] = ymdMatch;
          const date = new Date(
            parseInt(year, 10),
            parseInt(month, 10) - 1,
            parseInt(day, 10),
            12,
            0,
            0
          );
          return isNaN(date.getTime()) ? null : date;
        }
      }

      const date = new Date(input.date);
      return isNaN(date.getTime()) ? null : date;
    }

    return null;
  }

  static getOrderStartDate(orderData) {
    if (!orderData) return '-';

    const dateInput =
      orderData.fecha_creacion ||
      orderData.created_at ||
      orderData.created_at ||
      null;

    return this.format(dateInput);
  }

  static getOrderEstimatedDate(orderData) {
    if (!orderData) return '-';

    const dateInput =
      orderData.fecha_estimada_de_entrega ||
      orderData.fecha_estimada_entrega ||
      null;

    return this.format(dateInput);
  }

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

  static isValid(dateInput) {
    return this.#toDateObject(dateInput) !== null;
  }

  static getCurrentDate() {
    return this.format(new Date());
  }

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

