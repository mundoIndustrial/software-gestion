/**
 * Servicio de eliminacion de items (prenda/EPP) extraido desde GestionItemsUI.
 */
class ItemRemovalService {
    constructor(options = {}) {
        this.ui = options.ui || null;
    }

    async eliminarItem(index) {
        const result = await Swal.fire({
            title: '¿Eliminar este ítem?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        });

        if (!result.isConfirmed) return;

        try {
            if (!this.ui?._tieneServiciosBase?.()) {
                return;
            }

            const itemInfo = this._getItemInfoByPosition(index);
            if (!itemInfo) {
                console.warn('[eliminarItem] Item no encontrado en posicion:', index);
                return;
            }

            this._removeItemByInfo(itemInfo, index);
            await this.ui?._actualizarRenderItemsOrdenados?.();
            this.ui?.notificationService?.exito('Ítem eliminado');
        } catch (error) {
            this.ui?.notificationService?.error('Error: ' + error.message);
        }
    }

    _getItemInfoByPosition(index) {
        const itemsOrdenados = this.ui?.obtenerItemsOrdenados?.() || [];
        if (index < 0 || index >= itemsOrdenados.length) {
            return null;
        }

        const item = itemsOrdenados[index];
        let tipo = null;
        let indice = -1;

        if (item?.nombre_prenda) {
            tipo = 'prenda';
            indice = this.ui?.prendas?.indexOf(item) ?? -1;
        } else if (item?.nombre_completo || item?.nombre) {
            tipo = 'epp';
            indice = this.ui?.epps?.indexOf(item) ?? -1;
        }

        if (!tipo || indice < 0) {
            return null;
        }

        return { tipo, indice, item };
    }

    _removeItemByInfo(itemInfo, ordenIndex) {
        const { tipo, indice } = itemInfo;

        if (tipo === 'prenda') {
            const prendaAEliminar = this.ui?.prendas?.[indice] || {};
            const prendaIdExistente = prendaAEliminar.prenda_pedido_id || prendaAEliminar.id || null;

            if (prendaIdExistente) {
                this.ui?.prendasEliminadas?.push({
                    prenda_id: Number(prendaIdExistente),
                    nombre_prenda: prendaAEliminar.nombre_prenda || prendaAEliminar.nombre_producto || 'PRENDA',
                    motivo: 'Eliminada desde edicion de borrador'
                });
                debugLog('[eliminarItem]  Prenda existente marcada para eliminar en backend:', {
                    prenda_id: prendaIdExistente,
                    total_prendas_eliminadas: this.ui?.prendasEliminadas?.length || 0
                });
            }
        }

        if (tipo === 'prenda' && indice >= 0) {
            this.ui?._stateRemoveItem?.('prenda', indice);
            debugLog(`[eliminarItem]  Prenda eliminada del array. Quedan: ${this.ui?.prendas?.length || 0}`);
        } else if (tipo === 'epp' && indice >= 0) {
            this.ui?._stateRemoveItem?.('epp', indice);
            debugLog(`[eliminarItem]  EPP eliminado del array. Quedan: ${this.ui?.epps?.length || 0}`);
        }

        if (Array.isArray(this.ui?.ordenItems)) {
            this.ui.ordenItems.splice(ordenIndex, 1);
        }
        this.ui?._rebuildOrdenIndices?.();

        debugLog('[eliminarItem]  ordenItems actualizado:', JSON.stringify(this.ui?.ordenItems || []));

        if (tipo === 'prenda' && this.ui?._ctx?.('gestorPrendaSinCotizacion')?.eliminar) {
            this.ui._ctx('gestorPrendaSinCotizacion').eliminar(indice);
        }
    }
}
