/**
 * Módulo: ApiClient
 * Responsabilidad: Comunicación con el servidor
 * Principio SOLID: Single Responsibility + Dependency Inversion
 */

const ApiClient = (() => {
    /**
     * Obtiene los procesos de una orden
     * Intenta primero con /api/ordenes/{id}/procesos
     * Si falla, intenta con /api/tabla-original/{numeroPedido}/procesos
     * Si falla, intenta con /api/tabla-original-bodega/{numeroPedido}/procesos
     */
    async function getOrderProcesos(orderId) {
        try {
            // Intentar primero con la ruta de ordenes (PedidoProduccion)
            const response = await fetch(`/api/ordenes/${orderId}/procesos`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                return await response.json();
            }
            
            // Si falla, intentar con tabla_original (RegistroOrden)
            console.log(' Ruta /api/ordenes falló, intentando /api/tabla-original');
            const responseTablaOriginal = await fetch(`/api/tabla-original/${orderId}/procesos`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (responseTablaOriginal.ok) {
                return await responseTablaOriginal.json();
            }
            
            // Si falla, intentar con tabla_original_bodega
            console.log(' Ruta /api/tabla-original falló, intentando /api/tabla-original-bodega');
            const responseTablaOriginalBodega = await fetch(`/api/tabla-original-bodega/${orderId}/procesos`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!responseTablaOriginalBodega.ok) {
                throw new Error('No se encontraron los procesos de la orden');
            }
            
            return await responseTablaOriginalBodega.json();
        } catch (error) {
            console.error('Error al obtener procesos:', error);
            throw error;
        }
    }
    
    /**
     * Obtiene los días calculados de una orden
     * Intenta primero con /api/registros/{id}/dias
     * Si falla, intenta con /api/bodega/{id}/dias
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
            
            // Si falla, intentar con ruta de bodega
            console.log(' Ruta /api/registros falló, intentando /api/bodega');
            const responseBodega = await fetch(`/api/bodega/${orderId}/dias`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (responseBodega.ok) {
                return await responseBodega.json();
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
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error al actualizar proceso');
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

