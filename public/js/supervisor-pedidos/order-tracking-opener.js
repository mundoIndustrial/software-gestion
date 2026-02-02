/**
 * Order Tracking Modal Opener para Supervisor de Pedidos
 * Abre el modal de seguimiento de un pedido y carga sus datos
 */

/**
 * Abre el modal de seguimiento del pedido
 * @param {number} ordenId - ID de la orden/pedido
 */
window.openOrderTrackingModal = function(ordenId) {
    console.log('[openOrderTrackingModal] Abriendo modal para orden:', ordenId);
    
    // Primero verificar que mostrarTrackingModal está disponible
    if (typeof mostrarTrackingModal !== 'function') {
        console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible');
        console.log('[openOrderTrackingModal] Funciones globales disponibles:', Object.keys(window).filter(k => k.includes('mostrar') || k.includes('tracking')));
        alert('Error: El modal de seguimiento no está cargado correctamente. Por favor, recarga la página.');
        return;
    }
    
    console.log('[openOrderTrackingModal] mostrarTrackingModal está disponible');
    
    // Obtener datos del pedido desde la ruta de supervisor
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
            console.log('[openOrderTrackingModal] Response headers:', response.headers);
            
            if (!response.ok) {
                console.error('[openOrderTrackingModal] HTTP error! status:', response.status);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(pedidoData => {
            console.log('[openOrderTrackingModal] Datos del pedido recibidos:', pedidoData);
            console.log('[openOrderTrackingModal] Tipo de datos:', typeof pedidoData);
            console.log('[openOrderTrackingModal] Tiene procesos?:', !!pedidoData.procesos);
            
            // Si tenemos los datos, intentar obtener los procesos
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
                    
                    // Si la respuesta es exitosa, agregar los procesos
                    if (procResponse.ok) {
                        return procResponse.json().then(procesos => {
                            console.log('[openOrderTrackingModal] Procesos obtenidos:', procesos);
                            // Asegurar que procesos es un array
                            pedidoData.procesos = Array.isArray(procesos) ? procesos : (procesos.procesos || []);
                            return pedidoData;
                        });
                    }
                    // Si falla, devolver los datos sin procesos pero mostrar el modal igual
                    console.warn('[openOrderTrackingModal] No se pudieron cargar los procesos (status ' + procResponse.status + '), continuando sin ellos');
                    pedidoData.procesos = [];
                    return pedidoData;
                })
                .catch(error => {
                    // Log del error pero continuar sin procesos
                    console.warn('[openOrderTrackingModal] Error al obtener procesos:', error);
                    pedidoData.procesos = [];
                    return pedidoData;
                });
        })
        .then(data => {
            console.log('[openOrderTrackingModal] Datos finales listos. Llamando a mostrarTrackingModal...');
            console.log('[openOrderTrackingModal] Datos:', data);
            
            // Verificar nuevamente que la función existe
            if (typeof mostrarTrackingModal !== 'function') {
                console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible');
                alert('Error: El modal de seguimiento no está cargado correctamente.');
                return;
            }
            
            // Llamar a la función que rellena y muestra el modal
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
            console.error('[openOrderTrackingModal] Stack:', error.stack);
            alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
        });
};

/**
 * Cierra el modal de seguimiento
 */
window.closeOrderTracking = function() {
    console.log('[closeOrderTracking] Cerrando modal de seguimiento');
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
        modal.style.display = 'none';
    }
};
