/**
 * VERIFICACIÓN ESPECÍFICA - Script para inspeccionar el área del recibo 15
 * 
 * Copia y pega esto en la consola del navegador para ver exactamente
 * qué área tiene el recibo número 15 (consecutivo_actual = 15)
 */

console.log('🔍 INICIANDO VERIFICACIÓN DEL RECIBO 15');

// 1. Interceptar la petición de seguimiento
const originalFetch = globalThis.fetch;
globalThis.fetch = function(...args) {
    const [resource, config] = args;
    
    if (resource && typeof resource === 'string' && 
        resource.includes('seguimiento-prenda')) {
        
        return originalFetch.apply(this, args).then(response => {
            return response.json().then(data => {
                console.log('\n╔════════════════════════════════════════════════════════════════╗');
                console.log('║         BÚSQUEDA ESPECÍFICA: RECIBO 15 (consecutivo_actual=15)  ║');
                console.log('╚════════════════════════════════════════════════════════════════╝\n');
                
                if (data.success && data.pedido && data.pedido.prendas) {
                    let encontroRecibo15 = false;
                    
                    data.pedido.prendas.forEach((prenda, index) => {
                        console.log(`\n📌 PRENDA ${index + 1}: ${prenda.nombre_prenda} (ID: ${prenda.id})`);
                        console.log(`   ├── Área más reciente del sistema: ${prenda.area_mas_reciente || 'NO DEFINIDA'}`);
                        
                        console.log(`   └──  BUSCANDO RECIBO 15 EN CONSECUTIVOS:`);
                        let tieneRecibo15 = false;
                        prenda.consecutivos.forEach((cons, idx) => {
                            const esRecibo15 = cons.consecutivo_actual === 15;
                            const marca = esRecibo15 ? '🔴 RECIBO 15 ENCONTRADO' : '  ';
                            
                            console.log(`       ${marca}`);
                            console.log(`       ├── Índice: ${idx}`);
                            console.log(`       ├── Tipo: ${cons.tipo_recibo}`);
                            console.log(`       ├── Consecutivo Actual: ${cons.consecutivo_actual}`);
                            console.log(`       ├── ÁREA: ${cons.area || 'NO DEFINIDA'}`);
                            console.log(`       ├── Estado: ${cons.estado}`);
                            console.log(`       └── Activo: ${cons.activo}`);
                            console.log('');
                            
                            if (esRecibo15) {
                                tieneRecibo15 = true;
                                encontroRecibo15 = true;
                            }
                        });
                        
                        if (!tieneRecibo15) {
                            console.log(`       ❌ No se encontró recibo con consecutivo_actual = 15 en esta prenda\n`);
                        }
                    });
                    
                    console.log('\n╔════════════════════════════════════════════════════════════════╗');
                    if (encontroRecibo15) {
                        console.log('║  RECIBO 15 ENCONTRADO - Ver área arriba (🔴 RECIBO 15)      ║');
                    } else {
                        console.log('║ ⚠️  RECIBO 15 NO ENCONTRADO EN ESTE PEDIDO                      ║');
                    }
                    console.log('╚════════════════════════════════════════════════════════════════╝\n');
                }
                
                // Crear una nueva response con el JSON
                return new Response(
                    new Blob([JSON.stringify(data)], { type: 'application/json' }),
                    response
                );
            });
        });
    }
    
    return originalFetch.apply(this, args);
};

console.log(' Verificación del recibo 15 activada');
console.log('📖 Ahora carga el modal de seguimiento de prendas');
console.log('💡 Se mostrará en la consola el área exacta del recibo número 15\n');
