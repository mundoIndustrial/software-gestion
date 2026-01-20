/**
 * PedidoAPIService - Centraliza todas las llamadas a API de Pedidos
 * 
 * Elimina duplicación: guardarPedido() + actualizarPedido()
 * Ambas replicaban el patrón fetch con headers + manejo de respuesta (50 líneas)
 * 
 * Uso:
 * - PedidoAPI.crear(data)
 * - PedidoAPI.actualizar(pedidoId, data)
 */
window.PedidoAPI = {
    /**
     * Crear un nuevo pedido
     * @param {Object} data - Datos del pedido
     * @returns {Promise}
     */
    crear: async function(data) {
        return this._enviar('POST', '/asesores/pedidos', data);
    },

    /**
     * Actualizar un pedido existente
     * @param {number} pedidoId - ID del pedido
     * @param {Object} data - Datos a actualizar
     * @returns {Promise}
     */
    actualizar: async function(pedidoId, data) {
        return this._enviar('PUT', `/asesores/pedidos/${pedidoId}`, data);
    },

    /**
     * Guardar como borrador
     * @param {Object} data - Datos del borrador
     * @returns {Promise}
     */
    guardarBorrador: async function(data) {
        return this._enviar('POST', '/asesores/borradores/guardar', data);
    },

    /**
     * Realizar petición HTTP centralizada
     * @private
     */
    _enviar: async function(method, url, data) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error(' Error en PedidoAPI:', error);
            throw error;
        }
    }
};

console.log(' PedidoAPIService cargado');
