/**
 * Infrastructure Utility: ModalUtils
 * 
 * Centraliza operaciones comunes de modales (abrir/cerrar/styled show).
 * Evita duplicación de lógica de manipulación del DOM.
 */

class ModalUtils {
  static lockBackgroundScroll() {
    document.documentElement.classList.add('modal-scroll-lock');
    document.body.classList.add('modal-scroll-lock');
  }

  static unlockBackgroundScrollIfNoOverlayOpen() {
    const hasOpenSelector = !!document.querySelector('#trackingPrendasSelectorOverlay.show');
    if (hasOpenSelector) return;
    document.documentElement.classList.remove('modal-scroll-lock');
    document.body.classList.remove('modal-scroll-lock');
  }
  /**
   * Abrir un modal con fuerza de estilo (display flex con !important).
   * Usado para modales que necesitan override de CSS.
   * @param {String} modalId - ID del elemento modal
   * @param {Function} onOpen - Callback opcional después de abrir
   */
  static openWithForce(modalId, onOpen) {
    const modal = document.getElementById(modalId);
    if (!modal) return false;

    modal.classList.add('show');
    modal.style.setProperty('display', 'flex', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    modal.style.setProperty('z-index', '10000000', 'important');
    if (modalId === 'trackingPrendasSelectorOverlay') {
      ModalUtils.lockBackgroundScroll();
    }

    if (typeof onOpen === 'function') {
      onOpen();
    }
    return true;
  }

  /**
   * Abrir un modal simple (solo agregar clase show).
   * @param {String} modalId - ID del elemento modal
   * @param {Function} onOpen - Callback opcional después de abrir
   */
  static open(modalId, onOpen) {
    const modal = document.getElementById(modalId);
    if (!modal) return false;

    modal.classList.add('show');
    if (modalId === 'trackingPrendasSelectorOverlay') {
      ModalUtils.lockBackgroundScroll();
    }

    if (typeof onOpen === 'function') {
      onOpen();
    }
    return true;
  }

  /**
   * Cerrar un modal.
   * @param {String} modalId - ID del elemento modal
   * @param {Function} onClose - Callback opcional después de cerrar
   */
  static close(modalId, onClose) {
    const modal = document.getElementById(modalId);
    if (!modal) return false;

    modal.classList.remove('show');
    modal.style.display = 'none';
    if (modalId === 'trackingPrendasSelectorOverlay') {
      ModalUtils.unlockBackgroundScrollIfNoOverlayOpen();
    }

    if (typeof onClose === 'function') {
      onClose();
    }
    return true;
  }

  /**
   * Toggle de visibilidad (mostrar X milisegundos luego ocultar).
   * Útil para mensajes temporales.
   * @param {String} modalId - ID del elemento modal
   * @param {Number} durationMs - Duración en milisegundos (default 3000)
   */
  static showTemporary(modalId, durationMs = 3000) {
    const modal = document.getElementById(modalId);
    if (!modal) return false;

    modal.classList.add('show');
    modal.style.display = 'block';

    setTimeout(() => {
      modal.classList.remove('show');
      modal.style.display = 'none';
    }, durationMs);
    return true;
  }

  /**
   * Verificar si un modal está visible.
   * @param {String} modalId - ID del elemento modal
   * @returns {Boolean}
   */
  static isOpen(modalId) {
    const modal = document.getElementById(modalId);
    return modal ? modal.classList.contains('show') : false;
  }
}

export default ModalUtils;
