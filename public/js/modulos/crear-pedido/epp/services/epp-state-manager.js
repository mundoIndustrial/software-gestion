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
            pedidoEppId: null,
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
        console.log('🗑️ [EppStateManager] eliminarImagenSubida() llamado con ID:', imagenId, 'tipo:', typeof imagenId);
        console.log('🗑️ [EppStateManager] Imágenes en estado ANTES:', this.estado.imagenesSubidas.map(img => ({id: img.id, tipo: typeof img.id})));
        
        const imagenesAntes = this.estado.imagenesSubidas.length;
        
        this.estado.imagenesSubidas = this.estado.imagenesSubidas.filter(img => {
            const coincide = String(img.id) !== String(imagenId);
            console.log(`   Comparando: img.id=${img.id} (${typeof img.id}) vs imagenId=${imagenId} (${typeof imagenId}) => ${coincide ? 'MANTIENE' : 'ELIMINA'}`);
            return coincide;
        });
        
        const imagenesDepues = this.estado.imagenesSubidas.length;
        console.log(`✅ [EppStateManager] Eliminadas: ${imagenesAntes - imagenesDepues} imagen(es)`);
        console.log('✅ [EppStateManager] Imágenes en estado DESPUÉS:', this.estado.imagenesSubidas.map(img => ({id: img.id, nombre: img.nombre})));
    }

    /**
     * Iniciar edición
     */
    iniciarEdicion(eppId, desdeDB = false, pedidoEppId = null) {
        this.estado.editandoId = eppId;
        this.estado.editandoDesdeDB = desdeDB;
        this.estado.pedidoEppId = pedidoEppId;

    }

    /**
     * Obtener ID siendo editado
     */
    getEditandoId() {
        return this.estado.editandoId;
    }

    /**
     * Obtener pedido_epp_id siendo editado
     */
    getPedidoEppId() {
        return this.estado.pedidoEppId;
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
        this.estado.pedidoEppId = null;
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
            pedidoEppId: null,
            editandoDesdeDB: false,
            itemsData: {}
        };

    }
}

// Exportar instancia global
window.eppStateManager = new EppStateManager();
