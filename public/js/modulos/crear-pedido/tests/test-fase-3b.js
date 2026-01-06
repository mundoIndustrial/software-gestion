/**
 * TEST FASE 3b - Gestor de Pedido SIN COTIZACIÓN
 * 
 * Verifica que el gestor de pedido sin cotización funcione correctamente
 */

function testFase3b() {
    console.clear();
    console.log('%c=== TEST FASE 3b: PEDIDO SIN COTIZACIÓN ===', 'color: #f59e0b; font-size: 18px; font-weight: bold;');
    
    let testsPassed = 0;
    let testsFailed = 0;
    
    // =====================================================================
    // TEST 1: Verificar que GestorPedidoSinCotizacion existe
    // =====================================================================
    try {
        if (typeof GestorPedidoSinCotizacion === 'undefined') {
            throw new Error('GestorPedidoSinCotizacion no está definido');
        }
        console.log('%c✓ TEST 1 PASADO', 'color: green; font-weight: bold;', 'GestorPedidoSinCotizacion clase existe');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 1 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 2: Crear instancia de GestorPedidoSinCotizacion
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        console.log('%c✓ TEST 2 PASADO', 'color: green; font-weight: bold;', 'Instancia creada correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 2 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 3: Verificar métodos principales existen
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        const metodos = [
            'activar', 'desactivar', 'agregarPrenda', 'eliminarPrenda',
            'obtenerTodas', 'cantidad', 'establecerCliente', 'obtenerCliente',
            'establecerFormaPago', 'obtenerFormaPago', 'validar',
            'obtenerDatosParaEnvio', 'enviarAlServidor', 'limpiar'
        ];
        
        const metodosExisten = metodos.every(m => typeof gestor[m] === 'function');
        
        if (!metodosExisten) {
            throw new Error('Faltan métodos en GestorPedidoSinCotizacion');
        }
        
        console.log('%c✓ TEST 3 PASADO', 'color: green; font-weight: bold;', `Todos ${metodos.length} métodos existen`);
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 3 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 4: Agregar prendas
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        const index1 = gestor.agregarPrenda();
        const index2 = gestor.agregarPrenda();
        
        const cantidad = gestor.cantidad();
        
        if (cantidad !== 2 || index1 !== 0 || index2 !== 1) {
            throw new Error('No se agregaron prendas correctamente');
        }
        
        console.log('%c✓ TEST 4 PASADO', 'color: green; font-weight: bold;', `${cantidad} prendas agregadas`);
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 4 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 5: Eliminar prenda
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        gestor.agregarPrenda();
        gestor.agregarPrenda();
        gestor.eliminarPrenda(0);
        
        const cantidad = gestor.cantidad();
        
        if (cantidad !== 1) {
            throw new Error('No se eliminó la prenda correctamente');
        }
        
        console.log('%c✓ TEST 5 PASADO', 'color: green; font-weight: bold;', 'Prenda eliminada correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 5 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 6: Establecer y obtener cliente
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        gestor.establecerCliente('Cliente Test');
        const cliente = gestor.obtenerCliente();
        
        if (cliente !== 'Cliente Test') {
            throw new Error('Cliente no se estableció correctamente');
        }
        
        console.log('%c✓ TEST 6 PASADO', 'color: green; font-weight: bold;', 'Cliente establecido: ' + cliente);
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 6 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 7: Establecer y obtener forma de pago
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        gestor.establecerFormaPago('Efectivo');
        const formaPago = gestor.obtenerFormaPago();
        
        if (formaPago !== 'Efectivo') {
            throw new Error('Forma de pago no se estableció correctamente');
        }
        
        console.log('%c✓ TEST 7 PASADO', 'color: green; font-weight: bold;', 'Forma de pago: ' + formaPago);
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 7 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 8: Validar sin datos
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        const validacion = gestor.validar();
        
        if (validacion.valido !== false || validacion.errores.length === 0) {
            throw new Error('Debería detectar errores');
        }
        
        console.log('%c✓ TEST 8 PASADO', 'color: green; font-weight: bold;', `${validacion.errores.length} errores detectados`);
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 8 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 9: Validar con cliente pero sin prendas
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        gestor.establecerCliente('Test Client');
        const validacion = gestor.validar();
        
        if (validacion.valido !== false) {
            throw new Error('Debería fallar sin prendas');
        }
        
        console.log('%c✓ TEST 9 PASADO', 'color: green; font-weight: bold;', 'Validación correcta: requiere prendas');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 9 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 10: Obtener datos para envío
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        gestor.establecerCliente('Cliente Test');
        gestor.establecerFormaPago('Tarjeta');
        
        const datos = gestor.obtenerDatosParaEnvio();
        
        if (!datos.hasOwnProperty('cliente') || 
            !datos.hasOwnProperty('prendas') ||
            !datos.hasOwnProperty('es_sin_cotizacion')) {
            throw new Error('Estructura de datos incorrecta');
        }
        
        if (datos.es_sin_cotizacion !== true) {
            throw new Error('Debe indicar que es sin cotización');
        }
        
        console.log('%c✓ TEST 10 PASADO', 'color: green; font-weight: bold;', 'Datos estructurados correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 10 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 11: Limpiar gestor
    // =====================================================================
    try {
        const gestor = new GestorPedidoSinCotizacion();
        
        gestor.agregarPrenda();
        gestor.establecerCliente('Test');
        
        gestor.limpiar();
        
        if (gestor.cantidad() !== 0 || gestor.obtenerCliente() !== '') {
            throw new Error('Gestor no se limpió correctamente');
        }
        
        console.log('%c✓ TEST 11 PASADO', 'color: green; font-weight: bold;', 'Gestor limpiado correctamente');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 11 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 12: Verificar funciones globales de inicialización
    // =====================================================================
    try {
        if (typeof window.inicializarGestorSinCotizacion !== 'function') {
            throw new Error('inicializarGestorSinCotizacion no existe');
        }
        
        console.log('%c✓ TEST 12 PASADO', 'color: green; font-weight: bold;', 'Funciones de inicialización existen');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 12 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // TEST 13: Verificar instancia global
    // =====================================================================
    try {
        if (typeof window.gestorPedidoSinCotizacion === 'undefined') {
            throw new Error('gestorPedidoSinCotizacion no existe globalmente');
        }
        
        console.log('%c✓ TEST 13 PASADO', 'color: green; font-weight: bold;', 'Instancia global disponible');
        testsPassed++;
    } catch (error) {
        console.error('%c✗ TEST 13 FALLIDO', 'color: red; font-weight: bold;', error.message);
        testsFailed++;
    }

    // =====================================================================
    // RESUMEN FINAL
    // =====================================================================
    console.log('%c\n╔════════════════════════════════════════════╗', 'color: #f59e0b; font-size: 14px;');
    console.log('%c║   RESUMEN DE TESTS FASE 3b                 ║', 'color: #f59e0b; font-size: 14px;');
    console.log('%c╚════════════════════════════════════════════╝', 'color: #f59e0b; font-size: 14px;');
    
    const total = testsPassed + testsFailed;
    const porcentaje = Math.round((testsPassed / total) * 100);
    
    console.log(`%c✓ PASADOS: ${testsPassed}/${total}`, 'color: green; font-weight: bold; font-size: 14px;');
    console.log(`%c✗ FALLIDOS: ${testsFailed}/${total}`, testsFailed > 0 ? 'color: red; font-weight: bold; font-size: 14px;' : 'color: green; font-weight: bold; font-size: 14px;');
    console.log(`%c📊 ÉXITO: ${porcentaje}%`, porcentaje === 100 ? 'color: green; font-weight: bold; font-size: 14px;' : 'color: orange; font-weight: bold; font-size: 14px;');
    
    if (testsFailed === 0) {
        console.log('%c\n🎉 ¡TODOS LOS TESTS DE FASE 3b PASARON! 🎉', 'color: green; font-weight: bold; font-size: 16px;');
    } else {
        console.log('%c\n⚠️ ALGUNOS TESTS FALLARON - REVISAR CONSOLA ⚠️', 'color: red; font-weight: bold; font-size: 16px;');
    }
    
    console.log('%c\nCarga la página y ejecuta testFase3b() en la consola para verificar', 'color: #666; font-style: italic;');
    
    return {
        total,
        passed: testsPassed,
        failed: testsFailed,
        success: testsFailed === 0
    };
}

// Ejecutar automáticamente al cargar
document.addEventListener('DOMContentLoaded', () => {
    console.log('%cℹ️ Tests FASE 3b cargados. Ejecuta testFase3b() para correr', 'color: #f59e0b; font-style: italic;');
});
