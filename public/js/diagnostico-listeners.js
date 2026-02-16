/**
 * SCRIPT DE DIAGNÓSTICO: Verificar estado de listeners
 * Ejecutar en la consola cuando el wizard no responda
 */

window.diagnosticoWizard = {
    check: function() {
        console.log('====== DIAGNÓSTICO DEL WIZARD ======');
        
        // 1. Verificar si los managers existen
        console.log('1️⃣ MANAGERS DISPONIBLES:');
        console.log('   - WizardManager:', !!window.WizardManager);
        console.log('   - StateManager:', !!window.StateManager);
        console.log('   - UIRenderer:', !!window.UIRenderer);
        console.log('   - ColoresPorTalla:', !!window.ColoresPorTalla);
        
        // 2. Verificar estado actual del wizard
        console.log('\n2️⃣ ESTADO ACTUAL:');
        if (window.StateManager) {
            console.log('   - Paso actual:', window.StateManager.getPasoActual());
            console.log('   - Género seleccionado:', window.StateManager.getGeneroSeleccionado());
            console.log('   - Tallas seleccionadas:', window.StateManager.getTallasSeleccionadas());
            console.log('   - Tela actual:', window.StateManager.getTelaSeleccionada());
        }
        
        // 3. Verificar elementos del DOM
        console.log('\n3️⃣ ELEMENTOS DEL DOM:');
        const btnSiguiente = document.getElementById('wzd-btn-siguiente');
        const btnAtras = document.getElementById('wzd-btn-atras');
        const btnGuardar = document.getElementById('btn-guardar-asignacion');
        
        console.log('   - Botón Siguiente (wzd-btn-siguiente):', {
            existe: !!btnSiguiente,
            visible: btnSiguiente ? window.getComputedStyle(btnSiguiente).display !== 'none' : 'N/A',
            disabled: btnSiguiente ? btnSiguiente.disabled : 'N/A',
            listeners: btnSiguiente ? (btnSiguiente._getEventListeners ? btnSiguiente._getEventListeners('click') : 'No detectable') : 'N/A'
        });
        
        console.log('   - Botón Atrás (wzd-btn-atras):', {
            existe: !!btnAtras,
            visible: btnAtras ? window.getComputedStyle(btnAtras).display !== 'none' : 'N/A',
            disabled: btnAtras ? btnAtras.disabled : 'N/A'
        });
        
        console.log('   - Botón Guardar (btn-guardar-asignacion):', {
            existe: !!btnGuardar,
            visible: btnGuardar ? window.getComputedStyle(btnGuardar).display !== 'none' : 'N/A',
            disabled: btnGuardar ? btnGuardar.disabled : 'N/A'
        });
        
        // 4. Verificar Modal
        console.log('\n4️⃣ ESTADO DEL MODAL:');
        const modal = document.getElementById('modal-asignar-colores-por-talla');
        if (modal) {
            const bootstrapModal = window.bootstrap?.Modal?.getInstance(modal);
            console.log('   - Modal existe:', true);
            console.log('   - Modal visible:', window.getComputedStyle(modal).display !== 'none');
            console.log('   - Bootstrap Modal instancia:', !!bootstrapModal);
            if (bootstrapModal) {
                console.log('   - Bootstrap Modal state:', bootstrapModal._isShown);
            }
        } else {
            console.log('   - Modal NO existe');
        }
        
        // 5. Verificar paso actual visibles
        console.log('\n5️⃣ PASOS VISIBLES:');
        for (let i = 0; i <= 3; i++) {
            const paso = document.getElementById(`wizard-paso-${i}`);
            if (paso) {
                const visible = window.getComputedStyle(paso).display !== 'none';
                console.log(`   - Paso ${i}: ${visible ? '✓ VISIBLE' : '✗ OCULTO'}`);
            }
        }
        
        // 6. Verificar estado del flujoInterno
        console.log('\n6️⃣ FLUJO INTERNO (WizardManager):');
        if (window.WizardManager) {
            console.log('   - Accediendo a flujoInterno...');
            // Nota: flujoInterno es privado, intentar acceder
            const wmInstance = window.WizardManager;
            console.log('   - WizardManager es objeto:', typeof wmInstance === 'object');
            console.log('   - Métodos disponibles:', Object.getOwnPropertyNames(wmInstance).filter(m => typeof wmInstance[m] === 'function').slice(0, 10));
        }
        
        console.log('\n====== FIN DEL DIAGNÓSTICO ======');
    },
    
    // Simular click en botón Siguiente
    clickSiguiente: function() {
        console.log('[Diagnóstico] Simulando click en botón Siguiente...');
        const btn = document.getElementById('wzd-btn-siguiente');
        if (btn) {
            btn.click();
            console.log('[Diagnóstico] Click simulado');
        } else {
            console.error('[Diagnóstico] Botón no encontrado');
        }
    },
    
    // Simular click en botón Atrás
    clickAtras: function() {
        console.log('[Diagnóstico] Simulando click en botón Atrás...');
        const btn = document.getElementById('wzd-btn-atras');
        if (btn) {
            btn.click();
            console.log('[Diagnóstico] Click simulado');
        } else {
            console.error('[Diagnóstico] Botón no encontrado');
        }
    },
    
    // Forzar siguiente paso
    forzarSiguiente: function() {
        console.log('[Diagnóstico] Forzando pasoSiguiente()...');
        if (window.WizardManager && typeof window.WizardManager.pasoSiguiente === 'function') {
            const result = window.WizardManager.pasoSiguiente();
            console.log('[Diagnóstico] Resultado:', result);
        } else {
            console.error('[Diagnóstico] WizardManager.pasoSiguiente no disponible');
        }
    }
};

console.log('💡 Se ha cargado diagnosticoWizard. Usar:');
console.log('   - diagnosticoWizard.check() - Ver estado completo');
console.log('   - diagnosticoWizard.clickSiguiente() - Simular click');
console.log('   - diagnosticoWizard.forzarSiguiente() - Forzar avance');
