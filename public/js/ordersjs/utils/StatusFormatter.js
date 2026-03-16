/**
 * StatusFormatter - Utilidad para formateo centralizado de estados
 * Sigue patrón: Single Responsibility Principle (SRP)
 * 
 * Responsabilidad única: Normalizar y formatear estados/status strings
 */

class StatusFormatter {
  /**
   * Mapeo de estados a sus versiones formateadas
   * Permite personalizar formato sin cambiar código disperso
   * @type {Object}
   */
  static ESTADO_DISPLAY_CACHE = {};

  /**
   * Formatea un estado reemplazando guiones bajos por espacios
   * y convirtiendo a mayúsculas
   * Ejemplo: "en_produccion" -> "EN PRODUCCION"
   * 
   * @param {string} estado - Estado raw (con guiones bajos)
   * @param {string} fallback - Valor por defecto si estado es inválido
   * @returns {string} - Estado formateado o fallback
   */
  static format(estado, fallback = '-') {
    try {
      if (!estado || typeof estado !== 'string') {
        return fallback;
      }

      const trimmed = estado.trim();
      if (!trimmed) {
        return fallback;
      }

      // Reemplazar guiones bajos por espacios y convertir a mayúsculas
      return trimmed.replace(/_/g, ' ').toUpperCase();
    } catch (error) {
      console.warn('[StatusFormatter.format] Error formatting status:', error);
      return fallback;
    }
  }

  /**
   * Usa estado_display del backend si está disponible, sino formatea
   * Permite al backend controlar la visualización
   * 
   * @param {Object} data - Objeto con campos estado y posiblemente estado_display
   * @param {string} statusField - Campo de estado (default: 'estado')
   * @returns {string} - Estado final formateado
   */
  static getDisplayStatus(data, statusField = 'estado') {
    try {
      if (!data || typeof data !== 'object') {
        return '-';
      }

      // Prioridad 1: campo estado_display (desde Backend)
      if (data.estado_display && typeof data.estado_display === 'string') {
        return data.estado_display.trim() || '-';
      }

      // Prioridad 2: campo estado personalizado
      if (statusField !== 'estado' && data[statusField]) {
        return this.format(data[statusField]);
      }

      // Prioridad 3: campo estado por defecto
      if (data.estado) {
        return this.format(data.estado);
      }

      return '-';
    } catch (error) {
      console.warn('[StatusFormatter.getDisplayStatus] Error:', error);
      return '-';
    }
  }

  /**
   * Retorna la clase CSS para estilizar un estado
   * Útil para badges y colores de estado
   * 
   * @param {string} estado - Estado raw
   * @returns {string} - Clase CSS formateada
   * Ejemplo: "en_produccion" -> "estado-en-produccion"
   */
  static getCSSClass(estado) {
    try {
      if (!estado || typeof estado !== 'string') return 'estado-unknown';

      const formatted = estado
        .toLowerCase()
        .trim()
        .replace(/_/g, '-')
        .replace(/\s+/g, '-');

      return `estado-${formatted}`;
    } catch (error) {
      console.warn('[StatusFormatter.getCSSClass] Error:', error);
      return 'estado-unknown';
    }
  }

  /**
   * Normaliza múltiples estados para comparación
   * Permite comparar estados independientemente del formato
   * 
   * @param {string} estado1 - Primer estado
   * @param {string} estado2 - Segundo estado
   * @returns {boolean} - true si son iguales (normalizados)
   */
  static equals(estado1, estado2) {
    try {
      const normalized1 = (estado1 || '')
        .toLowerCase()
        .trim()
        .replace(/[\s_-]/g, '');

      const normalized2 = (estado2 || '')
        .toLowerCase()
        .trim()
        .replace(/[\s_-]/g, '');

      return normalized1 === normalized2;
    } catch (error) {
      console.warn('[StatusFormatter.equals] Error:', error);
      return false;
    }
  }

  /**
   * Verifica si un estado indica completación
   * 
   * @param {string} estado - Estado a verificar
   * @returns {boolean} - true si el estado es "completado"
   */
  static isCompleted(estado) {
    return this.equals(estado, 'completado') || this.equals(estado, 'complete');
  }

  /**
   * Verifica si un estado indica pendencia/activo
   * 
   * @param {string} estado - Estado a verificar
   * @returns {boolean} - true si el estado es "pendiente" o "activo"
   */
  static isPending(estado) {
    return this.equals(estado, 'pendiente') || 
           this.equals(estado, 'en_produccion') || 
           this.equals(estado, 'en_proceso') ||
           this.equals(estado, 'activo');
  }

  /**
   * Retorna color Tailwind para un estado
   * Útil para colores de badge sin necesidad de CSS
   * 
   * @param {string} estado - Estado
   * @returns {string} - Clase de color Tailwind
   */
  static getColorClass(estado) {
    if (this.isCompleted(estado)) return 'bg-green-100 text-green-800';
    if (this.isPending(estado)) return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
  }
}

// Exportar para uso como módulo o global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = StatusFormatter;
} else if (typeof window !== 'undefined') {
  window.StatusFormatter = StatusFormatter;
}
