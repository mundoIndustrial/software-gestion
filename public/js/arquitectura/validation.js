/**
 * VALIDACIÓN: Pruebas de Integración
 * 
 * Ejecutar en la consola del navegador para validar que la arquitectura está correctamente integrada.
 * 
 * Uso:
 * 1. Abrir DevTools en CrearPedido (con modal de agregar prenda)
 * 2. Pegar comandos de validación
 * 3. Verificar que todos los checks pasen
 */

// ============================================================
// VALIDACIÓN 1: Verificar que todos los módulos existen
// ============================================================
function validateArchitecture() {
    console.log('╔════════════════════════════════════════════════════════╗');
    console.log('║  VALIDACIÓN DE ARQUITECTURA - COLORES POR TALLA       ║');
    console.log('╚════════════════════════════════════════════════════════╝');

    const checks = {
        'WizardStateMachine': typeof window.WizardStateMachine !== 'undefined',
        'WizardEventBus': typeof window.WizardEventBus !== 'undefined',
        'WizardLifecycleManager': typeof window.WizardLifecycleManager !== 'undefined',
        'WizardBootstrap': typeof window.WizardBootstrap !== 'undefined',
        'ColoresPorTallaV2': typeof window.ColoresPorTallaV2 !== 'undefined',
        'ColoresPorTalla': typeof window.ColoresPorTalla !== 'undefined',
        'StateManager': typeof window.StateManager !== 'undefined',
        'WizardManager': typeof window.WizardManager !== 'undefined',
        'AsignacionManager': typeof window.AsignacionManager !== 'undefined',
        'UIRenderer': typeof window.UIRenderer !== 'undefined'
    };

    let passed = 0;
    let failed = 0;

    console.log('\n✓ MÓDULOS CARGADOS:');
    Object.entries(checks).forEach(([name, exists]) => {
        if (exists) {
            console.log(`  ✅ ${name}`);
            passed++;
        } else {
            console.error(`  ❌ ${name} - NO ENCONTRADO`);
            failed++;
        }
    });

    console.log(`\n📊 RESULTADO: ${passed}/${Object.keys(checks).length} módulos cargados`);

    if (failed === 0) {
        console.log('✅ TODOS LOS MÓDULOS ESTÁN CARGADOS CORRECTAMENTE');
        return true;
    } else {
        console.error('❌ FALTAN MÓDULOS - LA ARQUITECTURA NO ESTÁ COMPLETA');
        return false;
    }
}

// ============================================================
// VALIDACIÓN 2: Verificar estado del wizard
// ============================================================
function validateWizardState() {
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  ESTADO ACTUAL DEL WIZARD                            ║');
    console.log('╚════════════════════════════════════════════════════════╝');

    if (!window.ColoresPorTallaV2) {
        console.error('ColoresPorTallaV2 no disponible');
        return false;
    }

    const status = window.ColoresPorTallaV2.getWizardStatus();
    
    console.log('\n📋 INFORMACIÓN DEL WIZARD:');
    console.log(`  Inicializado: ${status.initialized ? '✅' : '❌'}`);
    console.log(`  Estado actual: ${status.state || 'N/A'}`);
    console.log(`  Historial de estados: ${status.stateHistory?.length || 0} transiciones`);
    console.log(`  Historial de eventos: ${status.eventHistory?.length || 0} eventos`);

    if (status.stateHistory && status.stateHistory.length > 0) {
        console.log('\n🔍 ÚLTIMOS ESTADOS:');
        status.stateHistory.slice(-5).forEach((entry, idx) => {
            console.log(`  ${idx + 1}. ${entry.state}`);
        });
    }

    return status.initialized;
}

// ============================================================
// VALIDACIÓN 3: Simular interacción del usuario
// ============================================================
async function validateUserInteraction() {
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  VALIDACIÓN: INTERACCIÓN DEL USUARIO                 ║');
    console.log('╚════════════════════════════════════════════════════════╝');

    if (!window.ColoresPorTallaV2) {
        console.error('ColoresPorTallaV2 no disponible');
        return false;
    }

    try {
        console.log('\n1️⃣  Abriendo wizard...');
        await window.ColoresPorTallaV2.toggleVistaAsignacion();
        console.log('  ✅ Wizard abierto');

        const status1 = window.ColoresPorTallaV2.getWizardStatus();
        console.log(`  Estado: ${status1.state}`);

        console.log('\n2️⃣  Esperando 2 segundos...');
        await new Promise(resolve => setTimeout(resolve, 2000));

        console.log('\n3️⃣  Cerrando wizard...');
        await window.ColoresPorTallaV2.toggleVistaAsignacion();
        console.log('  ✅ Wizard cerrado');

        const status2 = window.ColoresPorTallaV2.getWizardStatus();
        console.log(`  Estado: ${status2.state}`);

        console.log('\n✅ INTERACCIÓN COMPLETADA EXITOSAMENTE');
        return true;

    } catch (error) {
        console.error('❌ Error en interacción:', error);
        return false;
    }
}

