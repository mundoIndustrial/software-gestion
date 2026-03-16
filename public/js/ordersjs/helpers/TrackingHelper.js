/**
 * TrackingHelper - Utilidad centralizada para funciones específicas de tracking
 * Sigue patrón: Single Responsibility Principle (SRP)
 * 
 * Responsabilidad única: Encapsular lógica repetida y especializada de tracking
 */

class TrackingHelper {
  /**
   * Formatea un número de recibo COSTURA
   * Ejemplo: 123 -> "COSTURA #123"
   * 
   * @param {string|number} numeroRecibo - Número del recibo
   * @returns {string} - Recibo formateado (COSTURA #X)
   */
  static formatReciboCostura(numeroRecibo) {
    try {
      if (!numeroRecibo || numeroRecibo === '-') {
        return '-';
      }

      return `COSTURA #${numeroRecibo}`;
    } catch (error) {
      console.warn('[TrackingHelper.formatReciboCostura] Error:', error);
      return '-';
    }
  }

  /**
   * Resuelve el número de recibo COSTURA desde múltiples posibles fuentes
   * 
   * Prioridad:
   * 1. currentPrendaData.ultimo_recibo_numero
   * 2. Primera prenda del pedido
   * 3. Dato del fallback (parámetro)
   * 
   * @param {Object} prendaData - Datos de la prenda actual
   * @param {Object} orderData - Datos del pedido
   * @param {string} fallback - Valor por defecto
   * @returns {string} - Recibo formateado
   */
  static resolveReciboCostura(prendaData, orderData = null, fallback = '-') {
    try {
      // Prioridad 1: desde la prenda actual
      if (prendaData && prendaData.ultimo_recibo_numero) {
        return this.formatReciboCostura(prendaData.ultimo_recibo_numero);
      }

      // Prioridad 2: desde la primera prenda del pedido
      if (
        orderData &&
        orderData.prendas &&
        orderData.prendas.length > 0 &&
        orderData.prendas[0].ultimo_recibo_numero
      ) {
        return this.formatReciboCostura(orderData.prendas[0].ultimo_recibo_numero);
      }

      return fallback;
    } catch (error) {
      console.warn('[TrackingHelper.resolveReciboCostura] Error:', error);
      return fallback;
    }
  }

  /**
   * Obtiene una prenda enriquecida desde la lista global
   * Si la prenda tiene seguimientos incompletos, intenta completarla con datos globales
   * 
   * @param {Object} prenda - Prenda parcial
   * @param {Array} prendasGlobales - Array global de prendas (window.prendasData)
   * @returns {Object} - Prenda completa/enriquecida
   */
  static enrichPrenda(prenda, prendasGlobales = null) {
    try {
      if (!prenda || typeof prenda !== 'object') {
        return prenda;
      }

      const globalData = prendasGlobales || window.prendasData || [];
      if (!Array.isArray(globalData) || globalData.length === 0) {
        return prenda;
      }

      // Buscar si falta información de seguimiento
      const tieneSeguimiento = (
        (prenda.seguimientos_por_area && Object.keys(prenda.seguimientos_por_area).length > 0) ||
        (prenda.seguimientos && Object.keys(prenda.seguimientos).length > 0) ||
        (prenda.ultimo_recibo_numero && prenda.ultimo_recibo_numero !== '-')
      );

      if (tieneSeguimiento) {
        return prenda; // Ya tiene info suficiente
      }

      // Buscar en datos globales
      const prendaId = prenda?.id || prenda?.prenda_pedido_id;
      const prendaGlobal = globalData.find(p =>
        String(p?.id) === String(prendaId) || 
        String(p?.prenda_pedido_id) === String(prendaId)
      );

      if (prendaGlobal) {
        return Object.assign({}, prendaGlobal, prenda);
      }

      return prenda;
    } catch (error) {
      console.warn('[TrackingHelper.enrichPrenda] Error:', error);
      return prenda;
    }
  }

  /**
   * Determina si una prenda es de bodega (se saca, no se confecciona)
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {boolean} - true si es de bodega
   */
  static isDeBodega(prenda) {
    try {
      if (!prenda || typeof prenda !== 'object') {
        return false;
      }

      return prenda.de_bodega === true || prenda.de_bodega === 1;
    } catch (error) {
      console.warn('[TrackingHelper.isDeBodega] Error:', error);
      return false;
    }
  }

  /**
   * Obtiene información de procesos de una prenda en formato legible
   * Ejemplo: "COSTURA (PENDIENTE), SUBLIMADO (COMPLETADO)"
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {string} - Procesos formateados
   */
  static getProcesosInfo(prenda) {
    try {
      if (!prenda || typeof prenda !== 'object') {
        return '-';
      }

      // Intentar desde tipos_recibo_procesos (Backend moderno)
      if (prenda.tipos_recibo_procesos && prenda.tipos_recibo_procesos.length > 0) {
        return prenda.tipos_recibo_procesos
          .map(p => {
            const nombre = p.nombre || 'Proceso';
            const estado = (p.estado || 'PENDIENTE').replace(/_/g, ' ');
            return `${nombre} (${estado})`;
          })
          .join(', ');
      }

      // Fallback a procesos generales
      if (prenda.procesos && prenda.procesos.length > 0) {
        return prenda.procesos
          .map(p => {
            const tipoProceso = p.tipo_proceso;
            const nombre = tipoProceso?.nombre || 'Proceso';
            const estado = (p.estado || 'PENDIENTE').replace(/_/g, ' ');
            return `${nombre} (${estado})`;
          })
          .join(', ');
      }

      return '-';
    } catch (error) {
      console.warn('[TrackingHelper.getProcesosInfo] Error:', error);
      return '-';
    }
  }

