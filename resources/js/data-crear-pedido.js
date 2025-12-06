/**
 * Data module for crear-pedido view
 * Contiene los datos de cotizaciones pasados desde Laravel
 */

export function initCotizacionesData(cotizacionesData, asesorActualNombre) {
    // Filtrar solo las cotizaciones del asesor actual
    const misCotizaciones = cotizacionesData.filter(cot => cot.asesora === asesorActualNombre);
    
    // Debug: mostrar datos en consola
    console.log('🔍 Asesor actual:', asesorActualNombre);
    console.log('🔍 Todas las cotizaciones:', cotizacionesData);
    console.log('🔍 Mis cotizaciones (filtradas):', misCotizaciones);
    
    return misCotizaciones;
}

export function getCotizacionesData() {
    return window.cotizacionesData || [];
}

export function setCotizacionesData(data) {
    window.cotizacionesData = data;
}
