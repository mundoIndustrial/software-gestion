/**
 * DOM UTILITIES - Helpers para manipulación del DOM
 * 
 * Centraliza operaciones comunes en el DOM para evitar repetición
 * y mejorar mantenibilidad del código
 * 
 * @module DOMUtils
 */

class DOMUtils {
    /**
     * Obtener elemento del DOM de forma segura
     * @param {string} id - ID del elemento
     * @returns {HTMLElement|null} Elemento o null si no existe
     */
    static getElement(id) {
        const element = document.getElementById(id);
        if (!element) {

            return null;
        }
        return element;
    }

    /**
     * Obtener múltiples elementos
     * @param {Array<string>} ids - Array de IDs
     * @returns {Array<HTMLElement|null>} Array de elementos
     */
    static getElements(ids) {
        return ids.map(id => this.getElement(id));
    }

    /**
     * Obtener valor de elemento (input, select, textarea, etc)
     * @param {string} id - ID del elemento
     * @returns {string} Valor del elemento o empty string
     */
    static getValue(id) {
        const element = this.getElement(id);
        return element?.value || '';
    }

    /**
     * Establecer valor en elemento
     * @param {string} id - ID del elemento
     * @param {string} value - Valor a establecer
     */
    static setValue(id, value = '') {
        const element = this.getElement(id);
        if (element) {
            element.value = value;
        }
    }

    /**
     * Establecer valores en múltiples elementos
     * @param {Array<string>} ids - IDs de elementos
     * @param {string} value - Valor a establecer en todos
     */
    static setValues(ids, value = '') {
        ids.forEach(id => this.setValue(id, value));
    }

    /**
     * Limpiar contenido de elemento
     * @param {string} id - ID del elemento
     */
    static clear(id) {
        const element = this.getElement(id);
        if (element) {
            element.innerHTML = '';
            element.textContent = '';
        }
    }

    /**
     * Limpiar múltiples elementos
     * @param {Array<string>} ids - IDs de elementos
     */
    static clearAll(ids) {
        ids.forEach(id => this.clear(id));
    }

    /**
     * Limpiar valor de input/textarea
     * @param {string} id - ID del elemento
     */
    static clearValue(id) {
        this.setValue(id, '');
    }

    /**
     * Limpiar valores de múltiples inputs
     * @param {Array<string>} ids - IDs de elementos
     */
    static clearValues(ids) {
        ids.forEach(id => this.clearValue(id));
    }

    /**
     * Mostrar/ocultar elemento
     * @param {string} id - ID del elemento
     * @param {boolean} show - true para mostrar, false para ocultar
     */
    static toggle(id, show = true) {
        const element = this.getElement(id);
        if (element) {
            element.style.display = show ? 'block' : 'none';
        }
    }

    /**
     * Mostrar/ocultar múltiples elementos
     * @param {Array<string>} ids - IDs de elementos
     * @param {boolean} show - true para mostrar, false para ocultar
     */
    static toggleAll(ids, show = true) {
        ids.forEach(id => this.toggle(id, show));
    }

    /**
     * Marcar/desmarcar checkbox
     * @param {string} id - ID del checkbox
     * @param {boolean} checked - true para marcar, false para desmarcar
     */
    static setChecked(id, checked = false) {
        const checkbox = this.getElement(id);
        if (checkbox && checkbox.type === 'checkbox') {
            checkbox.checked = checked;
        }
    }

    /**
     * Marcar/desmarcar múltiples checkboxes
     * @param {Array<string>} ids - IDs de checkboxes
     * @param {boolean} checked - true para marcar, false para desmarcar
     */
    static setCheckedAll(ids, checked = false) {
        ids.forEach(id => this.setChecked(id, checked));
    }

    /**
     * Obtener estado de checkbox
     * @param {string} id - ID del checkbox
     * @returns {boolean} true si está marcado
     */
    static isChecked(id) {
        const checkbox = this.getElement(id);
        return checkbox?.checked || false;
    }

    /**
     * Agregar clase CSS a elemento
     * @param {string} id - ID del elemento
     * @param {string} className - Nombre de la clase
     */
    static addClass(id, className) {
        const element = this.getElement(id);
        if (element) {
            element.classList.add(className);
        }
    }

    /**
     * Remover clase CSS de elemento
     * @param {string} id - ID del elemento
     * @param {string} className - Nombre de la clase
     */
    static removeClass(id, className) {
        const element = this.getElement(id);
        if (element) {
            element.classList.remove(className);
        }
    }

    /**
     * Verificar si elemento tiene clase
     * @param {string} id - ID del elemento
     * @param {string} className - Nombre de la clase
     * @returns {boolean} true si tiene la clase
     */
    static hasClass(id, className) {
        const element = this.getElement(id);
        return element?.classList.contains(className) || false;
    }

