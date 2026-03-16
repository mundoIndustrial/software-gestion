/**
 * Validation Service - Centralización de todas las validaciones de negocio
 * 
 * ARQUITECTURA: Validation Layer Pattern  
 * - Responsabilidad única: validar datos
 * - SRP (Single Responsibility Principle) ✓
 * - DRY (Don't Repeat Yourself) ✓
 * - Reutilizable en múltiples contextos
 * 
 * USO:
 * ValidationService.proceso.esValido(area, encargado);
 * ValidationService.orden.tieneData();
 * const error = ValidationService.proceso.validar(...);
 */

const ValidationService = {
  /**
   * Validaciones para procesos
   */
  proceso: {
    /**
     * Validar que los datos del proceso sean válidos
     * @param {string} area - Área del proceso
     * @param {string} encargado - Nombre del encargado
     * @param {Object} currentPrendaData - Datos de la prenda actual
     * @param {Object} currentOrderData - Datos del pedido actual
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    validar: function(area, encargado, currentPrendaData, currentOrderData) {
      console.log('[ValidationService.proceso.validar] Validando:', { area, encargado });

      // Validar área
      if (!area || !area.trim()) {
        return {
          isValid: false,
          error: 'Por favor selecciona un área/proceso',
          field: 'area'
        };
      }

      // Validar encargado
      if (!encargado || !encargado.trim()) {
        return {
          isValid: false,
          error: 'Por favor ingresa el nombre del encargado',
          field: 'encargado'
        };
      }

      // Validar que hay datos de prenda y orden
      if (!currentPrendaData || !currentOrderData) {
        return {
          isValid: false,
          error: 'No hay datos de la prenda o pedido',
          field: 'datos'
        };
      }

      // Validar que la orden tiene número de pedido
      if (!currentOrderData.numero_pedido) {
        return {
          isValid: false,
          error: 'No hay número de pedido',
          field: 'orden'
        };
      }

      // Todo OK
      return {
        isValid: true,
        error: null
      };
    },

    /**
     * Validar que exista prenda y orden actual
     * @param {Object} currentPrendaData - Datos de prenda
     * @param {Object} currentOrderData - Datos de orden
     * @returns {boolean} Validación exitosa
     */
    tieneData: function(currentPrendaData, currentOrderData) {
      return !!(currentPrendaData && currentOrderData && currentOrderData.numero_pedido);
    },

    /**
     * Validar encargado (solo formato)
     * @param {string} encargado - Nombre del encargado
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    validarEncargado: function(encargado) {
      if (!encargado || !encargado.trim()) {
        return {
          isValid: false,
          error: 'El encargado no puede estar vacío'
        };
      }

      if (encargado.trim().length < 2) {
        return {
          isValid: false,
          error: 'El encargado debe tener al menos 2 caracteres'
        };
      }

      return {
        isValid: true,
        error: null
      };
    },

    /**
     * Validar área (solo formato)
     * @param {string} area - Nombre del área
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    validarArea: function(area) {
      if (!area || !area.trim()) {
        return {
          isValid: false,
          error: 'El área no puede estar vacía'
        };
      }

      return {
        isValid: true,
        error: null
      };
    }
  },

  /**
   * Validaciones para órdenes
   */
  orden: {
    /**
     * Verificar que hay datos de orden
     * @returns {boolean}
     */
    tieneData: function() {
      return !!(window.currentOrderData && window.currentOrderData.numero_pedido);
    },

    /**
     * Verificar que hay ID de orden
     * @returns {boolean}
     */
    tieneId: function() {
      return !!(window.currentOrderData && window.currentOrderData.id);
    },

    /**
     * Validar que la orden tiene todos los datos necesarios
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    validar: function() {
      if (!window.currentOrderData) {
        return {
          isValid: false,
          error: 'No hay datos de la orden'
        };
      }

      if (!window.currentOrderData.numero_pedido) {
        return {
          isValid: false,
          error: 'La orden no tiene número de pedido'
        };
      }

      if (!window.currentOrderData.id) {
        return {
          isValid: false,
          error: 'La orden no tiene ID'
        };
      }

      return {
        isValid: true,
        error: null
      };
    }
  },

  /**
   * Validaciones para prendas
   */
  prenda: {
    /**
     * Verificar que hay datos de prenda
     * @returns {boolean}
     */
    tieneData: function() {
      return !!(window.currentPrendaData && window.currentPrendaData.id);
    },

    /**
     * Verificar que hay ID de prenda
     * @returns {boolean}
     */
    tieneId: function() {
      return !!(window.currentPrendaData && window.currentPrendaData.id);
    },

    /**
     * Validar que la prenda tiene todos los datos necesarios
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    validar: function() {
      if (!window.currentPrendaData) {
        return {
          isValid: false,
          error: 'No hay datos de la prenda'
        };
      }

      if (!window.currentPrendaData.id) {
        return {
          isValid: false,
          error: 'La prenda no tiene ID'
        };
      }

      return {
        isValid: true,
        error: null
      };
    }
  },

  /**
   * Validaciones generales/utilidades
   */
  utils: {
    /**
     * Validar que una cadena no está vacía
     * @param {string} value - Valor a validar
     * @param {string} fieldName - Nombre del campo (para mensaje)
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    noEstaVacio: function(value, fieldName = 'Campo') {
      if (!value || !value.trim()) {
        return {
          isValid: false,
          error: `${fieldName} no puede estar vacío`
        };
      }
      return {
        isValid: true,
        error: null
      };
    },

    /**
     * Validar longitud mínima
     * @param {string} value - Valor a validar
     * @param {number} minLength - Longitud mínima
     * @param {string} fieldName - Nombre del campo
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    longitudMinima: function(value, minLength, fieldName = 'Campo') {
      if (!value || value.trim().length < minLength) {
        return {
          isValid: false,
          error: `${fieldName} debe tener al menos ${minLength} caracteres`
        };
      }
      return {
        isValid: true,
        error: null
      };
    },

    /**
     * Validar que un ID es válido (número > 0)
     * @param {number} id - ID a validar
     * @param {string} fieldName - Nombre del campo
     * @returns {Object} { isValid: boolean, error: string|null }
     */
    idValido: function(id, fieldName = 'ID') {
      if (!id || id <= 0) {
        return {
          isValid: false,
          error: `${fieldName} inválido`
        };
      }
      return {
        isValid: true,
        error: null
      };
    }
  }
};

// Exportar para uso global
if (typeof window !== 'undefined') {
  window.ValidationService = ValidationService;
}
