/**
 * BUTTON LOADING MANAGER
 * 
 * Responsabilidad: Gestionar estado de loading de botones
 * Elimina duplicación de setButtonLoading() llamado en múltiples lugares
 * 
 * ANTES: Llamadas manuales a setButtonLoading() con queries DOM repetidas
 * DESPUÉS: Instancia única reutilizable con cache de elementos
 * 
 * Arquitectura: DIP + Cache pattern
 */

export class ButtonLoadingManager {
  /**
   * @param {string} buttonId - ID del botón
   * @param {Object} config - { contentId, loadingId, spinnerClass }
   */
  constructor(buttonId, config = {}) {
    this.buttonId = buttonId;
    this.config = {
      contentId: config.contentId || null,
      loadingId: config.loadingId || null,
      spinnerClass: config.spinnerClass || 'spin',
      disableButton: config.disableButton !== false, // true por defecto
      ...config
    };

    // Cache de elementos para evitar queries DOM repetidas
    this.button = null;
    this.content = null;
    this.loading = null;
    this.isLoading = false;

    this._initializeElements();
  }

  /**
   * Inicializar referencias a elementos DOM
   * @private
   */
  _initializeElements() {
    try {
      this.button = document.getElementById(this.buttonId);
      if (this.config.contentId) {
        this.content = document.getElementById(this.config.contentId);
      }
      if (this.config.loadingId) {
        this.loading = document.getElementById(this.config.loadingId);
      }
    } catch (error) {
      console.error('[ButtonLoadingManager] Error inicializando elementos:', error);
    }
  }

  /**
   * Establecer estado de loading
   * @param {boolean} state - true para mostrar spinner, false para ocultar
   * @returns {boolean} true si se aplicó correctamente
   */
  setLoading(state = true) {
    try {
      // Re-inicializar si los elementos no existen
      if (!this.button) {
        this._initializeElements();
      }

      if (!this.button) return false;

      this.isLoading = state;

      if (state) {
        // Mostrar spinner
        if (this.content) this.content.style.display = 'none';
        if (this.loading) this.loading.style.display = 'flex';

        if (this.config.disableButton) {
          this.button.disabled = true;
          this.button.style.cursor = 'not-allowed';
          this.button.style.opacity = '0.6';
        }
      } else {
        // Ocultar spinner
        if (this.content) this.content.style.display = 'flex';
        if (this.loading) this.loading.style.display = 'none';

        if (this.config.disableButton) {
          this.button.disabled = false;
          this.button.style.cursor = 'pointer';
          this.button.style.opacity = '1';
        }
      }

      return true;
    } catch (error) {
      console.error('[ButtonLoadingManager.setLoading] Error:', error);
      return false;
    }
  }

  /**
   * Ejecutar función asincrónica manteniendo estado de loading
   * Útil para envolver llamadas a API
   * @param {Function} asyncFn - Función asincrónica a ejecutar
   * @returns {Promise<any>} Resultado de la función
   */
  async executeAsync(asyncFn) {
    try {
      this.setLoading(true);
      const result = await asyncFn();
      return result;
    } catch (error) {
      throw error;
    } finally {
      this.setLoading(false);
    }
  }

  /**
   * Establecer texto del botón
   * @param {string} text - Texto a mostrar
   * @returns {boolean} true si se estableció correcto
   */
  setText(text) {
    try {
      if (this.button) {
        this.button.textContent = text;
        return true;
      }
      return false;
    } catch (error) {
      console.error('[ButtonLoadingManager.setText] Error:', error);
      return false;
    }
  }

  /**
   * Obtener texto actual del botón
   * @returns {string} Texto del botón
   */
  getText() {
    return this.button?.textContent || '';
  }

  /**
   * Establecer si el botón debe estar habilitado/deshabilitado
   * @param {boolean} enabled - true para habilitar
   * @returns {boolean} true si se estableció correctamente
   */
  setEnabled(enabled = true) {
    try {
      if (this.button) {
        this.button.disabled = !enabled;
        this.button.style.cursor = enabled ? 'pointer' : 'not-allowed';
        this.button.style.opacity = enabled ? '1' : '0.6';
        return true;
      }
      return false;
    } catch (error) {
      console.error('[ButtonLoadingManager.setEnabled] Error:', error);
      return false;
    }
  }

  /**
   * Agregar clase CSS al botón
   * @param {string} className - Clase a agregar
   * @returns {boolean} true si se agregó correctamente
   */
  addClass(className) {
    try {
      if (this.button) {
        this.button.classList.add(className);
        return true;
      }
      return false;
    } catch (error) {
      console.error('[ButtonLoadingManager.addClass] Error:', error);
      return false;
    }
  }

  /**
   * Remover clase CSS del botón
   * @param {string} className - Clase a remover
   * @returns {boolean} true si se removió correctamente
   */
  removeClass(className) {
    try {
      if (this.button) {
        this.button.classList.remove(className);
        return true;
      }
      return false;
    } catch (error) {
      console.error('[ButtonLoadingManager.removeClass] Error:', error);
      return false;
    }
  }

  /**
   * Verificar si el botón está en estado de loading
   * @returns {boolean} true si está cargando
   */
  isCurrentlyLoading() {
    return this.isLoading;
  }

  /**
   * Resetear botón a estado inicial
   * @returns {boolean} true si se reseteó correctamente
   */
  reset() {
    try {
      this.setLoading(false);
      this.setText('');
      this.setEnabled(true);
      return true;
    } catch (error) {
      console.error('[ButtonLoadingManager.reset] Error:', error);
      return false;
    }
  }

  /**
   * Mostrar mensaje de error en el botón (visual feedback)
   * @param {string} message - Mensaje de error
   * @param {number} duration - Duración en ms antes de resetear (0 = no resetear)
   * @returns {boolean} true si se mostró correctamente
   */
  showError(message, duration = 0) {
    try {
      if (this.button) {
        const originalText = this.getText();
        this.setText(message);
        this.addClass('btn-error');

        if (duration > 0) {
          setTimeout(() => {
            this.setText(originalText);
            this.removeClass('btn-error');
          }, duration);
        }

        return true;
      }
      return false;
    } catch (error) {
      console.error('[ButtonLoadingManager.showError] Error:', error);
      return false;
    }
  }

  /**
   * Mostrar mensaje de éxito en el botón (visual feedback)
   * @param {string} message - Mensaje de éxito
   * @param {number} duration - Duración en ms antes de resetear (0 = no resetear)
   * @returns {boolean} true si se mostró correctamente
   */
  showSuccess(message, duration = 0) {
    try {
      if (this.button) {
        const originalText = this.getText();
        this.setText(message);
        this.addClass('btn-success');

        if (duration > 0) {
          setTimeout(() => {
            this.setText(originalText);
            this.removeClass('btn-success');
          }, duration);
        }

        return true;
      }
      return false;
    } catch (error) {
      console.error('[ButtonLoadingManager.showSuccess] Error:', error);
      return false;
    }
  }

  /**
   * Obtener estado actual del botón
   * @returns {Object} Objeto con estado
   */
  getState() {
    return {
      buttonId: this.buttonId,
      isLoading: this.isLoading,
      isEnabled: this.button ? !this.button.disabled : null,
      text: this.getText(),
      exists: !!this.button
    };
  }
}

export default ButtonLoadingManager;
