/**
 * INICIALIZACIÓN: Logo Pedido - Prendas Técnicas
 * 
 * Este script se ejecuta cuando se selecciona una cotización
 * tipo LOGO para cargar las prendas técnicas dinámicamente
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Interceptar cambios en el select de cotización
    const cotizacionSearchInput = document.getElementById('cotizacion_search_editable');
    
    if (cotizacionSearchInput) {
        cotizacionSearchInput.addEventListener('change', function() {
            
            // Obtener el ID de cotización seleccionada
            const cotizacionId = document.getElementById('cotizacion_id_editable').value;
            
            if (cotizacionId) {
                // Llamar a obtenerDatosCotizacion que ya está configurado para 
                // cargar prendas técnicas si es tipo LOGO
                
                // La función obtenerDatosCotizacion ya está sobrescrita
                // en integracion-logo-pedido-tecnicas.js
            }
        });
    }
});

/**
 * Hook: Se ejecuta después de cargar datos de una cotización
 * para verificar si es tipo LOGO y mostrar prendas técnicas
 */
window.afterLoadCotizacionData = function(cotizacion) {
    
    if (cotizacion && cotizacion.tipo === 'L') {
        mostrarSeccionPrendasTecnicasLogo();
    } else {
        ocultarSeccionPrendasTecnicasLogo();
    }
};

/**
 * Ocultar la sección de prendas técnicas cuando no es LOGO
 */
function ocultarSeccionPrendasTecnicasLogo() {
    const container = document.getElementById('logo-prendas-tecnicas-container');
    if (container) {
        container.style.display = 'none';
    }
}
