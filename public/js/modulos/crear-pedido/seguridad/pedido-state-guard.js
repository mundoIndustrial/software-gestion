(function () {
    "use strict";

    function nowIso() {
        return new Date().toISOString();
    }

    function isTelemetryEnabled() {
        return !!window.DEBUG_PEDIDO_TELEMETRY;
    }

    function getSessionId() {
        if (!window.PEDIDO_SESSION_ID) {
            window.PEDIDO_SESSION_ID = "pedido-" + Date.now() + "-" + Math.random().toString(36).slice(2, 8);
        }
        return window.PEDIDO_SESSION_ID;
    }

    function resumenItem(item) {
        if (!item || typeof item !== "object") return null;
        return {
            _local_id: item._local_id || null,
            tipo: item.tipo || (item.epp_id ? "epp" : "prenda"),
            epp_id: item.epp_id || null,
            ref: item.nombre_epp || item.nombre_producto || item.nombre_prenda || item.nombre || null
        };
    }

    function telemetryGroup(label, data, collapsed) {
        if (!isTelemetryEnabled()) return;
        var openFn = collapsed === false ? console.group : console.groupCollapsed;
        openFn(label);
        try {
            if (data !== undefined) {
                console.log(data);
            }
        } finally {
            console.groupEnd();
        }
    }

    function telemetryWarn(label, data, trace) {
        if (!isTelemetryEnabled()) return;
        console.warn(label, data || "");
        if (trace) {
            console.trace("[LEGACY MUTATION TRACE]");
        }
    }

    function safeCall(fn) {
        try {
            return typeof fn === "function" ? fn() : undefined;
        } catch (_) {
            return undefined;
        }
    }

    function clearSessionBackups() {
        try {
            var keys = [];
            for (var i = 0; i < sessionStorage.length; i++) {
                var k = sessionStorage.key(i);
                if (k && k.indexOf("pedido_prendas_backup_") === 0) {
                    keys.push(k);
                }
            }
            keys.forEach(function (k) {
                sessionStorage.removeItem(k);
            });
        } catch (_) {}
    }

    function clearGlobalState() {
        window.prendasEliminadas = new Set();
        window.__pedidoSubmitInFlight = false;

        if (window.eppStore && typeof window.eppStore.cargarItems === "function") {
            window.eppStore.cargarItems([]);
        }

        if (window.gestionItemsUI) {
            var ui = window.gestionItemsUI;
            ui.prendas = [];
            ui.epps = [];
            ui.ordenItems = [];
            ui.prendaEditIndex = null;
            ui.prendaModalMode = "create";
            ui.prendaEditKey = null;

            if (ui.itemsState) {
                ui.itemsState.prendas = [];
                ui.itemsState.epps = [];
                ui.itemsState.ordenItems = [];
            }

            safeCall(function () {
                if (ui.renderer && typeof ui.renderer.actualizar === "function") {
                    ui.renderer.actualizar([]);
                }
            });
        }

        if (window.pedidoSessionStore) {
            window.pedidoSessionStore.ui = window.gestionItemsUI || null;
        }

        if (window.gestorPrendaSinCotizacion) {
            window.gestorPrendaSinCotizacion.prendas = [];
            window.gestorPrendaSinCotizacion.fotosNuevas = {};
            window.gestorPrendaSinCotizacion.telasFotosNuevas = {};
        }

        // Buffers de edición / contextos previos
        window.datosEdicionPedido = null;
        window.pedidoEditarData = null;
        window.pedidoEdicionData = null;
        window.prendaOriginalDesdeSelector = null;
        window._telasMultiplesOriginales = [];

        // Estado de telas (legacy + nuevo)
        window.telasCreacion = [];
        window.telasAgregadas = [];
        window.imagenesTelaModalNueva = [];

        // Galerías y flags visuales
        window.prendasGaleria = [];
        window.telasGaleria = [];
        window.imagenesGaleriaProceso = null;
        window.__galeriaPrendaActiva = false;
        window.__galeriaTelaActiva = false;
        window.__galeriaPrendaAbierta = false;

        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        window.tallasSeleccionadasProceso = { dama: [], caballero: [], unisex: [], sobremedida: null };
        window.tallasCantidadesProceso = { dama: {}, caballero: {}, unisex: {}, sobremedida: {} };
        window.procesosSeleccionados = {};
        window.procesosGuardados = {};
        window.procesosParaEliminarIds = new Set();
        window.ubicacionesProcesoSeleccionadas = [];
        window.procesoActualIndex = undefined;
        window.imagenesAEliminar = [];
        window.imagenesProcesoActual = [null, null, null];
        window.imagenesProcesoExistentes = [];
        window.imagenesEliminadasProcesoStorage = [];
        window.datosExtendidosTallasProceso = {
            dama: {},
            caballero: {},
            unisex: {},
            sobremedida: {}
        };
        window._fotosGeneralesKeys = new Set();

        safeCall(function () {
            window.imagenesPrendaStorage && window.imagenesPrendaStorage.limpiar && window.imagenesPrendaStorage.limpiar();
        });
        safeCall(function () {
            window.imagenesTelaStorage && window.imagenesTelaStorage.limpiar && window.imagenesTelaStorage.limpiar();
        });
        safeCall(function () {
            window.imagenesReflectivoStorage && window.imagenesReflectivoStorage.limpiar && window.imagenesReflectivoStorage.limpiar();
        });
        safeCall(function () {
            window.universalImagenesStorage && window.universalImagenesStorage.limpiarTodo && window.universalImagenesStorage.limpiarTodo();
        });

        // Estado del adapter store
        if (window.pedidoSessionStore) {
            safeCall(function () {
                window.pedidoSessionStore.ui = window.gestionItemsUI || null;
            });
        }
    }

    function hardResetPedidoState(contexto) {
        var before = 0;
        safeCall(function () {
            if (typeof window.getPedidoSessionStore === "function") {
                var store = window.getPedidoSessionStore();
                if (store && typeof store.snapshot === "function") {
                    before = (store.snapshot() || []).length;
                }
            } else if (window.gestionItemsUI && typeof window.gestionItemsUI.obtenerItemsOrdenados === "function") {
                before = (window.gestionItemsUI.obtenerItemsOrdenados() || []).length;
            }
        });

        clearGlobalState();
        clearSessionBackups();
        safeCall(function () {
            window.idempotencyService && window.idempotencyService.limpiar && window.idempotencyService.limpiar();
        });

        var after = 0;
        safeCall(function () {
            if (typeof window.getPedidoSessionStore === "function") {
                var store = window.getPedidoSessionStore();
                if (store && typeof store.snapshot === "function") {
                    after = (store.snapshot() || []).length;
                }
            } else if (window.gestionItemsUI && typeof window.gestionItemsUI.obtenerItemsOrdenados === "function") {
                after = (window.gestionItemsUI.obtenerItemsOrdenados() || []).length;
            }
        });

        telemetryGroup(
            "[hardResetPedidoState][" + getSessionId() + "] " + nowIso(),
            {
                contexto: contexto || "desconocido",
                beforeItems: before,
                afterItems: after
            },
            true
        );
    }

    function normalizeText(value) {
        return String(value || "").trim().toLowerCase().replace(/\s+/g, " ");
    }

    function semanticKey(item) {
        var tipo = item.tipo || (item.epp_id ? "epp" : "prenda");
        if (tipo === "epp") {
            return "epp|" + normalizeText(item.nombre_epp || item.nombre || "") + "|" + (item.epp_id || "") + "|" + (item.cantidad || 0);
        }
        return "prenda|" + normalizeText(item.nombre_prenda || item.nombre || "") + "|" + normalizeText(item.origen || "") + "|" + JSON.stringify(item.cantidad_talla || {});
    }

    function sessionConsistencyCheck(items) {
        var list = Array.isArray(items) ? items : [];
        var report = {
            ok: true,
            errors: [],
            warnings: [],
            counts: { total: list.length, epp: 0, prenda: 0 }
        };

        var byTech = new Set();
        var bySemantic = new Set();

        list.forEach(function (item, idx) {
            var tipo = item.tipo || (item.epp_id ? "epp" : "prenda");
            if (tipo === "epp") report.counts.epp++;
            else report.counts.prenda++;

            if (!item._local_id) {
                report.errors.push({ code: "MISSING_LOCAL_ID", idx: idx, tipo: tipo });
            }

            if (tipo === "epp" && !item.epp_id) {
                report.errors.push({ code: "MISSING_EPP_ID", idx: idx, tipo: tipo });
            }

            var techId = tipo + "|" + (item.pedido_epp_id || item.prenda_pedido_id || item.id || item._local_id || "");
            if (byTech.has(techId)) {
                report.errors.push({ code: "DUP_TECHNICAL", idx: idx, tipo: tipo, techId: techId });
            }
            byTech.add(techId);

            var sem = semanticKey(item);
            if (bySemantic.has(sem)) {
                report.warnings.push({ code: "DUP_SEMANTIC", idx: idx, tipo: tipo, semantic: sem });
            }
            bySemantic.add(sem);
        });

        report.ok = report.errors.length === 0;

        if (!report.ok) {
            console.group("[sessionConsistencyCheck] FAILED [" + getSessionId() + "] " + nowIso());
            console.table(report.errors);
            if (report.warnings.length > 0) {
                console.table(report.warnings);
            }
            console.log("snapshot:", list);
            console.groupEnd();
        } else {
            console.info("[sessionConsistencyCheck] OK [" + getSessionId() + "]", report.counts);
        }

        return report;
    }

    function eliminarItemPedidoSeguro(tarjetaId) {
        var ui = window.gestionItemsUI;
        if (!ui) {
            return false;
        }

        var id = String(tarjetaId || "");
        var isEpp = id.indexOf("epp") >= 0 || /^(\d+)$/.test(id);

        if (isEpp && typeof ui.eliminarEPPPorTarjetaId === "function") {
            var removedEpp = ui.eliminarEPPPorTarjetaId(tarjetaId);
            if (removedEpp) {
                if (typeof ui._actualizarRenderItemsOrdenadosSinBloquear === "function") {
                    ui._actualizarRenderItemsOrdenadosSinBloquear();
                } else if (typeof ui._actualizarRenderItemsOrdenados === "function") {
                    ui._actualizarRenderItemsOrdenados();
                }
                return true;
            }
        }

        if (typeof ui.eliminarItem === "function") {
            var removedGeneric = ui.eliminarItem(tarjetaId);
            if (removedGeneric !== false) {
                return true;
            }
        }

        if (window.eppStore && typeof window.eppStore.eliminarItem === "function") {
            window.eppStore.eliminarItem(tarjetaId);
        }

        if (ui.renderer && typeof ui.renderer.actualizar === "function" && typeof ui.obtenerItemsOrdenados === "function") {
            ui.renderer.actualizar(ui.obtenerItemsOrdenados());
        }

        return true;
    }

    function stableStringify(obj) {
        if (obj === null || typeof obj !== "object") {
            return JSON.stringify(obj);
        }
        if (Array.isArray(obj)) {
            return "[" + obj.map(stableStringify).join(",") + "]";
        }
        var keys = Object.keys(obj).sort();
        var parts = keys.map(function (k) {
            return JSON.stringify(k) + ":" + stableStringify(obj[k]);
        });
        return "{" + parts.join(",") + "}";
    }

    function hashDjb2(str) {
        var hash = 5381;
        for (var i = 0; i < str.length; i++) {
            hash = ((hash << 5) + hash) + str.charCodeAt(i);
            hash = hash >>> 0;
        }
        return hash.toString(16);
    }

    function cleanUndefined(input) {
        if (Array.isArray(input)) {
            return input.map(cleanUndefined);
        }
        if (!input || typeof input !== "object") {
            return input;
        }
        var out = {};
        Object.keys(input).forEach(function (k) {
            if (input[k] !== undefined) {
                out[k] = cleanUndefined(input[k]);
            }
        });
        return out;
    }

    function sanitizeItem(item, idx) {
        var tipo = item.tipo || (item.epp_id ? "epp" : "prenda");
        var out = cleanUndefined(Object.assign({}, item, { tipo: tipo }));

        if (!out._local_id) {
            throw new Error("Item sin _local_id en indice " + idx);
        }
        if (tipo === "epp" && !out.epp_id) {
            throw new Error("EPP sin epp_id en indice " + idx);
        }
        return out;
    }

    function mapPrendaPayload(item) {
        return {
            tipo: "prenda",
            _local_id: item._local_id,
            nombre_producto: item.nombre_producto || item.nombre_prenda || item.nombre || "",
            descripcion: item.descripcion || "",
            genero: item.genero || "",
            cantidades: item.cantidades || item.cantidadesPorTalla || item.cantidad_talla || {},
            telas: Array.isArray(item.telas) ? item.telas : (Array.isArray(item.telasAgregadas) ? item.telasAgregadas : []),
            imagenes: Array.isArray(item.imagenes) ? item.imagenes : [],
            procesos: item.procesos || {},
            variaciones: item.variaciones || item.variantes || {},
            origen: item.origen || "bodega"
        };
    }

    function mapEppPayload(item) {
        return {
            tipo: "epp",
            _local_id: item._local_id,
            epp_id: item.epp_id || null,
            pedido_epp_id: item.pedido_epp_id || item.pedidoEppId || null,
            nombre_epp: item.nombre_epp || item.nombre_completo || item.nombre || "",
            categoria: item.categoria || "",
            cantidad: item.cantidad || 1,
            observaciones: item.observaciones || null,
            imagenes: Array.isArray(item.imagenes) ? item.imagenes : []
        };
    }

    function serializarPedidoSeguro(items) {
        var list = Array.isArray(items) ? items : [];
        var sane = list.map(function (it, idx) { return sanitizeItem(it, idx); });

        var prendas = sane
            .filter(function (it) { return it.tipo !== "epp"; })
            .map(mapPrendaPayload);

        var epps = sane
            .filter(function (it) { return it.tipo === "epp"; })
            .map(mapEppPayload);

        var payloadBase = {
            prendas: prendas,
            epps: epps,
            items: prendas.concat(epps)
        };
        var hash = hashDjb2(stableStringify(payloadBase));

        telemetryGroup(
            "[serializarPedidoSeguro][" + getSessionId() + "] " + nowIso(),
            {
                totalSnapshot: sane.length,
                totalEpps: epps.length,
                totalPrendas: prendas.length,
                auditHash: hash,
                localIds: sane.map(function (it) { return it._local_id; }).slice(0, 40)
            },
            true
        );

        return {
            prendas: prendas,
            epps: epps,
            items: prendas.concat(epps),
            audit_payload: {
                hash: hash,
                generated_at: new Date().toISOString(),
                total_items: sane.length
            }
        };
    }

    function tagArrayMutations(arrayRef, label) {
        if (!Array.isArray(arrayRef) || arrayRef.__legacyMutationTagged) return;
        try {
            var methods = ["push", "pop", "splice", "shift", "unshift", "sort", "reverse"];
            methods.forEach(function (m) {
                if (typeof arrayRef[m] !== "function") return;
                var original = arrayRef[m];
                Object.defineProperty(arrayRef, m, {
                    configurable: true,
                    writable: true,
                    value: function () {
                        telemetryWarn("[LEGACY MUTATION DETECTED] escritura fuera de PedidoSessionStore en " + label + "." + m, { timestamp: nowIso(), sessionId: getSessionId() }, true);
                        return original.apply(this, arguments);
                    }
                });
            });
            Object.defineProperty(arrayRef, "__legacyMutationTagged", {
                configurable: true,
                enumerable: false,
                writable: true,
                value: true
            });
        } catch (_) {}
    }

    function installLegacyMutationDetector() {
        if (window.__pedidoLegacyMutationDetectorInstalled) return;
        window.__pedidoLegacyMutationDetectorInstalled = true;

        var lastItemsPedido = window.itemsPedido;
        Object.defineProperty(window, "itemsPedido", {
            configurable: true,
            enumerable: true,
            get: function () {
                return lastItemsPedido;
            },
            set: function (next) {
                telemetryWarn("[LEGACY MUTATION DETECTED] escritura fuera de PedidoSessionStore en window.itemsPedido", { timestamp: nowIso(), sessionId: getSessionId(), nextType: typeof next }, true);
                lastItemsPedido = next;
                tagArrayMutations(lastItemsPedido, "window.itemsPedido");
            }
        });
        tagArrayMutations(lastItemsPedido, "window.itemsPedido");

        var lastEppStore = window.eppStore;
        Object.defineProperty(window, "eppStore", {
            configurable: true,
            enumerable: true,
            get: function () {
                return lastEppStore;
            },
            set: function (next) {
                telemetryWarn("[LEGACY MUTATION DETECTED] escritura fuera de PedidoSessionStore en window.eppStore", { timestamp: nowIso(), sessionId: getSessionId(), nextType: typeof next }, true);
                lastEppStore = next;
            }
        });

        var tries = 0;
        var maxTries = 120;
        var intervalId = setInterval(function () {
            tries++;
            var ui = window.gestionItemsUI;
            if (!ui && tries < maxTries) return;
            if (!ui) {
                clearInterval(intervalId);
                return;
            }

            ["prendas", "epps"].forEach(function (key) {
                var current = ui[key];
                tagArrayMutations(current, "gestionItemsUI." + key);
                try {
                    Object.defineProperty(ui, key, {
                        configurable: true,
                        enumerable: true,
                        get: function () {
                            return current;
                        },
                        set: function (next) {
                            telemetryWarn("[LEGACY MUTATION DETECTED] escritura fuera de PedidoSessionStore en gestionItemsUI." + key, { timestamp: nowIso(), sessionId: getSessionId(), nextType: typeof next }, true);
                            current = next;
                            tagArrayMutations(current, "gestionItemsUI." + key);
                        }
                    });
                } catch (_) {}
            });
            clearInterval(intervalId);
        }, 1000);
    }

    window.PedidoTelemetry = {
        enabled: isTelemetryEnabled,
        nowIso: nowIso,
        getSessionId: getSessionId,
        resumenItem: resumenItem,
        group: telemetryGroup,
        warn: telemetryWarn
    };

    installLegacyMutationDetector();

    window.hardResetPedidoState = hardResetPedidoState;
    window.sessionConsistencyCheck = sessionConsistencyCheck;
    window.eliminarItemPedidoSeguro = eliminarItemPedidoSeguro;
    window.serializarPedidoSeguro = serializarPedidoSeguro;
})();
