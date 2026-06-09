/**
 *  ADAPTER: Prenda Editor para Pedidos/Index
 * 
 * Proporciona las funciones globales necesarias para que el modal
 * modal-agregar-prenda-nueva funcione en el contexto de pedidos/index.blade.php
 * (edicion de prendas existentes en pedidos ya creados).
 * 
 * Flujo de datos:
 *   1. editarPrendaDePedido() GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
 *      Trae datos completos de BD (tallas, colores, telas, procesos, imagenes, variantes)
 *   2. cargarPrendaEnModal()  Abre modal y carga datos en formulario
 *   3. agregarPrendaNueva()  POST /asesores/pedidos/{id}/actualizar-prenda
 *       Guarda cambios usando actualizarPrendaCompleta
 * 
 * Tablas consultadas:
 *   prendas_pedido, prenda_pedido_variantes, prenda_pedido_tallas,
 *   prenda_pedido_talla_colores, prenda_pedido_colores_telas,
 *   prenda_fotos_tela_pedido, prenda_fotos_pedido,
 *   pedidos_procesos_prenda_detalles, pedidos_procesos_imagenes
 * 
 * Dependencias:
 *   PrendaEditor, PrendaModalManager, PrendaFormCollector, SweetAlert2
 */
(function() {
    'use strict';

    console.log('[PedidosAdapter]  Cargado');

    // Detectar contexto: supervisor vs asesor basado en la URL actual
    function _getUrlPrefix() {
        const path = window.location.pathname;
        if (path.startsWith('/supervisor-pedidos')) {
            return { fetch: '/api/supervisor-pedidos/ordenes', save: '/api/supervisor-pedidos/ordenes', context: 'supervisor' };
        }
        return { fetch: '/api/asesores/pedidos-produccion', save: '/api/asesores/pedidos', context: 'asesor' };
    }

    // ====================================================
    // 1. INICIALIZAR PrendaEditor global
    // ====================================================
    function initPrendaEditor() {
        if (typeof PrendaEditor !== 'undefined' && !window.prendaEditorGlobal) {
            window.prendaEditorGlobal = new PrendaEditor({
                modalId: 'modal-agregar-prenda-nueva'
            });
            console.log('[PedidosAdapter]  prendaEditorGlobal creado');
        }
    }

    // Intentar crear al cargar DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(initPrendaEditor, 300));
    } else {
        setTimeout(initPrendaEditor, 300);
    }

    // Intentar cuando el lazy loader termine
    window.addEventListener('prendaEditorRefactoredReady', () => {
        setTimeout(initPrendaEditor, 100);
    });

    // ====================================================
    // 1b. FALLBACKS centralizados
    // ====================================================
    if (window.PedidosAdapterFallbackUtils && typeof window.PedidosAdapterFallbackUtils.initImageFallbacks === 'function') {
        window.PedidosAdapterFallbackUtils.initImageFallbacks();
    } else {
        console.warn('[PedidosAdapter] PedidosAdapterFallbackUtils.initImageFallbacks no disponible');
    }

    function _cerrarVisualModalPrenda() {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open');
    }

    function _resetFSMYModalManagerPrenda() {
        if (window.__MODAL_FSM__ && typeof window.__MODAL_FSM__.forceReset === 'function') {
            window.__MODAL_FSM__.forceReset();
        } else if (window.__MODAL_FSM__) {
            window.__MODAL_FSM__.state = 'CLOSED';
        }

        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.limpiar('modal-agregar-prenda-nueva');
            } catch (e) {
                console.warn('[PedidosAdapter] Error limpiando modal:', e);
            }
        }
    }

    function _resetEstadoGlobalPrendaEditor() {
        window.prendaActual = null;
        window.prendaEditIndex = null;
        window.telasAgregadas = [];
        window.telasCreacion = [];
        window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        window.procesosSeleccionados = [];
        window._editandoPrendaDePedido = null;

        if (window.imagenesAEliminar) {
            console.log('[PedidosAdapter]  Limpiando imagenes marcadas para eliminacion:', window.imagenesAEliminar.length);
            window.imagenesAEliminar = [];
        }
        if (window.procesosParaEliminarIds) {
            window.procesosParaEliminarIds.clear();
        }
    }

    function _resetFormularioYTablasPrenda() {
        const form = document.getElementById('form-prenda-nueva');
        if (form) form.reset();

        const previewContainer = document.getElementById('imagenes-prenda-preview');
        if (previewContainer) previewContainer.innerHTML = '';

        const telasBody = document.getElementById('tbody-telas');
        if (telasBody) {
            const filaInputs = telasBody.querySelector('#nueva-prenda-tela')?.closest('tr');
            const filas = telasBody.querySelectorAll('tr');
            filas.forEach(fila => {
                if (fila !== filaInputs) fila.remove();
            });
            if (filaInputs) {
                filaInputs.querySelectorAll('input[type="text"]').forEach(inp => inp.value = '');
                const preview = filaInputs.querySelector('#nueva-prenda-tela-preview');
                if (preview) {
                    preview.innerHTML = '';
                    preview.style.display = 'none';
                }
            }
        }
    }

    function _resetBotonGuardarPrenda() {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.textContent = ' Agregar Prenda';
            btnGuardar.className = 'btn btn-primary';
        }
    }

    // ====================================================
    // 2. cerrarModalPrendaNueva  Cierra el modal de prenda
    // ====================================================
    window.cerrarModalPrendaNueva = function() {
        console.log('[PedidosAdapter] Cerrando modal de prenda');
        _cerrarVisualModalPrenda();
        _resetFSMYModalManagerPrenda();
        _resetEstadoGlobalPrendaEditor();
        _resetFormularioYTablasPrenda();
        _resetBotonGuardarPrenda();
    };

    // ====================================================
    // 3. agregarPrendaNueva  Guardar prenda editada (POST API)
    //    Nota: El modo "agregar nueva prenda" se maneja en
    //    prenda-agregar-pedido.js que wrappea esta funcion
    // ====================================================
    window.agregarPrendaNueva = function() {
        console.log('[PedidosAdapter] Guardando prenda editada');

        const editContext = window._editandoPrendaDePedido;
        if (!editContext) {
            console.warn('[PedidosAdapter] No hay contexto de edicion de pedido');
            // Intentar con contexto minimo si prendaActual existe
            if (!window.prendaActual) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'No se encontro contexto de edicion', 'error');
                }
                return;
            }
        }

        const pedidoId = editContext?.pedidoId || window.datosEdicionPedido?.id;
        const prendaId = editContext?.prendaId || window.prendaActual?.id || window.prendaActual?.prenda_pedido_id;
        const prendaIndex = editContext?.prendaIndex;

        if (!pedidoId || !prendaId) {
            console.error('[PedidosAdapter] Faltan pedidoId o prendaId:', { pedidoId, prendaId });
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo identificar el pedido o la prenda para guardar', 'error');
            }
            return;
        }

        // Recolectar datos del formulario
        let datosModificados = _recolectarDatosFormulario(prendaIndex);
        if (!datosModificados) {
            console.error('[PedidosAdapter] No se pudieron recolectar datos del formulario');
            if (typeof Swal !== 'undefined') {
                Swal.fire('validacion', 'Por favor completa los datos requeridos de la prenda', 'warning');
            }
            return;
        }

        console.log('[PedidosAdapter] Datos a guardar:', datosModificados);

        // Enviar PUT API
        _guardarPrendaEnAPI(pedidoId, prendaId, datosModificados, prendaIndex);
    };

    /**
     * Recolectar datos del formulario del modal
     * @private
     */
    function _recolectarDatosFormulario(prendaIndex) {
        if (typeof PrendaFormCollector === 'undefined') {
            console.error('[PedidosAdapter] PrendaFormCollector no esta cargado. Es una dependencia requerida.');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Componente PrendaFormCollector no disponible. Recarga la Pagina.', 'error');
            }
            return null;
        }

        const collector = new PrendaFormCollector();
        collector.setNotificationService({
            error: function(msg) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('validacion', msg, 'warning');
                }
            }
        });
        const prendasArray = window.datosEdicionPedido?.prendas || [];
        const prendaLocalId = window._editandoPrendaDePedido?.prendaLocalId
            || prendasArray?.[prendaIndex]?._local_id
            || prendasArray?.[prendaIndex]?.local_id
            || null;
        return collector.construirPrendaDesdeFormulario(
            prendaIndex,
            prendasArray,
            prendaLocalId
        );
    }

    function _asegurarEstiloOverlaySwal(styleId, containerClass, zIndex = 2000000) {
        if (window.PedidosSwalUtils && typeof window.PedidosSwalUtils.ensureOverlayStyle === 'function') {
            window.PedidosSwalUtils.ensureOverlayStyle(styleId, containerClass, zIndex);
            return;
        }
        console.warn('[PedidosAdapter] PedidosSwalUtils.ensureOverlayStyle no disponible');
    }

    function _centrarOverlaySwal(modal, zIndex = 2000000) {
        if (window.PedidosSwalUtils && typeof window.PedidosSwalUtils.centerOverlay === 'function') {
            window.PedidosSwalUtils.centerOverlay(modal, zIndex);
            return;
        }
        console.warn('[PedidosAdapter] PedidosSwalUtils.centerOverlay no disponible');
    }

    function _getUiUtils() {
        if (window.PedidosAdapterUiUtils) {
            return window.PedidosAdapterUiUtils;
        }
        console.warn('[PedidosAdapter] PedidosAdapterUiUtils no disponible');
        return null;
    }

    function _getSaveUtils() {
        if (window.PedidosAdapterSaveUtils) {
            return window.PedidosAdapterSaveUtils;
        }
        console.warn('[PedidosAdapter] PedidosAdapterSaveUtils no disponible');
        return null;
    }

    function _getEditUtils() {
        if (window.PedidosAdapterEditUtils) {
            return window.PedidosAdapterEditUtils;
        }
        console.warn('[PedidosAdapter] PedidosAdapterEditUtils no disponible');
        return null;
    }

    function _getDeleteUtils() {
        if (window.PedidosAdapterDeleteUtils) {
            return window.PedidosAdapterDeleteUtils;
        }
        console.warn('[PedidosAdapter] PedidosAdapterDeleteUtils no disponible');
        return null;
    }

    /**
     * Enviar datos al servidor via POST (actualizarPrendaCompleta)
     * Endpoint: POST /asesores/pedidos/{pedidoId}/actualizar-prenda
     * @private
     */
    async function _guardarPrendaEnAPI(pedidoId, prendaId, datos, prendaIndex) {
        const saveUtils = _getSaveUtils();
        if (saveUtils && typeof saveUtils.guardarPrendaEnAPI === 'function') {
            return saveUtils.guardarPrendaEnAPI({
                pedidoId,
                prendaId,
                datos,
                prendaIndex,
                pedirNovedad: _pedirNovedad,
                getUrlPrefix: _getUrlPrefix,
                normalizarDatosBD: _normalizarDatosBD,
                cerrarModalPrendaNueva: window.cerrarModalPrendaNueva
            });
        }
        console.error('[PedidosAdapter] PedidosAdapterSaveUtils.guardarPrendaEnAPI no disponible');
    }

    /**
     * Pedir novedad/justificacion antes de guardar (requerido por backend)
     * @returns {Promise<string|null>} novedad o null si cancela
     * @private
     */
    function _pedirNovedad() {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.pedirNovedad === 'function') {
            return uiUtils.pedirNovedad({
                fallbackValue: 'edicion de prenda desde lista de pedidos',
                ensureOverlayStyle: _asegurarEstiloOverlaySwal,
                centerOverlay: _centrarOverlaySwal
            });
        }
        return Promise.resolve('edicion de prenda desde lista de pedidos');
    }

    function _mostrarLoadingEditarPrenda() {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.mostrarLoadingEditarPrenda === 'function') {
            uiUtils.mostrarLoadingEditarPrenda();
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Cargando prenda...',
                text: 'Obteniendo datos completos',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
        }
    }

    function _cerrarSweetAlertsActivos() {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.cerrarSweetAlertsActivos === 'function') {
            uiUtils.cerrarSweetAlertsActivos();
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        document.querySelectorAll('.swal2-container').forEach(el => el.remove());
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
        document.body.style.overflow = '';
    }

    // ====================================================
    // 4. editarPrendaDePedido - Punto de entrada para editar
    //    Trae datos completos de BD antes de abrir modal
    // ====================================================
    window.editarPrendaDePedido = async function(prenda, prendaIndex, pedidoId) {
        const editUtils = _getEditUtils();
        if (editUtils && typeof editUtils.editarPrendaDePedido === 'function') {
            return editUtils.editarPrendaDePedido({
                prenda,
                prendaIndex,
                pedidoId,
                getUrlPrefix: _getUrlPrefix,
                normalizarDatosBD: _normalizarDatosBD,
                mostrarLoadingEditarPrenda: _mostrarLoadingEditarPrenda,
                cerrarSweetAlertsActivos: _cerrarSweetAlertsActivos,
                initPrendaEditor: initPrendaEditor,
                abrirModalManual: _abrirModalManual
            });
        }
        console.error('[PedidosAdapter] PedidosAdapterEditUtils.editarPrendaDePedido no disponible');
    };
    function _normalizarDatosBD(prenda) {
        if (window.PedidosAdapterDataUtils && typeof window.PedidosAdapterDataUtils.normalizarDatosBD === 'function') {
            return window.PedidosAdapterDataUtils.normalizarDatosBD(prenda);
        }
        console.error('[PedidosAdapter] PedidosAdapterDataUtils.normalizarDatosBD no disponible');
        return prenda;
    }
    function _abrirModalManual(prenda) {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.abrirModalManual === 'function') {
            uiUtils.abrirModalManual(prenda);
            return;
        }
        console.error('[PedidosAdapter] PedidosAdapterUiUtils.abrirModalManual no disponible');
    }

    // ====================================================
    // 6. abrirModalEliminarPrenda  Abre modal para eliminar prenda
    //    Pide motivo y luego elimina del servidor
    // ====================================================
    window.abrirModalEliminarPrenda = function(prenda, prendaIndex, pedidoId) {
        const prendaId = prenda.id || prenda.prenda_pedido_id;
        console.log('[PedidosAdapter]  Eliminando prenda:', prenda.nombre_prenda || prenda.nombre, 'id:', prendaId, 'pedidoId:', pedidoId);

        if (!pedidoId || !prendaId) {
            console.error('[PedidosAdapter] Faltan pedidoId o prendaId para eliminar');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudo identificar el pedido o la prenda para eliminar', 'error');
            }
            return;
        }

        if (typeof Swal === 'undefined') {
            console.error('[PedidosAdapter] SweetAlert2 no disponible');
            return;
        }

        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.confirmarEliminarPrenda === 'function') {
            uiUtils.confirmarEliminarPrenda({
                prendaNombre: prenda.nombre_prenda || prenda.nombre || 'esta prenda',
                ensureOverlayStyle: _asegurarEstiloOverlaySwal,
                centerOverlay: _centrarOverlaySwal,
                onConfirm: (motivo) => _eliminarPrendaDelAPI(pedidoId, prendaId, prendaIndex, prenda, motivo)
            });
            return;
        }

        _eliminarPrendaDelAPI(pedidoId, prendaId, prendaIndex, prenda, 'eliminacion de prenda desde lista de pedidos');
    };

    /**
     * Eliminar prenda del servidor y actualizar novedades
     * @private
     */
    function _mostrarLoadingEliminarPrenda() {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.mostrarLoadingEliminarPrenda === 'function') {
            uiUtils.mostrarLoadingEliminarPrenda({ centerOverlay: _centrarOverlaySwal });
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Eliminando prenda...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { container: 'swal-eliminar-prenda-container' },
                didOpen: (modal) => {
                    _centrarOverlaySwal(modal);
                    Swal.showLoading();
                }
            });
        }
    }

    function _mostrarErrorEliminarPrenda(texto) {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.mostrarErrorEliminarPrenda === 'function') {
            uiUtils.mostrarErrorEliminarPrenda(texto, { centerOverlay: _centrarOverlaySwal });
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: texto,
                customClass: { container: 'swal-eliminar-prenda-container' },
                didOpen: (modal) => _centrarOverlaySwal(modal)
            });
        }
    }

    function _mostrarExitoEliminarPrenda() {
        const uiUtils = _getUiUtils();
        if (uiUtils && typeof uiUtils.mostrarExitoEliminarPrenda === 'function') {
            uiUtils.mostrarExitoEliminarPrenda({ centerOverlay: _centrarOverlaySwal });
            return;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: ' Prenda eliminada',
                text: 'La prenda se elimino y se registro el motivo en novedades del pedido',
                timer: 1800,
                showConfirmButton: false,
                customClass: { container: 'swal-eliminar-prenda-container' },
                didOpen: (modal) => _centrarOverlaySwal(modal)
            });
        }
    }

    async function _eliminarPrendaDelAPI(pedidoId, prendaId, prendaIndex, prenda, motivo) {
        const deleteUtils = _getDeleteUtils();
        if (deleteUtils && typeof deleteUtils.eliminarPrendaDelAPI === 'function') {
            return deleteUtils.eliminarPrendaDelAPI({
                pedidoId,
                prendaId,
                prendaIndex,
                motivo,
                getUrlPrefix: _getUrlPrefix,
                ensureOverlayStyle: _asegurarEstiloOverlaySwal,
                mostrarLoading: _mostrarLoadingEliminarPrenda,
                mostrarError: _mostrarErrorEliminarPrenda,
                mostrarExito: _mostrarExitoEliminarPrenda
            });
        }

        try {
            _asegurarEstiloOverlaySwal('swal-eliminar-prenda-style', 'swal-eliminar-prenda-container');
            _mostrarLoadingEliminarPrenda();
            const urlPrefix = _getUrlPrefix();
            const deleteUrl = `${urlPrefix.save}/${pedidoId}/eliminar-prenda`;
            const response = await fetch(deleteUrl, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    prenda_id: prendaId,
                    motivo: motivo
                })
            });
            if (!response.ok) {
                let errorMsg = 'Error desconocido';
                try {
                    const error = await response.json();
                    errorMsg = error.message || error.error || JSON.stringify(error);
                } catch (e) {
                    errorMsg = `HTTP ${response.status}: ${response.statusText}`;
                }
                console.error('[PedidosAdapter] Error al eliminar:', errorMsg);
                _mostrarErrorEliminarPrenda(`No se pudo eliminar: ${errorMsg}`);
                return;
            }

            const result = await response.json();
            console.log('[PedidosAdapter]  Prenda eliminada:', result);
            if (window.datosEdicionPedido?.prendas && prendaIndex !== null && prendaIndex !== undefined) {
                window.datosEdicionPedido.prendas.splice(prendaIndex, 1);
                console.log('[PedidosAdapter]  Lista de prendas actualizada (removida prenda en indice', prendaIndex + ')');
            }
            _mostrarExitoEliminarPrenda();
            setTimeout(function() {
                abrirEditarPrendas();
            }, 1900);
        } catch (error) {
            console.error('[PedidosAdapter] Error de red al eliminar:', error);
            _mostrarErrorEliminarPrenda('Error de conexion al eliminar la prenda');
        }
    }
})();


