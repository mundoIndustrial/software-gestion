/**
 * AREAS CONFIG SERVICE
 * 
 * Responsabilidad: Centralizar toda la lógica de configuración de áreas
 * Elimina duplicación donde se repite: orderState.getAreasConfig() + filtros
 * 
 * ANTES: Lógica dispersa en 3-4 lugares
 * DESPUÉS: Servicios centralizados
 * 
 * Arquitectura: DIP + Single Responsibility
 */

export class AreasConfigService {
  /**
   * @param {Object} orderState - Estado centralizado del pedido
   * @param {Object} config - Configuración por defecto
   */
  constructor(orderState, config = {}) {
    this.orderState = orderState;
    this.defaultConfig = {
      areasConSelectorDinamico: config.areasConSelectorDinamico || ['corte', 'costura'],
      areasQueRequierenEncargado: config.areasQueRequierenEncargado || ['corte', 'costura', 'control de calidad'],
      ...config
    };
  }

  /**
   * Obtener configuración de áreas del estado
   * @private
   * @returns {Object} Configuración de áreas
   */
  _getAreasConfig() {
    const areasConfig = this.orderState?.getAreasConfig?.();
    return {
      ...this.defaultConfig,
      ...areasConfig
    };
  }

  /**
   * Verificar si un área requiere selector dinámico de encargados
   * @param {string} area - Nombre del área
   * @returns {boolean} true si requiere selector dinámico
   */
  hasSelectForArea(area) {
    const config = this._getAreasConfig();
    const areaNormalizada = String(area || '').toLowerCase().trim();
    const areasConSelector = config.areasConSelectorDinamico || [];

    return areasConSelector.some(a =>
      areaNormalizada.includes(String(a).toLowerCase().trim())
    );
  }

  /**
   * Verificar si un área requiere campo de encargado
   * @param {string} area - Nombre del área
   * @returns {boolean} true si requiere encargado
   */
  requiresEncargado(area) {
    const config = this._getAreasConfig();
    const areaNormalizada = String(area || '').toLowerCase().trim();
    const areasQueRequieren = config.areasQueRequierenEncargado || [];

    return areasQueRequieren.some(a =>
      areaNormalizada.includes(String(a).toLowerCase().trim())
    );
  }

  /**
   * Obtener tipo de campo para un área
   * @param {string} area - Nombre del área
   * @returns {string} 'select' o 'input'
   */
  getEncargadoFieldType(area) {
    return this.hasSelectForArea(area) ? 'select' : 'input';
  }

  /**
   * Obtener lista de encargados disponibles para un área
   * (Este método será delegado a API, pero centraliza la lógica)
   * @param {string} area - Nombre del área
   * @returns {Promise<Array>} Lista de encargados
   */
  async getEncargadosForArea(area, apiService) {
    if (!apiService) {
      throw new Error('API service es requerido');
    }

    try {
      const encargados = await apiService.loadEncargados(area);
      return Array.isArray(encargados) ? encargados : [];
    } catch (error) {
      console.error('[AreasConfigService.getEncargadosForArea] Error:', error);
      throw error;
    }
  }

  /**
   * Obtener todas las áreas configuradas
   * @returns {Array} Lista de áreas
   */
  getAllAreas() {
    const config = this._getAreasConfig();
    const areas = new Set();

    // Agregar áreas que tienen selector dinámico
    if (config.areasConSelectorDinamico) {
      config.areasConSelectorDinamico.forEach(a => areas.add(a));
    }

    // Agregar áreas que requieren encargado
    if (config.areasQueRequierenEncargado) {
      config.areasQueRequierenEncargado.forEach(a => areas.add(a));
    }

    return Array.from(areas);
  }

  /**
   * Obtener configuración completa (debuggin)
   * @returns {Object} Configuración actual
   */
  getCompleteConfig() {
    return this._getAreasConfig();
  }

  /**
   * Validar si una área es válida
   * @param {string} area - Nombre del área
   * @returns {boolean} true si es válida
   */
  isValidArea(area) {
    const areaNormalizada = String(area || '').toLowerCase().trim();
    return areaNormalizada.length > 0;
  }
}

export default AreasConfigService;