    /**
     * Limpiar formulario (vaciar todos los inputs, selects, textareas)
     * @param {string} formId - ID del formulario
     */
    static clearForm(formId) {
        const form = this.getElement(formId);
        if (form && form.tagName === 'FORM') {
            form.reset();
            // Además limpiar textareas y otros campos
            form.querySelectorAll('input[type="text"], input[type="email"], textarea, select')
                .forEach(field => {
                    field.value = '';
                });
        }
    }

    /**
     * Limpiar tabla (vaciar tbody)
     * @param {string} tableId - ID de la tabla o tbody
     */
    static clearTable(tableId) {
        const table = this.getElement(tableId);
        if (table) {
            const tbody = table.tagName === 'TABLE' 
                ? table.querySelector('tbody') 
                : table;
            if (tbody) {
                tbody.innerHTML = '';
            }
        }
    }

    /**
     * Agregar event listener a elemento
     * @param {string} id - ID del elemento
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Función a ejecutar
     */
    static addEventListener(id, event, callback) {
        const element = this.getElement(id);
        if (element) {
            element.addEventListener(event, callback);
        }
    }

    /**
     * Agregar event listeners a múltiples elementos
     * @param {Array<string>} ids - IDs de elementos
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Función a ejecutar
     */
    static addEventListenerAll(ids, event, callback) {
        ids.forEach(id => this.addEventListener(id, event, callback));
    }

    /**
     * Remover event listener
     * @param {string} id - ID del elemento
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Función a remover
     */
    static removeEventListener(id, event, callback) {
        const element = this.getElement(id);
        if (element) {
            element.removeEventListener(event, callback);
        }
    }

    /**
     * Activar/desactivar elemento (disabled)
     * @param {string} id - ID del elemento
     * @param {boolean} disabled - true para desactivar, false para activar
     */
    static setDisabled(id, disabled = false) {
        const element = this.getElement(id);
        if (element) {
            element.disabled = disabled;
        }
    }

    /**
     * Activar/desactivar múltiples elementos
     * @param {Array<string>} ids - IDs de elementos
     * @param {boolean} disabled - true para desactivar, false para activar
     */
    static setDisabledAll(ids, disabled = false) {
        ids.forEach(id => this.setDisabled(id, disabled));
    }

    /**
     * Seleccionar valor en select
     * @param {string} id - ID del select
     * @param {string} value - Valor a seleccionar
     */
    static selectOption(id, value) {
        const select = this.getElement(id);
        if (select) {
            select.value = value;
        }
    }

    /**
     * Obtener texto de elemento
     * @param {string} id - ID del elemento
     * @returns {string} Texto del elemento
     */
    static getText(id) {
        const element = this.getElement(id);
        return element?.textContent || '';
    }

    /**
     * Establecer texto de elemento
     * @param {string} id - ID del elemento
     * @param {string} text - Texto a establecer
     */
    static setText(id, text) {
        const element = this.getElement(id);
        if (element) {
            element.textContent = text;
        }
    }

    /**
     * Establecer HTML de elemento
     * @param {string} id - ID del elemento
     * @param {string} html - HTML a establecer
     */
    static setHTML(id, html) {
        const element = this.getElement(id);
        if (element) {
            element.innerHTML = html;
        }
    }

    /**
     * Obtener atributo de elemento
     * @param {string} id - ID del elemento
     * @param {string} attr - Nombre del atributo
     * @returns {string} Valor del atributo
     */
    static getAttribute(id, attr) {
        const element = this.getElement(id);
        return element?.getAttribute(attr) || '';
    }

    /**
     * Establecer atributo de elemento
     * @param {string} id - ID del elemento
     * @param {string} attr - Nombre del atributo
     * @param {string} value - Valor del atributo
     */
    static setAttribute(id, attr, value) {
        const element = this.getElement(id);
        if (element) {
            element.setAttribute(attr, value);
        }
    }

    /**
     * Remover atributo de elemento
     * @param {string} id - ID del elemento
     * @param {string} attr - Nombre del atributo
     */
    static removeAttribute(id, attr) {
        const element = this.getElement(id);
        if (element) {
            element.removeAttribute(attr);
        }
    }

    /**
     * Enfocar elemento
     * @param {string} id - ID del elemento
     */
    static focus(id) {
        const element = this.getElement(id);
        if (element) {
            element.focus();
        }
    }

    /**
     * Desfocar elemento
     * @param {string} id - ID del elemento
     */
    static blur(id) {
        const element = this.getElement(id);
        if (element) {
            element.blur();
        }
    }

    /**
     * Verificar si elemento está visible (display !== 'none')
     * @param {string} id - ID del elemento
     * @returns {boolean} true si está visible
     */
    static isVisible(id) {
        const element = this.getElement(id);
        if (!element) return false;
        return window.getComputedStyle(element).display !== 'none';
    }

    /**
     * Obtener referencia a elemento de forma segura
     * Retorna el elemento aunque puede ser null
     * @param {string} id - ID del elemento
     * @returns {HTMLElement|null} Elemento o null
     */
    static query(id) {
        return this.getElement(id);
    }
}

// Hacer disponible globalmente
window.DOMUtils = DOMUtils;
