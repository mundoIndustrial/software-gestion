/**
 * Script de prueba para WebSocket en tiempo real
 * Simula cambios de estado y área para verificar que los colores se actualicen
 */

console.log('🧪 WebSocket Test Script Cargado');

/**
 * Simular un evento OrdenUpdated desde el servidor
 * Útil para testing sin cambiar realmente la BD
 */
function simulateOrdenUpdate(numeroPedido, cambios) {
    console.log('🧪 Simulando actualización de orden:', { numeroPedido, cambios });
    
    // Obtener la orden actual
    const row = document.querySelector(`.table-row[data-orden-id="${numeroPedido}"]`);
    if (!row) {
        console.error(` Fila no encontrada para pedido ${numeroPedido}`);
        return;
    }
    
    // Construir objeto de orden con los cambios
    const ordenData = {
        numero_pedido: numeroPedido,
        estado: cambios.estado || row.querySelector('.estado-dropdown')?.value,
        area: cambios.area || row.querySelector('.area-dropdown')?.value,
        dia_de_entrega: cambios.dia_de_entrega || row.querySelector('.dia-entrega-dropdown')?.value
    };
    
    // Simular el evento
    const changedFields = Object.keys(cambios);
    
    if (typeof RealtimeOrderHandler !== 'undefined') {
        RealtimeOrderHandler.updateOrderRow(ordenData, changedFields);
        console.log(' Actualización simulada completada');
    } else {
        console.error(' RealtimeOrderHandler no disponible');
    }
}

/**
 * Ejemplos de uso:
 * 
 * // Cambiar estado a Entregado (debe volverse azul)
 * simulateOrdenUpdate(1, { estado: 'Entregado' });
 * 
 * // Cambiar estado a Anulada (debe volverse marrón)
 * simulateOrdenUpdate(1, { estado: 'Anulada' });
 * 
 * // Cambiar área
 * simulateOrdenUpdate(1, { area: 'Costura' });
 */

// Exponer globalmente para testing
window.simulateOrdenUpdate = simulateOrdenUpdate;
console.log(' Función simulateOrdenUpdate disponible en consola');
