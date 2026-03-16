/**
 * DOMManipulator - Utilidad centralizada para manipulación del DOM
 * Sigue patrón: Single Responsibility Principle (SRP)
 * 
 * Responsabilidad única: Encapsular operaciones del DOM
 * Beneficios: Fácil de testear, reutilizable, mantiene consistencia
 */

class DOMManipulator {
  /**
   * Cache de elementos encontrados para mejorar performance
   * @type {Map}
   */
  static elementCache = new Map();

  /**
   * Limpia el cache (llamar después de cambios significativos del DOM)
   */
  static clearCache() {
    this.elementCache.clear();
  }

  /**
   * Busca un elemento por ID con caching opcional
   * 
   * @param {string} elementId - ID del elemento
   * @param {boolean} useCache - Usar cache (default: true)
   * @returns {HTMLElement|null} - Elemento o null
   */
  static getElementById(elementId, useCache = true) {
    try {
      if (!elementId || typeof elementId !== 'string') return null;

      if (useCache && this.elementCache.has(elementId)) {
        return this.elementCache.get(elementId);
      }

      const element = document.getElementById(elementId);
      if (element && useCache) {
        this.elementCache.set(elementId, element);
      }

      return element;
    } catch (error) {
      console.warn('[DOMManipulator.getElementById] Error finding element:', error);
      return null;
    }
  }

  /**
   * Busca un elemento por selector CSS
   * 
   * @param {string} selector - Selector CSS
   * @returns {HTMLElement|null} - Primer elemento coincidente o null
   */
  static querySelector(selector) {
    try {
      if (!selector || typeof selector !== 'string') return null;
      return document.querySelector(selector);
    } catch (error) {
      console.warn('[DOMManipulator.querySelector] Error with selector:', error);
      return null;
    }
  }

  /**
   * Establece el contenido de texto de un elemento
   * Valida que el elemento exista antes de actuar
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} text - Texto a establecer
   * @param {string} defaultValue - Valor por defecto si el texto está vacío
   * @returns {boolean} - true si fue exitoso
   */
  static setText(elementId, text, defaultValue = '-') {
    try {
      const element = this.getElementById(elementId);
      if (!element) {
        console.warn(`[DOMManipulator.setText] Element not found: ${elementId}`);
        return false;
      }

      element.textContent = text || defaultValue;
      return true;
    } catch (error) {
      console.warn('[DOMManipulator.setText] Error setting text:', error);
      return false;
    }
  }

  /**
   * Establece el HTML interno de un elemento
   * ⚠️ Menos seguro que setText, solo usar con HTML de confianza
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} html - HTML a establecer
   * @returns {boolean} - true si fue exitoso
   */
  static setHTML(elementId, html) {
    try {
      const element = this.getElementById(elementId);
      if (!element) return false;

      element.innerHTML = html;
      return true;
    } catch (error) {
      console.warn('[DOMManipulator.setHTML] Error setting HTML:', error);
      return false;
    }
  }

  /**
   * Actualiza múltiples elementos con sus correspondientes valores
   * Útil para actualizar varias partes del DOM de una sola vez
   * 
   * @param {Object} updates - Objeto {elementId: value}
   * @param {string} defaultValue - Valor por defecto
   * @returns {number} - Cantidad de elementos actualizados exitosamente
   */
  static updateMultiple(updates, defaultValue = '-') {
    try {
      if (!updates || typeof updates !== 'object') return 0;

      let count = 0;
      Object.entries(updates).forEach(([elementId, value]) => {
        if (this.setText(elementId, value, defaultValue)) {
          count++;
        }
      });

      return count;
    } catch (error) {
      console.warn('[DOMManipulator.updateMultiple] Error:', error);
      return 0;
    }
  }

  /**
   * Establece múltiples estilos CSS en un elemento
   * 
   * @param {string} elementId - ID del elemento
   * @param {Object} styles - Objeto de estilos {propiedad: valor}
   * @param {boolean} important - Usar !important
   * @returns {boolean} - true si fue exitoso
   */
  static setStyles(elementId, styles, important = false) {
    try {
      const element = this.getElementById(elementId);
      if (!element) return false;

      Object.entries(styles).forEach(([property, value]) => {
        const importance = important ? 'important' : '';
        element.style.setProperty(property, value, importance);
      });

      return true;
    } catch (error) {
      console.warn('[DOMManipulator.setStyles] Error setting styles:', error);
      return false;
    }
  }

