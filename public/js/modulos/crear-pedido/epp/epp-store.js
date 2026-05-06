/**
 * EppStore - Adapter estricto sobre la fuente de verdad unificada.
 * No usa window.itemsPedido como respaldo legacy.
 */
(function () {
    'use strict';

    function str(v) {
        return (v === null || v === undefined) ? '' : String(v);
    }

    function getSessionStore() {
        if (typeof window.getPedidoSessionStore === 'function') {
            return window.getPedidoSessionStore();
        }
        return null;
    }

    function requireStore() {
        var store = getSessionStore();
        if (!store) {
            throw new Error('[EppStore] PedidoSessionStore no disponible');
        }
        return store;
    }

    var EppStore = {

        getItems: function () {
            var store = requireStore();
            if (typeof store.snapshot === 'function') {
                return (store.snapshot() || []).filter(function (it) {
                    return it && it.tipo === 'epp';
                });
            }
            return [];
        },

        findItem: function (eppId) {
            var id = str(eppId);
            return this.getItems().find(function (it) {
                return str(it.epp_id) === id || str(it.id) === id;
            }) || null;
        },

        _findIndex: function (eppId) {
            var id = str(eppId);
            var items = this.getItems();
            return items.findIndex(function (it) {
                return str(it.epp_id) === id || str(it.id) === id;
            });
        },

        count: function () {
            return this.getItems().length;
        },

        getSubtotal: function () {
            return this.getItems().reduce(function (sum, it) {
                var t = Number(it.total);
                if (isFinite(t) && t > 0) return sum + t;

                var vu = Number(it.valor_unitario);
                var c = Number(it.cantidad);
                if (isFinite(vu) && isFinite(c) && c > 0) return sum + (vu * c);

                return sum;
            }, 0);
        },

        agregarItem: function (data) {
            var item = Object.assign({}, data, {
                tipo: 'epp',
                epp_id: data.epp_id || data.id
            });

            var store = requireStore();
            if (typeof store.addItem !== 'function') {
                throw new Error('[EppStore] addItem no disponible en PedidoSessionStore');
            }
            store.addItem(item);

            this._notificar('agregar', item);
            return item;
        },

        actualizarItem: function (eppId, datos) {
            var found = this.findItem(eppId);
            if (!found) {
                console.warn('[EppStore] actualizarItem: no encontrado, eppId=' + eppId);
                return false;
            }

            var store = requireStore();
            if (found._local_id && typeof store.updateItem === 'function') {
                store.updateItem(found._local_id, datos);
            } else {
                throw new Error('[EppStore] updateItem requiere _local_id y store.updateItem');
            }

            this._notificar('actualizar', Object.assign({}, found, datos));
            return true;
        },

        eliminarItem: function (eppId) {
            var store = requireStore();
            if (typeof store.removeItem !== 'function') {
                throw new Error('[EppStore] removeItem no disponible en PedidoSessionStore');
            }
            var removed = store.removeItem(eppId);
            if (removed) {
                this._notificar('eliminar', { epp_id: eppId });
            }
            return !!removed;
        },

        cargarItems: function (items) {
            var store = requireStore();
            if (typeof store.snapshot !== 'function' || typeof store.addItem !== 'function' || typeof store.removeItem !== 'function') {
                throw new Error('[EppStore] PedidoSessionStore incompleto para cargarItems');
            }
            var actuales = store.snapshot() || [];
            actuales.forEach(function (it) {
                if (it && it.tipo === 'epp') {
                    store.removeItem(it._local_id || it.epp_id || it.id);
                }
            });
            (items || []).forEach(function (it) {
                store.addItem(Object.assign({}, it, {
                    tipo: 'epp',
                    epp_id: it.epp_id || it.id
                }));
            });
            this._notificar('cargar', this.getItems());
        },

        _listeners: [],

        onChange: function (fn) {
            if (typeof fn === 'function') this._listeners.push(fn);
        },

        offChange: function (fn) {
            this._listeners = this._listeners.filter(function (l) { return l !== fn; });
        },

        _notificar: function (accion, detalle) {
            var items = this.getItems();
            this._listeners.forEach(function (fn) {
                try {
                    fn(accion, detalle, items);
                } catch (e) {
                    console.error('[EppStore] Error en listener:', e);
                }
            });
        }
    };

    window.eppStore = EppStore;
})();