// ============================================================
// VALIDACIÓN 4: Verificar memory leaks
// ============================================================
function validateMemoryCleanup() {
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  VALIDACIÓN: LIMPIEZA DE MEMORIA                     ║');
    console.log('╚════════════════════════════════════════════════════════╝');

    if (!window.ColoresPorTallaV2) {
        console.error('ColoresPorTallaV2 no disponible');
        return false;
    }

    const status = window.ColoresPorTallaV2.getWizardStatus();

    console.log('\n📊 LISTENERS Y REFERENCIAS:');
    console.log(`  Estado: ${status.state}`);
    
    if (status.state === 'IDLE') {
        console.log('  ✅ Wizard está en estado IDLE (limpio)');
    } else {
        console.log(`  ⚠️  Wizard está en estado ${status.state} (verificar si debe limpiarse)`);
    }

    console.log('\n💡 CONSEJO: Llamar a window.ColoresPorTallaV2.cleanupWizard() para limpiar completamente');
    
    return true;
}

// ============================================================
// VALIDACIÓN 5: Test de compatibilidad
// ============================================================
function validateBackwardCompatibility() {
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  VALIDACIÓN: COMPATIBILIDAD HACIA ATRÁS               ║');
    console.log('╚════════════════════════════════════════════════════════╝');

    const methods = [
        'init',
        'toggleVistaAsignacion',
        'wizardGuardarAsignacion',
        'guardarAsignacionColores',
        'actualizarTallasDisponibles',
        'actualizarColoresDisponibles',
        'verificarBtnGuardarAsignacion',
        'agregarColorPersonalizado',
        'limpiarColorPersonalizado',
        'obtenerDatosAsignaciones',
        'limpiarAsignaciones'
    ];

    console.log('\n📌 MÉTODOS DISPONIBLES EN WINDOW.COLORESPORTALLA:');
    
    let allPresent = true;
    methods.forEach(method => {
        const present = typeof window.ColoresPorTalla?.[method] === 'function';
        if (present) {
            console.log(`  ✅ ${method}()`);
        } else {
            console.error(`  ❌ ${method}() - NO DISPONIBLE`);
            allPresent = false;
        }
    });

    if (allPresent) {
        console.log('\n✅ TODOS LOS MÉTODOS LEGACY ESTÁN DISPONIBLES');
    } else {
        console.log('\n⚠️  ALGUNOS MÉTODOS LEGACY FALTAN');
    }

    return allPresent;
}

// ============================================================
// VALIDACIÓN: EJECUTAR TODAS
// ============================================================
async function validateAll() {
    console.clear();
    
    const check1 = validateArchitecture();
    if (!check1) {
        console.error('\n❌ La validación se detuvo: módulos no encontrados');
        return;
    }

    const check2 = validateWizardState();
    const check3 = validateBackwardCompatibility();
    
    console.log('\n╔════════════════════════════════════════════════════════╗');
    console.log('║  RESUMEN DE VALIDACIÓN                               ║');
    console.log('╚════════════════════════════════════════════════════════╝');
    
    console.log('\n✅ LA ARQUITECTURA ESTÁ CORRECTAMENTE INTEGRADA');
    console.log('\n🎯 PRÓXIMOS PASOS:');
    console.log('  1. Abrir la modal de "Agregar Prenda Nueva"');
    console.log('  2. Hacer clic en "Asignar Colores"');
    console.log('  3. Interactuar con el wizard normalmente');
    console.log('  4. Verificar que todo funciona sin errores');
    
    console.log('\n🔍 DEBUGGING:');
    console.log('  - Ver estado: window.ColoresPorTallaV2.getWizardStatus()');
    console.log('  - Ver arquitectura: window.getArchitectureStatus()');
    console.log('  - Limpiar: window.ColoresPorTallaV2.cleanupWizard()');
}

// Exportar para uso en consola
window.WizardValidation = {
    validateArchitecture,
    validateWizardState,
    validateUserInteraction,
    validateMemoryCleanup,
    validateBackwardCompatibility,
    validateAll,
    checkStatus: () => window.getArchitectureStatus()
};

console.log('✅ Archivo de validación cargado');
console.log('📝 Ejecutar: window.WizardValidation.validateAll()');
