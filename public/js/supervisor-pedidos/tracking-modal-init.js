/**
 * =====================================================
 * SUPERVISOR PEDIDOS - TRACKING MODAL INIT
 * =====================================================
 * Funciones para abrir/cerrar el modal de seguimiento.
 */

window.openOrderTrackingModal = function(ordenId) {
    console.log('[openOrderTrackingModal] Abriendo modal para orden:', ordenId);

    if (typeof mostrarTrackingModal !== 'function') {
        console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible');
        alert('Error: El modal de seguimiento no está cargado correctamente. Por favor, recarga la página.');
        return;
    }

    console.log('[openOrderTrackingModal] mostrarTrackingModal está disponible');
    console.log('[openOrderTrackingModal] Obteniendo datos de /supervisor-pedidos/' + ordenId + '/datos');

    fetch(`/supervisor-pedidos/${ordenId}/datos`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    })
        .then(response => {
            console.log('[openOrderTrackingModal] Response status:', response.status);
            if (!response.ok) {
                console.error('[openOrderTrackingModal] HTTP error! status:', response.status);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(pedidoData => {
            console.log('[openOrderTrackingModal] Datos del pedido recibidos:', pedidoData);
            console.log('[openOrderTrackingModal] Obteniendo procesos de /api/ordenes/' + ordenId + '/procesos');

            return fetch(`/api/ordenes/${ordenId}/procesos`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(procResponse => {
                    console.log('[openOrderTrackingModal] Procesos response status:', procResponse.status);
                    if (procResponse.ok) {
                        return procResponse.json().then(procesos => {
                            console.log('[openOrderTrackingModal] Procesos obtenidos:', procesos);
                            pedidoData.procesos = procesos;
                            return pedidoData;
                        });
                    }
                    console.warn('[openOrderTrackingModal] No se pudieron cargar los procesos (status ' + procResponse.status + ')');
                    pedidoData.procesos = [];
                    return pedidoData;
                })
                .catch(error => {
                    console.warn('[openOrderTrackingModal] Error al obtener procesos:', error);
                    pedidoData.procesos = [];
                    return pedidoData;
                });
        })
        .then(data => {
            console.log('[openOrderTrackingModal] Datos finales listos. Llamando a mostrarTrackingModal...');
            if (typeof mostrarTrackingModal !== 'function') {
                console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible en el then final');
                alert('Error: El modal de seguimiento no está cargado correctamente.');
                return;
            }
            try {
                mostrarTrackingModal(data);
                console.log('[openOrderTrackingModal] Modal mostrado exitosamente');
            } catch (e) {
                console.error('[openOrderTrackingModal] Error al llamar mostrarTrackingModal:', e);
                alert('Error: ' + e.message);
            }
        })
        .catch(error => {
            console.error('[openOrderTrackingModal] Error general:', error);
            alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
        });
};

window.closeOrderTracking = function() {
    console.log('[closeOrderTracking] Cerrando modal de seguimiento');
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
        modal.style.display = 'none';
    }
};
