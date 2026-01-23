/**
 * EppModalInterface - Interfaz clara y unificada del modal de EPP
 * Patrón: Facade
 * Responsabilidad: Proporcionar una interfaz única y limpia para interactuar con el modal
 */

class EppModalInterface {
    static initialize(eppService, eppImagenManager, eppCreationService, eppFormManager) {

        
        window.eppService = eppService;
        window.eppImagenManager = eppImagenManager;
        window.eppCreationService = eppCreationService;
        window.eppFormManager = eppFormManager;
    }

    /**
     * Abrir modal
     */
    static abrirModal() {
        if (window.eppService) {
            window.eppService.abrirModalAgregar();
        }
    }

    /**
     * Cerrar modal
     */
    static cerrarModal() {
        if (window.eppService) {
            window.eppService.cerrarModal();
        }
    }

    /**
     * Mostrar formulario de crear EPP
     */
    static mostrarFormularioCrear() {
        if (window.eppFormManager) {
            window.eppFormManager.mostrarFormularioCrear();
        }
    }

    /**
     * Ocultar formulario de crear EPP
     */
    static ocultarFormularioCrear() {
        if (window.eppFormManager) {
            window.eppFormManager.ocultarFormularioCrear();
        }
    }

    /**
     * Crear nuevo EPP
     */
    static async crearEPP() {
        try {
            if (!window.eppFormManager || !window.eppCreationService) {
                throw new Error('Servicios no inicializados');
            }

            const datos = window.eppFormManager.obtenerDatosFormularioCrear();
            const producto = await window.eppCreationService.crearEPP(datos);

            // Actualizar UI
            window.eppCreationService.actualizarUIPostCreacion(producto);
            window.eppFormManager.ocultarFormularioCrear();

            // Establecer como producto seleccionado
            if (window.eppService) {
                window.eppService.seleccionarProducto(producto);
            }

            return producto;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Agregar EPP al pedido
     */
    static agregarEPP() {
        if (window.eppService) {
            window.eppService.guardarEPP();
        }
    }

    /**
     * Editar EPP
     */
    static editarEPP(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
        if (window.eppService) {
            window.eppService.editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes);
        }
    }

    /**
     * Editar EPP desde BD
     */
    static async editarEPPDesdeDB(eppId) {
        if (window.eppService) {
            return window.eppService.editarEPPDesdeDB(eppId);
        }
    }

    /**
     * Eliminar EPP
     */
    static eliminarEPP(id) {
        if (window.eppService) {
            window.eppService.eliminarEPP(id);
        }
    }

    /**
     * Filtrar EPP en buscador
     */
    static filtrarEPP(valor) {
        if (window.eppService) {
            window.eppService.filtrarEPP(valor);
        }
    }

    /**
     * Actualizar estilos del botón
     */
    static actualizarBoton() {
        if (window.eppService) {
            window.eppService.actualizarBoton();
        }
    }

    /**
     * Manejar selección de imágenes
     */
    static async manejarImagenes(event) {
        if (window.eppImagenManager) {
            return window.eppImagenManager.manejarSeleccionImagenes(event);
        }
    }

    /**
     * Eliminar imagen
     */
    static async eliminarImagen(imagenId) {
        if (window.eppImagenManager) {
            return window.eppImagenManager.eliminarImagen(imagenId);
        }
    }
}

// Exportar clase
window.EppModalInterface = EppModalInterface;
