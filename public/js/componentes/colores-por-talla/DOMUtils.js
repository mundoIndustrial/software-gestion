/**
 * Módulo: DOMUtils
 * Utilidades para manipulación del DOM
 */

window.DOMUtils = (function() {
    'use strict';

    return {
        /**
         * Obtener elemento por ID con manejo de errores
         */
        getElement(id, errorMsg = null) {
            const element = document.getElementById(id);
            if (!element && errorMsg) {
                console.error(`[DOMUtils] Elemento no encontrado: ${id}`);
            }
            return element;
        },

        /**
         * Obtener elemento por selector
         */
        querySelector(selector, errorMsg = null) {
            const element = document.querySelector(selector);
            if (!element && errorMsg) {
                console.error(`[DOMUtils] Elemento no encontrado: ${selector}`);
            }
            return element;
        },

        /**
         * Obtener todos los elementos por selector
         */
        querySelectorAll(selector) {
            return document.querySelectorAll(selector);
        },

        /**
         * Mostrar/ocultar elemento
         */
        toggleElement(element, show) {
            if (!element) return;
            element.style.display = show ? 'block' : 'none';
        },

        /**
         * Establecer estilo a un elemento
         */
        setStyles(element, styles) {
            if (!element) return;
            Object.assign(element.style, styles);
        },

        /**
         * Limpiar contenido de un elemento
         */
        clearElement(element) {
            if (!element) return;
            element.innerHTML = '';
        },

        /**
         * Crear elemento con atributos y estilos
         */
        createElement(tag, options = {}) {
            const element = document.createElement(tag);
            
            // Establecer atributos
            if (options.attributes) {
                Object.entries(options.attributes).forEach(([key, value]) => {
                    element.setAttribute(key, value);
                });
            }
            
            // Establecer estilos
            if (options.styles) {
                this.setStyles(element, options.styles);
            }
            
            // Establecer texto
            if (options.textContent) {
                element.textContent = options.textContent;
            }
            
            // Establecer HTML
            if (options.innerHTML) {
                element.innerHTML = options.innerHTML;
            }
            
            // Establecer clase
            if (options.className) {
                element.className = options.className;
            }
            
            return element;
        },

        /**
         * Crear input con configuración común
         */
        createInput(type, options = {}) {
            const input = this.createElement('input', {
                attributes: { type, ...options.attributes },
                styles: options.styles,
                className: options.className
            });
            
            if (options.placeholder) input.placeholder = options.placeholder;
            if (options.value !== undefined) input.value = options.value;
            if (options.min !== undefined) input.min = options.min;
            if (options.max !== undefined) input.max = options.max;
            
            return input;
        },

        /**
         * Crear botón con configuración común
         */
        createButton(text, options = {}) {
            const button = this.createElement('button', {
                attributes: { type: 'button', ...options.attributes },
                styles: {
                    padding: '0.5rem 1rem',
                    border: '1px solid #d1d5db',
                    background: 'white',
                    borderRadius: '4px',
                    cursor: 'pointer',
                    fontSize: '0.9rem',
                    ...options.styles
                },
                className: options.className,
                textContent: text
            });
            
            return button;
        },

        /**
         * Agregar evento con manejo de errores
         */
        addEventListener(element, event, handler) {
            if (!element) return;
            element.addEventListener(event, handler);
        },

        /**
         * Remover evento
         */
        removeEventListener(element, event, handler) {
            if (!element) return;
            element.removeEventListener(event, handler);
        },

        /**
         * Encontrar elemento padre más cercano con selector
         */
        closest(element, selector) {
            if (!element) return null;
            return element.closest(selector);
        },

        /**
         * Agregar clase a un elemento
         */
        addClass(element, className) {
            if (!element) return;
            element.classList.add(className);
        },

        /**
         * Remover clase de un elemento
         */
        removeClass(element, className) {
            if (!element) return;
            element.classList.remove(className);
        },

        /**
         * Verificar si elemento tiene clase
         */
        hasClass(element, className) {
            if (!element) return false;
            return element.classList.contains(className);
        },

        /**
         * Toggle de clase
         */
        toggleClass(element, className) {
            if (!element) return;
            element.classList.toggle(className);
        },

        /**
         * Deshabilitar/habilitar elemento
         */
        setDisabled(element, disabled) {
            if (!element) return;
            element.disabled = disabled;
            element.style.opacity = disabled ? '0.5' : '1';
            element.style.cursor = disabled ? 'not-allowed' : 'pointer';
        },

        /**
         * Crear datalist para colores
         */
        createDatalist(id, options = []) {
            const datalist = this.createElement('datalist', { attributes: { id } });
            
            options.forEach(option => {
                const optionElement = this.createElement('option', {
                    attributes: { value: option.value || option.nombre },
                    textContent: option.nombre || option.value
                });
                
                if (option.id) optionElement.dataset.id = option.id;
                if (option.codigo) optionElement.dataset.codigo = option.codigo;
                
                datalist.appendChild(optionElement);
            });
            
            return datalist;
        },

        /**
         * Mostrar notificación (toast o alert)
         */
        showNotification(message, type = 'info') {
            if (typeof showToast === 'function') {
                showToast(message, type);
            } else {
                alert(message);
            }
        },

        /**
         * Crear contenedor flex con opciones
         */
        createFlexContainer(options = {}) {
            const styles = {
                display: 'flex',
                gap: options.gap || '1rem',
                alignItems: options.alignItems || 'center',
                justifyContent: options.justifyContent || 'flex-start',
                flexWrap: options.flexWrap || 'nowrap',
                ...options.styles
            };
            
            return this.createElement('div', { styles });
        },

        /**
         * Crear contenedor grid con opciones
         */
        createGridContainer(options = {}) {
            const styles = {
                display: 'grid',
                gap: options.gap || '1rem',
                gridTemplateColumns: options.gridTemplateColumns || '1fr',
                ...options.styles
            };
            
            return this.createElement('div', { styles });
        },

        /**
         * Validar que un elemento exista
         */
        validateElement(element, name) {
            if (!element) {
                console.error(`[DOMUtils] Elemento requerido no encontrado: ${name}`);
                return false;
            }
            return true;
        },

        /**
         * Esperar a que el DOM esté listo
         */
        ready(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                callback();
            }
        }
    };
})();
