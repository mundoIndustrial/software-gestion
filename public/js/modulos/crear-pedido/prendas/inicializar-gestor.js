/**
 * INICIALIZACIÓN DEL GESTOR DE PRENDAS SIN COTIZACIÓN
 * 
 * Archivo mínimo que solo inicializa el gestor global.
 * Reemplaza a prenda-sin-cotizacion-core.js que fue eliminado.
 */

/**
 * Inicializar el gestor de prenda sin cotización tipo PRENDA
 */
window.inicializarGestorPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion = new GestorPrendaSinCotizacion();
        console.log(' GestorPrendaSinCotizacion inicializado');
    }
};

// Inicializar automáticamente cuando se carga este script
window.inicializarGestorPrendaSinCotizacion();

console.log(' [INIT] Inicializador de gestor cargado');
