(function () {
    "use strict";

    function ensureSessionId() {
        if (!window.PEDIDO_SESSION_ID) {
            window.PEDIDO_SESSION_ID = "pedido-" + Date.now() + "-" + Math.random().toString(36).slice(2, 8);
        }
        return window.PEDIDO_SESSION_ID;
    }

    function telemetryLog(action, payload, collapsed) {
        if (!window.PedidoTelemetry || typeof window.PedidoTelemetry.enabled !== "function" || !window.PedidoTelemetry.enabled()) {
            return;
        }
        var sessionId = ensureSessionId();
        var stamp = window.PedidoTelemetry.nowIso ? window.PedidoTelemetry.nowIso() : new Date().toISOString();
        var label = "[PedidoSessionStore][" + action + "][" + sessionId + "] " + stamp;
        window.PedidoTelemetry.group(label, payload, collapsed !== false);
    }

    class PedidoSessionStore {
        constructor(options) {
            var opts = options || {};
            this.ui = opts.gestionItemsUI || window.gestionItemsUI || null;
            this.sessionId = ensureSessionId();
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

            var ok = false;
            if (item.tipo === "epp") {
                ok = typeof this.ui.agregarEPPAlOrden === "function" ? this.ui.agregarEPPAlOrden(item) : false;
            } else {
                ok = typeof this.ui.agregarPrendaAlOrden === "function" ? this.ui.agregarPrendaAlOrden(item) : false;
            }
            telemetryLog("ADD", {
                _local_id: item._local_id || null,
                tipo: item.tipo || null,
                epp_id: item.epp_id || null,
                ref: item.nombre_epp || item.nombre_producto || item.nombre_prenda || item.nombre || null,
                ok: !!ok,
                totalItems: this._items().length
            }, true);
            return ok;
        }

        updateItem(localId, patch) {
            const items = this._items();
            const idx = items.findIndex(function (it) { return it && it._local_id === localId; });
            if (idx < 0) {
                telemetryLog("UPDATE_MISS", { _local_id: localId, totalItems: items.length }, true);
                return false;
            }

            Object.assign(items[idx], patch || {});
            this._refreshUIRef();
            if (this.ui && this.ui.renderer && typeof this.ui.renderer.actualizar === "function") {
                this.ui.renderer.actualizar(this._items());
            }
            telemetryLog("UPDATE", {
                _local_id: localId,
                tipo: items[idx].tipo || null,
                epp_id: items[idx].epp_id || null,
                patch: patch || {},
                totalItems: this._items().length
            }, true);
            return true;
        }

        removeItem(ref) {
            if (typeof window.eliminarItemPedidoSeguro === "function") {
                var ok = window.eliminarItemPedidoSeguro(ref);
                telemetryLog("REMOVE", {
                    ref: ref,
                    ok: !!ok,
                    totalItems: this._items().length
                }, true);
                return ok;
            }
            telemetryLog("REMOVE_FAIL", { ref: ref, reason: "eliminarItemPedidoSeguro unavailable" }, true);
            return false;
        }

        clear() {
            if (typeof window.hardResetPedidoState === "function") {
                telemetryLog("CLEAR", { reason: "PedidoSessionStore.clear", totalItemsBefore: this._items().length }, true);
                window.hardResetPedidoState("pedido-session-store-clear");
                telemetryLog("CLEAR_DONE", { totalItemsAfter: this._items().length }, true);
            }
        }

        snapshot() {
            var snap = JSON.parse(JSON.stringify(this._items()));
            telemetryLog("SNAPSHOT", {
                totalItems: snap.length,
                localIds: snap.map(function (it) { return it && it._local_id; }).filter(Boolean).slice(0, 40)
            }, true);
            return snap;
        }

        toPayload() {
            const items = this.snapshot();
            const prendas = items.filter(function (it) { return it && it.tipo !== "epp"; });
            const epps = items.filter(function (it) { return it && it.tipo === "epp"; });
            telemetryLog("TO_PAYLOAD", {
                totalItems: items.length,
                totalPrendas: prendas.length,
                totalEpps: epps.length,
                localIds: items.map(function (it) { return it && it._local_id; }).filter(Boolean).slice(0, 40)
            }, true);
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
