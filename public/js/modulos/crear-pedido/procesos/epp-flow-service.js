/**
 * Servicio de flujo EPP (agregar/eliminar) extraido desde GestionItemsUI.
 */
class EppFlowService {
    constructor(options = {}) {
        this.ui = options.ui || null;
    }

    _normalizarId(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return '';
        }

        let texto = String(valor).trim();
        if (!texto) {
            return '';
        }

        texto = texto.replace(/^epp[-:]/i, '');
        return texto;
    }

    _obtenerEppDesdeColeccionPorId(tarjetaId, coleccion = []) {
        const buscado = this._normalizarId(tarjetaId);
        if (!buscado || !Array.isArray(coleccion)) {
            return { index: -1, item: null };
        }

        const index = coleccion.findIndex((epp) => {
            const candidatos = [
                epp?.tarjetaId,
                epp?.pedido_epp_id,
                epp?.pedidoEppId,
                epp?.epp_id,
                epp?.id,
                epp?.data_epp_original_id
            ].map((value) => this._normalizarId(value));

            return candidatos.includes(buscado);
        });

        return index >= 0 ? { index, item: coleccion[index] } : { index: -1, item: null };
    }

    agregarEPPAlOrden(epp) {
        if (epp && !Array.isArray(epp._imagenes_originales)) {
            const imagenesOriginales = Array.isArray(epp.imagenes) ? epp.imagenes : [];
            epp._imagenes_originales = imagenesOriginales.map((img) => (
                img && typeof img === 'object'
                    ? { ...img }
                    : img
            ));
        }

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
            const buscado = this._normalizarId(tarjetaId);
            const coleccion = this.ui?.epps || [];
            const encontrado = this._obtenerEppDesdeColeccionPorId(tarjetaId, coleccion);

            // Fuente canónica, si existe, para evitar desalineaciones entre DOM y estado interno.
            if (window.eppStore && typeof window.eppStore.eliminarItem === 'function' && buscado) {
                window.eppStore.eliminarItem(buscado);
            }

            if (encontrado.index >= 0) {
                this.ui?._stateRemoveItem?.('epp', encontrado.index);
                debugLog(`[gestionItemsUI]  EPP eliminado del array. Quedan: ${this.ui?.epps?.length || 0}`);

                const ordenIdx = this._findOrdenItemIndexForEpp(encontrado.index);
                if (ordenIdx >= 0 && Array.isArray(this.ui?.ordenItems)) {
                    this.ui.ordenItems.splice(ordenIdx, 1);
                }

                this._rebuildOrdenIndices();

                debugLog('[gestionItemsUI]  ordenItems actualizado:', JSON.stringify(this.ui?.ordenItems || []));
                debugLog(`[gestionItemsUI]  EPPs restantes: ${this.ui?.epps?.length || 0}, Prendas: ${this.ui?.prendas?.length || 0}`);
                return true;
            }

            // Fallback visual: si el estado aún no está sincronizado, retiramos la fila visible.
            const selectorFila = [
                `.item-epp[data-item-id="${tarjetaId}"]`,
                `.item-epp[data-item-id="${buscado}"]`,
                `.item-epp-card-nuevo[data-epp-id="${tarjetaId}"]`,
                `.item-epp-card-nuevo[data-epp-id="epp-${buscado}"]`,
                `.item-epp-card[data-epp-id="${tarjetaId}"]`,
                `.item-epp-card[data-epp-id="epp-${buscado}"]`
            ].join(', ');
            const fila = document.querySelector(selectorFila);
            if (fila) {
                fila.remove();
                this._rebuildOrdenIndices();
                console.warn('[gestionItemsUI] EPP eliminado solo del DOM por desincronizacion temporal:', tarjetaId);
                return true;
            }

            console.warn('[gestionItemsUI] No se pudo eliminar EPP - no encontrado en estado ni DOM:', tarjetaId);
            return false;
        } catch (error) {
            console.error('[gestionItemsUI] Error eliminando EPP:', error);
            return false;
        }
    }

    _getEppPositionFromDom(tarjetaId) {
        const tarjetas = document.querySelectorAll('.item-epp-card-nuevo, .item-epp-card, .item-epp');
        const buscado = this._normalizarId(tarjetaId);
        for (let i = 0; i < tarjetas.length; i++) {
            const dataId = this._normalizarId(tarjetas[i].dataset.eppId || tarjetas[i].dataset.itemId || tarjetas[i].dataset.eppOriginalId);
            if (dataId === buscado || dataId === this._normalizarId(tarjetaId)) {
                return i;
            }
        }
        return -1;
    }

    _resolveEppIndexById(tarjetaId) {
        const epps = this.ui?.epps || [];
        const encontrado = this._obtenerEppDesdeColeccionPorId(tarjetaId, epps);

        if (encontrado.index >= 0) {
            return encontrado.index;
        }

        // Fallback defensivo para casos heredados: intentar por posicion visual.
        const posicionVisual = this._getEppPositionFromDom(tarjetaId);
        if (posicionVisual >= 0) {
            return posicionVisual;
        }

        return this._getLastEppIndex();
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
