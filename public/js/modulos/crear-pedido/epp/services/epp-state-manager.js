/**
 * EppStateManager - Gestiona el estado de los EPPs
 * Patrón: State Management
 */

class EppStateManager {
    constructor() {
        this.estado = {
            productoSeleccionado: null,
            imagenesSubidas: [],
            editandoId: null,
            editandoDesdeDB: false,
            itemsData: {}
        };
    }

    /**
     * Establecer producto seleccionado
     */
    setProductoSeleccionado(producto) {
        this.estado.productoSeleccionado = producto;

    }

    /**
     * Obtener producto seleccionado
     */
    getProductoSeleccionado() {
        return this.estado.productoSeleccionado;
    }

    /**
     * Agregar imagen subida
     */
    agregarImagenSubida(imagen) {
        this.estado.imagenesSubidas.push(imagen);

    }

    /**
     * Obtener imágenes subidas
     */
    getImagenesSubidas() {
        return this.estado.imagenesSubidas;
    }

    /**
     * Limpiar imágenes subidas
     */
    limpiarImagenesSubidas() {
        this.estado.imagenesSubidas = [];

    }

    /**
     * Eliminar imagen subida por ID
     */
    eliminarImagenSubida(imagenId) {
        this.estado.imagenesSubidas = this.estado.imagenesSubidas.filter(img => img.id !== imagenId);

    }

    /**
     * Iniciar edición
     */
    iniciarEdicion(eppId, desdeDB = false) {
        this.estado.editandoId = eppId;
        this.estado.editandoDesdeDB = desdeDB;

    }

    /**
     * Obtener ID siendo editado
     */
    getEditandoId() {
        return this.estado.editandoId;
    }

    /**
     * ¿Está editando desde BD?
     */
    isEditandoDesdeDB() {
        return this.estado.editandoDesdeDB;
    }

    /**
     * Finalizar edición
     */
    finalizarEdicion() {
        this.estado.editandoId = null;
        this.estado.editandoDesdeDB = false;
        this.limpiarImagenesSubidas();

    }

    /**
     * Guardar datos de item para edición
     */
    guardarDatosItem(id, datos) {
        this.estado.itemsData[id] = datos;
    }

    /**
     * Obtener datos de item
     */
    obtenerDatosItem(id) {
        return this.estado.itemsData[id];
    }

    /**
     * Obtener todo el estado (para debugging)
     */
    getEstado() {
        return { ...this.estado };
    }

    /**
     * Resetear estado completo
     */
    resetear() {
        this.estado = {
            productoSeleccionado: null,
            imagenesSubidas: [],
            editandoId: null,
            editandoDesdeDB: false,
            itemsData: {}
        };

    }
}

// Exportar instancia global
window.eppStateManager = new EppStateManager();
