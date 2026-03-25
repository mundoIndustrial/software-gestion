(function() {
    'use strict';

    async function enviarBorrador(formData, opciones = {}) {
        const modoEdicion = opciones.modoEdicion || false;
        const pedidoId = opciones.pedidoId || null;
        let endpoint = opciones.endpointCrear || '/asesores/pedidos/borrador';

        if (modoEdicion && pedidoId) {
            endpoint = `/asesores/pedidos/${pedidoId}/actualizar`;
            formData.append('pedido_id', pedidoId);
            console.debug('[DraftPedidoSaveService] MODO EDICION - Actualizando pedido:', pedidoId);
        } else {
            console.debug('[DraftPedidoSaveService] MODO CREACION - Creando nuevo borrador');
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const resultado = await response.json();

        return {
            response,
            resultado
        };
    }

    window.DraftPedidoSaveService = {
        enviarBorrador
    };
})();