  /**
   * Muestra/oculta un elemento con consideraciones de display
   * 
   * @param {string} elementId - ID del elemento
   * @param {boolean} visible - true para mostrar, false para ocultar
   * @param {string} displayType - Tipo de display (flex, block, etc)
   * @returns {boolean} - true si fue exitoso
   */
  static setVisible(elementId, visible = true, displayType = 'flex') {
    try {
      const element = this.getElementById(elementId);
      if (!element) return false;

      if (visible) {
        element.classList.add('show');
        element.style.setProperty('display', displayType, 'important');
        element.style.setProperty('visibility', 'visible', 'important');
        element.style.setProperty('opacity', '1', 'important');
      } else {
        element.classList.remove('show');
        element.style.display = 'none';
      }

      return true;
    } catch (error) {
      console.warn('[DOMManipulator.setVisible] Error:', error);
      return false;
    }
  }

  /**
   * Agrega un event listener de forma segura
   * Verifica que el elemento exista y el handler sea válido
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} eventName - Nombre del evento
   * @param {Function} handler - Función manejadora
   * @returns {boolean} - true si fue exitoso
   */
  static addEventListener(elementId, eventName, handler) {
    try {
      if (!elementId || !eventName || typeof handler !== 'function') {
        return false;
      }

      const element = this.getElementById(elementId, false); // No cachear listeners
      if (!element) return false;

      element.addEventListener(eventName, handler);
      return true;
    } catch (error) {
      console.warn('[DOMManipulator.addEventListener] Error:', error);
      return false;
    }
  }

  /**
   * Agrega listeners a múltiples elementos a la vez
   * 
   * @param {Array} listeners - Array de {elementId, eventName, handler}
   * @returns {number} - Cantidad de listeners agregados exitosamente
   */
  static addEventListeners(listeners) {
    try {
      if (!Array.isArray(listeners)) return 0;

      let count = 0;
      listeners.forEach(({ elementId, eventName, handler }) => {
        if (this.addEventListener(elementId, eventName, handler)) {
          count++;
        }
      });

      return count;
    } catch (error) {
      console.warn('[DOMManipulator.addEventListeners] Error:', error);
      return 0;
    }
  }

  /**
   * Valida si un elemento existe en el DOM
   * 
   * @param {string} elementId - ID del elemento
   * @returns {boolean} - true si existe
   */
  static exists(elementId) {
    return this.getElementById(elementId, false) !== null;
  }

  /**
   * Agrega una clase CSS a un elemento
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} className - Nombre de clase
   * @returns {boolean} - true si fue exitoso
   */
  static addClass(elementId, className) {
    try {
      const element = this.getElementById(elementId);
      if (!element) return false;

      element.classList.add(className);
      return true;
    } catch (error) {
      console.warn('[DOMManipulator.addClass] Error:', error);
      return false;
    }
  }

  /**
   * Remueve una clase CSS de un elemento
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} className - Nombre de clase
   * @returns {boolean} - true si fue exitoso
   */
  static removeClass(elementId, className) {
    try {
      const element = this.getElementById(elementId);
      if (!element) return false;

      element.classList.remove(className);
      return true;
    } catch (error) {
      console.warn('[DOMManipulator.removeClass] Error:', error);
      return false;
    }
  }

  /**
   * Obtiene el valor de un atributo data
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} attributeName - Nombre del atributo (sin data-)
   * @returns {string|null} - Valor del atributo o null
   */
  static getDataAttribute(elementId, attributeName) {
    try {
      const element = this.getElementById(elementId);
      if (!element) return null;

      return element.getAttribute(`data-${attributeName}`);
    } catch (error) {
      console.warn('[DOMManipulator.getDataAttribute] Error:', error);
      return null;
    }
  }

  /**
   * Establece un atributo data
   * 
   * @param {string} elementId - ID del elemento
   * @param {string} attributeName - Nombre del atributo (sin data-)
   * @param {string} value - Valor a establecer
   * @returns {boolean} - true si fue exitoso
   */
  static setDataAttribute(elementId, attributeName, value) {
    try {
      const element = this.getElementById(elementId);
      if (!element) return false;

      element.setAttribute(`data-${attributeName}`, value);
      return true;
    } catch (error) {
      console.warn('[DOMManipulator.setDataAttribute] Error:', error);
      return false;
    }
  }
}

// Exportar para uso como módulo o global
if (typeof module !== 'undefined' && module.exports) {
  module.exports = DOMManipulator;
} else if (typeof window !== 'undefined') {
  window.DOMManipulator = DOMManipulator;
}
