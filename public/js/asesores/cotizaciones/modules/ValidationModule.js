/**
 * ValidationModule - Validación de datos
 * 
 * Single Responsibility: Solo validaciones
 * 
 * @module ValidationModule
 */
class ValidationModule {
    constructor() {
        this.rules = new Map();
        this.setupDefaultRules();
    }

    /**
     * Configura reglas de validación por defecto
     */
    setupDefaultRules() {
        this.addRule('cliente', this.validarCliente.bind(this));
        this.addRule('tipo_cotizacion', this.validarTipoCotizacion.bind(this));
        this.addRule('productos', this.validarProductos.bind(this));
    }

    /**
     * Agrega una regla de validación
     */
    addRule(field, validator) {
        this.rules.set(field, validator);
    }

    /**
     * Valida un campo específico
     */
    validarCampo(field, value) {
        const validator = this.rules.get(field);
        if (!validator) {
            return { valid: true, message: '' };
        }
        return validator(value);
    }

    /**
     * Valida múltiples campos
     */
    validarMultiples(fields) {
        const errors = [];

        for (const [field, value] of Object.entries(fields)) {
            const result = this.validarCampo(field, value);
            if (!result.valid) {
                errors.push({
                    field,
                    message: result.message
                });
            }
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    /**
     * Validar cliente
     */
    validarCliente(value) {
        if (!value || !value.trim()) {
            return {
                valid: false,
                message: 'Por favor escribe el NOMBRE DEL CLIENTE'
            };
        }

        if (value.trim().length < 3) {
            return {
                valid: false,
                message: 'El nombre del cliente debe tener al menos 3 caracteres'
            };
        }

        return { valid: true, message: '' };
    }

    /**
     * Validar tipo de cotización
     */
    validarTipoCotizacion(value) {
        if (!value) {
            return {
                valid: false,
                message: 'Por favor selecciona el TIPO DE COTIZACIÓN (M, D o X)'
            };
        }

        const tiposValidos = ['M', 'D', 'X'];
        if (!tiposValidos.includes(value)) {
            return {
                valid: false,
                message: 'Tipo de cotización inválido'
            };
        }

        return { valid: true, message: '' };
    }

    /**
     * Validar productos
     */
    validarProductos(productos) {
        if (!productos || productos.length === 0) {
            return {
                valid: false,
                message: 'Debe agregar al menos una prenda'
            };
        }

        let tieneValido = false;
        productos.forEach(producto => {
            if (producto.nombre_producto && producto.nombre_producto.trim()) {
                tieneValido = true;
            }
        });

        return {
            valid: tieneValido,
            message: tieneValido ? '' : 'Al menos una prenda debe tener un nombre'
        };
    }

    /**
     * Valida estructura completa de formulario
     */
    validarFormularioCompleto() {
        const cliente = document.getElementById('header-cliente')?.value;
        const tipoCotizacion = document.getElementById('header-tipo-cotizacion')?.value;

        return this.validarMultiples({
            cliente,
            tipo_cotizacion: tipoCotizacion,
            productos: productoModule?.getTodosProductos() || []
        });
    }
}

// Exportar para uso global
const validationModule = new ValidationModule();
