/**
 * Indexed Image Storage Service
 *
 * Extiende ImageStorageService para mantener almacenamiento SEPARADO por prenda.
 * Previene la desincronización de imágenes cuando se editan múltiples prendas.
 *
 * ✅ PROBLEMA RESUELTO: Ya no hay conflicto entre almacenamientos globales
 * Cada prenda tiene su propio contexto de imágenes
 */

class IndexedImageStorageService {
    constructor(maxImagesPerPrenda = 6) {
        this.maxImagesPerPrenda = maxImagesPerPrenda;
        this.storageByPrenda = new Map(); // Map<prendaIndex/localId, ImageStorageService>
        this.prendaActualIndex = null; // Índice o ID de la prenda actualmente abierta
    }

    /**
     * Establecer la prenda activa
     * Busca o crea almacenamiento para esa prenda
     */
    setPrendaActual(prendaIndexOrId) {
        this.prendaActualIndex = prendaIndexOrId;

        // Si no existe storage para esta prenda, crearla
        if (!this.storageByPrenda.has(this.prendaActualIndex)) {
            this.storageByPrenda.set(this.prendaActualIndex, new ImageStorageService(this.maxImagesPerPrenda));
            console.log(`[IndexedImageStorageService] Storage creado para prenda: ${prendaIndexOrId}`);
        }

        return this.storageByPrenda.get(this.prendaActualIndex);
    }

    /**
     * Obtener storage actual sin cambiar la prenda activa
     */
    getStorageActual() {
        if (this.prendaActualIndex === null) {
            console.warn('[IndexedImageStorageService] ⚠️ setPrendaActual() no fue llamado - usando almacenamiento por defecto');
            return this.setPrendaActual('default');
        }
        return this.storageByPrenda.get(this.prendaActualIndex);
    }

    /**
     * Obtener storage para una prenda específica (sin cambiar actual)
     */
    getStoragePara(prendaIndexOrId) {
        if (!this.storageByPrenda.has(prendaIndexOrId)) {
            this.storageByPrenda.set(prendaIndexOrId, new ImageStorageService(this.maxImagesPerPrenda));
        }
        return this.storageByPrenda.get(prendaIndexOrId);
    }

    /**
     * Delegados hacia el storage actual
     */
    async agregarImagen(file) {
        return this.getStorageActual().agregarImagen(file);
    }

    obtenerImagenes() {
        return this.getStorageActual().obtenerImagenes();
    }

    establecerImagenes(nuevasImagenes) {
        return this.getStorageActual().establecerImagenes(nuevasImagenes);
    }

    obtenerImagen(index) {
        return this.getStorageActual().obtenerImagen(index);
    }

    eliminarImagen(index) {
        return this.getStorageActual().eliminarImagen(index);
    }

    contar() {
        return this.getStorageActual().contar();
    }

    tieneEspacio() {
        return this.getStorageActual().tieneEspacio();
    }

    limpiar() {
        return this.getStorageActual().limpiar();
    }

    obtenerArchivos() {
        return this.getStorageActual().obtenerArchivos();
    }

    toFormData(fieldName = 'imagenes') {
        return this.getStorageActual().toFormData(fieldName);
    }

    /**
     * NUEVO: Obtener imágenes de una prenda específica
     * Usado cuando se serializa para enviar al servidor
     */
    obtenerImagenesDe(prendaIndexOrId) {
        const storage = this.getStoragePara(prendaIndexOrId);
        return storage.obtenerImagenes();
    }

    /**
     * NUEVO: Limpiar storage de una prenda específica
     * Llamado cuando se elimina una prenda
     */
    limpiarPrenda(prendaIndexOrId) {
        if (this.storageByPrenda.has(prendaIndexOrId)) {
            const storage = this.storageByPrenda.get(prendaIndexOrId);
            storage.limpiar();
            this.storageByPrenda.delete(prendaIndexOrId);
            console.log(`[IndexedImageStorageService] Storage limpiado para prenda: ${prendaIndexOrId}`);
        }
    }

    /**
     * NUEVO: Obtener diagnóstico de todos los storages
     * Para debug/monitoreo
     */
    getDiagnostico() {
        const diagnostico = {};
        this.storageByPrenda.forEach((storage, prendaId) => {
            diagnostico[prendaId] = {
                imagenes: storage.contar(),
                activa: prendaId === this.prendaActualIndex
            };
        });
        return {
            prendaActual: this.prendaActualIndex,
            storages: diagnostico,
            total: this.storageByPrenda.size
        };
    }
}

// Asignar a window para disponibilidad global
window.IndexedImageStorageService = IndexedImageStorageService;
