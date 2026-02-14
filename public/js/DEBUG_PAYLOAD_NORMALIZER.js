/**
 * Script de diagnóstico para verificar PayloadNormalizer
 * Agrega este script en el HTML DESPUÉS de payload-normalizer.js
 * 
 * Ej: <script src="/js/DEBUG_PAYLOAD_NORMALIZER.js"></script>
 */

console.log('='.repeat(60));
console.log('[DEBUG] Iniciando diagnóstico de PayloadNormalizer');
console.log('='.repeat(60));

// Esperar un poco para que todos los scripts se carguen
setTimeout(() => {
    console.log('\n ESTADO DE DEPENDENCIAS GLOBALES:');
    console.log('  - window.PayloadNormalizer:', window.PayloadNormalizer ? ' EXISTE' : ' NO EXISTE');
    
    if (window.PayloadNormalizer) {
        console.log('\n MÉTODOS DISPONIBLES EN PayloadNormalizer:');
        Object.keys(window.PayloadNormalizer).forEach(metodo => {
            const tipo = typeof window.PayloadNormalizer[metodo];
            console.log(`    ${metodo}: ${tipo}`);
        });
        
        // Test básico
        console.log('\n🧪 TEST BÁSICO:');
        try {
            const testPedido = {
                cliente: 'TEST',
                asesora: 'TEST',
                forma_de_pago: 'TEST',
                prendas: [],
                epps: []
            };
            const resultado = window.PayloadNormalizer.normalizar(testPedido);
            console.log(' normalizar() funciona correctamente');
            console.log('  Resultado:', resultado);
        } catch (error) {
            console.error(' ERROR en normalizar():', error.message);
        }
    } else {
        console.error('\n CRITICAL: PayloadNormalizer no está disponible en window');
        console.log('\nVerifica:');
        console.log('  1. payload-normalizer.js está en el HTML');
        console.log('  2. Se carga ANTES de item-api-service.js');
        console.log('  3. No hay errores en la consola');
    }
    
    console.log('\n' + '='.repeat(60));
    console.log('[DEBUG] Diagnóstico completado');
    console.log('='.repeat(60));
    
}, 1000);
