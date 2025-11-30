/**
 * Módulo: ApiClient
 * Responsabilidad: Comunicación con el servidor
 * Principio SOLID: Single Responsibility + Dependency Inversion
 */

const ApiClient = (() => {
    /**
     * Obtiene los procesos de una orden
     */
    async function getOrderProcesos(orderId) {
        try {
            const response = await fetch(`/api/ordenes/${orderId}/procesos`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('No se encontraron los procesos de la orden');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error al obtener procesos:', error);
            throw error;
        }
    }
    
    /**
     * Obtiene los días calculados de una orden
     */
    async function getOrderDays(orderId) {
        try {
            const response = await fetch(`/api/registros/${orderId}/dias`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                return await response.json();
            }
            
            return null;
        } catch (error) {
            console.error('Error al obtener días:', error);
            return null;
        }
    }
    
    /**
     * Busca un proceso por número de pedido y nombre
     */
    async function buscarProceso(numeroPedido, nombreProceso) {
        try {
            const response = await fetch(`/api/procesos/buscar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    numero_pedido: numeroPedido,
                    proceso: nombreProceso
                })
            });
            
            if (!response.ok) {
                throw new Error('Proceso no encontrado');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error al buscar proceso:', error);
            throw error;
        }
    }
    
    /**
     * Actualiza un proceso
     */
    async function updateProceso(procesId, data) {
        try {
            const response = await fetch(`/api/procesos/${procesId}/editar`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error('Error al actualizar proceso');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error al actualizar:', error);
            throw error;
        }
    }
    
    /**
     * Elimina un proceso
     */
    async function deleteProceso(procesId, numeroPedido) {
        try {
            const response = await fetch(`/api/procesos/${procesId}/eliminar`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ numero_pedido: numeroPedido })
            });
            
            if (!response.ok) {
                throw new Error('Error al eliminar proceso');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error al eliminar:', error);
            throw error;
        }
    }
    
    // Interfaz pública
    return {
        getOrderProcesos,
        getOrderDays,
        buscarProceso,
        updateProceso,
        deleteProceso
    };
})();

globalThis.ApiClient = ApiClient;

