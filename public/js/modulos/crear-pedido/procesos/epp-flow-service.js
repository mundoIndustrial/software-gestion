/**
 * Servicio de flujo EPP (agregar/eliminar) extraido desde GestionItemsUI.
 */
class EppFlowService {
    constructor(options = {}) {
        this.ui = options.ui || null;
    }

    agregarEPPAlOrden(epp) {
        const index = this.ui?._stateAddItem?.('epp', epp);

        debugLog('[gestionItemsUI]  agregarEPPAlOrden() - EPP agregado:', epp?.nombre_completo || epp?.nombre);
        debugLog('[gestionItemsUI]  agregarEPPAlOrden() - Nuevo index EPP:', index);
        debugLog('[gestionItemsUI]  agregarEPPAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ui?.ordenItems || []));
        debugLog('[gestionItemsUI]  agregarEPPAlOrden() - Total EPPs:', this.ui?.epps?.length || 0);

        return typeof index === 'number' ? index : -1;
    }

    async agregarEPPDesdeModal(eppData) {
        try {
            debugLog('[gestionItemsUI]  agregarEPPDesdeModal() iniciado con EPP:', eppData?.nombre_completo || eppData?.nombre);

            this.agregarEPPAlOrden(eppData);

            debugLog('[gestionItemsUI]  Despues de agregarEPPAlOrden()');
            debugLog('[gestionItemsUI]  this.epps:', this.ui?.epps?.length || 0);
            debugLog('[gestionItemsUI]  this.ordenItems:', JSON.stringify(this.ui?.ordenItems || []));

            this.ui?.notificationService?.exito('EPP agregado correctamente');
            await this.ui?._actualizarRenderItemsOrdenados?.();
            return true;
        } catch (error) {
            this.ui?.notificationService?.error('Error al agregar EPP: ' + error.message);
            return false;
        }
    }

    eliminarEPPPorTarjetaId(tarjetaId) {
        try {
            const posicionVisual = this._getEppPositionFromDom(tarjetaId);
            const eppIdx = posicionVisual >= 0 ? posicionVisual : this._getLastEppIndex();

            if (!this._isPosicionValidaEpp(eppIdx)) {
                console.warn('[gestionItemsUI] No se pudo eliminar EPP - posicion invalida:', eppIdx);
                return false;
            }

            this.ui?._stateRemoveItem?.('epp', eppIdx);
            debugLog(`[gestionItemsUI]  EPP eliminado del array. Quedan: ${this.ui?.epps?.length || 0}`);

            const ordenIdx = this._findOrdenItemIndexForEpp(eppIdx);
            if (ordenIdx >= 0 && Array.isArray(this.ui?.ordenItems)) {
                this.ui.ordenItems.splice(ordenIdx, 1);
            }

            this._rebuildOrdenIndices();

            debugLog('[gestionItemsUI]  ordenItems actualizado:', JSON.stringify(this.ui?.ordenItems || []));
            debugLog(`[gestionItemsUI]  EPPs restantes: ${this.ui?.epps?.length || 0}, Prendas: ${this.ui?.prendas?.length || 0}`);
            return true;
        } catch (error) {
            console.error('[gestionItemsUI] Error eliminando EPP:', error);
            return false;
        }
    }

    _getEppPositionFromDom(tarjetaId) {
        const tarjetas = document.querySelectorAll('.item-epp-card-nuevo');
        for (let i = 0; i < tarjetas.length; i++) {
            if (tarjetas[i].dataset.eppId === tarjetaId) {
                return i;
            }
        }
        return -1;
    }

    _getLastEppIndex() {
        return this.ui?.itemsState?.getLastEppIndex?.() ?? -1;
    }

    _isPosicionValidaEpp(index) {
        return this.ui?.itemsState?.isPosicionValidaEpp?.(index) ?? false;
    }

    _findOrdenItemIndexForEpp(eppIndex) {
        return this.ui?.itemsState?.findOrdenItemIndexForEpp?.(eppIndex) ?? -1;
    }

    _rebuildOrdenIndices() {
        this.ui?.itemsState?.rebuildOrdenIndices?.();
    }
}
