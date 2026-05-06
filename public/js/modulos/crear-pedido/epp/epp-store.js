/**
 * EppStore - Compat adapter sobre la fuente de verdad unificada.
 *
 * Prioriza PedidoSessionStore cuando esta disponible.
 * Mantiene fallback legacy sobre window.itemsPedido para compatibilidad.
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

    var EppStore = {
        _ensureArray: function () {
            if (!window.itemsPedido) window.itemsPedido = [];
        },

        getItems: function () {
            var store = getSessionStore();
            if (store && typeof store.snapshot === 'function') {
                return (store.snapshot() || []).filter(function (it) {
                    return it && it.tipo === 'epp';
                });
            }

            this._ensureArray();
            return window.itemsPedido.filter(function (it) {
                return it && (it.tipo === 'epp' || !it.tipo);
            });
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
            this._ensureArray();

            var item = Object.assign({}, data, {
                tipo: 'epp',
                epp_id: data.epp_id || data.id
            });

            var store = getSessionStore();
            if (store && typeof store.addItem === 'function') {
                store.addItem(item);
            } else {
                window.itemsPedido.push(item);
            }

            this._notificar('agregar', item);
            return item;
        },

        actualizarItem: function (eppId, datos) {
            var found = this.findItem(eppId);
            if (!found) {
                console.warn('[EppStore] actualizarItem: no encontrado, eppId=' + eppId);
                return false;
            }

            var store = getSessionStore();
            if (store && found._local_id && typeof store.updateItem === 'function') {
                store.updateItem(found._local_id, datos);
            } else {
                this._ensureArray();
                var idx = window.itemsPedido.findIndex(function (it) {
                    return it && (str(it.epp_id) === str(eppId) || str(it.id) === str(eppId));
                });
                if (idx >= 0) {
                    Object.assign(window.itemsPedido[idx], datos);
                }
            }

            this._notificar('actualizar', Object.assign({}, found, datos));
            return true;
        },

        eliminarItem: function (eppId) {
            var store = getSessionStore();
            if (store && typeof store.removeItem === 'function') {
                var deletedByStore = store.removeItem(eppId);
                if (deletedByStore) {
                    this._notificar('eliminar', { epp_id: eppId });
                }
                return !!deletedByStore;
            }

            this._ensureArray();
            var id = str(eppId);
            var before = window.itemsPedido.length;

            window.itemsPedido = window.itemsPedido.filter(function (it) {
                if (it.tipo !== 'epp' && it.tipo) return true;
                return str(it.epp_id) !== id && str(it.id) !== id;
            });

            var removed = before - window.itemsPedido.length;
            if (removed > 0) {
                this._notificar('eliminar', { epp_id: eppId });
            }
            return removed > 0;
        },

        cargarItems: function (items) {
            var store = getSessionStore();
            if (store && typeof store.snapshot === 'function' && typeof store.addItem === 'function' && typeof store.removeItem === 'function') {
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
                return;
            }

            this._ensureArray();
            var noEpp = window.itemsPedido.filter(function (it) {
                return it.tipo && it.tipo !== 'epp';
            });

            var normalizados = (items || []).map(function (it) {
                return Object.assign({}, it, {
                    tipo: 'epp',
                    epp_id: it.epp_id || it.id
                });
            });

            window.itemsPedido = noEpp.concat(normalizados);
            this._notificar('cargar', normalizados);
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
