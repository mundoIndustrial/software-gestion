/**
 * =====================================================
 * SUPERVISOR PEDIDOS - TRACKING MODAL INIT
 * =====================================================
 * Funciones para abrir/cerrar el modal de seguimiento.
 *
 * Requiere: shared/bootstrap.js -> window.shared (http, notify, modal)
 */

if (!window.shared?.isReady) {
    throw new Error('[tracking-modal-init] window.shared no está disponible. Asegúrate de cargar shared/bootstrap.js ANTES de este archivo.');
}

const { http: _trackingHttp, notify: _trackingNotify, modal: _trackingModal } = window.shared;

window.openOrderTrackingModal = async function(ordenId) {
    console.log('[openOrderTrackingModal] Abriendo modal para orden:', ordenId);

    if (typeof mostrarTrackingModal !== 'function') {
        console.error('[openOrderTrackingModal] mostrarTrackingModal no está disponible');
        _trackingNotify.error('El modal de seguimiento no está cargado. Recarga la página.');
        return;
    }

    try {
        const pedidoData = await _trackingHttp.get(`/supervisor-pedidos/${ordenId}/datos`);
        console.log('[openOrderTrackingModal] Datos del pedido recibidos');

        try {
            pedidoData.procesos = await _trackingHttp.get(`/api/ordenes/${ordenId}/procesos`);
        } catch (procError) {
            console.warn('[openOrderTrackingModal] Error al obtener procesos:', procError);
            pedidoData.procesos = [];
        }

        mostrarTrackingModal(pedidoData);
        console.log('[openOrderTrackingModal] Modal mostrado exitosamente');

    } catch (error) {
        console.error('[openOrderTrackingModal] Error general:', error);
        _trackingNotify.error('No se puede abrir el seguimiento. Intenta nuevamente.');
    }
};

window.closeOrderTracking = function() {
    console.log('[closeOrderTracking] Cerrando modal de seguimiento');
    _trackingModal.close('orderTrackingModal');
};
