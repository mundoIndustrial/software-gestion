/**
 * extension-guardar-datos-tallas-extendida.js
 * 
 * Extiende la función guardarTallasSeleccionadas para incluir:
 * - Ubicaciones por talla
 * - Imágenes por talla
 * - Observaciones por talla
 */

// Guardar la función original antes de reemplazarla
const guardarTallasSeleccionadasOriginal = window.guardarTallasSeleccionadas;

/**
 * Nueva función que extiende guardarTallasSeleccionadas
 * Guarda también los datos extendidos (ubicaciones, imágenes, observaciones)
 */
window.guardarTallasSeleccionadas = function() {
    
    // Primero, guardar observaciones pendientes
    document.querySelectorAll('.observaciones-talla-input-extended').forEach(textarea => {
        const genero = textarea.dataset.genero;
        const talla = textarea.dataset.talla;
        if (window.guardarObservacionesTallaExtendida) {
            window.guardarObservacionesTallaExtendida(genero, talla, textarea.value);
        }
    });
    
    // Llamar a la función original para guardar tallas estándar
    guardarTallasSeleccionadasOriginal.call(this);
    
    // Ahora guardar los datos extendidos en el objeto del proceso
    if (procesoActual && window.procesosSeleccionados[procesoActual]?.datos) {
        
        // Guardar datos extendidos
        const datosExtendidos = window.datosExtendidosTallasProceso || {};
        
        // Inicializar estructura de datos extendidos si no existe
        if (!window.procesosSeleccionados[procesoActual].datos.datosExtendidos) {
            window.procesosSeleccionados[procesoActual].datos.datosExtendidos = {
                dama: {},
                caballero: {},
                sobremedida: {}
            };
        }
        
        // Copiar datos extendidos al proceso
        Object.keys(datosExtendidos).forEach(genero => {
            window.procesosSeleccionados[procesoActual].datos.datosExtendidos[genero] = {
                ...datosExtendidos[genero]
            };
        });
        
        console.log(` [guardarTallasSeleccionadas EXTENDIDO] Datos extendidos guardados para "${procesoActual}":`, {
            datosExtendidos: window.procesosSeleccionados[procesoActual].datos.datosExtendidos,
            ubicaciones: Object.values(datosExtendidos.dama || {}).map(d => d.ubicaciones).flat(),
            observaciones: Object.values(datosExtendidos.dama || {}).map(d => d.observaciones).filter(o => o),
            imagenes: Object.values(datosExtendidos.dama || {}).map(d => d.imagen ? '✓' : '✗').filter(i => i === '✓').length
        });
    }
};

/**
 * Función auxiliar para obtener los datos extendidos de un talla específica
 */
window.obtenerDatosExtendidosTalla = function(proceso, genero, talla) {
    if (window.procesosSeleccionados?.[proceso]?.datos?.datosExtendidos?.[genero]?.[ talla]) {
        return window.procesosSeleccionados[proceso].datos.datosExtendidos[genero][talla];
    }
    return null;
};

/**
 * Función auxiliar para restaurar datos extendidos cuando se abre el editor
 */
window.restaurarDatosExtendidosTallasProceso = function(proceso) {
    if (!window.procesosSeleccionados?.[proceso]?.datos?.datosExtendidos) {
        // No hay datos extendidos, limpiar
        window.datosExtendidosTallasProceso = { dama: {}, caballero: {}, sobremedida: {} };
        return;
    }
    
    const datosGuardados = window.procesosSeleccionados[proceso].datos.datosExtendidos;
    
    // Restaurar en la estructura de trabajo
    window.datosExtendidosTallasProceso = {
        dama: { ...datosGuardados.dama || {} },
        caballero: { ...datosGuardados.caballero || {} },
        sobremedida: { ...datosGuardados.sobremedida || {} }
    };
    
    console.log(` [restaurarDatosExtendidosTallasProceso] Datos restaurados para "${proceso}":`, window.datosExtendidosTallasProceso);
};
