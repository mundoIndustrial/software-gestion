/**
 * PrendaCardContextService
 * Gestiona dependencias externas para PrendaCardService.
 */
window.PrendaCardContextService = {
    _base: {
        imageConverter: null,
        coloresPorTallaStore: null,
        gestionItemsUI: null,
        showProcessImage: null,
    },

    configurarBase(contexto = {}) {
        this._base = {
            ...this._base,
            ...contexto,
        };
    },

    crear(overrides = {}) {
        return {
            ...this._base,
            ...overrides,
        };
    },

    validar(ctx) {
        if (!ctx || typeof ctx !== 'object') {
            throw new Error('[PrendaCardService] Contexto requerido. Pasa un objeto ctx con dependencias.');
        }
        if (!ctx.imageConverter) {
            throw new Error('[PrendaCardService] Falta ctx.imageConverter.');
        }
        if (typeof ctx.showProcessImage !== 'function') {
            throw new Error('[PrendaCardService] Falta ctx.showProcessImage(src).');
        }
    }
};
