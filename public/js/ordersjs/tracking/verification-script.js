'use strict';

// Script de verificación para asegurar que todos los módulos carguen correctamente
(function() {
    console.log(' Iniciando verificación de módulos de tracking...');

    // Lista de módulos esperados
    const expectedModules = [
        'DateUtils',
        'TrackingModalManager', 
        'TrackingDaysSelector',
        'TrackingDataLoader',
        'TrackingUIComponents',
        'ProcessManager',
        'PrendasRenderer',
        'AreaCards',
        'TrackingMain'
    ];

    // Lista de funciones globales esperadas
    const expectedGlobalFunctions = [
        'formatDate',
        'formatDateTime',
        'toDateObject',
        'normalizeConsecutivos',
        'formatDurationHuman',
        'calcularDiasHabiles',
        'calcularDiasHabilesSync',
        'precargarFestivos',
        'obtenerFestivos',
        'openOrderTracking',
        'mostrarTrackingModal',
        'loadOrderBasicData',
        'loadPrendasWithTracking',
        'saveDiaEntregaSelection',
        'actualizarAreaEnTablaRecibos',
        'openAddProcesoModal',
        'closeAddProcesoModal',
        'closeTrackingModal',
        'showConfirmDeleteModal',
        'closeConfirmDeleteModal',
        'handleCrearProcesoDesdeArea',
        'handleEliminarProceso',
        'executeDeleteProcess',
        'handleEditarProceso',
        'handleActualizarProceso',
        'limpiarFormularioProceso',
        'resetFormButton',
        'handleAgregarProceso',
        'updateOrderInfo',
        'updateEstimatedDeliveryDate',
        'showPrendasSelector',
        'cerrarSelectorPrendas',
        'showPrendasView',
        'iniciarTimerContadores',
        'detenerTimerContadores',
        'actualizarContadoresDinamicos',
        'showError',
        'showSuccess',
        'renderPrendas',
        'showPrendaTrackingFromTable',
        'showPrendaTracking',
        'renderPrendaTrackingTimeline',
        'createAreaCard',
        'renderSeguimientosBadges',
        'renderAreasBadges',
        'createPrendaCard'
    ];

    // Verificar módulos
    function verifyModules() {
        const moduleResults = {};
        let allModulesLoaded = true;

        expectedModules.forEach(moduleName => {
            const isLoaded = typeof window[moduleName] !== 'undefined';
            const hasInstance = window[moduleName.toLowerCase()] !== undefined;
            
            moduleResults[moduleName] = {
                loaded: isLoaded,
                hasInstance: hasInstance,
                status: isLoaded ? (hasInstance ? ' Completo' : ' Sin instancia') : ' No cargado'
            };
            
            if (!isLoaded) {
                allModulesLoaded = false;
            }
        });

        console.group(' Estado de Módulos');
        Object.entries(moduleResults).forEach(([name, result]) => {
            console.log(`${result.status} ${name}: ${result.loaded ? 'Cargado' : 'No cargado'}${result.hasInstance ? ' (con instancia)' : ''}`);
        });
        console.groupEnd();

        return allModulesLoaded;
    }

    // Verificar funciones globales
    function verifyGlobalFunctions() {
        const functionResults = {};
        let allFunctionsAvailable = true;

        expectedGlobalFunctions.forEach(functionName => {
            const isAvailable = typeof window[functionName] === 'function';
            
            functionResults[functionName] = {
                available: isAvailable,
                status: isAvailable ? '' : ''
            };
            
            if (!isAvailable) {
                allFunctionsAvailable = false;
            }
        });

        console.group(' Funciones Globales');
        Object.entries(functionResults).forEach(([name, result]) => {
            console.log(`${result.status} ${name}: ${result.available ? 'Disponible' : 'No disponible'}`);
        });
        console.groupEnd();

        return allFunctionsAvailable;
    }

    // Verificar dependencias entre módulos
    function verifyDependencies() {
        console.group(' Verificando Dependencias');
        
        const dependencies = [
            {
                name: 'DateUtils → formatDate',
                check: () => typeof window.formatDate === 'function' && typeof window.DateUtils !== 'undefined'
            },
            {
                name: 'ModalManager → openAddProcesoModal', 
                check: () => typeof window.openAddProcesoModal === 'function' && typeof window.TrackingModalManager !== 'undefined'
            },
            {
                name: 'ProcessManager → handleAgregarProceso',
                check: () => typeof window.handleAgregarProceso === 'function' && typeof window.ProcessManager !== 'undefined'
            },
            {
                name: 'PrendasRenderer → renderPrendas',
                check: () => typeof window.renderPrendas === 'function' && typeof window.PrendasRenderer !== 'undefined'
            },
            {
                name: 'AreaCards → createAreaCard',
                check: () => typeof window.createAreaCard === 'function' && typeof window.AreaCards !== 'undefined'
            }
        ];

        let allDependenciesOK = true;
        dependencies.forEach(dep => {
            const ok = dep.check();
            console.log(`${ok ? '' : ''} ${dep.name}`);
            if (!ok) allDependenciesOK = false;
        });

        console.groupEnd();
        return allDependenciesOK;
    }

    // Función de prueba rápida
    function quickTest() {
        console.group(' Pruebas Rápidas');
        
        try {
            // Probar DateUtils
            const testDate = new Date();
            const formatted = window.formatDate ? formatDate(testDate) : 'ERROR';
            console.log('📅 formatDate test:', formatted);

            // Probar cálculo de días hábiles
            const workingDays = window.calcularDiasHabilesSync ? 
                calcularDiasHabilesSync(new Date(2024, 0, 1), new Date(2024, 0, 10)) : 'ERROR';
            console.log(' calcularDiasHabilesSync test:', workingDays, 'días');

            // Probar duración humana
            const duration = window.formatDurationHuman ? 
                formatDurationHuman(86400000) : 'ERROR';
            console.log(' formatDurationHuman test:', duration);

            console.log(' Pruebas rápidas completadas');
        } catch (error) {
            console.error(' Error en pruebas rápidas:', error);
        }
        
        console.groupEnd();
    }

    // Ejecutar verificación completa
    function runFullVerification() {
        console.log(' Ejecutando verificación completa del sistema de tracking...');
        
        const modulesOK = verifyModules();
        const functionsOK = verifyGlobalFunctions();
        const dependenciesOK = verifyDependencies();
        
        quickTest();

        const allOK = modulesOK && functionsOK && dependenciesOK;
        
        console.log('\n' + '='.repeat(50));
        console.log(allOK ? 
            '🎉  VERIFICACIÓN COMPLETADA CON ÉXITO' : 
            '  HAY PROBLEMAS EN LA VERIFICACIÓN'
        );
        console.log('='.repeat(50));
        
        if (allOK) {
            console.log(' Todos los módulos cargaron correctamente');
            console.log(' Todas las funciones globales están disponibles');
            console.log(' Las dependencias están funcionando');
            console.log(' El sistema está listo para usarse');
        } else {
            console.log(' Se encontraron problemas que deben ser resueltos');
        }

        return allOK;
    }

    // Esperar a que la página esté completamente cargada
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(runFullVerification, 500);
        });
    } else {
        setTimeout(runFullVerification, 500);
    }

    // Hacer la función disponible globalmente para ejecución manual
    window.verifyTrackingSystem = runFullVerification;
    window.quickTrackingTest = quickTest;

})();
