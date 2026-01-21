/**
 * Script de Debug para inspeccionar el endpoint /ordenes/{id}/editar-pedido
 * Abre la consola del navegador (F12) y ejecuta: debugEndpoint(45767)
 */

async function debugEndpoint(ordenId) {
    console.log('🔍 [DEBUG] Iniciando inspección del endpoint...');
    console.log(`📍 Orden ID: ${ordenId}`);
    console.log(`🌐 URL: /ordenes/${ordenId}/editar-pedido`);
    
    try {
        const response = await fetch(`/ordenes/${ordenId}/editar-pedido`);
        const data = await response.json();
        
        console.log('\n✅ [RESPUESTA COMPLETA]');
        console.log(data);
        
        if (data.success) {
            const orden = data.orden;
            
            console.log('\n📋 [DATOS DE ORDEN]');
            console.log('- numero_pedido:', orden.numero_pedido);
            console.log('- cliente:', orden.cliente);
            console.log('- forma_de_pago:', orden.forma_de_pago);
            console.log('- estado:', orden.estado);
            console.log('- prendas:', orden.prendas.length);
            console.log('- epp:', orden.epp.length);
            
            console.log('\n👕 [ESTRUCTURA DE PRENDA 1]');
            if (orden.prendas.length > 0) {
                const prenda = orden.prendas[0];
                console.log('Claves disponibles:', Object.keys(prenda));
                console.log('\nDetalles completos:');
                console.log(prenda);
                
                console.log('\n🎨 [COLORES Y TELAS]');
                console.log('- telasAgregadas:', prenda.telasAgregadas);
                console.log('- color_nombre:', prenda.color_nombre);
                console.log('- tela_nombre:', prenda.tela_nombre);
                
                console.log('\n📸 [IMÁGENES]');
                console.log('- fotos:', prenda.fotos);
                console.log('- imagenes:', prenda.imagenes);
                console.log('- fotos_tela:', prenda.fotos_tela);
                console.log('- imagenes_tela:', prenda.imagenes_tela);
                
                console.log('\n📊 [TALLAS]');
                console.log('- cantidad_talla:', prenda.cantidad_talla);
                console.log('- generosConTallas:', prenda.generosConTallas);
                
                console.log('\n🔧 [VARIANTES]');
                console.log('- variantes:', prenda.variantes);
                
                console.log('\n⚙️ [PROCESOS]');
                console.log('- procesos:', prenda.procesos);
                if (prenda.procesos && prenda.procesos.length > 0) {
                    console.log('\nProceso 1 detallado:');
                    console.log(prenda.procesos[0]);
                }
            }
            
            console.log('\n🎨 [COLORES DISPONIBLES]');
            console.log(data.colores);
            
            console.log('\n🧵 [TELAS DISPONIBLES]');
            console.log(data.telas);
            
            console.log('\n✨ [COMPARACIÓN DE ESTRUCTURAS]');
            console.log('Frontend espera:');
            console.log('- fotos (para prenda)');
            console.log('- fotos_logo');
            console.log('- fotos_tela');
            console.log('- obs_manga, obs_bolsillos, obs_broche, obs_reflectivo');
            console.log('\nBackend retorna:');
            console.log('- imagenes (para prenda)');
            console.log('- fotos_logo');
            console.log('- imagenes_tela');
            console.log('- obs_manga, obs_bolsillos, obs_broche, obs_reflectivo');
            
        } else {
            console.error('❌ Error:', data.message);
        }
        
    } catch (error) {
        console.error('❌ Error en fetch:', error);
    }
}

// Función auxiliar para ver estructura de una prenda
function verPrendaCompleta(ordenId, prendaIndex = 0) {
    fetch(`/ordenes/${ordenId}/editar-pedido`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.orden.prendas[prendaIndex]) {
                const prenda = data.orden.prendas[prendaIndex];
                console.table(prenda);
                console.log('JSON:', JSON.stringify(prenda, null, 2));
            }
        });
}

// Función para comparar mapeo
function compararMapeo(ordenId) {
    fetch(`/ordenes/${ordenId}/editar-pedido`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.orden.prendas.length > 0) {
                const prenda = data.orden.prendas[0];
                
                console.log('🔄 [MAPEO DE DATOS]');
                console.log('\nAntes del mapeo (backend):');
                console.log('- imagenes:', prenda.imagenes?.length || 0, 'fotos');
                console.log('- imagenes_tela:', prenda.imagenes_tela?.length || 0, 'fotos');
                
                // Simular el mapeo que hace edit-pedido.js
                const prendaMapeada = {
                    ...prenda,
                    fotos: prenda.imagenes || [],
                    fotos_logo: prenda.fotos_logo || [],
                    fotos_tela: prenda.fotos_tela || prenda.imagenes_tela || [],
                    obs_manga: prenda.obs_manga || '',
                    obs_bolsillos: prenda.obs_bolsillos || '',
                    obs_broche: prenda.obs_broche || '',
                    obs_reflectivo: prenda.obs_reflectivo || '',
                    tiene_bolsillos: prenda.tiene_bolsillos || false,
                    tiene_reflectivo: prenda.tiene_reflectivo || false
                };
                
                console.log('\nDespués del mapeo (frontend):');
                console.log('- fotos:', prendaMapeada.fotos?.length || 0, 'fotos');
                console.log('- fotos_tela:', prendaMapeada.fotos_tela?.length || 0, 'fotos');
                console.log('- obs_manga:', prendaMapeada.obs_manga);
                console.log('- obs_broche:', prendaMapeada.obs_broche);
                
                console.log('\n✅ Mapeo completado correctamente');
            }
        });
}

console.log('✅ Debug script cargado');
console.log('Usa: debugEndpoint(45767) para inspeccionar el endpoint');
console.log('Usa: verPrendaCompleta(45767) para ver estructura completa');
console.log('Usa: compararMapeo(45767) para ver el mapeo de datos');
