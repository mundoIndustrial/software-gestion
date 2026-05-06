(function () {
    "use strict";

    class PedidoSessionStore {
        constructor(options) {
            var opts = options || {};
            this.ui = opts.gestionItemsUI || window.gestionItemsUI || null;
        }

        _refreshUIRef() {
            if (!this.ui && window.gestionItemsUI) {
                this.ui = window.gestionItemsUI;
            }
        }

        _items() {
            this._refreshUIRef();
            if (!this.ui || typeof this.ui.obtenerItemsOrdenados !== "function") {
                return [];
            }
            return this.ui.obtenerItemsOrdenados() || [];
        }

        addItem(item) {
            this._refreshUIRef();
            if (!this.ui || !item) return false;

            if (typeof window.asegurarLocalId === "function") {
                window.asegurarLocalId(item, item.tipo || "item");
            }

            if (item.tipo === "epp") {
                return typeof this.ui.agregarEPPAlOrden === "function" ? this.ui.agregarEPPAlOrden(item) : false;
            }

            return typeof this.ui.agregarPrendaAlOrden === "function" ? this.ui.agregarPrendaAlOrden(item) : false;
        }

        updateItem(localId, patch) {
            const items = this._items();
            const idx = items.findIndex(function (it) { return it && it._local_id === localId; });
            if (idx < 0) return false;

            Object.assign(items[idx], patch || {});
            this._refreshUIRef();
            if (this.ui && this.ui.renderer && typeof this.ui.renderer.actualizar === "function") {
                this.ui.renderer.actualizar(this._items());
            }
            return true;
        }

        removeItem(ref) {
            if (typeof window.eliminarItemPedidoSeguro === "function") {
                return window.eliminarItemPedidoSeguro(ref);
            }
            return false;
        }

        clear() {
            if (typeof window.hardResetPedidoState === "function") {
                window.hardResetPedidoState("pedido-session-store-clear");
            }
        }

        snapshot() {
            return JSON.parse(JSON.stringify(this._items()));
        }

        toPayload() {
            const items = this.snapshot();
            const prendas = items.filter(function (it) { return it && it.tipo !== "epp"; });
            const epps = items.filter(function (it) { return it && it.tipo === "epp"; });
            return {
                items: items,
                prendas: prendas,
                epps: epps
            };
        }
    }

    function getPedidoSessionStore() {
        if (!window.pedidoSessionStore || !(window.pedidoSessionStore instanceof PedidoSessionStore)) {
            window.pedidoSessionStore = new PedidoSessionStore({ gestionItemsUI: window.gestionItemsUI });
        } else if (!window.pedidoSessionStore.ui && window.gestionItemsUI) {
            window.pedidoSessionStore.ui = window.gestionItemsUI;
        }
        return window.pedidoSessionStore;
    }

    window.PedidoSessionStore = PedidoSessionStore;
    window.getPedidoSessionStore = getPedidoSessionStore;
})();

