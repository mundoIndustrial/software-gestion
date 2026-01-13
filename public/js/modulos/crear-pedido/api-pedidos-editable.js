/**
 * Pedidos Editable Web Client
 * Comunicación con rutas web tradicionales (no API REST)
 * Arquitectura: Web tradicional + JSON responses
 */

class PedidosEditableWebClient {
    constructor(baseUrl = '/asesores/pedidos-editable') {
        this.baseUrl = baseUrl;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Agregar un ítem al pedido
     */
    async agregarItem(itemData) {
        try {
            const response = await fetch(`${this.baseUrl}/items/agregar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(itemData),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al agregar ítem');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en agregarItem:', error);
            throw error;
        }
    }

    /**
     * Eliminar un ítem del pedido
     */
    async eliminarItem(index) {
        try {
            const response = await fetch(`${this.baseUrl}/items/eliminar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ index }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al eliminar ítem');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en eliminarItem:', error);
            throw error;
        }
    }

    /**
     * Obtener todos los ítems del pedido
     */
    async obtenerItems() {
        try {
            const response = await fetch(`${this.baseUrl}/items`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al obtener ítems');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en obtenerItems:', error);
            throw error;
        }
    }

    /**
     * Validar el pedido antes de crear
     */
    async validarPedido() {
        try {
            const response = await fetch(`${this.baseUrl}/validar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                return {
                    valid: false,
                    errores: data.errores || [data.message],
                };
            }

            return data;
        } catch (error) {
            console.error('❌ Error en validarPedido:', error);
            return {
                valid: false,
                errores: ['Error al validar el pedido'],
            };
        }
    }

    /**
     * Crear el pedido
     */
    async crearPedido(pedidoData) {
        try {
            const response = await fetch(`${this.baseUrl}/crear`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(pedidoData),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al crear pedido');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en crearPedido:', error);
            throw error;
        }
    }
}

// Instancia global
window.pedidosAPI = new PedidosEditableWebClient();
