/**
 * TEST FASE 2 - Modularización de Gestores
 * 
 * Verifica que todos los gestores de FASE 2 se carguen correctamente
 * y que sus métodos principales funcionen
 */

function testFase2() {
    console.clear();
    console.log('%c=== TEST FASE 2: MODULARIZACIÓN DE GESTORES ===', 'color: #0066cc; font-size: 18px; font-weight: bold;');
    
    let testsPassed = 0;
    let testsFailed = 0;
    
    // =====================================================================
    // TEST 1: Verificar que GestorCotizacion existe
    // =====================================================================
    try {
        if (typeof GestorCotizacion === 'undefined') {
            throw new Error('GestorCotizacion no está definido');
        }
        console.log('%c✓ TEST 1 PASADO', 'color: green; font-weight: bold;', 'GestorCotizacion clase existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 1 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 2: Crear instancia de GestorCotizacion
    // =====================================================================
    try {
        const gestorCotizacion = new GestorCotizacion(
            [{id: 1, numero: 'COT001', cliente: 'Cliente A'}],
            '#search-cotizacion',
            '#dropdown-cotizacion',
            '#seleccionada-cotizacion',
            () => {}
        );
        console.log('%c✓ TEST 2 PASADO', 'color: green; font-weight: bold;', 'GestorCotizacion instancia creada');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 2 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 3: Verificar métodos de GestorCotizacion
    // =====================================================================
    try {
        const gestorCotizacion = new GestorCotizacion(
            [{id: 1, numero: 'COT001', cliente: 'Cliente A'}],
            '#search-cotizacion',
            '#dropdown-cotizacion',
            '#seleccionada-cotizacion',
            () => {}
        );
        
        const metodos = ['mostrarOpciones', 'filtrar', 'obtenerSeleccionada', 'obtenerTodas', 'limpiar'];
        const metodosExisten = metodos.every(m => typeof gestorCotizacion[m] === 'function');
        
        if (!metodosExisten) {
            throw new Error('Faltan métodos en GestorCotizacion');
        }
        
        console.log('%c✓ TEST 3 PASADO', 'color: green; font-weight: bold;', 'Todos los métodos existen');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 3 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 4: Verificar que GestorPrendas existe
    // =====================================================================
    try {
        if (typeof GestorPrendas === 'undefined') {
            throw new Error('GestorPrendas no está definido');
        }
        console.log('%c✓ TEST 4 PASADO', 'color: green; font-weight: bold;', 'GestorPrendas clase existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 4 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 5: Crear instancia de GestorPrendas
    // =====================================================================
    try {
        const gestorPrendas = new GestorPrendas([
            { id: 1, nombre_producto: 'Camisa', cantidad: 10 }
        ], 'prendas-container');
        console.log('%c✓ TEST 5 PASADO', 'color: green; font-weight: bold;', 'GestorPrendas instancia creada');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 5 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 6: Verificar métodos de GestorPrendas
    // =====================================================================
    try {
        const gestorPrendas = new GestorPrendas([
            { id: 1, nombre_producto: 'Camisa', cantidad: 10 }
        ], 'prendas-container');
        
        const metodos = [
            'obtenerTodas', 'obtenerActivas', 'agregar', 'eliminar', 
            'agregarFotos', 'agregarTalla', 'validar', 'obtenerDatosFormato'
        ];
        const metodosExisten = metodos.every(m => typeof gestorPrendas[m] === 'function');
        
        if (!metodosExisten) {
            throw new Error('Faltan métodos en GestorPrendas');
        }
        
        console.log('%c✓ TEST 6 PASADO', 'color: green; font-weight: bold;', 'Todos los métodos existen');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 6 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 7: Verificar que GestorLogo existe
    // =====================================================================
    try {
        if (typeof GestorLogo === 'undefined') {
            throw new Error('GestorLogo no está definido');
        }
        console.log('%c✓ TEST 7 PASADO', 'color: green; font-weight: bold;', 'GestorLogo clase existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 7 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 8: Crear instancia de GestorLogo
    // =====================================================================
    try {
        const gestorLogo = new GestorLogo({
            descripcion: 'Logo principal',
            tecnicas: ['BORDADO'],
            ubicaciones: []
        });
        console.log('%c✓ TEST 8 PASADO', 'color: green; font-weight: bold;', 'GestorLogo instancia creada');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 8 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 9: Verificar métodos de GestorLogo
    // =====================================================================
    try {
        const gestorLogo = new GestorLogo({});
        
        const metodos = [
            'obtenerDescripcion', 'establecerDescripcion', 'agregarTecnica',
            'eliminarTecnica', 'obtenerTecnicas', 'agregarUbicacion',
            'obtenerUbicaciones', 'agregarFoto', 'eliminarFoto',
            'obtenerFotos', 'validar', 'obtenerDatosFormato'
        ];
        const metodosExisten = metodos.every(m => typeof gestorLogo[m] === 'function');
        
        if (!metodosExisten) {
            throw new Error('Faltan métodos en GestorLogo');
        }
        
        console.log('%c✓ TEST 9 PASADO', 'color: green; font-weight: bold;', 'Todos los métodos existen');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 9 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 10: Probar agregar técnica en GestorLogo
    // =====================================================================
    try {
        const gestorLogo = new GestorLogo({});
        
        const resultado = gestorLogo.agregarTecnica('BORDADO');
        const tecnicas = gestorLogo.obtenerTecnicas();
        
        if (!resultado || tecnicas.length !== 1 || tecnicas[0] !== 'BORDADO') {
            throw new Error('No se agregó la técnica correctamente');
        }
        
        console.log('%c✓ TEST 10 PASADO', 'color: green; font-weight: bold;', 'Técnica agregada correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 10 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 11: Probar agregar y obtener fotos
    // =====================================================================
    try {
        const gestorLogo = new GestorLogo({});
        
        gestorLogo.agregarFoto({
            preview: 'data:image/png;base64,fake',
            url: 'data:image/png;base64,fake'
        });
        
        const fotos = gestorLogo.obtenerFotos();
        
        if (fotos.length !== 1) {
            throw new Error('No se agregó la foto correctamente');
        }
        
        console.log('%c✓ TEST 11 PASADO', 'color: green; font-weight: bold;', 'Foto agregada correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 11 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 12: Probar validación de GestorLogo
    // =====================================================================
    try {
        const gestorLogo = new GestorLogo({});
        
        const validacion = gestorLogo.validar();
        
        if (validacion.valido !== false || validacion.errores.length === 0) {
            throw new Error('Validación no funciona correctamente');
        }
        
        console.log('%c✓ TEST 12 PASADO', 'color: green; font-weight: bold;', 'Validación funciona: ' + validacion.errores.length + ' errores encontrados');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 12 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 13: Probar que GestorPrendas maneja prendas eliminadas
    // =====================================================================
    try {
        const gestorPrendas = new GestorPrendas([
            { id: 1, nombre_producto: 'Camisa', cantidad: 10 },
            { id: 2, nombre_producto: 'Pantalón', cantidad: 5 }
        ], 'prendas-container');
        
        gestorPrendas.eliminar(0);
        const activas = gestorPrendas.obtenerActivas();
        
        if (activas.length !== 1) {
            throw new Error('No se eliminó la prenda correctamente');
        }
        
        console.log('%c✓ TEST 13 PASADO', 'color: green; font-weight: bold;', 'Prenda eliminada y recuperada correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 13 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 14: Verificar CargadorCotizacion
    // =====================================================================
    try {
        if (typeof CargadorCotizacion === 'undefined') {
            throw new Error('CargadorCotizacion no está definido');
        }
        
        const cargador = new CargadorCotizacion('/api/cotizaciones');
        
        if (typeof cargador.cargar !== 'function') {
            throw new Error('CargadorCotizacion no tiene método cargar');
        }
        
        console.log('%c✓ TEST 14 PASADO', 'color: green; font-weight: bold;', 'CargadorCotizacion existe y funciona');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 14 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // RESUMEN FINAL
    // =====================================================================
    console.log('%c\n╔════════════════════════════════════════════╗', 'color: #0066cc; font-size: 14px;');
    console.log('%c║   RESUMEN DE TESTS FASE 2                  ║', 'color: #0066cc; font-size: 14px;');
    console.log('%c╚════════════════════════════════════════════╝', 'color: #0066cc; font-size: 14px;');
    
    const total = testsPassed + testsFailed;
    const porcentaje = Math.round((testsPassed / total) * 100);
    
    console.log(`%c✓ PASADOS: ${testsPassed}/${total}`, 'color: green; font-weight: bold; font-size: 14px;');
    console.log(`%c✗ FALLIDOS: ${testsFailed}/${total}`, testsFailed > 0 ? 'color: red; font-weight: bold; font-size: 14px;' : 'color: green; font-weight: bold; font-size: 14px;');
    console.log(`%c📊 ÉXITO: ${porcentaje}%`, porcentaje === 100 ? 'color: green; font-weight: bold; font-size: 14px;' : 'color: orange; font-weight: bold; font-size: 14px;');
    
    if (testsFailed === 0) {
        console.log('%c\n🎉 ¡TODOS LOS TESTS DE FASE 2 PASARON! 🎉', 'color: green; font-weight: bold; font-size: 16px;');
    } else {
        console.log('%c\n⚠️ ALGUNOS TESTS FALLARON - REVISAR CONSOLA ⚠️', 'color: red; font-weight: bold; font-size: 16px;');
    }
    
    console.log('%c\nCarga la página y ejecuta testFase2() en la consola para verificar', 'color: #666; font-style: italic;');
    
    return {
        total,
        passed: testsPassed,
        failed: testsFailed,
        success: testsFailed === 0
    };
}

// Ejecutar automáticamente al cargar
document.addEventListener('DOMContentLoaded', () => {
    console.log('%cℹ️ Tests FASE 2 cargados. Ejecuta testFase2() para correr', 'color: #0066cc; font-style: italic;');
});
