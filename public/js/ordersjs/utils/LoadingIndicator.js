/**
 * Loading Indicator Helper
 * Maneja la visualización y ocultamiento de indicadores de carga en botones
 * Patrón: Un botón tiene 3 elementos:
 *   - btnContent: elemento con el contenido visible (icono + texto)
 *   - btnLoading: elemento con el spinner/loader
 *   - btn: el botón principal para deshabilitar
 * 
 * ARQUITECTURA: Utility Helper
 * - Responsabilidad única: gestionar estado de carga visual
 * - Sin dependencias externas
 * - Reutilizable en múltiples contextos
 */

const LoadingIndicator = {
  /**
   * Mostrar indicador de carga
   * @param {string} buttonId - ID del botón (ej: 'btnConfirmAddProceso')
   * @example
   * LoadingIndicator.show('btnConfirmAddProceso');
   */
  show: function(buttonId) {
    try {
      const btnContent = document.getElementById(buttonId + 'Content');
      const btnLoading = document.getElementById(buttonId + 'Loading');
      const btnConfirm = document.getElementById(buttonId);
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'none';
        btnLoading.style.display = 'flex';
        btnConfirm.disabled = true;
      } else {
        console.warn('[LoadingIndicator.show] Elementos no encontrados para:', buttonId, {
          hasContent: !!btnContent,
          hasLoading: !!btnLoading,
          hasButton: !!btnConfirm
        });
      }
    } catch (error) {
      console.error('[LoadingIndicator.show] Error:', error);
    }
  },

  /**
   * Ocultar indicador de carga
   * @param {string} buttonId - ID del botón (ej: 'btnConfirmAddProceso')
   * @example
   * LoadingIndicator.hide('btnConfirmAddProceso');
   */
  hide: function(buttonId) {
    try {
      const btnContent = document.getElementById(buttonId + 'Content');
      const btnLoading = document.getElementById(buttonId + 'Loading');
      const btnConfirm = document.getElementById(buttonId);
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'flex';
        btnLoading.style.display = 'none';
        btnConfirm.disabled = false;
      } else {
        console.warn('[LoadingIndicator.hide] Elementos no encontrados para:', buttonId, {
          hasContent: !!btnContent,  
          hasLoading: !!btnLoading,
          hasButton: !!btnConfirm
        });
      }
    } catch (error) {
      console.error('[LoadingIndicator.hide] Error:', error);
    }
  },

  /**
   * Ejecutar función con indicador de carga automático
   * Muestra el indicador, ejecuta la función, y lo oculta al terminar
   * @param {string} buttonId - ID del botón
   * @param {Function} asyncFunction - Función async a ejecutar
   * @returns {Promise} Resultado de la función
   * @example
   * await LoadingIndicator.withIndicator('btnSave', async () => {
   *   await saveData();
   * });
   */
  withIndicator: async function(buttonId, asyncFunction) {
    try {
      this.show(buttonId);
      const result = await asyncFunction();
      return result;
    } catch (error) {
      throw error;
    } finally {
      this.hide(buttonId);
    }
  },

  /**
   * Obtener estado actual del indicador
   * @param {string} buttonId - ID del botón
   * @returns {Object} Estado con propiedades: isLoading, isDisabled
   * @example
   * const state = LoadingIndicator.getState('btnSave');
   * if (state.isLoading) { ... }
   */
  getState: function(buttonId) {
    const btnConfirm = document.getElementById(buttonId);
    const btnLoading = document.getElementById(buttonId + 'Loading');
    
    return {
      isLoading: btnLoading ? btnLoading.style.display !== 'none' : false,
      isDisabled: btnConfirm ? btnConfirm.disabled : false
    };
  },

  /**
   * Resetear estado del indicador (útil para errores)
   * @param {string} buttonId - ID del botón
   * @example
   * LoadingIndicator.reset('btnSave');
   */
  reset: function(buttonId) {
    this.hide(buttonId);
  }
};

// Exportar para uso global
if (typeof window !== 'undefined') {
  window.LoadingIndicator = LoadingIndicator;
}
