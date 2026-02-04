/**
 * INICIALIZACIÓN - Sistema de Origen Automático de Prendas
 * 
 * Este archivo inicializa y configura automáticamente el sistema
 * de origen automático de prendas desde cotizaciones.
 * 
 * Incluir ANTES de usar PrendaEditor
 */

// ============================================================================
// VERIFICAR DISPONIBILIDAD DE SCRIPTS
// ============================================================================

console.group(' Inicializando Sistema de Origen Automático de Prendas');

// Verificar que los scripts necesarios estén disponibles
const scriptsRequeridos = {
    'CotizacionPrendaHandler': typeof CotizacionPrendaHandler !== 'undefined',
    'CotizacionPrendaConfig': typeof CotizacionPrendaConfig !== 'undefined',
    'PrendaEditor': typeof PrendaEditor !== 'undefined'
};

console.log('📋 Scripts disponibles:', scriptsRequeridos);

// Verificar que todos están disponibles
const todosDisponibles = Object.values(scriptsRequeridos).every(v => v);
if (!todosDisponibles) {
    console.error('❌ Faltan scripts requeridos. Verificar inclusión en HTML:');
    console.error('   - cotizacion-prenda-handler.js');
    console.error('   - cotizacion-prenda-config.js');
    Object.entries(scriptsRequeridos)
        .filter(([_, disponible]) => !disponible)
        .forEach(([nombre, _]) => {
            console.error(`   ❌ ${nombre} NO disponible`);
        });
} else {
    console.info(' Todos los scripts están disponibles');
}

// ============================================================================
// INICIALIZACIÓN AUTOMÁTICA AL CARGAR DOM
// ============================================================================

document.addEventListener('DOMContentLoaded', async function() {
    console.group('🚀 Inicio Automático - DOMContentLoaded');

    try {
        // PASO 1: Inicializar configuración de tipos de cotización
        console.info('Paso 1/2: Inicializando configuración de tipos...');
        
        if (typeof CotizacionPrendaConfig !== 'undefined') {
            // Usar inicialización inteligente con retroalimentación
            await CotizacionPrendaConfig.inicializarConRetroalimentacion();
            
            // Mostrar estado
            CotizacionPrendaConfig.mostrarEstado();
            
            console.info(' Tipos de cotización cargados');
        } else {
            console.warn(' CotizacionPrendaConfig no disponible, omitiendo inicialización');
        }

        // PASO 2: Extender PrendaEditor si está disponible
        console.info('Paso 2/2: Preparando PrendaEditor...');
        
        if (typeof PrendaEditor !== 'undefined') {
            // La extensión ya está integrada en PrendaEditor
            console.info(' PrendaEditor listo para origen automático');
            
            // Mensaje para desarrolladores
            console.log('%c📝 NOTA PARA DESARROLLADORES:', 'color: blue; font-weight: bold;');
            console.log('PrendaEditor ahora soporta origen automático desde cotización.');
            console.log('Uso: new PrendaEditor({ cotizacionActual: cotizacion })');
            console.log('O después de crear instancia: prendaEditor.cargarPrendasDesdeCotizacion(prendas, cotizacion)');
        } else {
            console.warn(' PrendaEditor no disponible');
        }

        console.info(' Sistema de Origen Automático inicializado correctamente');

    } catch (error) {
        console.error('❌ Error durante inicialización:', error);
    }

    console.groupEnd();
}, { once: true }); // Ejecutar una sola vez

// ============================================================================
// FUNCIONES GLOBALES DE UTILIDAD
// ============================================================================

/**
 * Función global para crear instancia de PrendaEditor con origen automático
 * 
 * @param {Object} options - Opciones del PrendaEditor
 * @returns {PrendaEditor} - Instancia configurada
 */
window.crearPrendaEditorConOrigenAutomatico = function(options = {}) {
    if (typeof PrendaEditor === 'undefined') {
        console.error('PrendaEditor no está disponible');
        return null;
    }

    console.info('[crearPrendaEditorConOrigenAutomatico] Creando instancia...');
    
    const prendaEditor = new PrendaEditor({
        notificationService: options.notificationService || window.notificationService,
        modalId: options.modalId,
        cotizacionActual: options.cotizacionActual
    });

    console.info(' PrendaEditor creado con éxito');
    return prendaEditor;
};

/**
 * Función global para registrar nuevos tipos de cotización que requieren bodega
 * 
 * @param {number|string} tipoId - ID del tipo
 * @param {string} nombreTipo - Nombre del tipo
 */
window.registrarTipoCotizacionBodega = function(tipoId, nombreTipo) {
    if (typeof CotizacionPrendaHandler === 'undefined') {
        console.error('CotizacionPrendaHandler no disponible');
        return false;
    }

    console.info(`[registrarTipoCotizacionBodega] Registrando: "${nombreTipo}" (ID: ${tipoId})`);
    return CotizacionPrendaHandler.registrarTipoBodega(tipoId, nombreTipo);
};

/**
 * Función global para obtener estadísticas de prendas
 * 
 * @returns {Object} - Estadísticas de prendas (bodega, confección, etc)
 */
window.obtenerEstadisticasPrendas = function() {
    if (!window.prendas || !Array.isArray(window.prendas)) {
        console.warn('No hay prendas cargadas');
        return null;
    }

    const stats = {
        total: window.prendas.length,
        bodega: window.prendas.filter(p => p.origen === 'bodega').length,
        confeccion: window.prendas.filter(p => p.origen === 'confeccion').length,
        sinOrigen: window.prendas.filter(p => !p.origen).length
    };

    console.table(stats);
    return stats;
};

/**
 * Función global para debugging
 */
window.debugOrigenAutomatico = function() {
    console.group('🐛 Debug - Origen Automático');
    
    console.log('CotizacionPrendaHandler:', typeof CotizacionPrendaHandler !== 'undefined' ? '' : '❌');
    console.log('CotizacionPrendaConfig:', typeof CotizacionPrendaConfig !== 'undefined' ? '' : '❌');
    console.log('PrendaEditor:', typeof PrendaEditor !== 'undefined' ? '' : '❌');
    
    if (typeof CotizacionPrendaHandler !== 'undefined') {
        console.log('Tipos registrados:', CotizacionPrendaHandler.obtenerTiposBodega());
    }
    
    if (window.prendas) {
        console.log('Prendas cargadas:', window.prendas.length);
        window.obtenerEstadisticasPrendas();
    }
    
    if (typeof testearOrigenAutomatico === 'function') {
        console.log('Tests disponibles: ');
    }
    
    console.groupEnd();
};

// ============================================================================
// VERIFICACIÓN EN CONSOLA
// ============================================================================

console.log('%c✨ Sistema de Origen Automático Cargado ✨', 'color: green; font-weight: bold; font-size: 14px;');
console.log('%cPrueba estos comandos en consola:', 'color: blue; font-weight: bold;');
console.log('• debugOrigenAutomatico() - Ver estado del sistema');
console.log('• testearOrigenAutomatico() - Ejecutar tests');
console.log('• CotizacionPrendaConfig.mostrarEstado() - Ver tipos configurados');
console.log('• window.obtenerEstadisticasPrendas() - Ver estadísticas de prendas');

console.groupEnd();
