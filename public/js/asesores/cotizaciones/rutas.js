/**
 * SISTEMA DE COTIZACIONES - RUTAS GLOBALES
 * Responsabilidad: Definir rutas accesibles para todos los módulos
 */

window.routes = {
    guardarCotizacion: null,
    subirImagenes: function(id) { return `/asesores/cotizaciones/${id}/imagenes`; },
    cotizacionesIndex: null
};

// Las rutas se cargarán desde el servidor en el Blade
