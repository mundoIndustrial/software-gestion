(function() {
    'use strict';

    async function enviarBorrador(formData, opciones = {}) {
        const modoEdicion = opciones.modoEdicion || false;
        const pedidoId = opciones.pedidoId || null;
        const esActualizacion = !!(modoEdicion && pedidoId && !isNaN(pedidoId) && pedidoId > 0);
        let endpoint = opciones.endpointCrear || '/api/asesores/pedidos/borrador';
        let metodo = 'POST';

        // Compatibilidad Laravel/PHP: multipart con PUT puede perder campos.
        // Para actualizar se envia POST con _method=PUT.
        if (esActualizacion) {
            endpoint = `/api/asesores/pedidos/${pedidoId}/borrador`;
            metodo = 'POST';
            if (formData && typeof formData.set === 'function') {
                formData.set('_method', 'PUT');
            }

            console.warn('[DraftPedidoSaveService] MODO ACTUALIZACION OK', {
                endpoint,
                metodoReal: 'PUT',
                metodoTransporte: metodo,
                pedidoId,
                timestamp: new Date().toISOString()
            });
        } else {
            console.warn('[DraftPedidoSaveService] MODO CREACION OK', {
                endpoint,
                metodo,
                timestamp: new Date().toISOString()
            });
        }

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        // Idempotencia solo en creacion.
        if (!esActualizacion && window.idempotencyService) {
            const idempotencyKey = window.idempotencyService.obtenerIdempotencyKey();
            if (idempotencyKey) {
                headers['X-Idempotency-Key'] = idempotencyKey;
                console.warn('[DraftPedidoSaveService] Idempotency-Key agregado', {
                    key: idempotencyKey
                });
            }
        }

        const response = await fetch(endpoint, {
            method: metodo,
            body: formData,
            headers,
            credentials: 'include'
        });

        let resultado;
        try {
            resultado = await response.json();
        } catch (parseError) {
            console.error('[DraftPedidoSaveService] Error al parsear JSON de respuesta:', {
                status: response.status,
                statusText: response.statusText,
                error: parseError.message
            });
            // Crear un resultado de error genérico si el JSON es inválido
            resultado = {
                success: false,
                message: `Error del servidor (HTTP ${response.status}). La respuesta no es JSON válido.`,
                error: parseError.message
            };
        }

        return {
            response,
            resultado
        };
    }

    window.DraftPedidoSaveService = {
        enviarBorrador
    };
})();
