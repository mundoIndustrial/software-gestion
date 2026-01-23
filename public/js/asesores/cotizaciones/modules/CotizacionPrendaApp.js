/**
 * CotizacionPrendaApp - Orquestador principal
 * 
 * Responsabilidad: Coordinar todos los módulos y eventos
 * Patrón: Mediator/Facade
 * 
 * @module CotizacionPrendaApp
 */
class CotizacionPrendaApp {
    constructor() {
        this.modules = {
            form: null,
            producto: null,
            especificaciones: null,
            validation: null,
            tallas: null
        };
    }

    /**
     * Inicializa la aplicación
     */
    init() {


        // Inicializar módulos en orden de dependencias
        this.modules.validation = validationModule;
        this.modules.tallas = tallasModule;
        this.modules.especificaciones = especificacionesModule;
        this.modules.producto = productoModule;
        this.modules.form = formModule;

        // Inicializar cada módulo
        this.modules.validation.init?.();
        this.modules.tallas.init?.();
        this.modules.especificaciones.init?.();
        this.modules.producto.init?.();
        this.modules.form.init?.();

        // Agregar primer producto
        this.modules.producto.agregarProducto();

        // Configurar listeners globales
        this.setupGlobalListeners();


    }

    /**
     * Configura listeners globales
     */
    setupGlobalListeners() {
        // Nota: Los botones del menú flotante ahora manejan su propio onclick en el HTML
        // No agregamos addEventListener aquí para evitar conflictos
        // Los handlers se llaman directamente desde el HTML:
        // - agregarProductoPrenda() para agregar
        // - abrirModalEspecificaciones() para especificaciones
    }

    /**
     * Toggle del menú flotante
     */
    toggleMenuFlotante() {
        const menu = document.getElementById('menuFlotante');
        const btn = document.getElementById('btnFlotante');

        if (!menu || !btn) return;

        const isVisible = menu.style.display === 'block';
        menu.style.display = isVisible ? 'none' : 'block';
        btn.style.transform = isVisible ? 'scale(1) rotate(0deg)' : 'scale(1) rotate(45deg)';
    }

    /**
     * Maneja la adición de un nuevo producto
     */
    onAgregarProducto() {
        const productoId = this.modules.producto.agregarProducto();


        // Cerrar menú flotante
        const menu = document.getElementById('menuFlotante');
        if (menu) menu.style.display = 'none';
    }

    /**
     * Maneja la eliminación de producto
     */
    onEliminarProducto(productoCard) {
        this.modules.producto.eliminarProducto(productoCard);
    }

    /**
     * Obtiene el estado actual de la aplicación
     */
    getState() {
        return {
            form: this.modules.form?.getState?.(),
            productos: this.modules.producto?.getTodosProductos?.(),
            especificaciones: this.modules.especificaciones?.getEspecificaciones?.(),
        };
    }

    /**
     * Valida la aplicación completa
     */
    validate() {
        const errors = [];

        // Validar formulario
        const formValidation = this.modules.form?.validate?.();
        if (!formValidation?.valid) {
            errors.push(...formValidation.errors);
        }

        // Validar productos
        const productosValidation = this.modules.producto?.validarProductos?.();
        if (!productosValidation?.valid) {
            errors.push({
                field: 'productos',
                message: productosValidation.message
            });
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    /**
     * Guarda la cotización
     */
    async guardar(action = 'borrador') {
        const validation = this.validate();

        if (!validation.valid) {

            this.showValidationErrors(validation.errors);
            return false;
        }

        try {
            const formData = this.modules.form?.buildFormData?.(action);
            if (!formData) {
                throw new Error('No se pudo construir el formulario');
            }

            const response = await this.submitForm(formData);

            if (response.success) {
                this.modules.form?.handleSuccess?.(response);
                return true;
            } else {
                this.modules.form?.handleError?.(response);
                return false;
            }
        } catch (error) {

            alert('Error: ' + error.message);
            return false;
        }
    }

    /**
     * Envía el formulario al servidor
     */
    async submitForm(formData) {
        const response = await fetch(window.routes?.guardarCotizacion || '/asesores/cotizaciones/guardar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        });

        return await response.json();
    }

    /**
     * Muestra errores de validación
     */
    showValidationErrors(errors) {
        const message = errors.map(e => ` ${e.message}`).join('\n\n');
        alert(message);
    }

    /**
     * Reinicia la aplicación
     */
    reset() {
        this.modules.especificaciones?.limpiar?.();

    }
}

// Crear instancia global
const app = new CotizacionPrendaApp();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    app.init();
});

// Exportar para compatibilidad con funciones globales existentes
window.agregarProductoPrenda = () => app.onAgregarProducto();
window.eliminarProductoPrenda = (btn) => app.onEliminarProducto(btn.closest('.producto-card'));
window.guardarCotizacionPrenda = (action) => app.guardar(action);
