/**
 * MANUAL PRENDA HANDLER - Lavandería
 * Maneja la adición de prendas manuales sin recibo
 */

class ManualPrendaHandler {
    constructor() {
        this.manualPrendas = [];
        this.nextTempId = -1; // IDs temporales negativos para prendas manuales
    }

    normalizeGenero(genero) {
        return String(genero || '').trim().toUpperCase();
    }

    /**
     * Agrega una prenda manual
     */
    addManualPrenda(descripcion, genero = null) {
        const tempId = this.nextTempId;
        this.nextTempId--;

        this.manualPrendas.push({
            id: tempId,
            descripcion: descripcion,
            genero: this.normalizeGenero(genero),
            tipo: 'MANUAL',
            tallas: [],
            selectedTallas: []
        });

        return tempId;
    }

    /**
     * Elimina una prenda manual
     */
    removeManualPrenda(tempId) {
        this.manualPrendas = this.manualPrendas.filter(p => p.id !== tempId);
    }

    /**
     * Obtiene una prenda manual
     */
    getManualPrenda(tempId) {
        return this.manualPrendas.find(p => p.id === tempId);
    }

    /**
     * Actualiza el género de una prenda manual
     */
    setGeneroForManualPrenda(tempId, genero) {
        const prenda = this.getManualPrenda(tempId);
        if (prenda) {
            prenda.genero = this.normalizeGenero(genero);
        }
    }

    /**
     * Obtiene todas las prendas manuales
     */
    getAllManualPrendas() {
        return this.manualPrendas;
    }

    /**
     * Establece las tallas seleccionadas para una prenda manual
     */
    setSelectedTallasForManualPrenda(tempId, tallas) {
        const prenda = this.getManualPrenda(tempId);
        if (prenda) {
            prenda.selectedTallas = tallas;
        }
    }

    /**
     * Obtiene las tallas seleccionadas para una prenda manual
     */
    getSelectedTallasForManualPrenda(tempId) {
        const prenda = this.getManualPrenda(tempId);
        return prenda ? prenda.selectedTallas : [];
    }

    /**
     * Limpia todas las prendas manuales
     */
    clear() {
        this.manualPrendas = [];
        this.nextTempId = -1;
    }

    /**
     * Obtiene el total de prendas manuales
     */
    getCount() {
        return this.manualPrendas.length;
    }
}

export { ManualPrendaHandler };
