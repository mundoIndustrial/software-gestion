/**
 * ItemAPIService - Servicio de API para Ítems
 * 
 * Responsabilidad única: Comunicación con el backend
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo gestiona llamadas a API
 * - DIP: Puede ser inyectado como dependencia
 * - OCP: Fácil de extender para nuevos endpoints
 */
class ItemAPIService {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api/pedidos-editable';
        this.csrfToken = options.csrfToken || this.obtenerCSRFToken();
    }

    /**
     * Obtener token CSRF del DOM
     */
    obtenerCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Realizar petición HTTP genérica
     * @private
     */
    async realizarPeticion(url, opciones = {}) {
        const configuracion = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                ...opciones.headers
            },
            ...opciones
        };

        const respuesta = await fetch(url, configuracion);
        
        if (!respuesta.ok) {
            throw new Error(`HTTP error! status: ${respuesta.status}`);
        }

        return await respuesta.json();
    }

    /**
     * Obtener ítems desde el servidor
     */
    async obtenerItems() {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items`);
        } catch (error) {
            console.error('Error al obtener ítems:', error);
            throw error;
        }
    }

    /**
     * Agregar un nuevo ítem
     */
    async agregarItem(itemData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items`, {
                method: 'POST',
                body: JSON.stringify(itemData)
            });
        } catch (error) {
            console.error('Error al agregar ítem:', error);
            throw error;
        }
    }

    /**
     * Eliminar un ítem
     */
    async eliminarItem(index) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items/${index}`, {
                method: 'DELETE'
            });
        } catch (error) {
            console.error('Error al eliminar ítem:', error);
            throw error;
        }
    }

    /**
     * Renderizar tarjeta de ítem (HTML)
     */
    async renderizarItemCard(item, index) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/render-item-card`, {
                method: 'POST',
                body: JSON.stringify({ item, index })
            });
        } catch (error) {
            console.error('Error al renderizar tarjeta:', error);
            throw error;
        }
    }

    /**
     * Validar un pedido completo
     */
    async validarPedido(pedidoData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/validar`, {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            });
        } catch (error) {
            console.error('Error al validar pedido:', error);
            throw error;
        }
    }

    /**
     * Crear un nuevo pedido
     */
    async crearPedido(pedidoData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/crear`, {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            });
        } catch (error) {
            console.error('Error al crear pedido:', error);
            throw error;
        }
    }

    /**
     * Actualizar un pedido existente
     */
    async actualizarPedido(pedidoId, pedidoData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/${pedidoId}`, {
                method: 'PUT',
                body: JSON.stringify(pedidoData)
            });
        } catch (error) {
            console.error('Error al actualizar pedido:', error);
            throw error;
        }
    }
}

window.ItemAPIService = ItemAPIService;
