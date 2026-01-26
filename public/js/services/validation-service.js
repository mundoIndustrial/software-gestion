/**
 * Servicio de Validación para Pedidos
 * Centraliza todas las validaciones del lado del cliente
 * 
 * @class ValidationService
 */

class ValidationService {
    constructor() {
        this.errors = [];
    }

    /**
     * Limpiar errores
     */
    clearErrors() {
        this.errors = [];
    }

    /**
     * Agregar error
     * @param {string} field - Campo con error
     * @param {string} message - Mensaje de error
     */
    addError(field, message) {
        this.errors.push({ field, message });
    }

    /**
     * Obtener errores
     * @returns {Array}
     */
    getErrors() {
        return this.errors;
    }

    /**
     * Verificar si hay errores
     * @returns {boolean}
     */
    hasErrors() {
        return this.errors.length > 0;
    }

    // ============================================================
    // VALIDACIONES DE CAMPOS BÁSICOS
    // ============================================================

    /**
     * Validar campo requerido
     * @param {*} value - Valor a validar
     * @param {string} fieldName - Nombre del campo
     * @returns {boolean}
     */
    required(value, fieldName) {
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            this.addError(fieldName, `${fieldName} es requerido`);
            return false;
        }
        return true;
    }

    /**
     * Validar email
     * @param {string} email - Email a validar
     * @param {string} fieldName - Nombre del campo
     * @returns {boolean}
     */
    email(email, fieldName = 'Email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            this.addError(fieldName, 'Email inválido');
            return false;
        }
        return true;
    }

    /**
     * Validar número
     * @param {*} value - Valor a validar
     * @param {string} fieldName - Nombre del campo
     * @param {Object} options - Opciones (min, max)
     * @returns {boolean}
     */
    number(value, fieldName, options = {}) {
        const num = Number(value);
        
        if (isNaN(num)) {
            this.addError(fieldName, `${fieldName} debe ser un número`);
            return false;
        }

        if (options.min !== undefined && num < options.min) {
            this.addError(fieldName, `${fieldName} debe ser mayor o igual a ${options.min}`);
            return false;
        }

        if (options.max !== undefined && num > options.max) {
            this.addError(fieldName, `${fieldName} debe ser menor o igual a ${options.max}`);
            return false;
        }

        return true;
    }

    /**
     * Validar longitud de string
     * @param {string} value - Valor a validar
     * @param {string} fieldName - Nombre del campo
     * @param {Object} options - Opciones (min, max)
     * @returns {boolean}
     */
    length(value, fieldName, options = {}) {
        const len = value?.length || 0;

        if (options.min !== undefined && len < options.min) {
            this.addError(fieldName, `${fieldName} debe tener al menos ${options.min} caracteres`);
            return false;
        }

        if (options.max !== undefined && len > options.max) {
            this.addError(fieldName, `${fieldName} debe tener máximo ${options.max} caracteres`);
            return false;
        }

        return true;
    }

    // ============================================================
    // VALIDACIONES ESPECÍFICAS DE PEDIDOS
    // ============================================================

    /**
     * Validar datos de cotización
     * @param {Object} cotizacion - Datos de cotización
     * @returns {boolean}
     */
    validateCotizacion(cotizacion) {
        this.clearErrors();

        if (!cotizacion || !cotizacion.id) {
            this.addError('cotizacion', 'Debe seleccionar una cotización');
            return false;
        }

        this.required(cotizacion.cliente, 'Cliente');
        this.required(cotizacion.asesora, 'Asesora');

        return !this.hasErrors();
    }

    /**
     * Validar prenda
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice de la prenda
     * @returns {boolean}
     */
    validatePrenda(prenda, index) {
        const prefix = `Prenda ${index + 1}`;

        if (!prenda) {
            this.addError(prefix, 'Datos de prenda inválidos');
            return false;
        }

        // Validar nombre
        if (!this.required(prenda.nombre_producto, `${prefix} - Nombre`)) {
            return false;
        }

        // Validar cantidades
        if (!prenda.cantidades || Object.keys(prenda.cantidades).length === 0) {
            this.addError(prefix, 'Debe agregar cantidades por talla');
            return false;
        }

        // Validar que las cantidades sean números positivos
        for (const [talla, cantidad] of Object.entries(prenda.cantidades)) {
            if (!this.number(cantidad, `${prefix} - Talla ${talla}`, { min: 1 })) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validar prendas (permite cero prendas si hay EPPs)
     * @param {Array} prendas - Array de prendas
     * @param {Array} epps - Array de EPPs (opcional)
     * @returns {boolean}
     */
    validatePrendas(prendas, epps = null) {
        this.clearErrors();

        // Permitir cero prendas SI hay EPPs
        if ((!prendas || prendas.length === 0) && (!epps || epps.length === 0)) {
            this.addError('prendas', 'Debe agregar al menos una prenda o un EPP');
            return false;
        }

        // Si hay prendas, validarlas
        if (prendas && prendas.length > 0) {
            prendas.forEach((prenda, index) => {
                this.validatePrenda(prenda, index);
            });
        }

        return !this.hasErrors();
    }

    /**
     * Validar cantidades por talla
     * @param {Object} cantidades - Objeto con cantidades por talla
     * @param {string} context - Contexto (nombre de prenda, etc.)
     * @returns {boolean}
     */
    validateCantidades(cantidades, context = 'Prenda') {
        if (!cantidades || typeof cantidades !== 'object') {
            this.addError(context, 'Cantidades inválidas');
            return false;
        }

        const tallas = Object.keys(cantidades);
        
        if (tallas.length === 0) {
            this.addError(context, 'Debe agregar al menos una talla con cantidad');
            return false;
        }

        // Validar que todas las cantidades sean números positivos
        for (const [talla, cantidad] of Object.entries(cantidades)) {
            const cantidadNum = Number(cantidad);
            
            if (isNaN(cantidadNum) || cantidadNum < 0) {
                this.addError(context, `Cantidad inválida para talla ${talla}`);
                return false;
            }

            if (cantidadNum === 0) {
                this.addError(context, `La cantidad para talla ${talla} debe ser mayor a 0`);
                return false;
            }
        }

        return true;
    }

    /**
     * Validar telas de una prenda
     * @param {Array} telas - Array de telas
     * @param {string} context - Contexto
     * @returns {boolean}
     */
    validateTelas(telas, context = 'Prenda') {
        if (!telas || !Array.isArray(telas)) {
            return true; // Telas son opcionales
        }

        telas.forEach((tela, index) => {
            if (tela.nombre_tela && !tela.color) {
                this.addError(context, `Tela ${index + 1}: debe especificar el color`);
            }
        });

        return !this.hasErrors();
    }

    /**
     * Validar datos de logo
     * @param {Object} logo - Datos del logo
     * @returns {boolean}
     */
    validateLogo(logo) {
        if (!logo) {
            this.addError('logo', 'Datos de logo requeridos');
            return false;
        }

        if (!logo.descripcion || logo.descripcion.trim() === '') {
            this.addError('logo', 'Descripción del logo es requerida');
            return false;
        }

        if (!logo.tecnicas || logo.tecnicas.length === 0) {
            this.addError('logo', 'Debe seleccionar al menos una técnica');
            return false;
        }

        if (!logo.ubicaciones || logo.ubicaciones.length === 0) {
            this.addError('logo', 'Debe agregar al menos una ubicación');
            return false;
        }

        return true;
    }

    /**
     * Validar datos de reflectivo
     * @param {Object} reflectivo - Datos del reflectivo
     * @returns {boolean}
     */
    validateReflectivo(reflectivo) {
        if (!reflectivo) {
            this.addError('reflectivo', 'Datos de reflectivo requeridos');
            return false;
        }

        if (!reflectivo.descripcion || reflectivo.descripcion.trim() === '') {
            this.addError('reflectivo', 'Descripción del reflectivo es requerida');
            return false;
        }

        return true;
    }

    /**
     * Validar formulario completo de pedido
     * @param {Object} formData - Datos del formulario
     * @returns {boolean}
     */
    validatePedidoCompleto(formData) {
        this.clearErrors();

        // Validar información básica
        this.required(formData.cliente, 'Cliente');
        this.required(formData.asesora, 'Asesora');

        // Validar según tipo de pedido
        if (formData.tipo === 'P' || formData.tipo === 'PL') {
            // Pedido de prendas (permite prendas o EPPs)
            if (!this.validatePrendas(formData.prendas, formData.epps)) {
                return false;
            }
        }

        if (formData.tipo === 'L' || formData.tipo === 'PL') {
            // Pedido de logo
            if (!this.validateLogo(formData.logo)) {
                return false;
            }
        }

        if (formData.tipo === 'RF') {
            // Pedido reflectivo
            if (!this.validateReflectivo(formData.reflectivo)) {
                return false;
            }
        }

        return !this.hasErrors();
    }

    // ============================================================
    // VALIDACIONES DE IMÁGENES
    // ============================================================

    /**
     * Validar archivo de imagen
     * @param {File} file - Archivo a validar
     * @returns {boolean}
     */
    validateImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (!file) {
            this.addError('imagen', 'Debe seleccionar un archivo');
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            this.addError('imagen', 'Tipo de archivo no permitido. Use JPG, PNG o WebP');
            return false;
        }

        if (file.size > maxSize) {
            this.addError('imagen', 'El archivo es demasiado grande. Máximo 10MB');
            return false;
        }

        return true;
    }

    /**
     * Validar múltiples archivos de imagen
     * @param {FileList|Array} files - Archivos a validar
     * @param {number} maxFiles - Máximo de archivos permitidos
     * @returns {boolean}
     */
    validateImageFiles(files, maxFiles = 10) {
        if (!files || files.length === 0) {
            this.addError('imagenes', 'Debe seleccionar al menos un archivo');
            return false;
        }

        if (files.length > maxFiles) {
            this.addError('imagenes', `Máximo ${maxFiles} archivos permitidos`);
            return false;
        }

        // Validar cada archivo
        for (let i = 0; i < files.length; i++) {
            if (!this.validateImageFile(files[i])) {
                return false;
            }
        }

        return true;
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Mostrar errores al usuario
     * @param {string} title - Título del mensaje
     */
    showErrors(title = 'Errores de Validación') {
        if (!this.hasErrors()) {
            return;
        }

        const errorList = this.errors.map(err => `• ${err.field}: ${err.message}`).join('\n');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: title,
                html: `<div style="text-align: left; white-space: pre-line;">${errorList}</div>`,
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert(`${title}\n\n${errorList}`);
        }
    }

    /**
     * Validar y mostrar errores si los hay
     * @param {Function} validationFn - Función de validación
     * @param {string} title - Título del mensaje de error
     * @returns {boolean}
     */
    validateAndShow(validationFn, title = 'Errores de Validación') {
        const isValid = validationFn();
        
        if (!isValid) {
            this.showErrors(title);
        }

        return isValid;
    }

    /**
     * Obtener resumen de errores
     * @returns {string}
     */
    getErrorSummary() {
        if (!this.hasErrors()) {
            return 'Sin errores';
        }

        return this.errors.map(err => `${err.field}: ${err.message}`).join('; ');
    }

    /**
     * Validar campo en tiempo real (para usar con eventos input)
     * @param {HTMLElement} input - Input a validar
     * @param {Function} validationFn - Función de validación
     */
    validateRealtime(input, validationFn) {
        input.addEventListener('input', () => {
            this.clearErrors();
            const isValid = validationFn(input.value);
            
            if (isValid) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
        });
    }
}

// Crear instancia global
window.ValidationService = new ValidationService();
