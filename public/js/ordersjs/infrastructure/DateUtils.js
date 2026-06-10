/**
 * Infrastructure Service: DateUtils
 * 
 * Centraliza operaciones de fecha que NO pertenecen al dominio:
 * - Conversión de formatos (d/m/Y, ISO, Carbon)
 * - Formateo con hora (formatDateTime)
 * - Cálculo de días hábiles con festivos colombianos
 * - Formateo de duración humana
 * - Normalización de consecutivos
 * 
 * Singleton: se exporta una instancia con festivosCache propio.
 */

class DateUtils {
  #festivosCache;

  constructor() {
    this.#festivosCache = new Map();
  }

  /**
   * Convertir valor a objeto Date nativo
   * Soporta: Date, string ISO, string d/m/Y, objeto Carbon {date: ...}
   */
  toDateObject(value) {
    if (!value) return null;
    const raw = (value && typeof value === 'object' && value.date)
      ? value.date
      : value;
    const date = raw instanceof Date ? raw : new Date(raw);
    return isNaN(date.getTime()) ? null : date;
  }

  /**
   * Formatear fecha (solo día/mes/año)
   * Soporta formato d/m/Y entrante y lo normaliza
   */
  formatDate(dateString) {
    if (!dateString) return null;

    try {
      // Si el formato es d/m/Y, convertirlo a Y-m-d
      if (typeof dateString === 'string' && dateString.includes('/')) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
          const [day, month, year] = parts;
          const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          const date = new Date(isoDate);
          return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
      }

      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch (error) {
      console.warn('[DateUtils.formatDate] Error:', dateString, error);
      return dateString;
    }
  }

  /**
   * Formatear fecha con hora completa (dd/mm/yyyy, HH:mm:ss)
   */
  formatDateTime(dateString) {
    if (!dateString) return null;

    try {
      const raw = (dateString && typeof dateString === 'object' && dateString.date)
        ? dateString.date
        : dateString;

      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return String(dateString);

      return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      });
    } catch (error) {
      console.warn('[DateUtils.formatDateTime] Error:', dateString, error);
      return String(dateString);
    }
  }

  /**
   * Formatear fecha con hora en formato dd/mm/yyyy hh:mm:ss AM/PM
   */
  formatDateWithAmPm(dateString) {
    if (!dateString) return null;

    try {
      const raw = (dateString && typeof dateString === 'object' && dateString.date)
        ? dateString.date
        : dateString;

      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return String(dateString);

      const datePart = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
      const timePart = date.toLocaleString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      });

      return `${datePart} ${timePart}`;
    } catch (error) {
      console.warn('[DateUtils.formatDateWithAmPm] Error:', dateString, error);
      return String(dateString);
    }
  }

  /**
   * Normalizar consecutivos (array u objeto indexado → array)
   */
  normalizeConsecutivos(consecutivos) {
    if (!consecutivos) return [];
    if (Array.isArray(consecutivos)) return consecutivos;

    if (typeof consecutivos === 'object') {
      try {
        return Object.values(consecutivos).filter(Boolean);
      } catch (e) {
        return [];
      }
    }

    return [];
  }

  /**
   * Formatear duración en milisegundos a texto legible
   */
  formatDurationHuman(diffMs) {
    const totalSeconds = Math.floor((diffMs || 0) / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const parts = [];
    if (days > 0) parts.push(`${days} ${days === 1 ? 'día' : 'días'}`);
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);
    if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`);
    return parts.join(' ');
  }

  /**
   * Calcular días hábiles entre dos fechas (excluyendo fines de semana y festivos colombianos)
   * Versión síncrona que usa cache de festivos
   */
  calcularDiasHabilesSync(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
    const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);

    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    if (inicio.toDateString() === fin.toDateString()) return 0;

    const anio = inicio.getFullYear();
    let festivos = this.#festivosCache.get(anio) || this.#getFestivosFijos(anio);

    if (fin.getFullYear() > inicio.getFullYear()) {
      const festivosSiguiente = this.#festivosCache.get(fin.getFullYear()) || this.#getFestivosFijos(fin.getFullYear());
      festivos = [...festivos, ...festivosSiguiente];
    }

    let diasHabiles = 0;
    const actual = new Date(inicio);

    while (actual <= fin) {
      if (actual.getDay() !== 0 && actual.getDay() !== 6) {
        const fechaStr = actual.toISOString().slice(0, 10);
        if (!festivos.includes(fechaStr)) {
          diasHabiles++;
        }
      }
      actual.setDate(actual.getDate() + 1);
    }

    const inicioStr = inicio.toISOString().slice(0, 10);
    const inicioEsDiaHabil = inicio.getDay() !== 0 && inicio.getDay() !== 6 && !festivos.includes(inicioStr);
    return Math.max(0, diasHabiles - (inicioEsDiaHabil ? 1 : 0));
  }

  /**
   * Festivos fijos colombianos como fallback
   */
  #getFestivosFijos(anio) {
    return [
      `${anio}-01-01`,
      `${anio}-05-01`,
      `${anio}-07-01`,
      `${anio}-07-20`,
      `${anio}-08-07`,
      `${anio}-12-08`,
      `${anio}-12-25`,
    ];
  }

  /**
   * Permite inyectar festivos desde el exterior (ej: precargados por tracking/date-utils.js)
   */
  setFestivosCache(anio, festivos) {
    this.#festivosCache.set(anio, festivos);
  }
}

const dateUtils = new DateUtils();

export { DateUtils };
export default dateUtils;
