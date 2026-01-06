/**
 * TEST SIMPLE - VERIFICAR CARGA DE MÓDULOS
 * Abre la consola del navegador (F12) y ejecuta: testFase1()
 */

function testFase1() {
    console.log('🧪 Iniciando test Fase 1...\n');
    
    let testsPasados = 0;
    let testsTotales = 0;
    
    // ============================================================
    // TEST 1: Verificar carga de constantes
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof LOGO_OPCIONES_POR_UBICACION === 'object', 'LOGO_OPCIONES_POR_UBICACION no está cargado');
        console.assert(LOGO_OPCIONES_POR_UBICACION.CAMISA, 'CAMISA no tiene opciones');
        console.log('✅ TEST 1 PASADO: Constantes de configuración cargadas correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 1 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 2: Verificar carga de TALLAS_ESTANDAR
    // ============================================================
    testsTotales++;
    try {
        console.assert(Array.isArray(TALLAS_ESTANDAR), 'TALLAS_ESTANDAR no es un array');
        console.assert(TALLAS_ESTANDAR.length > 0, 'TALLAS_ESTANDAR está vacío');
        console.assert(TALLAS_ESTANDAR.includes('M'), 'TALLAS_ESTANDAR no contiene M');
        console.log('✅ TEST 2 PASADO: Tallas estándar cargadas correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 2 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 3: Verificar carga de CONFIG
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof CONFIG === 'object', 'CONFIG no está cargado');
        console.assert(CONFIG.MAX_FOTOS_LOGO === 5, 'MAX_FOTOS_LOGO incorrecto');
        console.assert(CONFIG.MAX_FOTOS_PRENDA === 10, 'MAX_FOTOS_PRENDA incorrecto');
        console.log('✅ TEST 3 PASADO: Configuración general cargada correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 3 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 4: Verificar carga de MENSAJES
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof MENSAJES === 'object', 'MENSAJES no está cargado');
        console.assert(typeof MENSAJES.PRENDA_ELIMINADA === 'string', 'PRENDA_ELIMINADA no es string');
        console.assert(MENSAJES.PRENDA_ELIMINADA.length > 0, 'PRENDA_ELIMINADA está vacío');
        console.log('✅ TEST 4 PASADO: Mensajes cargados correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 4 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 5: Verificar helpers de modal
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof confirmarEliminacion === 'function', 'confirmarEliminacion no es función');
        console.assert(typeof mostrarExito === 'function', 'mostrarExito no es función');
        console.assert(typeof mostrarError === 'function', 'mostrarError no es función');
        console.log('✅ TEST 5 PASADO: Helpers de modal cargados correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 5 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 6: Verificar helpers de DOM
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof getElement === 'function', 'getElement no es función');
        console.assert(typeof getElements === 'function', 'getElements no es función');
        console.assert(typeof toggleVisibility === 'function', 'toggleVisibility no es función');
        console.log('✅ TEST 6 PASADO: Helpers de DOM cargados correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 6 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 7: Verificar helpers de datos
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof parseArrayData === 'function', 'parseArrayData no es función');
        console.assert(typeof fotoToUrl === 'function', 'fotoToUrl no es función');
        console.assert(typeof generarUUID === 'function', 'generarUUID no es función');
        console.log('✅ TEST 7 PASADO: Helpers de datos cargados correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 7 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 8: Verificar helpers de validación
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof estaVacio === 'function', 'estaVacio no es función');
        console.assert(typeof esNumero === 'function', 'esNumero no es función');
        console.assert(estaVacio('') === true, 'estaVacio no funciona con string vacío');
        console.assert(estaVacio('  ') === true, 'estaVacio no funciona con espacios');
        console.assert(estaVacio('hola') === false, 'estaVacio falla con string lleno');
        console.log('✅ TEST 8 PASADO: Helpers de validación funcionan correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 8 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 9: Verificar clases de gestor de fotos
    // ============================================================
    testsTotales++;
    try {
        console.assert(typeof GestorFotos === 'function', 'GestorFotos no es clase');
        console.assert(typeof GestorFotosLogo === 'function', 'GestorFotosLogo no es clase');
        console.assert(typeof GestorFotosPrenda === 'function', 'GestorFotosPrenda no es clase');
        console.assert(typeof GestorFotosTela === 'function', 'GestorFotosTela no es clase');
        console.log('✅ TEST 9 PASADO: Clases de gestor de fotos cargadas correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 9 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 10: Verificar método de gestor
    // ============================================================
    testsTotales++;
    try {
        const gestor = new GestorFotos([], 5);
        console.assert(gestor.espaciosDisponibles() === 5, 'espaciosDisponibles incorrecto');
        console.assert(gestor.cantidadFotos() === 0, 'cantidadFotos incorrecto');
        const resultado = gestor.puedeAgregarFoto(3);
        console.assert(resultado.permitido === true, 'puedeAgregarFoto no permite 3 fotos');
        console.log('✅ TEST 10 PASADO: Métodos del gestor funcionan correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 10 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 11: Verificar parseArrayData
    // ============================================================
    testsTotales++;
    try {
        const resultado1 = parseArrayData('["a", "b", "c"]');
        console.assert(Array.isArray(resultado1), 'parseArrayData no retorna array');
        console.assert(resultado1.length === 3, 'parseArrayData no parsea correctamente');
        
        const resultado2 = parseArrayData(['x', 'y']);
        console.assert(Array.isArray(resultado2), 'parseArrayData no funciona con array');
        
        const resultado3 = parseArrayData(null);
        console.assert(Array.isArray(resultado3), 'parseArrayData no retorna array con null');
        console.assert(resultado3.length === 0, 'parseArrayData no retorna array vacío con null');
        
        console.log('✅ TEST 11 PASADO: parseArrayData funciona correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 11 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 12: Verificar sinDuplicados
    // ============================================================
    testsTotales++;
    try {
        const resultado = sinDuplicados([1, 2, 2, 3, 3, 3, 4]);
        console.assert(Array.isArray(resultado), 'sinDuplicados no retorna array');
        console.assert(resultado.length === 4, 'sinDuplicados no elimina duplicados correctamente');
        console.log('✅ TEST 12 PASADO: sinDuplicados funciona correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 12 FALLIDO:', e.message);
    }
    
    // ============================================================
    // TEST 13: Verificar generarUUID
    // ============================================================
    testsTotales++;
    try {
        const uuid1 = generarUUID();
        const uuid2 = generarUUID();
        console.assert(typeof uuid1 === 'string', 'generarUUID no retorna string');
        console.assert(uuid1.length > 0, 'generarUUID retorna string vacío');
        console.assert(uuid1 !== uuid2, 'generarUUID genera UUIDs duplicados');
        console.log('✅ TEST 13 PASADO: generarUUID funciona correctamente');
        testsPasados++;
    } catch (e) {
        console.error('❌ TEST 13 FALLIDO:', e.message);
    }
    
    // ============================================================
    // RESUMEN
    // ============================================================
    console.log('\n' + '='.repeat(60));
    console.log(`📊 RESULTADO: ${testsPasados}/${testsTotales} tests pasados`);
    console.log('='.repeat(60) + '\n');
    
    if (testsPasados === testsTotales) {
        console.log('🎉 ¡TODOS LOS TESTS PASARON! Fase 1 está lista para usar.');
        return true;
    } else {
        console.log(`⚠️  ${testsTotales - testsPasados} test(s) fallaron. Revisa los errores arriba.`);
        return false;
    }
}

// Auto-ejecutar si se carga directamente
if (typeof window !== 'undefined' && window.location.pathname.includes('pedidos')) {
    console.log('💡 Tip: Ejecuta testFase1() en la consola para verificar que todo está cargado correctamente.');
}
