/**
 * ============================================================================
 * VALIDACIÓN PayloadNormalizer v3 - Script de Diagnóstico
 * ============================================================================
 * 
 * Copia y pega esto en la consola del navegador (F12) para validar
 * que PayloadNormalizer está completamente cargado y funcionando
 */

(function() {
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║  🔍 VALIDACIÓN PAYLOADNORMALIZER v3                       ║');
    console.log('╚════════════════════════════════════════════════════════════╝');

    // ========================================================================
    // 1. VERIFICAR QUE EXISTE
    // ========================================================================
    console.log('\n PASO 1: Verificar existencia');
    if (!window.PayloadNormalizer) {
        console.error(' CRÍTICO: window.PayloadNormalizer NO EXISTE');
        console.error('   Posibles causas:');
        console.error('   - El archivo payload-normalizer-v3-definitiva.js no se cargó');
        console.error('   - Hay error de sintaxis en el archivo');
        console.error('   - Se está usando la URL sin cache busting');
        return;
    }
    console.log('✅ window.PayloadNormalizer EXISTE');

    // ========================================================================
    // 2. VERIFICAR MÉTODOS
    // ========================================================================
    console.log('\n PASO 2: Verificar métodos');
    const todosMethods = Object.keys(window.PayloadNormalizer);
    const metodosPublicos = todosMethods.filter(m => !m.startsWith('_'));
    const metodosPrivados = todosMethods.filter(m => m.startsWith('_'));

    console.log('Total de propiedades:', todosMethods.length);
    console.log('Métodos públicos:', metodosPublicos.length);
    console.log('Propiedades privadas:', metodosPrivados.length);

    const metodosEsperados = [
        'normalizar',
        'buildFormData',
        'limpiarFiles',
        'validarNoHayFiles',
        'normalizarTallas',
        'normalizarTelas',
        'normalizarProcesos'
    ];

    console.log('\n📋 Verificación de métodos requeridos:');
    let todosPresentes = true;
    metodosEsperados.forEach(metodo => {
        const existe = typeof window.PayloadNormalizer[metodo] === 'function';
        console.log(`   ${existe ? '✅' : ''} ${metodo}: ${existe ? 'FUNCIÓN' : 'NO EXISTE'}`);
        if (!existe) todosPresentes = false;
    });

    if (!todosPresentes) {
        console.error('\n CRÍTICO: Faltan métodos requeridos');
        console.log('Métodos actuales:', metodosPublicos);
        return;
    }


    // ========================================================================
    // 4. VERIFICAR FLAG DE INICIALIZACIÓN
    // ========================================================================
    console.log('\n PASO 4: Verificar inicialización');
    if (window.PayloadNormalizer._initialized !== true) {
        console.warn('  Flag _initialized NO ESTÁ EN TRUE');
    } else {
        console.log('✅ Flag _initialized = true');
    }
    console.log('   Versión:', window.PayloadNormalizer._version || 'Sin versión');

    // ========================================================================
    // 5. PROBAR NORMALIZAR CON DATOS DE PRUEBA
    // ========================================================================
    console.log('\n PASO 5: Probar normalizar con datos de prueba');
    
    const testPedido = {
        cliente: 'Test Cliente',
        asesora: 'Test Asesora',
        forma_de_pago: 'CONTADO',
        prendas: [
            {
                tipo: 'prenda_nueva',
                nombre_prenda: 'Camiseta',
                descripcion: 'Test',
                origen: 'bodega',
                cantidad_talla: {
                    'DAMA': { 'M': '10', 'L': '5' }
                },
                variaciones: {},
                telas: [
                    { tela_id: 1, color_id: 2, tela: 'Algodón', color: 'Blanco' }
                ],
                procesos: {}
            }
        ],
        epps: []
    };

    try {
        const resultado = window.PayloadNormalizer.normalizar(testPedido);
        console.log('✅ normalizar() ejecutado sin errores');
        console.log('   Entrada prendas:', testPedido.prendas.length);
        console.log('   Salida prendas:', resultado.prendas.length);
        
        if (resultado.prendas.length > 0) {
            const prenda = resultado.prendas[0];
            console.log('   Primera prenda normalizada:');
            console.log('   - nombre:', prenda.nombre_prenda);
            console.log('   - tallas:', prenda.cantidad_talla);
            console.log('   - telas:', prenda.telas.length);
        }
    } catch (error) {
        console.error(' ERROR al ejecutar normalizar():', error.message);
        console.error('   Stack:', error.stack);
        return;
    }

    // ========================================================================
    // 6. PROBAR BUILD FORM DATA
    // ========================================================================
    console.log('\n PASO 6: Probar buildFormData');
    
    try {
        const resultado = window.PayloadNormalizer.normalizar(testPedido);
        const filesExtraidos = { prendas: [], epps: [] };
        const formData = window.PayloadNormalizer.buildFormData(resultado, filesExtraidos);
        
        if (formData instanceof FormData) {
            console.log('✅ buildFormData() retorna FormData válido');
            console.log('   Tipo:', formData.constructor.name);
        } else {
            console.warn('  buildFormData() NO retorna FormData, retorna:', typeof formData);
        }
    } catch (error) {
        console.error(' ERROR al ejecutar buildFormData():', error.message);
    }

    // ========================================================================
    // 7. RESUMEN FINAL
    // ========================================================================

})();
