/**
 * MODAL EVENT BINDER
 * 
 * Responsabilidad: Patron reutilizable para bindear eventos de modales
 * Elimina duplicidad en setupAddProcesoModalListeners y setupConfirmDeleteModalListeners
 * 
 * ANTES: Busqueda manual de elementos + binding onclick en multiples funciones
 * Despues: Interfaz declarativa y reutilizable
 * 
 * Arquitectura: DIP + Strategy Pattern
 */

export class ModalEventBinder {
  /**
   * @param {string} modalId - ID del modal (ej: 'addProcesoModal')
   * @param {Object} options - Opciones de configuracion
   */
  constructor(modalId, options = {}) {
    this.modalId = modalId;
    this.boundElements = new Map();
    this.logger = options.logger || console;
  }

  /**
   * Obtener elemento por selectores alternativos
   * Intenta por ID primero, luego por class si falla
   * @private
   */
  _querySelector(selector) {
    try {
      // Si es un ID (comienza con #)
      if (selector.startsWith('#')) {
        return document.getElementById(selector.slice(1));
      }
      // Si es una clase o selector CSS
      return document.querySelector(selector);
    } catch (error) {
      return null;
    }
  }

  /**
   * Bindear botones de cerrar modal (X, Cancel)
   * @param {Object} config - { closeButtonId, cancelButtonId, overlaySelector, callback }
   * @returns {ModalEventBinder} this (para chainig)
   */
  bindCloseButtons(config = {}) {
    const {
      closeButtonId = `close${this.modalId.charAt(0).toUpperCase() + this.modalId.slice(1)}`,
      cancelButtonId = `btnCancel${this.modalId.charAt(0).toUpperCase() + this.modalId.slice(1)}`,
      overlaySelector = `.${this.modalId}-overlay`,
      callback = null
    } = config;

    // Boton X (close)
    const closeBtn = this._querySelector(`#${closeButtonId}`);
    if (closeBtn) {
      closeBtn.onclick = (e) => {
        e.preventDefault();
        this._executeCallback(callback);
      };
      this.boundElements.set(`${closeButtonId}:close`, closeBtn);
      this.logger.log(`[ModalEventBinder]  Boton cerrar bind para: ${closeButtonId}`);
    }

    // Boton Cancel
    const cancelBtn = this._querySelector(`#${cancelButtonId}`);
    if (cancelBtn) {
      cancelBtn.onclick = (e) => {
        e.preventDefault();
        this._executeCallback(callback);
      };
      this.boundElements.set(`${cancelButtonId}:cancel`, cancelBtn);
      this.logger.log(`[ModalEventBinder]  Boton cancelar bind para: ${cancelButtonId}`);
    }

    // Overlay
    const overlay = this._querySelector(overlaySelector);
    if (overlay) {
      overlay.onclick = (e) => {
        if (e.target === overlay) {
          e.preventDefault();
          this._executeCallback(callback);
        }
      };
      this.boundElements.set(`${overlaySelector}:overlay`, overlay);
      this.logger.log(`[ModalEventBinder]  Overlay bind para: ${overlaySelector}`);
    }

    return this;
  }

  /**
   * Bindear boton de accion principal (confirmacion, agregar, etc.)
   * @param {Object} config - { buttonId, callback, loadingConfig }
   * @returns {ModalEventBinder} this (para chaining)
   */
  bindActionButton(config = {}) {
    const {
      buttonId = `btnConfirm${this.modalId.charAt(0).toUpperCase() + this.modalId.slice(1)}`,
      callback = null,
      loadingConfig = null
    } = config;

    const btn = this._querySelector(`#${buttonId}`);
    if (btn) {
      btn.onclick = async (e) => {
        e.preventDefault();

        // Si hay config de loading, mostrar indicador
        if (loadingConfig) {
          this._setButtonLoading(btn, true, loadingConfig);
        }

        try {
          await this._executeCallback(callback);
        } finally {
          // Restaurar estado del boton
          if (loadingConfig) {
            this._setButtonLoading(btn, false, loadingConfig);
          }
        }
      };
      this.boundElements.set(`${buttonId}:action`, btn);
      this.logger.log(`[ModalEventBinder]  Boton de accion bind para: ${buttonId}`);
    }

    return this;
  }

