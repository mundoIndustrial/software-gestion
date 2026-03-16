/**
 * ModalHelper - Utilidad centralizada para gestión de modales
 * Sigue patrón: Single Responsibility Principle (SRP)
 * 
 * Responsabilidad única: Encapsular lógica repetida de modales
 * Elimina duplicación de .setProperty('display', 'flex', 'important') etc.
 */

class ModalHelper {
  /**
   * Estilos base para que un modal sea visible
   * Se aplican con !important para asegurar que no sean sobrescritos
   * @type {Object}
   */
  static MODAL_VISIBLE_STYLES = {
    'display': 'flex',
    'visibility': 'visible',
    'opacity': '1'
  };

  static MODAL_HIDDEN_STYLES = {
    'display': 'none'
  };

  /**
   * Abre/muestra un modal con los estilos necesarios
   * 
   * @param {string} modalId - ID del modal a abrir
   * @param {number} zIndex - z-index del modal (default: 9999)
   * @param {boolean} addClass - Agregar clase 'show' (default: true)
   * @returns {boolean} - true si fue exitoso
   */
  static open(modalId, zIndex = 9999, addClass = true) {
    try {
      if (!modalId || typeof modalId !== 'string') {
        return false;
      }

      const modal = document.getElementById(modalId);
      if (!modal) {
        console.warn(`[ModalHelper.open] Modal not found: ${modalId}`);
        return false;
      }

      // Agregar clase show si se solicita
      if (addClass) {
        modal.classList.add('show');
      }

      // Aplicar estilos con !important
      Object.entries(this.MODAL_VISIBLE_STYLES).forEach(([property, value]) => {
        modal.style.setProperty(property, value, 'important');
      });

      // Establecer z-index
      modal.style.setProperty('z-index', String(zIndex), 'important');

      console.log(`[ModalHelper.open] Modal opened: ${modalId}`);
      return true;
    } catch (error) {
      console.warn('[ModalHelper.open] Error opening modal:', error);
      return false;
    }
  }

  /**
   * Cierra/oculta un modal
   * 
   * @param {string} modalId - ID del modal a cerrar
   * @param {boolean} removeClass - Remover clase 'show' (default: true)
   * @returns {boolean} - true si fue exitoso
   */
  static close(modalId, removeClass = true) {
    try {
      if (!modalId || typeof modalId !== 'string') {
        return false;
      }

      const modal = document.getElementById(modalId);
      if (!modal) {
        return false;
      }

      // Remover clase show si se solicita
      if (removeClass) {
        modal.classList.remove('show');
      }

      // Aplicar estilos de ocultamiento
      Object.entries(this.MODAL_HIDDEN_STYLES).forEach(([property, value]) => {
        modal.style.setProperty(property, value, 'important');
      });

      console.log(`[ModalHelper.close] Modal closed: ${modalId}`);
      return true;
    } catch (error) {
      console.warn('[ModalHelper.close] Error closing modal:', error);
      return false;
    }
  }

  /**
   * Cambia el estado visible/oculto de un modal
   * 
   * @param {string} modalId - ID del modal
   * @param {boolean} visible - true para mostrar, false para ocultar
   * @param {number} zIndex - z-index (solo si visible=true)
   * @returns {boolean} - true si fue exitoso
   */
  static toggle(modalId, visible, zIndex = 9999) {
    return visible ? this.open(modalId, zIndex) : this.close(modalId);
  }

  /**
   * Abre múltiples modales a la vez
   * Útil cuando se usan modales con overlay
   * 
   * @param {Array} modalIds - Array de IDs de modales
   * @param {number} baseZIndex - z-index base (se incrementa para cada uno)
   * @returns {number} - Cantidad de modales abiertos exitosamente
   */
  static openMultiple(modalIds, baseZIndex = 9999) {
    try {
      if (!Array.isArray(modalIds)) return 0;

      let count = 0;
      modalIds.forEach((modalId, index) => {
        if (this.open(modalId, baseZIndex + index)) {
          count++;
        }
      });

      return count;
    } catch (error) {
      console.warn('[ModalHelper.openMultiple] Error:', error);
      return 0;
    }
  }

