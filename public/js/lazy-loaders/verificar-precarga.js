/**
 * 🔍 SCRIPT DE VERIFICACIÓN - Precarguía Inteligente
 * 
 * Ejecuta esto en la consola del navegador:
 * 
 * fetch('/verificar-precarga.js').then(r=>r.text()).then(eval)
 * 
 * O copia y pega directamente en la consola
 */

(function() {
    console.log('%c\n🔍 VERIFICACIÓN DE PRECARGUÍA\n', 'font-size: 18px; font-weight: bold; color: #3498db; background: #ecf0f1; padding: 10px;');

    const checks = {
        preloaderExists: false,
        loaderExists: false,
        preloaderFunctional: false,
        loaderFunctional: false,
        precargaIniciada: false,
        warnings: [],
        errors: []
    };

    // 1. Verificar que el preloader existe
    if (window.PrendaEditorPreloader) {
        checks.preloaderExists = true;
        console.log('✅ PrendaEditorPreloader disponible');
    } else {
        checks.errors.push('PrendaEditorPreloader no encontrado');
        console.log('❌ PrendaEditorPreloader no encontrado');
    }

    // 2. Verificar que el loader existe
    if (window.PrendaEditorLoader) {
        checks.loaderExists = true;
        console.log('✅ PrendaEditorLoader disponible');
    } else {
        checks.errors.push('PrendaEditorLoader no encontrado');
        console.log('❌ PrendaEditorLoader no encontrado');
    }

    // 3. Verificar métodos del preloader
    if (checks.preloaderExists) {
        const requiredMethods = ['start', 'loadWithLoader', 'getStatus', 'isReady', 'clearCache', 'forceReload'];
        const availableMethods = requiredMethods.filter(m => typeof window.PrendaEditorPreloader[m] === 'function');
        
        if (availableMethods.length === requiredMethods.length) {
            checks.preloaderFunctional = true;
            console.log(`✅ Todos los métodos disponibles (${availableMethods.join(', ')})`);
        } else {
            const missing = requiredMethods.filter(m => !availableMethods.includes(m));
            checks.errors.push(`Métodos faltantes: ${missing.join(', ')}`);
            console.log(`⚠️ Métodos faltantes: ${missing.join(', ')}`);
        }
    }

    // 4. Verificar métodos del loader
    if (checks.loaderExists) {
        const requiredMethods = ['load', 'isLoaded', 'isLoading'];
        const availableMethods = requiredMethods.filter(m => typeof window.PrendaEditorLoader[m] === 'function');
        
        if (availableMethods.length === requiredMethods.length) {
            checks.loaderFunctional = true;
            console.log(`✅ PrendaEditorLoader funcional (${availableMethods.join(', ')})`);
        } else {
            const missing = requiredMethods.filter(m => !availableMethods.includes(m));
            checks.warnings.push(`PrendaEditorLoader: métodos faltantes: ${missing.join(', ')}`);
            console.log(`⚠️ PrendaEditorLoader: ${missing.join(', ')}`);
        }
    }

    // 5. Verificar estado de precarga
    if (checks.preloaderFunctional) {
        const status = window.PrendaEditorPreloader.getStatus();
        console.log('\n📊 ESTADO DE PRECARGA:');
        console.log(`  ├─ Precargando: ${status.isPreloading ? '🔄 SÍ' : '❌ NO'}`);
        console.log(`  ├─ Precargado: ${status.isPreloaded ? '✅ SÍ' : '❌ NO'}`);
        console.log(`  ├─ Error: ${status.preloadError ? `⚠️ ${status.preloadError}` : '✓ NO'}`);
        console.log(`  ├─ Scripts en caché: ${status.scriptCacheSize}`);
        console.log(`  ├─ Módulos en caché: ${status.moduleCacheSize}`);
        console.log(`  └─ Config:`, status.config);

        if (status.isPreloading) {
            checks.precargaIniciada = true;
        }
    }

    // 6. Verificar Swal2
    if (window.Swal) {
        console.log('\n✅ SweetAlert2 disponible');
    } else {
        checks.warnings.push('SweetAlert2 no encontrado (necesario para loader modal)');
        console.log('\n⚠️ SweetAlert2 no encontrado');
    }

    // Resumen
    console.log('\n' + '═'.repeat(60));
    console.log('%c📋 RESUMEN', 'font-weight: bold; font-size: 14px; color: #2ecc71;');
    console.log('═'.repeat(60));

    const allChecksPassed = 
        checks.preloaderExists && 
        checks.loaderExists && 
        checks.preloaderFunctional && 
        checks.loaderFunctional && 
        checks.errors.length === 0;

    if (allChecksPassed) {
        console.log('%c✅ TODAS LAS VERIFICACIONES PASARON', 'color: #27ae60; font-weight: bold; font-size: 14px;');
        console.log('La precarguía está lista para usar. 🚀');
    } else {
        console.log('%c⚠️ ALGUNAS VERIFICACIONES FALLARON', 'color: #e74c3c; font-weight: bold; font-size: 14px;');
    }

    // Errores
    if (checks.errors.length > 0) {
        console.log('\n%c❌ ERRORES:', 'color: #c0392b; font-weight: bold;');
        checks.errors.forEach(e => console.log(`   • ${e}`));
    }

    // Warnings
    if (checks.warnings.length > 0) {
        console.log('\n%c⚠️ ADVERTENCIAS:', 'color: #f39c12; font-weight: bold;');
        checks.warnings.forEach(w => console.log(`   • ${w}`));
    }

    // Comandos útiles
    console.log('\n%c🎮 COMANDOS DISPONIBLES:', 'font-weight: bold; color: #9b59b6;');
    console.log(`
  Ver estado:              window.PrendaEditorPreloader.getStatus()
  Forzar precarguía:       window.PrendaEditorPreloader.forceReload()
  Limpiar caché:           window.PrendaEditorPreloader.clearCache()
  Verificar si está listo: window.PrendaEditorPreloader.isReady()
  Simular carga con modal: await window.PrendaEditorPreloader.loadWithLoader({title: 'Test'})
    `);

    // Resultado final
    const resultClass = allChecksPassed ? 'color: #27ae60;' : 'color: #e74c3c;';
    const resultText = allChecksPassed ? '✅ LISTO PARA USAR' : '⚠️ REVISAR ERRORES';
    console.log(`%c${resultText}`, `${resultClass} font-weight: bold; font-size: 16px;`);
    console.log('═'.repeat(60) + '\n');

    // Retornar resultado para verificación programática
    return {
        success: allChecksPassed,
        checks: checks,
        status: checks.preloaderFunctional ? window.PrendaEditorPreloader.getStatus() : null
    };
})();
