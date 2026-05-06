(function () {
    "use strict";

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
        window.itemsPedido = [];

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

        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        window.tallasSeleccionadasProceso = { dama: [], caballero: [], unisex: [], sobremedida: null };
        window.tallasCantidadesProceso = { dama: {}, caballero: {}, unisex: {}, sobremedida: {} };
        window.procesosSeleccionados = {};
        window.procesosParaEliminarIds = new Set();
        window.ubicacionesProcesoSeleccionadas = [];
        window.imagenesAEliminar = [];
        window.imagenesProcesoActual = [null, null, null];
        window.imagenesProcesoExistentes = [];
        window.imagenesEliminadasProcesoStorage = [];

        safeCall(function () {
            window.imagenesPrendaStorage && window.imagenesPrendaStorage.limpiar && window.imagenesPrendaStorage.limpiar();
        });
        safeCall(function () {
            window.imagenesTelaStorage && window.imagenesTelaStorage.limpiar && window.imagenesTelaStorage.limpiar();
        });
        safeCall(function () {
            window.imagenesReflectivoStorage && window.imagenesReflectivoStorage.limpiar && window.imagenesReflectivoStorage.limpiar();
        });
    }

    function hardResetPedidoState(contexto) {
        console.warn("[hardResetPedidoState] reset iniciado", { contexto: contexto || "desconocido" });
        clearGlobalState();
        clearSessionBackups();
        safeCall(function () {
            window.idempotencyService && window.idempotencyService.limpiar && window.idempotencyService.limpiar();
        });
        console.warn("[hardResetPedidoState] reset completado");
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
            console.group("[sessionConsistencyCheck] FAILED");
            console.table(report.errors);
            if (report.warnings.length > 0) {
                console.table(report.warnings);
            }
            console.groupEnd();
        } else {
            console.info("[sessionConsistencyCheck] OK", report.counts);
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

    window.hardResetPedidoState = hardResetPedidoState;
    window.sessionConsistencyCheck = sessionConsistencyCheck;
    window.eliminarItemPedidoSeguro = eliminarItemPedidoSeguro;
})();
