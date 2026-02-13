/**
 * System Validation Test - Verifica que todos los servicios estén cargados y funcionen
 * Ejecuta automáticamente cuando la página carga
 * 
 * Uso: Abre la consola del navegador y verifica los logs
 */

(async function validateSystem() {
    console.log('\n🔍 ===== SYSTEM VALIDATION TEST =====\n');
    
    const results = {
        passed: 0,
        failed: 0,
        warnings: 0,
        tests: []
    };
    
    // Test 1: EventBus cargado
    try {
        if (typeof EventBus !== 'undefined') {
            console.log('✅ EventBus cargado y disponible');
            results.passed++;
            results.tests.push({ name: 'EventBus', status: 'PASS' });
        } else {
            throw new Error('EventBus no definido');
        }
    } catch (err) {
        console.error('❌ FALLO EventBus:', err.message);
        results.failed++;
        results.tests.push({ name: 'EventBus', status: 'FAIL', error: err.message });
    }
    
    // Test 2: FormatDetector cargado
    try {
        if (typeof FormatDetector !== 'undefined') {
            console.log('✅ FormatDetector cargado y disponible');
            results.passed++;
            results.tests.push({ name: 'FormatDetector', status: 'PASS' });
        } else {
            throw new Error('FormatDetector no definido');
        }
    } catch (err) {
        console.error('❌ FALLO FormatDetector:', err.message);
        results.failed++;
        results.tests.push({ name: 'FormatDetector', status: 'FAIL', error: err.message });
    }
    
    // Test 3: SharedPrendaValidationService cargado
    try {
        if (typeof SharedPrendaValidationService !== 'undefined') {
            console.log('✅ SharedPrendaValidationService cargado');
            results.passed++;
            results.tests.push({ name: 'Validation Service', status: 'PASS' });
        } else {
            throw new Error('SharedPrendaValidationService no definido');
        }
    } catch (err) {
        console.error('❌ FALLO Validation Service:', err.message);
        results.failed++;
        results.tests.push({ name: 'Validation Service', status: 'FAIL', error: err.message });
    }
    
    // Test 4: SharedPrendaDataService cargado
    try {
        if (typeof SharedPrendaDataService !== 'undefined') {
            console.log('✅ SharedPrendaDataService cargado');
            results.passed++;
            results.tests.push({ name: 'Data Service', status: 'PASS' });
        } else {
            throw new Error('SharedPrendaDataService no definido');
        }
    } catch (err) {
        console.error('❌ FALLO Data Service:', err.message);
        results.failed++;
        results.tests.push({ name: 'Data Service', status: 'FAIL', error: err.message });
    }
    
    // Test 5: SharedPrendaStorageService cargado
    try {
        if (typeof SharedPrendaStorageService !== 'undefined') {
            console.log('✅ SharedPrendaStorageService cargado');
            results.passed++;
            results.tests.push({ name: 'Storage Service', status: 'PASS' });
        } else {
            throw new Error('SharedPrendaStorageService no definido');
        }
    } catch (err) {
        console.error('❌ FALLO Storage Service:', err.message);
        results.failed++;
        results.tests.push({ name: 'Storage Service', status: 'FAIL', error: err.message });
    }
    
    // Test 6: SharedPrendaEditorService cargado
    try {
        if (typeof SharedPrendaEditorService !== 'undefined') {
            console.log('✅ SharedPrendaEditorService cargado');
            results.passed++;
            results.tests.push({ name: 'Editor Service', status: 'PASS' });
        } else {
            throw new Error('SharedPrendaEditorService no definido');
        }
    } catch (err) {
        console.error('❌ FALLO Editor Service:', err.message);
        results.failed++;
        results.tests.push({ name: 'Editor Service', status: 'FAIL', error: err.message });
    }
    
    // Test 7: PrendaServiceContainer cargado
    try {
        if (typeof PrendaServiceContainer !== 'undefined') {
            console.log('✅ PrendaServiceContainer cargado');
            results.passed++;
            results.tests.push({ name: 'Service Container', status: 'PASS' });
        } else {
            throw new Error('PrendaServiceContainer no definido');
        }
    } catch (err) {
        console.error('❌ FALLO Service Container:', err.message);
        results.failed++;
        results.tests.push({ name: 'Service Container', status: 'FAIL', error: err.message });
    }
    
    // Test 8: PrendasEditorHelper cargado
    try {
        if (typeof PrendasEditorHelper !== 'undefined' && typeof PrendasEditorHelper.inicializar === 'function') {
            console.log('✅ PrendasEditorHelper cargado con métodos públicos');
            results.passed++;
            results.tests.push({ name: 'Editor Helper', status: 'PASS' });
        } else {
            throw new Error('PrendasEditorHelper no tiene métodos públicos');
        }
    } catch (err) {
        console.error('❌ FALLO Editor Helper:', err.message);
        results.failed++;
        results.tests.push({ name: 'Editor Helper', status: 'FAIL', error: err.message });
    }
    
    // Test 9: Service Container initialization
    try {
        if (window.prendasServiceContainer) {
            console.log('✅ Service Container ya instanciado en window');
            results.passed++;
            results.tests.push({ name: 'Container Instance', status: 'PASS' });
        } else {
            console.warn('⚠️ Service Container no instanciado aún (se instancia con PrendasEditorHelper.inicializar())');
            results.warnings++;
            results.tests.push({ name: 'Container Instance', status: 'WARN', note: 'Instancia se crea con inicializar()' });
        }
    } catch (err) {
        console.error('❌ FALLO Container Instance:', err.message);
        results.failed++;
        results.tests.push({ name: 'Container Instance', status: 'FAIL', error: err.message });
    }
    
    // Test 10: Initialize system
    try {
        console.log('\n📌 Intentando inicializar el sistema...');
        const editor = await PrendasEditorHelper.inicializar();
        
        if (editor && window.editorPrendas) {
            console.log('✅ Sistema inicializado EXITOSAMENTE');
            console.log('   - Editor disponible en window.editorPrendas');
            console.log('   - Service Container disponible en window.prendasServiceContainer');
            results.passed++;
            results.tests.push({ name: 'System Init', status: 'PASS' });
        } else {
            throw new Error('Editor o ServiceContainer no disponibles después de inicializar');
        }
    } catch (err) {
        console.error('❌ FALLO System Init:', err.message);
        results.failed++;
        results.tests.push({ name: 'System Init', status: 'FAIL', error: err.message });
    }
    
    // Resumen final
    console.log('\n📊 ===== RESUMEN FINAL =====');
    console.log(`✅ Exitosos: ${results.passed}`);
    console.log(`❌ Fallos: ${results.failed}`);
    if (results.warnings > 0) {
        console.log(`⚠️ Advertencias: ${results.warnings}`);
    }
    
    if (results.failed === 0) {
        console.log('\n🎉 TODOS LOS TESTS PASARON - SISTEMA LISTO PARA USO');
        console.log('\nAPI Disponible:');
        console.log('  window.PrendasEditorHelper.abrirCrearNueva(options)');
        console.log('  window.PrendasEditorHelper.abrirEditar(prendaId, options)');
        console.log('  window.PrendasEditorHelper.abrirDesdeCotizacion(cotId, prendaId, dataCopy, options)');
    } else {
        console.log('\n⚠️ REVISAR ERRORES ARRIBA');
    }
    
    // Exportar resultados para debugging
    window.__PRENDA_SYSTEM_VALIDATION_RESULTS = results;
    console.log('\n📋 Resultados detallados en window.__PRENDA_SYSTEM_VALIDATION_RESULTS');
    console.log('═══════════════════════════════════════\n');
})();
