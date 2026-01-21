/**
 * Script de Debug para inspeccionar el endpoint /asesores/pedidos/{id}/recibos-datos
 * Abre la consola del navegador (F12) y ejecuta: debugRecibosEndpoint(45767)
 */

async function debugRecibosEndpoint(pedidoId) {
    console.log('🔍 [DEBUG RECIBOS] Iniciando inspección del endpoint...');
    console.log(`📍 Pedido ID: ${pedidoId}`);
    console.log(`🌐 URL: /asesores/pedidos/${pedidoId}/recibos-datos`);
    
    try {
        const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`);
        const data = await response.json();
        
        console.log('\n✅ [RESPUESTA COMPLETA]');
        console.log(data);
        
        console.log('\n📋 [ESTRUCTURA DE RESPUESTA]');
        console.log('Claves principales:', Object.keys(data));
        
        if (data.prendas && data.prendas.length > 0) {
            const prenda = data.prendas[0];
            
            console.log('\n👕 [ESTRUCTURA DE PRENDA 1]');
            console.log('Claves disponibles:', Object.keys(prenda));
            console.log('\nDetalles completos:');
            console.log(prenda);
            
            console.log('\n✨ [CAMPOS CRÍTICOS PARA EDICIÓN]');
            console.log('- telasAgregadas:', prenda.telasAgregadas);
            console.log('- generosConTallas:', prenda.generosConTallas);
            console.log('- obs_manga:', prenda.obs_manga);
            console.log('- obs_bolsillos:', prenda.obs_bolsillos);
            console.log('- obs_broche:', prenda.obs_broche);
            console.log('- obs_reflectivo:', prenda.obs_reflectivo);
            console.log('- tiene_bolsillos:', prenda.tiene_bolsillos);
            console.log('- tiene_reflectivo:', prenda.tiene_reflectivo);
            console.log('- variantes:', prenda.variantes);
            console.log('- procesos:', prenda.procesos);
        }
        
    } catch (error) {
        console.error('❌ Error en fetch:', error);
    }
}

console.log('✅ Debug script cargado');
console.log('Usa: debugRecibosEndpoint(45767) para inspeccionar el endpoint /asesores/pedidos/{id}/recibos-datos');
