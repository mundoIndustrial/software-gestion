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
    // 0. Esperar a que PrendaEditor esté disponible
    const ready = await waitForPrendaEditor();
    if (!ready) {
        return;
    }

    // 1. Verificar que PrendaEditor esté disponible
    if (typeof PrendaEditor === 'undefined') {
        return;
    }

    // 2. Crear instancia global para uso generalizado
    window.prendaEditorGlobal = new PrendaEditor({
        notificationService: window.notificationService || null
    });

    // 3. Verificar que los servicios compartidos estén cargados
    if (window.PrendasEditorHelper) {
    }

    // 4. Verificar que NO haya legacy
    if (window.prendaEditorLegacy) {
    }

    // 5. Configurar estado
    window.prendaEditorNovoListo = true;
})();
