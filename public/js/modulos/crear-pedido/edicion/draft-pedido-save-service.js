(function() {
    'use strict';

    async function enviarBorrador(formData, opciones = {}) {
        const modoEdicion = opciones.modoEdicion || false;
        const pedidoId = opciones.pedidoId || null;
        let endpoint = opciones.endpointCrear || '/api/asesores/pedidos/borrador';
        let metodo = 'POST';

        // 🔧 Determinar si es creación o actualización
        if (modoEdicion && pedidoId && !isNaN(pedidoId) && pedidoId > 0) {
            // ACTUALIZACIÓN: usar PUT
            endpoint = `/api/asesores/pedidos/${pedidoId}/borrador`;
            metodo = 'PUT';
            console.warn('[DraftPedidoSaveService] MODO ACTUALIZACIÓN ✅', {
                endpoint,
                metodo,
                pedidoId,
                timestamp: new Date().toISOString()
            });
        } else {
            // CREACIÓN: usar POST con idempotencia
            console.warn('[DraftPedidoSaveService] MODO CREACIÓN ✅', {
                endpoint,
                metodo,
                timestamp: new Date().toISOString()
            });
        }

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        // 🔧 Agregar idempotency key SOLO para POST (creación)
        if (metodo === 'POST' && window.idempotencyService) {
            const idempotencyKey = window.idempotencyService.obtenerIdempotencyKey();
            if (idempotencyKey) {
                headers['X-Idempotency-Key'] = idempotencyKey;
                console.warn('[DraftPedidoSaveService] Idempotency-Key agregado', {
                    key: idempotencyKey,
                });
            }
        }

        const response = await fetch(endpoint, {
            method: metodo,
            body: formData,
            headers
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
