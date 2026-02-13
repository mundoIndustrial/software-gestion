/**
 * 🆕 PrendaEditor Global Initializer
 * 
 * Asegura que:
 * 1. PrendaEditor esté disponible globalmente (cargado de prenda-editor.js)
 * 2. No haya referencias a servicios legacy
 * 3. La nueva arquitectura esté lista
 */

// Esperar a que PrendaEditor esté disponible
function waitForPrendaEditor(maxAttempts = 50) {
    return new Promise((resolve) => {
        let attempts = 0;
        const check = setInterval(() => {
            attempts++;
            if (typeof PrendaEditor !== 'undefined' || attempts >= maxAttempts) {
                clearInterval(check);
                resolve(attempts < maxAttempts);
            }
        }, 20);
    });
}

(async function initializePrendaEditorNew() {
    console.log('🔄 [PrendaEditor Init] Inicializando sistema de edición de prendas (SIN LEGACY)...');

    // 0. Esperar a que PrendaEditor esté disponible
    const ready = await waitForPrendaEditor();
    if (!ready) {
        console.error('❌ [PrendaEditor Init] Timeout: PrendaEditor NO está definido después de esperar');
        return;
    }

    // 1. Verificar que PrendaEditor esté disponible
    if (typeof PrendaEditor === 'undefined') {
        console.error('❌ [PrendaEditor Init] PrendaEditor NO está definido');
        return;
    }

    console.log('✅ [PrendaEditor Init] PrendaEditor cargado correctamente');

    // 2. Crear instancia global para uso generalizado
    window.prendaEditorGlobal = new PrendaEditor({
        notificationService: window.notificationService || null
    });

    console.log('✅ [PrendaEditor Init] Instancia global creada: window.prendaEditorGlobal');

    // 3. Verificar que los servicios compartidos estén cargados
    if (window.PrendasEditorHelper) {
        console.log('✅ [PrendaEditor Init] Servicios compartidos nuevos detectados');
    } else {
        console.warn('⚠️ [PrendaEditor Init] Servicios compartidos NO encontrados (OK si se cargan después)');
    }

    // 4. Verificar que NO haya legacy
    if (window.prendaEditorLegacy) {
        console.warn('⚠️ [PrendaEditor Init] LEGACY DETECTADO - Debería ser eliminado');
    } else {
        console.log('✅ [PrendaEditor Init] Sin dependencias legacy');
    }

    // 5. Configurar estado
    window.prendaEditorNovoListo = true;

    console.log('🎉 [PrendaEditor Init] Sistema de edición de prendas LISTO');
})();
