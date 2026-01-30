/**
 * UTILIDADES Y HELPERS - Crear Pedido Editable
 * 
 * Funciones reutilizables para operaciones comunes
 */

// ============================================================
// HELPERS PARA MODALES (Swal.fire)
// ============================================================

/**
 * Mostrar modal de confirmación para eliminar
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje de confirmación
 * @param {function} callback - Función a ejecutar si confirma
 */
function confirmarEliminacion(titulo, mensaje, callback) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

/**
 * Mostrar modal de éxito
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje de éxito
 * @param {number} duracion - Duración en ms (default 2000)
 */
function mostrarExito(titulo, mensaje, duracion = 2000) {
    Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensaje,
        timer: duracion,
        showConfirmButton: false
    });
}

/**
 * Mostrar modal de error
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje de error
 */
function mostrarError(titulo, mensaje) {
    Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje
    });
}

/**
 * Mostrar modal de advertencia
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje de advertencia
 * @param {number} duracion - Duración en ms (default 2000)
 */
function mostrarAdvertencia(titulo, mensaje, duracion = 2000) {
    Swal.fire({
        icon: 'warning',
        title: titulo,
        text: mensaje,
        timer: duracion,
        showConfirmButton: false
    });
}

/**
 * Mostrar modal de información
 * @param {string} titulo - Título del modal
 * @param {string} mensaje - Mensaje informativo
 * @param {number} duracion - Duración en ms (default 2000)
 */
function mostrarInfo(titulo, mensaje, duracion = 2000) {
    Swal.fire({
        icon: 'info',
        title: titulo,
        text: mensaje,
        timer: duracion
    });
}

// ============================================================
// HELPERS PARA MANEJO DE DOM
// ============================================================

/**
 * Obtener elemento del DOM de forma segura
 * @param {string} selector - ID o selector CSS
 * @returns {Element|null} El elemento o null si no existe
 */
function getElement(selector) {
    return document.getElementById(selector) || document.querySelector(selector);
}

/**
 * Obtener múltiples elementos
 * @param {string} selector - Selector CSS
 * @returns {NodeList} Lista de elementos
 */
function getElements(selector) {
    return document.querySelectorAll(selector);
}

/**
 * Mostrar/ocultar elemento
 * @param {Element|string} element - Elemento o ID
 * @param {boolean} visible - true para mostrar, false para ocultar
 */
function toggleVisibility(element, visible) {
    const el = typeof element === 'string' ? getElement(element) : element;
    if (el) {
        el.style.display = visible ? 'block' : 'none';
    }
}

/**
 * Agregar clase CSS con transición
 * @param {Element} element - Elemento del DOM
 * @param {string} className - Nombre de la clase
 * @param {number} duration - Duración de la transición en ms
 */
function addClassWithTransition(element, className, duration = CONFIG.ANIMATION_DURATION) {
    if (!element) return;
    element.classList.add(className);
    setTimeout(() => {
        element.classList.remove(className);
    }, duration);
}

// ============================================================
// HELPERS PARA DATOS Y CONVERSIÓN
// ============================================================

/**
 * Parsear datos JSON de forma segura
 * @param {any} data - Datos a parsear (puede ser string, array u objeto)
 * @returns {array} Array parseado o array vacío si falla
 */
function parseArrayData(data) {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {

            return [];
        }
    }
    return [];
}

/**
 * Convertir foto a URL (maneja múltiples formatos)
 * @param {Object|string} foto - Objeto de foto o string URL
 * @returns {string|null} URL de la foto o null
 */
function fotoToUrl(foto) {
    if (!foto) return null;
    if (typeof foto === 'string') return foto;
    return foto.preview || foto.url || foto.ruta_webp || foto.ruta_original || null;
}

/**
 * Generar UUID único
 * @returns {string} UUID generado
 */
function generarUUID() {
    return 'sec_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
}

// ============================================================
// HELPERS PARA FILTRADO Y BÚSQUEDA
// ============================================================

/**
 * Buscar elemento en array por propiedad
 * @param {Array} array - Array a buscar
 * @param {string} propiedad - Nombre de la propiedad
 * @param {any} valor - Valor a buscar
 * @returns {Object|null} El elemento encontrado o null
 */
function buscarEnArray(array, propiedad, valor) {
    return array.find(item => item[propiedad] === valor) || null;
}

