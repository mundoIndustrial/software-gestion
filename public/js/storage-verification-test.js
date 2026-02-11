/**
 * 🧪 SCRIPT DE VERIFICACIÓN RÁPIDA
 * 
 * Ejecuta este script en la consola para verificar que todo está configurado correctamente
 * 
 * Copia y pega en la consola del navegador (F12)
 */

(function() {
    console.clear();
    console.log(' INICIANDO VERIFICACIÓN DE STORAGE Y HANDLERS...\n');
    
    const checks = {
        passed: 0,
        failed: 0,
        warnings: 0,
        results: []
    };
    
    function pass(name, details = '') {
        checks.passed++;
        console.log(` ${name}`);
        if (details) console.log(`     ${details}`);
        checks.results.push({ status: 'pass', name });
    }
    
    function fail(name, error) {
        checks.failed++;
        console.log(` ${name}`);
        if (error) console.log(`     ${error}`);
        checks.results.push({ status: 'fail', name });
    }
    
    function warn(name, details) {
        checks.warnings++;
        console.log(`  ${name}`);
        if (details) console.log(`     ${details}`);
        checks.results.push({ status: 'warn', name });
    }
    
    // ==================== VERIFICACIONES ====================
    
    console.log(' VERIFICANDO MÓDULOS CARGADOS...\n');
    
    // 1. Storage Proxy
    if (window.StorageProxyState) {
        pass('StorageProxy cargado', `localStorage: ${window.StorageProxyState.isLocalStorageAvailable}, sessionStorage: ${window.StorageProxyState.isSessionStorageAvailable}`);
    } else {
        fail('StorageProxy NO cargado', 'Asegúrate de que storage-proxy.js está al inicio de <head>');
    }
    
    // 2. Message Handler
    if (window.UniversalMessageHandler) {
        const state = window.UniversalMessageHandler.getState();
        pass('MessageHandler cargado', `Entorno: ${state.environment}, Inicializado: ${state.initialized}`);
    } else {
        fail('MessageHandler NO cargado', 'Asegúrate de que message-handler-universal.js se cargó');
    }
    
    // 3. Storage Module
    if (window.StorageModule) {
        pass('StorageModule cargado', `Inicializado: ${window.StorageModule.initialized}`);
    } else {
        warn('StorageModule NO cargado', 'Opcional - solo necesario si usas sincronización entre pestañas');
    }
    
    // 4. Extension Listener
    if (window.ExtensionStorageAPI) {
        pass('ExtensionStorageAPI disponible', 'Listeners de extensión cargados');
    } else {
        warn('ExtensionStorageAPI NO disponible', 'Opcional - solo si usas Chrome Extension');
    }
    
    console.log('\n📝 VERIFICANDO FUNCIONALIDADES...\n');
    
    // 5. Test localStorage.setItem
    try {
        const testKey = `test_${Date.now()}`;
        localStorage.setItem(testKey, 'test_value');
        const retrieved = localStorage.getItem(testKey);
        localStorage.removeItem(testKey);
        
        if (retrieved === 'test_value') {
            pass('localStorage.setItem/getItem', 'Funciona correctamente');
        } else {
            fail('localStorage.setItem/getItem', 'Valores no coinciden');
        }
    } catch (e) {
        fail('localStorage.setItem/getItem', e.message);
    }
    
    // 6. Test sessionStorage.setItem
    try {
        const testKey = `test_${Date.now()}`;
        sessionStorage.setItem(testKey, 'test_value');
        const retrieved = sessionStorage.getItem(testKey);
        sessionStorage.removeItem(testKey);
        
        if (retrieved === 'test_value') {
            pass('sessionStorage.setItem/getItem', 'Funciona correctamente');
        } else {
            fail('sessionStorage.setItem/getItem', 'Valores no coinciden');
        }
    } catch (e) {
        fail('sessionStorage.setItem/getItem', e.message);
    }
    
    // 7. Test localStorage.clear
    try {
        localStorage.setItem('test_clear_1', 'v1');
        localStorage.setItem('test_clear_2', 'v2');
        localStorage.clear();
        
        if (localStorage.length === 0) {
            pass('localStorage.clear()', 'Funcionó correctamente');
        } else {
            fail('localStorage.clear()', `Aún hay ${localStorage.length} items`);
        }
    } catch (e) {
        fail('localStorage.clear()', e.message);
    }
    
    // 8. Test BroadcastChannel
    if (typeof BroadcastChannel === 'undefined') {
        warn('BroadcastChannel', 'No disponible en este navegador (fallback a Storage Events)');
    } else {
        try {
            const bc = new BroadcastChannel('test');
            bc.close();
            pass('BroadcastChannel', 'Disponible y funcional');
        } catch (e) {
            warn('BroadcastChannel', `Disponible pero con errores: ${e.message}`);
        }
    }
    
    // 9. Test mensaje universal
    if (window.UniversalMessageHandler) {
        try {
            const state = window.UniversalMessageHandler.getState();
            if (state.initialized) {
                pass('UniversalMessageHandler.sendMessage', 'API disponible');
            } else {
                fail('UniversalMessageHandler.sendMessage', 'No inicializado');
            }
        } catch (e) {
            fail('UniversalMessageHandler.sendMessage', e.message);
        }
    }
    
    // 10. Test StorageModule.broadcastUpdate
    if (window.StorageModule) {
        try {
            window.StorageModule.initializeListener();
            pass('StorageModule.initializeListener()', 'Funciona');
        } catch (e) {
            fail('StorageModule.initializeListener()', e.message);
        }
    }
    
    console.log('\n VERIFICANDO CONFIGURACIÓN...\n');
    
    // 11. Verificar order de carga
    try {
        const scripts = Array.from(document.querySelectorAll('script[src]'))
            .map(s => s.src.split('/').pop());
        
        const storageProxyIndex = scripts.findIndex(s => s.includes('storage-proxy'));
        const messageHandlerIndex = scripts.findIndex(s => s.includes('message-handler'));
        const storageModuleIndex = scripts.findIndex(s => s.includes('storageModule'));
        
        if (storageProxyIndex >= 0 && 
            (messageHandlerIndex < 0 || storageProxyIndex < messageHandlerIndex)) {
            pass('Orden de carga', 'storage-proxy antes que message-handler ✓');
        } else {
            warn('Orden de carga', 'Verifica que storage-proxy sea el PRIMER script');
        }
    } catch (e) {
        warn('Orden de carga', e.message);
    }
    
    // 12. Verificar memoria como fallback
    try {
        const debug = window.StorageProxyState.getDebugInfo();
        if (debug.localStorage && debug.localStorage.memoryLength >= 0) {
            pass('Memory fallback', `${debug.localStorage.memoryLength} items en memoria (localStorage)`);
        }
    } catch (e) {
        console.debug('Memory fallback info no disponible');
    }
    
    console.log('\n' + '='.repeat(50));
    console.log(` RESUMEN:`);
    console.log(`    Pasadas: ${checks.passed}`);
    console.log(`    Fallidas: ${checks.failed}`);
    console.log(`     Advertencias: ${checks.warnings}`);
    console.log('='.repeat(50) + '\n');
    
    if (checks.failed === 0) {
        console.log('🎉 ¡PERFECTO! Todo está configurado correctamente.\n');
    } else {
        console.log('  Hay problemas que necesitan atención. Revisa los errores arriba.\n');
    }
    
    // ==================== COMANDOS DE DEBUG ====================
    console.log('💡 COMANDOS ÚTILES:\n');
    console.log('  // Ver estado del proxy');
    console.log('  console.log(window.StorageProxyState.getStatus())\n');
    
    console.log('  // Ver información detallada');
    console.log('  console.log(window.StorageProxyState.getDebugInfo())\n');
    
    console.log('  // Ver estado del handler');
    console.log('  console.log(window.UniversalMessageHandler.getState())\n');
    
    console.log('  // Ver estado del módulo');
    console.log('  console.log(window.StorageModule.getState())\n');
    
    console.log('  // Habilitar logs detallados');
    console.log('  window.UniversalMessageHandler.setDebug(true)\n');
    
    console.log('  // Probar storage');
    console.log('  localStorage.setItem("test", "valor")');
    console.log('  localStorage.getItem("test")\n');
    
    console.log('  // Inicializar sincronización');
    console.log('  StorageModule.initializeListener()\n');
    
    console.log('  // Transmitir actualización');
    console.log('  StorageModule.broadcastUpdate("status_update", 123, "estado", "nuevo", "anterior")\n');
    
    // ==================== EXPORTAR DATOS ====================
    window.__STORAGE_VERIFICATION__ = {
        timestamp: Date.now(),
        checks: checks,
        proxyState: window.StorageProxyState ? window.StorageProxyState.getStatus() : null,
        handlerState: window.UniversalMessageHandler ? window.UniversalMessageHandler.getState() : null,
        moduleState: window.StorageModule ? window.StorageModule.getState() : null
    };
    
    console.log('💾 Datos de verificación guardados en: window.__STORAGE_VERIFICATION__\n');
    
})();
