/**
 * AreaResolver - Utilidad para resolución centralizada de áreas
 * Sigue patrón: Single Responsibility Principle (SRP)
 * 
 * Responsabilidad única: Resolver el área actual con lógica de prioridades
 * Centraliza la lógica que estaba duplicada en múltiples lugares
 */

class AreaResolver {
  /**
   * Resuelve el área actual con lógica de prioridades
   * 
   * Orden de prioridad:
   * 1. Area del último proceso (último_proceso_area)
   * 2. Area asignada directamente a la prenda
   * 3. Area del pedido actual (solo si no es recibos-costura)
   * 4. Valor por defecto "-"
   * 
   * @param {Object} prenda - Objeto con datos de la prenda
   * @param {Object} orderData - Datos del pedido (opcional)
   * @param {boolean} esRecibosCostura - true si estamos en vista recibos-costura
   * @returns {string} - Area resuelta
   */
  static resolve(prenda, orderData = null, esRecibosCostura = false) {
    try {
      // Validar entrada
      if (!prenda || typeof prenda !== 'object') {
        return '-';
      }

      // Prioridad 1: Area del ultimo proceso
      if (prenda.ultimo_proceso_area && this.isValidArea(prenda.ultimo_proceso_area)) {
        return prenda.ultimo_proceso_area;
      }

      // Prioridad 2: Area asignada a la prenda
      if (prenda.area && this.isValidArea(prenda.area)) {
        return prenda.area;
      }

      // Prioridad 3: Area del pedido (solo si NO es recibos-costura)
      if (
        !esRecibosCostura &&
        orderData &&
        orderData.area &&
        this.isValidArea(orderData.area)
      ) {
        return orderData.area;
      }

      return '-';
    } catch (error) {
      console.warn('[AreaResolver.resolve] Error resolving area:', error);
      return '-';
    }
  }

  /**
   * Resuelve el encargado (responsable del proceso) con lógica de prioridades
   * 
   * Orden de prioridad:
   * 1. Encargado del último proceso
   * 2. Encargado del data actual del consecutivo
   * 3. Valor por defecto "No asignado"
   * 
   * @param {Object} prenda - Datos de la prenda
   * @param {Object} consecutivoData - Datos del consecutivo actual (opcional)
   * @returns {string} - Encargado resuelto
   */
  static resolveEncargado(prenda, consecutivoData = null) {
    try {
      if (!prenda || typeof prenda !== 'object') {
        return 'No asignado';
      }

      // Prioridad 1: Encargado del ultimo proceso
      if (
        prenda.ultimo_proceso_encargado &&
        this.isValidName(prenda.ultimo_proceso_encargado)
      ) {
        return String(prenda.ultimo_proceso_encargado).toUpperCase();
      }

      // Prioridad 2: Encargado del consecutivo actual
      if (
        consecutivoData &&
        consecutivoData.encargado &&
        this.isValidName(consecutivoData.encargado)
      ) {
        return String(consecutivoData.encargado).toUpperCase();
      }

      return 'No asignado';
    } catch (error) {
      console.warn('[AreaResolver.resolveEncargado] Error resolving encargado:', error);
      return 'No asignado';
    }
  }

  /**
   * Resuelve el estado del proceso con validación
   * 
   * @param {Object} prenda - Datos de la prenda
   * @param {string} fallback - Valor por defecto
   * @returns {string} - Estado resuelto
   */
  static resolveEstado(prenda, fallback = 'Pendiente') {
    try {
      if (!prenda || typeof prenda !== 'object') {
        return fallback;
      }

      return prenda.ultimo_proceso_estado || fallback;
    } catch (error) {
      console.warn('[AreaResolver.resolveEstado] Error resolving estado:', error);
      return fallback;
    }
  }

  /**
   * Valida si un área es válida (no vacía ni "-")
   * Función helper interna
   * 
   * @param {any} area - Area a validar
   * @returns {boolean} - true si es válida
   */
  static isValidArea(area) {
    return (
      area &&
      typeof area === 'string' &&
      area.trim() !== '' &&
      area !== '-'
    );
  }

  /**
   * Valida si un nombre es válido (no vacío)
   * Función helper interna
   * 
   * @param {any} name - Nombre a validar
   * @returns {boolean} - true si es válido
   */
  static isValidName(name) {
    return (
      name &&
      typeof name === 'string' &&
      name.trim() !== '' &&
      name !== '-'
    );
  }

  /**
   * Obtiene información completa de la "área actual" para mostrar
   * Combina area y encargado en un objeto útil
   * 
   * @param {Object} prenda - Datos de la prenda
   * @param {Object} orderData - Datos del pedido
   * @param {Object} consecutivoData - Datos del consecutivo
   * @param {boolean} esRecibosCostura - ¿En recibos-costura?
   * @returns {Object} - {area, encargado, estado, tieneProcesoReal}
   */
  static getCompleteAreaInfo(
    prenda,
    orderData = null,
    consecutivoData = null,
    esRecibosCostura = false
  ) {
    try {
      const area = this.resolve(prenda, orderData, esRecibosCostura);
      const encargado = this.resolveEncargado(prenda, consecutivoData);
      const estado = this.resolveEstado(prenda);
      const tieneProcesoReal = Boolean(prenda?.ultimo_proceso_id);

      return {
        area,
        encargado,
        estado,
        tieneProcesoReal
      };
    } catch (error) {
      console.warn('[AreaResolver.getCompleteAreaInfo] Error:', error);
      return {
        area: '-',
        encargado: 'No asignado',
        estado: 'Pendiente',
        tieneProcesoReal: false
      };
    }
  }

  /**
   * Formatea un header de recibo con área y número
   * Ejemplo: "Recibo #123 - COSTURA"
   * 
   * @param {string} numeroRecibo - Número del recibo
   * @param {string} area - Area del proceso
   * @returns {string} - Header formateado
   */
  static getReciboHeader(numeroRecibo, area = null) {
    try {
      const numero = numeroRecibo || 'Sin recibo';

      if (!area || area === '-') {
        return numero;
      }

      return `${numero} - ${area}`;
    } catch (error) {
      console.warn('[AreaResolver.getReciboHeader] Error:', error);
      return 'Error';
    }
  }

  /**
   * Determina si una prenda está activa (tiene proceso en ejecución)
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {boolean} - true si está activa
   */
  static isActive(prenda) {
    try {
      if (!prenda) return false;

      const estado = this.resolveEstado(prenda);
      return estado !== 'Completado' && estado !== 'COMPLETADO';
    } catch (error) {
      console.warn('[AreaResolver.isActive] Error:', error);
      return false;
    }
  }
}

// Exportar para uso como módulo o global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = AreaResolver;
} else if (typeof window !== 'undefined') {
  window.AreaResolver = AreaResolver;
}
