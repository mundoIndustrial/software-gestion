/**
 * Utilities for prenda-editor-pedidos-adapter edit flow.
 * Exposes: window.PedidosAdapterEditUtils
 */
(function() {
    'use strict';

    async function _obtenerPrendaCompletaDesdeAPI(prenda, pedidoId, prendaId, getUrlPrefix, normalizarDatosBD) {
        let prendaCompleta = prenda;
        if (!(pedidoId && prendaId)) return prendaCompleta;

        const urlPrefix = getUrlPrefix();
        const fetchUrl = `${urlPrefix.fetch}/${pedidoId}/prenda/${prendaId}/datos`;
        console.log('[PedidosAdapter]  Fetching datos de BD:', fetchUrl, '(contexto:', urlPrefix.context + ')');

        const response = await fetch(fetchUrl, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (!response.ok) {
            console.warn('[PedidosAdapter]  Error HTTP', response.status, '- usando datos locales');
            return prendaCompleta;
        }

        const result = await response.json();
        if (!(result.success && result.prenda)) {
            console.warn('[PedidosAdapter]  Respuesta sin datos de prenda, usando datos locales');
            return prendaCompleta;
        }

        prendaCompleta = result.prenda;
        prendaCompleta.pedido_id = pedidoId;
        prendaCompleta.pedidoId = pedidoId;
        prendaCompleta._fromDB = true;
        prendaCompleta = normalizarDatosBD(prendaCompleta);

        console.log('[PedidosAdapter]  Datos completos de BD (normalizados):', {
            nombre: prendaCompleta.nombre_prenda || prendaCompleta.nombre,
            cantidad_talla: prendaCompleta.cantidad_talla ? Object.keys(prendaCompleta.cantidad_talla) : [],
            variantes: prendaCompleta.variantes,
            telasAgregadas: prendaCompleta.telasAgregadas?.length || 0,
            procesos: prendaCompleta.procesos?.length || 0,
            imagenes: prendaCompleta.imagenes?.length || 0
        });

        return prendaCompleta;
    }

    async function _asegurarEditorPrendaCargado(initPrendaEditor) {
        if (window.PrendaEditorLoader && typeof window.PrendaEditorLoader.load === 'function') {
            await window.PrendaEditorLoader.load();
        }
        initPrendaEditor();
    }

    async function _abrirModalConPrendaCompleta(prendaCompleta, prendaIndex, abrirModalManual) {
        const editor = window.prendaEditorGlobal;
        if (editor && typeof editor.cargarPrendaEnModal === 'function') {
            window.prendaActual = prendaCompleta;
            await editor.cargarPrendaEnModal(prendaCompleta, prendaIndex);
            if (typeof window.cargarPrendaEnFormularioModal === 'function') {
                console.log('[PedidosAdapter]  Llamando cargarPrendaEnFormularioModal para detectar UNISEX...');
                window.cargarPrendaEnFormularioModal(prendaCompleta);
            }
            return;
        }

        console.error('[PedidosAdapter] prendaEditorGlobal no disponible, abriendo modal manualmente');
        abrirModalManual(prendaCompleta);
    }

    function _ajustarUIModoEdicionPrenda() {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.textContent = ' Guardar Cambios';
            btnGuardar.className = 'btn btn-success';
        }
        const tituloModal = document.getElementById('modal-prenda-titulo');
        if (tituloModal) {
            tituloModal.textContent = 'Editar Prenda';
        }
    }

    async function editarPrendaDePedido(options = {}) {
        const prenda = options.prenda;
        const prendaIndex = options.prendaIndex;
        const pedidoId = options.pedidoId;
        const getUrlPrefix = options.getUrlPrefix;
        const normalizarDatosBD = options.normalizarDatosBD;
        const mostrarLoadingEditarPrenda = options.mostrarLoadingEditarPrenda;
        const cerrarSweetAlertsActivos = options.cerrarSweetAlertsActivos;
        const initPrendaEditor = options.initPrendaEditor;
        const abrirModalManual = options.abrirModalManual;

        const prendaId = prenda.id || prenda.prenda_pedido_id;
        console.log('[PedidosAdapter] Editando prenda:', prenda.nombre_prenda || prenda.nombre, 'id:', prendaId, 'pedidoId:', pedidoId);

        window._editandoPrendaDePedido = {
            pedidoId: pedidoId,
            prendaIndex: prendaIndex,
            prendaId: prendaId
        };

        mostrarLoadingEditarPrenda();

        try {
            const prendaCompleta = await _obtenerPrendaCompletaDesdeAPI(prenda, pedidoId, prendaId, getUrlPrefix, normalizarDatosBD);
            cerrarSweetAlertsActivos();
            await _asegurarEditorPrendaCargado(initPrendaEditor);
            await _abrirModalConPrendaCompleta(prendaCompleta, prendaIndex, abrirModalManual);
            _ajustarUIModoEdicionPrenda();
        } catch (error) {
            console.error('[PedidosAdapter] Error al cargar prenda:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se pudieron cargar los datos de la prenda: ' + error.message, 'error');
            }
        }
    }

    window.PedidosAdapterEditUtils = {
        editarPrendaDePedido
    };
})();
