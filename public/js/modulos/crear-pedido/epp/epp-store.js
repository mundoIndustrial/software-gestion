/**
 * EppStore — Fuente única de verdad para items EPP.
 *
 * Opera SOBRE window.itemsPedido (no lo reemplaza) para mantener
 * compatibilidad con el código de prendas que también usa ese array.
 *
 * Uso:
 *   eppStore.agregarItem({ epp_id: 5, nombre_epp: '...', cantidad: 2, ... });
 *   eppStore.actualizarItem(5, { cantidad: 4, total: 800 });
 *   eppStore.eliminarItem(5);
 *   eppStore.getSubtotal();
 *   eppStore.onChange((accion, item) => { ... });
 */
(function () {
    'use strict';

    /* ───────── helpers ───────── */

    /** Normaliza a string para comparaciones de ID seguras */
    function str(v) {
        return (v === null || v === undefined) ? '' : String(v);
    }

    /** Obtiene el ID canónico de un item EPP (siempre epp_id) */
    function idOf(item) {
        return str(item.epp_id || item.id);
    }

    /* ───────── store ───────── */

    var EppStore = {

        /* ---- array subyacente ---- */

        _ensureArray: function () {
            if (!window.itemsPedido) window.itemsPedido = [];
        },

        /* ---- lectura ---- */

        /** Devuelve solo los items de tipo 'epp' */
        getItems: function () {
            this._ensureArray();
            return window.itemsPedido.filter(function (it) {
                return it && (it.tipo === 'epp' || !it.tipo);
            });
        },

        /** Busca un item EPP por su epp_id */
        findItem: function (eppId) {
            this._ensureArray();
            var id = str(eppId);
            return window.itemsPedido.find(function (it) {
                return (it.tipo === 'epp' || !it.tipo) && (str(it.epp_id) === id || str(it.id) === id);
            }) || null;
        },

        /** Devuelve el índice dentro de window.itemsPedido (-1 si no existe) */
        _findIndex: function (eppId) {
            var id = str(eppId);
            return window.itemsPedido.findIndex(function (it) {
                return (it.tipo === 'epp' || !it.tipo) && (str(it.epp_id) === id || str(it.id) === id);
            });
        },

        /** Cantidad de items EPP */
        count: function () {
            return this.getItems().length;
        },

        /** Calcula el subtotal sumando totales de todos los EPP */
        getSubtotal: function () {
            return this.getItems().reduce(function (sum, it) {
                var t = Number(it.total);
                if (isFinite(t) && t > 0) return sum + t;

                var vu = Number(it.valor_unitario);
                var c  = Number(it.cantidad);
                if (isFinite(vu) && isFinite(c) && c > 0) return sum + (vu * c);

                return sum;
            }, 0);
        },

        /* ---- escritura ---- */

        /**
         * Agrega un item EPP (normaliza a epp_id).
         * @param {Object} data — debe contener al menos epp_id o id.
         * @returns {Object} el item normalizado que fue insertado.
         */
        agregarItem: function (data) {
            this._ensureArray();

            var item = Object.assign({}, data, {
                tipo: 'epp',
                epp_id: data.epp_id || data.id
            });

            window.itemsPedido.push(item);
            console.log('[EppStore] agregarItem:', item.epp_id, item.nombre_epp);
            this._notificar('agregar', item);
            return item;
        },

        /**
         * Actualiza campos de un item existente.
         * @param {number|string} eppId
         * @param {Object} datos — campos a fusionar.
         * @returns {boolean} true si se encontró y actualizó.
         */
        actualizarItem: function (eppId, datos) {
            this._ensureArray();
            var idx = this._findIndex(eppId);
            if (idx === -1) {
                console.warn('[EppStore] actualizarItem: no encontrado, eppId=' + eppId);
                return false;
            }
            Object.assign(window.itemsPedido[idx], datos);
            console.log('[EppStore] actualizarItem:', eppId, datos);
            this._notificar('actualizar', window.itemsPedido[idx]);
            return true;
        },

        /**
         * Elimina un item EPP por su epp_id.
         * @returns {boolean} true si se eliminó algo.
         */
        eliminarItem: function (eppId) {
            this._ensureArray();
            var id = str(eppId);
            var before = window.itemsPedido.length;

            window.itemsPedido = window.itemsPedido.filter(function (it) {
                if (it.tipo !== 'epp' && it.tipo) return true; // no tocar prendas
                return str(it.epp_id) !== id && str(it.id) !== id;
            });

            var removed = before - window.itemsPedido.length;
            if (removed > 0) {
                console.log('[EppStore] eliminarItem:', eppId, '(' + removed + ' eliminado(s))');
                this._notificar('eliminar', { epp_id: eppId });
            }
            return removed > 0;
        },

        /**
         * Reemplaza todos los items EPP (usado al cargar desde servidor).
         * Preserva items no-EPP que ya estén en itemsPedido.
         */
        cargarItems: function (items) {
            this._ensureArray();

            // Conservar items no-EPP
            var noEpp = window.itemsPedido.filter(function (it) {
                return it.tipo && it.tipo !== 'epp';
            });

            // Normalizar los nuevos
            var normalizados = (items || []).map(function (it) {
                return Object.assign({}, it, {
                    tipo: 'epp',
                    epp_id: it.epp_id || it.id
                });
            });

            window.itemsPedido = noEpp.concat(normalizados);
            console.log('[EppStore] cargarItems:', normalizados.length, 'EPPs cargados');
            this._notificar('cargar', normalizados);
        },

        /* ---- observadores ---- */

        _listeners: [],

        /**
         * Registra un listener que se ejecuta cada vez que el store cambia.
         * @param {function(accion, item, allItems)} fn
         */
        onChange: function (fn) {
            if (typeof fn === 'function') this._listeners.push(fn);
        },

        /** Desregistra un listener */
        offChange: function (fn) {
            this._listeners = this._listeners.filter(function (l) { return l !== fn; });
        },

        /** Notifica a todos los listeners */
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

    // Exponer como global
    window.eppStore = EppStore;

    console.log('[EppStore] Módulo inicializado');
})();
