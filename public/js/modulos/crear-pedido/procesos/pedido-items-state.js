/**
 * Estado de items del pedido (prendas + EPPs + orden visual).
 * Servicio extraído para reducir acoplamiento en GestionItemsUI.
 */
class PedidoItemsState {
    constructor(options = {}) {
        this.prendas = Array.isArray(options.prendas) ? options.prendas : [];
        this.epps = Array.isArray(options.epps) ? options.epps : [];
        this.ordenItems = Array.isArray(options.ordenItems) ? options.ordenItems : [];
        this.prendasEliminadas = Array.isArray(options.prendasEliminadas) ? options.prendasEliminadas : [];
    }

    getCollection(tipo) {
        if (tipo === 'prenda') return this.prendas;
        if (tipo === 'epp') return this.epps;
        return null;
    }

    addItem(tipo, item) {
        const collection = this.getCollection(tipo);
        if (!collection) return -1;

        const index = collection.length;
        collection.push(item);
        this.ordenItems.push({ tipo, index });
        return index;
    }

    removeItem(tipo, index) {
        const collection = this.getCollection(tipo);
        if (!collection || index < 0 || index >= collection.length) return false;
        collection.splice(index, 1);
        return true;
    }

    obtenerItemsOrdenados() {
        const itemsOrdenados = [];
        this.ordenItems.forEach(({ tipo, index }) => {
            const collection = this.getCollection(tipo);
            if (collection && collection[index]) {
                itemsOrdenados.push(collection[index]);
            }
        });
        return itemsOrdenados;
    }

    findOrdenItemIndexForEpp(eppIndex) {
        let eppCount = 0;
        for (let i = 0; i < this.ordenItems.length; i++) {
            if (this.ordenItems[i].tipo === 'epp') {
                if (eppCount === eppIndex) {
                    return i;
                }
                eppCount++;
            }
        }
        return -1;
    }

    rebuildOrdenIndices() {
        let prendaIdx = 0;
        let eppIdx = 0;
        this.ordenItems.forEach(item => {
            if (item.tipo === 'prenda') {
                item.index = prendaIdx++;
            } else if (item.tipo === 'epp') {
                item.index = eppIdx++;
            }
        });
    }

    getLastEppIndex() {
        const eppPositions = this.ordenItems.filter(item => item.tipo === 'epp');
        if (eppPositions.length === 0) return -1;
        return eppPositions.length - 1;
    }

    isPosicionValidaEpp(index) {
        return Number.isInteger(index) && index >= 0 && index < this.epps.length;
    }
}