  /**
   * Obtiene el primer recibo activo desde los consecutivos
   * O retorna el primero si no hay ninguno activo
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {Object|null} - Objeto recibo o null
   */
  static getActiveRecibo(prenda) {
    try {
      if (!prenda || !prenda.consecutivos || prenda.consecutivos.length === 0) {
        return null;
      }

      // Buscar recibo activo
      const reciboActivo = prenda.consecutivos.find(r => r.activo === 1);
      if (reciboActivo) {
        return reciboActivo;
      }

      // Fallback: primer recibo
      return prenda.consecutivos[0];
    } catch (error) {
      console.warn('[TrackingHelper.getActiveRecibo] Error:', error);
      return null;
    }
  }

  /**
   * Formatea información de recibo para display
   * Ejemplo: "COSTURA #123"
   * 
   * @param {Object} recibo - Objeto recibo
   * @returns {string} - Recibo formateado
   */
  static formatReciboDisplay(recibo) {
    try {
      if (!recibo || typeof recibo !== 'object') {
        return 'Sin recibo';
      }

      const tipo = recibo.tipo_recibo || 'RECIBO';
      const consecutivo = recibo.consecutivo_actual || recibo.numero || '';

      return consecutivo ? `${tipo} #${consecutivo}` : 'Sin recibo';
    } catch (error) {
      console.warn('[TrackingHelper.formatReciboDisplay] Error:', error);
      return 'Sin recibo';
    }
  }

  /**
   * Obtiene la información completa de la tabla de prendas
   * Útil para preparar datos antes de renderizar
   * 
   * @param {Array} prendas - Array de prendas
   * @param {Object} orderData - Datos del pedido actual
   * @returns {Array} - Prendas con info completa
   */
  static enrichPrendas(prendas, orderData = null) {
    try {
      if (!Array.isArray(prendas)) return [];

      return prendas.map(prenda => ({
        ...prenda,
        procesosInfo: this.getProcesosInfo(prenda),
        reciboCostura: this.resolveReciboCostura(prenda, orderData),
        isDeBodega: this.isDeBodega(prenda),
        activeRecibo: this.getActiveRecibo(prenda)
      }));
    } catch (error) {
      console.warn('[TrackingHelper.enrichPrendas] Error:', error);
      return prendas;
    }
  }

  /**
   * Determina si un botón de seguimiento debe estar desactivado
   * (Para prendas de bodega)
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {boolean} - true si debe estar disabled
   */
  static shouldDisableTrackingButton(prenda) {
    return this.isDeBodega(prenda);
  }

  /**
   * Obtiene el título para el botón de una prenda
   * Mensaje descriptivo cuando está desactivado
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {string} - Título del botón
   */
  static getTrackingButtonTitle(prenda) {
    if (this.isDeBodega(prenda)) {
      return 'Prenda de bodega - no disponible para seguimiento';
    }
    return 'Ver seguimiento detallado';
  }

  /**
   * Formatea título de prenda con badge de origen si aplica
   * 
   * @param {Object} prenda - Datos de la prenda
   * @returns {Object} - {nombre, badge, badgeClass}
   */
  static getPrendaDisplayInfo(prenda) {
    try {
      return {
        nombre: prenda?.nombre_prenda || `Prenda ${prenda?.id || 'N/A'}`,
        badge: this.isDeBodega(prenda) ? 'SE SACA DE BODEGA' : 'SE CONFECCIONA',
        badgeClass: this.isDeBodega(prenda) ? 'bodega-badge' : 'confeccion-badge'
      };
    } catch (error) {
      console.warn('[TrackingHelper.getPrendaDisplayInfo] Error:', error);
      return {
        nombre: 'Prenda',
        badge: 'Desconocido',
        badgeClass: 'unknown-badge'
      };
    }
  }

  /**
   * Obtiene estado agregado de múltiples prendas
   * Útil para mostrar salud general del pedido
   * 
   * @param {Array} prendas - Array de prendas
   * @returns {Object} - {totalPrendas, enProduccion, completadas, bodega}
   */
  static getAggregatedStatus(prendas) {
    try {
      if (!Array.isArray(prendas)) {
        return {
          totalPrendas: 0,
          enProduccion: 0,
          completadas: 0,
          bodega: 0
        };
      }

      return {
        totalPrendas: prendas.length,
        enProduccion: prendas.filter(p => !this.isDeBodega(p)).length,
        completadas: prendas.filter(p => p.ultimo_proceso_estado === 'Completado').length,
        bodega: prendas.filter(p => this.isDeBodega(p)).length
      };
    } catch (error) {
      console.warn('[TrackingHelper.getAggregatedStatus] Error:', error);
      return {
        totalPrendas: 0,
        enProduccion: 0,
        completadas: 0,
        bodega: 0
      };
    }
  }
}

// Exportar para uso como módulo o global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = TrackingHelper;
} else if (typeof window !== 'undefined') {
  window.TrackingHelper = TrackingHelper;
}
