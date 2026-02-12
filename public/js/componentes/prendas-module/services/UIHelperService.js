/**
 * ================================================
 * UI HELPER SERVICE
 * ================================================
 * 
 * Utilidades comunes para la interfaz de usuario
 * Funciones reutilizables para manejo de DOM, estilos y feedback visual
 * 
 * @class UIHelperService
 */

class UIHelperService {
    /**
     * Mostrar modal de error usando SweetAlert o fallback
     * @param {string} mensaje - Mensaje a mostrar
     * @static
     */
    static mostrarModalError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Cerrar'
            });
        } else {
            // Fallback a alert si Swal no está disponible
            alert(' Error: ' + mensaje);
        }
    }

    /**
     * Obtener o crear contenedor para overlays sin restricciones de overflow
     * Esto previene que elementos fixed sean clipeados por overflow: hidden en padre
     * @returns {HTMLElement} Contenedor overlay
     * @static
     */
    static obtenerContenedorOverlay() {
        let container = document.getElementById('drag-drop-overlay-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'drag-drop-overlay-container';
            container.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999999999;
                pointer-events: none;
                overflow: visible;
            `;
            document.body.appendChild(container);
            console.log('[UIHelperService] Contenedor overlay creado');
        }
        return container;
    }

    /**
     * Aplicar estilos de drag over a un elemento
     * @param {HTMLElement} element - Elemento a estilizar
     * @param {Object} options - Opciones de estilo personalizadas
     * @static
     */
    static aplicarEstilosDragOver(element, options = {}) {
        const defaultStyles = {
            background: '#eff6ff',
            border: '2px dashed #3b82f6',
            opacity: '0.8'
        };
        
        const styles = { ...defaultStyles, ...options };
        
        Object.assign(element.style, styles);
    }

    /**
     * Restaurar estilos normales de un elemento
     * @param {HTMLElement} element - Elemento a restaurar
     * @param {Array} properties - Propiedades específicas a restaurar (opcional)
     * @static
     */
    static restaurarEstilos(element, properties = ['background', 'border', 'opacity', 'transform', 'padding', 'borderRadius']) {
        properties.forEach(prop => {
            element.style[prop] = '';
        });
    }

    /**
     * Aplicar estilos de focus a un elemento
     * @param {HTMLElement} element - Elemento a enfocar visualmente
     * @static
     */
    static aplicarEstilosFocus(element) {
        element.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        element.style.border = '2px solid #3b82f6';
    }

    /**
     * Quitar estilos de focus de un elemento
     * @param {HTMLElement} element - Elemento a desenfocar visualmente
     * @static
     */
    static quitarEstilosFocus(element) {
        element.style.boxShadow = '';
        element.style.border = '';
    }

    /**
     * Hacer un elemento focusable
     * @param {HTMLElement} element - Elemento a hacer focusable
     * @static
     */
    static hacerFocusable(element) {
        element.setAttribute('tabindex', '0');
        element.style.outline = 'none';
    }

    /**
     * Calcular posición para menú contextual evitando bordes
     * @param {number} clientX - Posición X del cursor
     * @param {number} clientY - Posición Y del cursor
     * @param {Object} menuDimensions - Dimensiones del menú {width, height}
     * @param {number} padding - Padding adicional
     * @returns {Object} Posición calculada {left, top}
     * @static
     */
    static calcularPosicionMenu(clientX, clientY, menuDimensions = { width: 180, height: 50 }, padding = 10) {
        let left = clientX;
        let top = clientY;
        
        // Ajustar posición horizontal si se sale por la derecha
        if (left + menuDimensions.width > window.innerWidth - padding) {
            left = window.innerWidth - menuDimensions.width - padding;
        }
        
        // Ajustar posición vertical si se sale por abajo
        if (top + menuDimensions.height > window.innerHeight - padding) {
            top = window.innerHeight - menuDimensions.height - padding;
        }
        
        // Asegurar que no sea negativo
        left = Math.max(padding, left);
        top = Math.max(padding, top);
        
        return { left, top };
    }

    /**
     * Crear un input file temporal con archivos
     * @param {FileList|Array} files - Archivos a agregar
     * @returns {HTMLInputElement} Input temporal
     * @static
     */
    static crearInputTemporal(files) {
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        
        if (files && files.length > 0) {
            const dataTransfer = new DataTransfer();
            Array.from(files).forEach(file => {
                dataTransfer.items.add(file);
            });
            tempInput.files = dataTransfer.files;
        }
        
        return tempInput;
    }

    /**
     * Verificar si un modal está visible
     * @param {HTMLElement} modal - Elemento modal a verificar
     * @returns {boolean} True si está visible
     * @static
     */
    static isModalVisible(modal) {
        if (!modal) return false;
        
        const style = window.getComputedStyle(modal);
        return style.display !== 'none' && style.visibility !== 'hidden';
    }

    /**
     * Prevenir comportamiento por defecto de eventos
     * @param {Event} e - Evento a prevenir
     * @static
     */
    static preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    /**
     * Limpiar event listeners de un elemento clonándolo
     * @param {HTMLElement} element - Elemento a limpiar
     * @returns {HTMLElement} Elemento clonado sin listeners
     * @static
     */
    static limpiarEventListeners(element) {
        const newElement = element.cloneNode(true);
        element.parentNode.replaceChild(newElement, element);
        return newElement;
    }

    /**
     * Verificar si un archivo es una imagen
     * @param {File} file - Archivo a verificar
     * @returns {boolean} True si es imagen
     * @static
     */
    static esImagen(file) {
        return file && file.type && file.type.startsWith('image/');
    }

    /**
     * Agregar logging con prefijo consistente
     * @param {string} servicio - Nombre del servicio
     * @param {string} mensaje - Mensaje a loguear
     * @param {string} nivel - Nivel de log (info, warn, error)
     * @static
     */
    static log(servicio, mensaje, nivel = 'info') {
        const prefijo = `[${servicio}]`;
        const mensajeCompleto = `${prefijo} ${mensaje}`;
        
        switch (nivel) {
            case 'warn':
                console.warn(mensajeCompleto);
                break;
            case 'error':
                console.error(mensajeCompleto);
                break;
            default:
                console.log(mensajeCompleto);
        }
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIHelperService;
}

// Asignar al window para uso global
window.UIHelperService = UIHelperService;