  /**
   * Bindear multiples botones a traves de selectores
   * @param {Object[]} buttons - Array de configuraciones {selector, handler, event}
   * @returns {ModalEventBinder} this (para chaining)
   */
  bindButtons(buttons = []) {
    buttons.forEach((btn) => {
      const {
        selector,
        handler,
        event = 'click'
      } = btn;

      const element = this._querySelector(selector);
      if (element) {
        const bindingKey = `${this.modalId}:${selector}:${event}`;
        if (!element.__modalBinderHandlers) {
          element.__modalBinderHandlers = {};
        }
        if (element.__modalBinderHandlers[bindingKey]) {
          element.removeEventListener(event, element.__modalBinderHandlers[bindingKey]);
        }

        const handlerFn = (e) => {
          e.preventDefault();
          e.stopPropagation();
          this._executeCallback(handler);
        };

        element.addEventListener(event, handlerFn);
        element.__modalBinderHandlers[bindingKey] = handlerFn;
        this.boundElements.set(`${selector}:${event}`, element);
        this.logger.log(`[ModalEventBinder] bind para: ${selector} (${event})`);
      }
    });

    return this;
  }

  /**
   * Bindear selector dinamico (dropdown, autocomplete, etc.)
   * @param {Object} config - { triggerId, menuSelector, itemSelector, onSelect, onToggle }
   * @returns {ModalEventBinder} this (para chaining)
   */
  bindDynamicSelector(config = {}) {
    const {
      triggerId,
      menuSelector,
      itemSelector,
      onSelect = null,
      onToggle = null
    } = config;

    const trigger = this._querySelector(`#${triggerId}`);
    const menu = this._querySelector(menuSelector);

    if (!trigger || !menu) return this;

    // Toggle menu al hacer clic en trigger
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      const isOpen = menu.style.display !== 'none' && menu.style.display !== '';
      if (isOpen) {
        menu.style.display = 'none';
      } else {
        menu.style.display = 'block';
      }

      this._executeCallback(onToggle);
    });

    // Click en items del menu
    menu.addEventListener('click', (e) => {
      const item = e.target.closest(itemSelector);
      if (item) {
        e.preventDefault();
        e.stopPropagation();
        menu.style.display = 'none';

        this._executeCallback(onSelect, [item]);
      }
    });

    // Cerrar menu al hacer clic afuera
    document.addEventListener('click', (e) => {
      if (!trigger.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = 'none';
      }
    });

    // Cerrar menu con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        menu.style.display = 'none';
      }
    });

    this.boundElements.set(`${triggerId}:selector`, { trigger, menu });
    this.logger.log(`[ModalEventBinder]  Selector dinamico bind para: ${triggerId}`);

    return this;
  }

  /**
   * Ejecutar callback (funcion o async)
   * @private
   */
  async _executeCallback(callback, args = []) {
    if (typeof callback === 'function') {
      try {
        const result = callback(...args);
        if (result instanceof Promise) {
          await result;
        }
      } catch (error) {
        this.logger.error('[ModalEventBinder] Error en callback:', error);
      }
    }
  }

  /**
   * Establecer estado de loading del boton
   * @private
   */
  _setButtonLoading(btn, isLoading, loadingConfig = {}) {
    const { contentId, loadingId } = loadingConfig;

    if (contentId || loadingId) {
      const content = document.getElementById(contentId);
      const loading = document.getElementById(loadingId);

      if (content && loading) {
        if (isLoading) {
          content.style.display = 'none';
          loading.style.display = 'flex';
          btn.disabled = true;
        } else {
          content.style.display = 'flex';
          loading.style.display = 'none';
          btn.disabled = false;
        }
      }
    } else {
      // Fallback: solo deshabilitar
      btn.disabled = isLoading;
    }
  }

  /**
   * Desbindear todos los eventos (limpieza)
   * @returns {void}
   */
  unbind() {
    this.boundElements.forEach((element, key) => {
      element.onclick = null;
      element.remove?.();
    });
    this.boundElements.clear();
    this.logger.log(`[ModalEventBinder]  Todos los eventos desbindeados para: ${this.modalId}`);
  }

  /**
   * Obtener estado de elementos bindeados
   * @returns {number} Cantidad de elementos bindeados
   */
  getBoundElementsCount() {
    return this.boundElements.size;
  }
}

export default ModalEventBinder;

