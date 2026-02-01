/**
 * EJEMPLO DE INTEGRACIÓN - CotizacionPrendaHandler con PrendaEditor
 * 
 * Este archivo muestra cómo integrar la lógica de origen automático
 * en el flujo de carga de prendas desde cotizaciones
 */

// ============================================================================
// EJEMPLO 1: Integración en el modal de agregar prenda
// ============================================================================

/**
 * Cuando se agrega una prenda desde una cotización
 * Llamar a esta función antes de abrir el modal de edición
 */
function agregarPrendaDesdeCtizacion(prendaData, cotizacionSeleccionada) {
    // Preparar la prenda aplicando reglas de origen automático
    const prendaProcesada = CotizacionPrendaHandler.prepararPrendaParaEdicion(
        prendaData,
        cotizacionSeleccionada
    );

    // Agregar a la lista de prendas del pedido
    window.prendas = window.prendas || [];
    window.prendas.push(prendaProcesada);

    // Abrir modal de edición si es necesario
    if (window.prendaEditor && typeof window.prendaEditor.abrirModal === 'function') {
        window.prendaEditor.abrirModal(false, window.prendas.length - 1);
    }

    console.log('Prenda agregada desde cotización:', prendaProcesada);
}

// ============================================================================
// EJEMPLO 2: Integración en listener de selección de cotización
// ============================================================================

/**
 * Cuando se selecciona una cotización en el dropdown
 * Se cargan automáticamente sus prendas con origen correcto
 */
document.addEventListener('seleccionar-cotizacion', function(event) {
    const cotizacion = event.detail.cotizacion;
    const prendas = event.detail.prendas || [];

    console.log('Cotización seleccionada:', cotizacion);
    console.log('Verificando tipo:', cotizacion.tipo_cotizacion_id);

    // Procesar cada prenda de la cotización
    const prendasProcesadas = prendas.map(prenda => 
        CotizacionPrendaHandler.prepararPrendaParaEdicion(prenda, cotizacion)
    );

    console.log('Prendas procesadas con origen automático:', prendasProcesadas);

    // Aquí ir al paso siguiente: agregar prendas al pedido
    cargarPrendasEnPedido(prendasProcesadas);
});

// ============================================================================
// EJEMPLO 3: Integración directa en el módulo de cargar cotización
// ============================================================================

/**
 * Función para cargar prendas de una cotización al pedido actual
 * Aplica automáticamente las reglas de origen según tipo de cotización
 */
function cargarPrendasDesdeCtizacion(cotizacionId, cotizacionData) {
    fetch(`/api/cotizaciones/${cotizacionId}/prendas`)
        .then(response => response.json())
        .then(data => {
            const prendas = data.prendas || [];

            // Procesar cada prenda con origen automático
            const prendasConOrigen = prendas.map(prenda => 
                CotizacionPrendaHandler.prepararPrendaParaEdicion(prenda, cotizacionData)
            );

            // Agregar al pedido actual
            window.prendas = window.prendas || [];
            window.prendas = [...window.prendas, ...prendasConOrigen];

            console.log('Prendas cargadas desde cotización:', prendasConOrigen);
            actualizarVistaPrendas();
        })
        .catch(error => {
            console.error('Error cargando prendas de cotización:', error);
        });
}

// ============================================================================
// EJEMPLO 4: Usar con diferentes tipos de cotización
// ============================================================================

/**
 * Ejemplo de cómo registrar nuevos tipos de cotización dinámicamente
 */
function inicializarTiposCotizacion() {
    // Los tipos por defecto ya están en la clase:
    // - Reflectivo → bodega
    // - Logo → bodega

    // Si necesitas agregar más tipos (ej: desde una API):
    CotizacionPrendaHandler.registrarTipoBodega('3', 'Estampado Especial');
    CotizacionPrendaHandler.registrarTipoBodega('4', 'Bordado Premium');

    // Obtener tipos disponibles
    console.log('Tipos que requieren bodega:', 
        CotizacionPrendaHandler.obtenerTiposBodega()
    );
}

// ============================================================================
// EJEMPLO 5: Testing y debugging
// ============================================================================

/**
 * Función para testear la lógica de origen automático
 */
function testearOrigenAutomatico() {
    // Mock de datos
    const prendaTest = {
        id: 1,
        nombre: 'Camiseta Reflectiva',
        talla: 'M',
        color: 'Azul'
    };

    const cotizacionReflectivo = {
        id: 100,
        tipo_cotizacion_id: 'Reflectivo',
        numero_cotizacion: 'CZ-2026-001',
        cliente_id: 5
    };

    const cotizacionLogo = {
        id: 101,
        tipo_cotizacion_id: 'Logo',
        numero_cotizacion: 'CZ-2026-002'
    };

    const cotizacionNormal = {
        id: 102,
        tipo_cotizacion_id: 'Estándar',
        numero_cotizacion: 'CZ-2026-003'
    };

    console.group('🧪 Test CotizacionPrendaHandler');

    // Test 1: Cotización Reflectivo
    console.log('\n✓ Test 1: Cotización Reflectivo');
    const prenda1 = CotizacionPrendaHandler.prepararPrendaParaEdicion(
        { ...prendaTest },
        cotizacionReflectivo
    );
    console.log('Resultado esperado: origen = "bodega"');
    console.log('Resultado obtenido:', prenda1);
    console.assert(prenda1.origen === 'bodega', '❌ FALLÓ: Origen debe ser bodega');

    // Test 2: Cotización Logo
    console.log('\n✓ Test 2: Cotización Logo');
    const prenda2 = CotizacionPrendaHandler.prepararPrendaParaEdicion(
        { ...prendaTest },
        cotizacionLogo
    );
    console.log('Resultado esperado: origen = "bodega"');
    console.log('Resultado obtenido:', prenda2);
    console.assert(prenda2.origen === 'bodega', '❌ FALLÓ: Origen debe ser bodega');

    // Test 3: Cotización Normal
    console.log('\n✓ Test 3: Cotización Normal (Estándar)');
    const prenda3 = CotizacionPrendaHandler.prepararPrendaParaEdicion(
        { ...prendaTest },
        cotizacionNormal
    );
    console.log('Resultado esperado: origen = "confeccion"');
    console.log('Resultado obtenido:', prenda3);
    console.assert(prenda3.origen === 'confeccion', '❌ FALLÓ: Origen debe ser confeccion');

    // Test 4: Sin cotización (prenda manual)
    console.log('\n✓ Test 4: Prenda manual (sin cotización)');
    const prenda4 = CotizacionPrendaHandler.prepararPrendaParaEdicion({ ...prendaTest });
    console.log('Resultado esperado: origen = undefined (no debe cambiar)');
    console.log('Resultado obtenido:', prenda4);

    console.groupEnd();
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

// Ejecutar al cargar el documento
document.addEventListener('DOMContentLoaded', function() {
    console.info('Inicializando CotizacionPrendaHandler...');
    
    // Verificar que la clase está disponible
    if (typeof CotizacionPrendaHandler !== 'undefined') {
        console.log('✓ CotizacionPrendaHandler cargado correctamente');
        console.log('Tipos disponibles:', CotizacionPrendaHandler.obtenerTiposBodega());
    } else {
        console.error('❌ CotizacionPrendaHandler no está disponible');
    }

    // Descomenta para ejecutar tests
    // testearOrigenAutomatico();
});
