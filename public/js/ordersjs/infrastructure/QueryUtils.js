/**
 * Infrastructure: QueryUtils
 * 
 * Utilidades para interactuar con el DOM.
 * Estos son "Puertos" de la arquitectura de capas limpias.
 * 
 * Responsabilidades:
 * - Selectores de DOM seguros
 * - Operaciones comunes del DOM
 * - Evitar null checks repetidos en el código llamador
 * 
 * Patrón: Defense in Depth
 * Si un elemento no existe, retorna null en lugar de lanzar error.
 * El codigo llamador puede decidir reforzarse o ignorar.
 */

class QueryUtils {
  /**
   * Encontrar elemento por ID
   * @param {String} id - ID del elemento
   * @returns {Element|null}
   */
  byId(id) {
    if (!id) return null;
    return document.getElementById(id) || null;
  }

  /**
   * Encontrar elemento por selector
   * @param {String} selector - CSS selector
   * @returns {Element|null}
   */
  bySelector(selector) {
    if (!selector) return null;
    return document.querySelector(selector) || null;
  }

  /**
   * Encontrar elemento por nombre de clase
   * @param {String} className - Nombre de clase (sin el .)
   * @returns {Element|null}
   */
  byClass(className) {
    if (!className) return null;
    return document.querySelector(`.${className}`) || null;
  }

  /**
   * Encontrar todos los elementos por selector
   * @param {String} selector - CSS selector
   * @returns {NodeList}
   */
  allBySelector(selector) {
    if (!selector) return [];
    return document.querySelectorAll(selector) || [];
  }

  /**
   * Encontrar elemento ancestro más cercano
   * @param {Element} element - Elemento desde donde buscar
   * @param {String} selector - CSS selector del ancestro
   * @returns {Element|null}
   */
  closest(element, selector) {
    if (!element || !selector) return null;
    return element.closest(selector) || null;
  }

  /**
   * Establecer contenido de texto de un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} text - Texto a establecer
   * @returns {Boolean} true si tuvo éxito
   */
  setText(target, text) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    element.textContent = text;
    return true;
  }

  /**
   * Establecer contenido HTML de un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} html - HTML a establecer
   * @returns {Boolean} true si tuvo éxito
   */
  setHTML(target, html) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    element.innerHTML = html;
    return true;
  }

  /**
   * Agregar clase a un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} className - Nombre de clase
   * @returns {Boolean}
   */
  addClass(target, className) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    element.classList.add(className);
    return true;
  }

  /**
   * Remover clase de un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} className - Nombre de clase
   * @returns {Boolean}
   */
  removeClass(target, className) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    element.classList.remove(className);
    return true;
  }

  /**
   * Toggle clase en un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} className - Nombre de clase
   * @returns {Boolean} true si la clase fue añadida
   */
  toggleClass(target, className) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    return element.classList.toggle(className);
  }
  /**
   * Verificar si un elemento tiene una clase
   * @param {String|Element} target - ID o elemento
   * @param {String} className - Nombre de clase
   * @returns {Boolean}
   */
  hasClass(target, className) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    return element.classList.contains(className);
  }

  /**
   * Establecer atributo en un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} attr - Nombre del atributo
   * @param {String} value - Valor del atributo
   * @returns {Boolean}
   */
  setAttribute(target, attr, value) {
    const element = this.#resolveElement(target);
    if (!element) return false;

    element.setAttribute(attr, value);
    return true;
  }

  /**
   * Obtener atributo de un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} attr - Nombre del atributo
   * @returns {String|null}
   */
  getAttribute(target, attr) {
    const element = this.#resolveElement(target);
    if (!element) return null;

    return element.getAttribute(attr) || null;
  }

  /**
   * Establecer style inline
   * @param {String|Element} target - ID o elemento
   * @param {Object} styles - Objeto con propiedades CSS
   * @returns {Boolean}
   */
  setStyles(target, styles) {
    const element = this.#resolveElement(target);
    if (!element || !styles) return false;

    for (const [key, value] of Object.entries(styles)) {
      element.style[key] = value;
    }
    return true;
  }

  /**
   * Mostrar un elemento
   * @param {String|Element} target - ID o elemento
   * @param {String} display - Valor de display (default: 'block')
   * @returns {Boolean}
   */
  show(target, display = 'block') {
    return this.setStyles(target, { display });
  }

  /**
   * Ocultar un elemento
   * @param {String|Element} target - ID o elemento
   * @returns {Boolean}
   */
  hide(target) {
    return this.setStyles(target, { display: 'none' });
  }

  /**
   * Verificar si un elemento existe
   * @param {String|Element} target - ID o elemento
   * @returns {Boolean}
   */
  exists(target) {
    return this.#resolveElement(target) !== null;
  }

  /**
   * Private: Resolver si es string (ID) o elemento
   * @private
   * @param {String|Element} target
   * @returns {Element|null}
   */
  #resolveElement(target) {
    if (target instanceof Element) return target;
    if (typeof target === 'string') return this.byId(target);
    return null;
  }
}

/**
 * Singleton: instancia única de QueryUtils
 * Se usa en toda la aplicación para acceder a utilidades de DOM
 */
const queryUtils = new QueryUtils();

export { QueryUtils, queryUtils };
export default queryUtils;