/**
 * Filtrar cotizaciones por texto de búsqueda
 * @param {Array} cotizaciones - Array de cotizaciones
 * @param {string} filtro - Texto a filtrar
 * @returns {Array} Cotizaciones filtradas
 */
function filtrarCotizaciones(cotizaciones, filtro = '') {
    if (!filtro || filtro.trim() === '') {
        return cotizaciones;
    }
    
    const filtroLower = filtro.toLowerCase().trim();
    
    return cotizaciones.filter(cot => {
        return (
            cot.numero_cotizacion.toLowerCase().includes(filtroLower) ||
            cot.cliente.toLowerCase().includes(filtroLower) ||
            (cot.asesora && cot.asesora.toLowerCase().includes(filtroLower)) ||
            (cot.forma_pago && Array.isArray(cot.forma_pago) && 
             cot.forma_pago.some(fp => fp.toLowerCase().includes(filtroLower)))
        );
    });
}

// ============================================================
// HELPERS PARA VALIDACIÓN
// ============================================================

/**
 * Validar si un valor está vacío
 * @param {any} valor - Valor a validar
 * @returns {boolean} true si está vacío
 */
function estaVacio(valor) {
    return !valor || (typeof valor === 'string' && valor.trim() === '');
}

/**
 * Validar email
 * @param {string} email - Email a validar
 * @returns {boolean} true si es válido
 */
function esEmailValido(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validar número
 * @param {any} valor - Valor a validar
 * @returns {boolean} true si es número válido
 */
function esNumero(valor) {
    return !isNaN(parseFloat(valor)) && isFinite(valor);
}

// ============================================================
// HELPERS PARA ARRAYS
// ============================================================

/**
 * Eliminar duplicados de array
 * @param {Array} array - Array original
 * @returns {Array} Array sin duplicados
 */
function sinDuplicados(array) {
    return [...new Set(array)];
}

/**
 * Agrupar array por propiedad
 * @param {Array} array - Array a agrupar
 * @param {string} propiedad - Propiedad para agrupar
 * @returns {Object} Objeto agrupado
 */
function agruparPor(array, propiedad) {
    return array.reduce((acc, item) => {
        const key = item[propiedad];
        if (!acc[key]) acc[key] = [];
        acc[key].push(item);
        return acc;
    }, {});
}

// ============================================================
// HELPERS PARA OPERACIONES COMUNES CON ELEMENTOS
// ============================================================

/**
 * Limpiar contenido de un elemento
 * @param {string|Element} selector - ID o elemento
 */
function limpiarContenido(selector) {
    const element = typeof selector === 'string' ? getElement(selector) : selector;
    if (element) element.innerHTML = '';
}

/**
 * Establecer atributo a múltiples elementos
 * @param {NodeList} elements - Lista de elementos
 * @param {string} atributo - Nombre del atributo
 * @param {any} valor - Valor del atributo
 */
function setAtributoMultiple(elements, atributo, valor) {
    elements.forEach(el => el.setAttribute(atributo, valor));
}

/**
 * Scroll suave a un elemento
 * @param {string|Element} selector - ID o elemento
 * @param {string} block - Posición (start, center, end)
 */
function scrollSuave(selector, block = 'start') {
    const element = typeof selector === 'string' ? getElement(selector) : selector;
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block });
    }
}

// ============================================================
// HELPERS DE LOGGING
// ============================================================

/**
 * Log con emoji y color para debugging
 * @param {string} emoji - Emoji a mostrar
 * @param {string} mensaje - Mensaje a mostrar
 * @param {any} datos - Datos adicionales (opcional)
 */


// Exportar para uso en otros módulos (si usas ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        // Modales
        confirmarEliminacion,
        mostrarExito,
        mostrarError,
        mostrarAdvertencia,
        mostrarInfo,
        // DOM
        getElement,
        getElements,
        toggleVisibility,
        addClassWithTransition,
        // Datos
        parseArrayData,
        fotoToUrl,
        generarUUID,
        // Filtrado
        filtrarCotizaciones,
        buscarEnArray,
        // Validación
        estaVacio,
        esEmailValido,
        esNumero,
        // Arrays
        sinDuplicados,
        agruparPor,
        // Operaciones DOM
        limpiarContenido,
        setAtributoMultiple,
        scrollSuave,
        // Logging
    };
}