  /**
   * Cierra múltiples modales a la vez
   * 
   * @param {Array} modalIds - Array de IDs de modales
   * @returns {number} - Cantidad de modales cerrados exitosamente
   */
  static closeMultiple(modalIds) {
    try {
      if (!Array.isArray(modalIds)) return 0;

      let count = 0;
      modalIds.forEach((modalId) => {
        if (this.close(modalId)) {
          count++;
        }
      });

      return count;
    } catch (error) {
      console.warn('[ModalHelper.closeMultiple] Error:', error);
      return 0;
    }
  }

  /**
   * Configura el listener para cerrar un modal con tecla ESC
   * 
   * @param {string} modalId - ID del modal
   * @param {Function} onClose - Callback opcional al cerrar
   * @returns {boolean} - true si fue exitoso
   */
  static setupEscapeListener(modalId, onClose = null) {
    try {
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          const modal = document.getElementById(modalId);
          if (modal && modal.style.display !== 'none') {
            this.close(modalId);
            if (typeof onClose === 'function') {
              onClose();
            }
          }
        }
      });

      return true;
    } catch (error) {
      console.warn('[ModalHelper.setupEscapeListener] Error:', error);
      return false;
    }
  }

  /**
   * Configura un overlay para cerrar su modal asociado
   * 
   * @param {string} overlayId - ID del overlay
   * @param {string} modalId - ID del modal a cerrar
   * @returns {boolean} - true si fue exitoso
   */
  static setupOverlayClose(overlayId, modalId) {
    try {
      const overlay = document.getElementById(overlayId);
      if (!overlay) return false;

      overlay.addEventListener('click', () => {
        this.close(modalId);
      });

      return true;
    } catch (error) {
      console.warn('[ModalHelper.setupOverlayClose] Error:', error);
      return false;
    }
  }

  /**
   * Verifica si un modal está visible
   * 
   * @param {string} modalId - ID del modal
   * @returns {boolean} - true si el modal está visible
   */
  static isOpen(modalId) {
    try {
      const modal = document.getElementById(modalId);
      if (!modal) return false;

      const style = window.getComputedStyle(modal);
      return style.display !== 'none' && style.visibility !== 'hidden';
    } catch (error) {
      console.warn('[ModalHelper.isOpen] Error:', error);
      return false;
    }
  }

  /**
   * Obtiene el contenedor del modal para manipulación adicional
   * 
   * @param {string} modalId - ID del modal
   * @returns {HTMLElement|null} - El elemento del modal o null
   */
  static getModal(modalId) {
    try {
      return document.getElementById(modalId);
    } catch (error) {
      console.warn('[ModalHelper.getModal] Error:', error);
      return null;
    }
  }

  /**
   * Aplica estilos de modal de dialog (ventana flotante)
   * Útil para modales centrados
   * 
   * @param {string} modalId - ID del modal
   * @param {number} zIndex - z-index
   * @returns {boolean} - true si fue exitoso
   */
  static applyDialogStyles(modalId, zIndex = 9999) {
    try {
      const modal = document.getElementById(modalId);
      if (!modal) return false;

      const dialogStyles = {
        'position': 'fixed',
        'top': '0',
        'left': '0',
        'width': '100vw',
        'height': '100vh',
        'display': 'flex',
        'align-items': 'center',
        'justify-content': 'center',
        'background': 'rgba(0, 0, 0, 0.5)',
        'z-index': String(zIndex)
      };

      Object.entries(dialogStyles).forEach(([property, value]) => {
        modal.style.setProperty(property, value, 'important');
      });

      return true;
    } catch (error) {
      console.warn('[ModalHelper.applyDialogStyles] Error:', error);
      return false;
    }
  }

  /**
   * Limpia todos los estilos inline de un modal
   * Útil para resetear después de usos previos
   * 
   * @param {string} modalId - ID del modal
   * @returns {boolean} - true si fue exitoso
   */
  static clearStyles(modalId) {
    try {
      const modal = document.getElementById(modalId);
      if (!modal) return false;

      modal.setAttribute('style', '');
      return true;
    } catch (error) {
      console.warn('[ModalHelper.clearStyles] Error:', error);
      return false;
    }
  }
}

// Exportar para uso como módulo o global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ModalHelper;
} else if (typeof window !== 'undefined') {
  window.ModalHelper = ModalHelper;
}
