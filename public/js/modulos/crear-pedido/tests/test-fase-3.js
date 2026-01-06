/**
 * TEST FASE 3 - Validación y Envío de Datos
 * 
 * Verifica que las funciones de validación y envío funcionen correctamente
 */

function testFase3() {
    console.clear();
    console.log('%c=== TEST FASE 3: VALIDACIÓN Y ENVÍO ===', 'color: #16a34a; font-size: 18px; font-weight: bold;');
    
    let testsPassed = 0;
    let testsFailed = 0;
    
    // =====================================================================
    // TEST 1: Verificar que funciones de validación existen
    // =====================================================================
    try {
        if (typeof window.validarFormularioConGestores !== 'function') {
            throw new Error('validarFormularioConGestores no existe');
        }
        console.log('%c✓ TEST 1 PASADO', 'color: green; font-weight: bold;', 'validarFormularioConGestores existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 1 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 2: Verificar que funciones de preparación existen
    // =====================================================================
    try {
        if (typeof window.prepararDatosParaEnvio !== 'function') {
            throw new Error('prepararDatosParaEnvio no existe');
        }
        console.log('%c✓ TEST 2 PASADO', 'color: green; font-weight: bold;', 'prepararDatosParaEnvio existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 2 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 3: Verificar que función de envío existe
    // =====================================================================
    try {
        if (typeof window.enviarDatosAlServidor !== 'function') {
            throw new Error('enviarDatosAlServidor no existe');
        }
        console.log('%c✓ TEST 3 PASADO', 'color: green; font-weight: bold;', 'enviarDatosAlServidor existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 3 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 4: Verificar que función de procesar submit existe
    // =====================================================================
    try {
        if (typeof window.procesarSubmitFormulario !== 'function') {
            throw new Error('procesarSubmitFormulario no existe');
        }
        console.log('%c✓ TEST 4 PASADO', 'color: green; font-weight: bold;', 'procesarSubmitFormulario existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 4 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 5: Verificar que función de mostrar errores existe
    // =====================================================================
    try {
        if (typeof window.mostrarErroresValidacion !== 'function') {
            throw new Error('mostrarErroresValidacion no existe');
        }
        console.log('%c✓ TEST 5 PASADO', 'color: green; font-weight: bold;', 'mostrarErroresValidacion existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 5 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 6: Verificar que función de resumen existe
    // =====================================================================
    try {
        if (typeof window.obtenerResumenPedido !== 'function') {
            throw new Error('obtenerResumenPedido no existe');
        }
        console.log('%c✓ TEST 6 PASADO', 'color: green; font-weight: bold;', 'obtenerResumenPedido existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 6 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 7: Validar que retorna objeto correcto sin datos
    // =====================================================================
    try {
        const resultado = window.validarFormularioConGestores();
        
        if (!resultado.hasOwnProperty('valido') || !Array.isArray(resultado.errores)) {
            throw new Error('Estructura de respuesta incorrecta');
        }
        
        if (resultado.valido !== false) {
            throw new Error('Debería ser inválido sin datos');
        }
        
        if (resultado.errores.length === 0) {
            throw new Error('Debería haber errores');
        }
        
        console.log('%c✓ TEST 7 PASADO', 'color: green; font-weight: bold;', `Validación correcta: ${resultado.errores.length} errores`);
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 7 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 8: Preparar datos retorna objeto correcto
    // =====================================================================
    try {
        const datos = window.prepararDatosParaEnvio();
        
        const camposRequeridos = ['cliente', 'asesora', 'forma_de_pago', 'prendas', 'fotos_nuevas', 'logo'];
        const tieneTodasLasCampos = camposRequeridos.every(campo => datos.hasOwnProperty(campo));
        
        if (!tieneTodasLasCampos) {
            throw new Error('Faltan campos en datos preparados');
        }
        
        if (!Array.isArray(datos.prendas)) {
            throw new Error('prendas debe ser un array');
        }
        
        console.log('%c✓ TEST 8 PASADO', 'color: green; font-weight: bold;', 'Datos preparados correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 8 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 9: Obtener resumen retorna estructura correcta
    // =====================================================================
    try {
        const resumen = window.obtenerResumenPedido();
        
        const camposRequeridos = ['cliente', 'cantidad_prendas', 'cantidad_total_prendas', 'tiene_logo', 'tiene_fotos'];
        const tieneTodasLasCampos = camposRequeridos.every(campo => resumen.hasOwnProperty(campo));
        
        if (!tieneTodasLasCampos) {
            throw new Error('Faltan campos en resumen');
        }
        
        console.log('%c✓ TEST 9 PASADO', 'color: green; font-weight: bold;', 'Resumen generado correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 9 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 10: enviarDatosAlServidor retorna Promise
    // =====================================================================
    try {
        const promise = window.enviarDatosAlServidor({}, '/test-endpoint');
        
        if (!(promise instanceof Promise)) {
            throw new Error('enviarDatosAlServidor debe retornar una Promise');
        }
        
        console.log('%c✓ TEST 10 PASADO', 'color: green; font-weight: bold;', 'enviarDatosAlServidor retorna Promise');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 10 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 11: procesarSubmitFormulario retorna Promise
    // =====================================================================
    try {
        const promise = window.procesarSubmitFormulario('/test-endpoint');
        
        if (!(promise instanceof Promise)) {
            throw new Error('procesarSubmitFormulario debe retornar una Promise');
        }
        
        console.log('%c✓ TEST 11 PASADO', 'color: green; font-weight: bold;', 'procesarSubmitFormulario retorna Promise');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 11 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 12: mostrarErroresValidacion es función
    // =====================================================================
    try {
        // Solo verificar que es función
        if (typeof window.mostrarErroresValidacion !== 'function') {
            throw new Error('mostrarErroresValidacion no es función');
        }
        
        console.log('%c✓ TEST 12 PASADO', 'color: green; font-weight: bold;', 'mostrarErroresValidacion es función');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 12 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // RESUMEN FINAL
    // =====================================================================
    console.log('%c\n╔════════════════════════════════════════════╗', 'color: #16a34a; font-size: 14px;');
    console.log('%c║   RESUMEN DE TESTS FASE 3                  ║', 'color: #16a34a; font-size: 14px;');
    console.log('%c╚════════════════════════════════════════════╝', 'color: #16a34a; font-size: 14px;');
    
    const total = testsPassed + testsFailed;
    const porcentaje = Math.round((testsPassed / total) * 100);
    
    console.log(`%c✓ PASADOS: ${testsPassed}/${total}`, 'color: green; font-weight: bold; font-size: 14px;');
    console.log(`%c✗ FALLIDOS: ${testsFailed}/${total}`, testsFailed > 0 ? 'color: red; font-weight: bold; font-size: 14px;' : 'color: green; font-weight: bold; font-size: 14px;');
    console.log(`%c📊 ÉXITO: ${porcentaje}%`, porcentaje === 100 ? 'color: green; font-weight: bold; font-size: 14px;' : 'color: orange; font-weight: bold; font-size: 14px;');
    
    if (testsFailed === 0) {
        console.log('%c\n🎉 ¡TODOS LOS TESTS DE FASE 3 PASARON! 🎉', 'color: green; font-weight: bold; font-size: 16px;');
    } else {
        console.log('%c\n⚠️ ALGUNOS TESTS FALLARON - REVISAR CONSOLA ⚠️', 'color: red; font-weight: bold; font-size: 16px;');
    }
    
    console.log('%c\nCarga la página y ejecuta testFase3() en la consola para verificar', 'color: #666; font-style: italic;');
    
    return {
        total,
        passed: testsPassed,
        failed: testsFailed,
        success: testsFailed === 0
    };
}

// Ejecutar automáticamente al cargar
document.addEventListener('DOMContentLoaded', () => {
    console.log('%cℹ️ Tests FASE 3 cargados. Ejecuta testFase3() para correr', 'color: #16a34a; font-style: italic;');
});
