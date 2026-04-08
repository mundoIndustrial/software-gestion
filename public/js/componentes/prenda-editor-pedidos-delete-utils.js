/**
 * Utilities for prenda-editor-pedidos-adapter delete flow.
 * Exposes: window.PedidosAdapterDeleteUtils
 */
(function() {
    'use strict';

    function _centrarModalSwal(modal, zIndex = 2000000) {
        const container = modal?.closest('.swal2-container');
        if (!container) return;

        container.style.position = 'fixed';
        container.style.inset = '0';
        container.style.display = 'flex';
        container.style.alignItems = 'center';
        container.style.justifyContent = 'center';
        container.style.padding = '1rem';
        container.style.zIndex = String(zIndex);
    }

    async function _enviarEliminarPrendaRequest(pedidoId, prendaId, motivo, getUrlPrefix) {
        const urlPrefix = getUrlPrefix();
        const deleteUrl = `${urlPrefix.save}/${pedidoId}/eliminar-prenda`;

        console.log('[PedidosAdapter]  Enviando DELETE a:', deleteUrl);

        return fetch(deleteUrl, {
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
    }

    async function _obtenerErrorEliminarDesdeResponse(response) {
        let errorMsg = 'Error desconocido';
        let errorJson = null;
        try {
            errorJson = await response.json();
            errorMsg = errorJson.message || errorJson.error || JSON.stringify(errorJson);
        } catch (e) {
            errorMsg = `HTTP ${response.status}: ${response.statusText}`;
        }
        return { errorMsg, errorJson };
    }

    function _mostrarBloqueoEliminacion(errorMsg) {
        const texto = errorMsg || 'Esta prenda se encuentra en produccion, por ende no se puede eliminar. Comunicate con el lider de produccion.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminacion bloqueada',
                text: texto,
                confirmButtonText: 'Entendido',
                customClass: {
                    container: 'swal-bloqueo-eliminacion-container'
                },
                didOpen: (modal) => _centrarModalSwal(modal)
            });
            return;
        }
        alert(texto);
    }

    function _actualizarEstadoLocalTrasEliminar(prendaIndex) {
        if (window.datosEdicionPedido?.prendas && prendaIndex !== null && prendaIndex !== undefined) {
            window.datosEdicionPedido.prendas.splice(prendaIndex, 1);
            console.log('[PedidosAdapter]  Lista de prendas actualizada (removida prenda en indice', prendaIndex + ')');
        }
    }

    function _recargarListaPrendasTrasEliminar() {
        setTimeout(function() {
            if (typeof window.abrirEditarPrendas === 'function') {
                window.abrirEditarPrendas();
            }
        }, 1900);
    }

    async function eliminarPrendaDelAPI(options = {}) {
        const pedidoId = options.pedidoId;
        const prendaId = options.prendaId;
        const prendaIndex = options.prendaIndex;
        const motivo = options.motivo;
        const getUrlPrefix = typeof options.getUrlPrefix === 'function' ? options.getUrlPrefix : null;
        const ensureOverlayStyle = typeof options.ensureOverlayStyle === 'function' ? options.ensureOverlayStyle : null;
        const mostrarLoading = typeof options.mostrarLoading === 'function' ? options.mostrarLoading : null;
        const mostrarError = typeof options.mostrarError === 'function' ? options.mostrarError : null;
        const mostrarExito = typeof options.mostrarExito === 'function' ? options.mostrarExito : null;

        if (!getUrlPrefix) {
            throw new Error('getUrlPrefix es requerido');
        }

        try {
            if (ensureOverlayStyle) {
                ensureOverlayStyle('swal-eliminar-prenda-style', 'swal-eliminar-prenda-container');
            }
            if (mostrarLoading) {
                mostrarLoading();
            }

            const response = await _enviarEliminarPrendaRequest(pedidoId, prendaId, motivo, getUrlPrefix);
            if (!response.ok) {
                const { errorMsg, errorJson } = await _obtenerErrorEliminarDesdeResponse(response);
                console.error('[PedidosAdapter] Error al eliminar:', errorMsg);
                if (response.status === 409) {
                    _mostrarBloqueoEliminacion(errorMsg);
                    return;
                }
                if (mostrarError) {
                    mostrarError(`No se pudo eliminar: ${errorMsg}`);
                }
                return;
            }

            const result = await response.json();
            console.log('[PedidosAdapter]  Prenda eliminada:', result);

            _actualizarEstadoLocalTrasEliminar(prendaIndex);
            if (mostrarExito) {
                mostrarExito();
            }
            _recargarListaPrendasTrasEliminar();
        } catch (error) {
            console.error('[PedidosAdapter] Error de red al eliminar:', error);
            if (mostrarError) {
                mostrarError('Error de conexion al eliminar la prenda');
            }
        }
    }

    window.PedidosAdapterDeleteUtils = {
        eliminarPrendaDelAPI
    };
})();
