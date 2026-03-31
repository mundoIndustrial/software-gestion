/**
 * extension-guardar-datos-tallas-extendida.js
 * 
 * Extiende la función guardarTallasSeleccionadas para incluir:
 * - Ubicaciones por talla
 * - Imágenes por talla
 * - Observaciones por talla
 */

// Guardar la función original antes de reemplazarla
const guardarTallasSeleccionadasOriginal = globalThis.guardarTallasSeleccionadas;

/**
 * Nueva función que extiende guardarTallasSeleccionadas
 * Guarda también los datos extendidos (ubicaciones, imágenes, observaciones)
 */
globalThis.guardarTallasSeleccionadas = function() {
    
    // Primero, guardar observaciones pendientes
    document.querySelectorAll('.observaciones-talla-input-extended').forEach(textarea => {
        const genero = textarea.dataset.genero;
        const talla = textarea.dataset.talla;
        if (globalThis.guardarObservacionesTallaExtendida) {
            globalThis.guardarObservacionesTallaExtendida(genero, talla, textarea.value);
        }
    });
    
    // Llamar a la función original para guardar tallas estándar
    guardarTallasSeleccionadasOriginal.call(this);
    
    // Ahora guardar los datos extendidos en el objeto del proceso
    if (procesoActual && globalThis.procesosSeleccionados[procesoActual]?.datos) {
        
        // Guardar datos extendidos
        const datosExtendidos = globalThis.datosExtendidosTallasProceso || {};
        
        // Inicializar estructura de datos extendidos si no existe
        if (!globalThis.procesosSeleccionados[procesoActual].datos.datosExtendidos) {
            globalThis.procesosSeleccionados[procesoActual].datos.datosExtendidos = {
                dama: {},
                caballero: {},
                sobremedida: {}
            };
        }
        
        // Copiar datos extendidos al proceso
        Object.keys(datosExtendidos).forEach(genero => {
            globalThis.procesosSeleccionados[procesoActual].datos.datosExtendidos[genero] = {
                ...datosExtendidos[genero]
            };
        });
        
        console.log(` [guardarTallasSeleccionadas EXTENDIDO] Datos extendidos guardados para "${procesoActual}":`, {
            datosExtendidos: globalThis.procesosSeleccionados[procesoActual].datos.datosExtendidos,
            ubicaciones: Object.values(datosExtendidos.dama || {}).flatMap(d => d.ubicaciones),
            observaciones: Object.values(datosExtendidos.dama || {}).map(d => d.observaciones).filter(Boolean),
            imagenes: Object.values(datosExtendidos.dama || {}).map(d => d.imagen ? '✓' : '✗').filter(i => i === '✓').length
        });
    }
};

/**
 * Función auxiliar para obtener los datos extendidos de un talla específica
 */
globalThis.obtenerDatosExtendidosTalla = function(proceso, genero, talla) {
    if (globalThis.procesosSeleccionados?.[proceso]?.datos?.datosExtendidos?.[genero]?.[ talla]) {
        return globalThis.procesosSeleccionados[proceso].datos.datosExtendidos[genero][talla];
    }
    return null;
};

/**
 * Función auxiliar para restaurar datos extendidos cuando se abre el editor
 */
globalThis.restaurarDatosExtendidosTallasProceso = function(proceso) {
    if (!globalThis.procesosSeleccionados?.[proceso]?.datos?.datosExtendidos) {
        // No hay datos extendidos, limpiar
        globalThis.datosExtendidosTallasProceso = { dama: {}, caballero: {}, sobremedida: {} };
        return;
    }
    
    const datosGuardados = globalThis.procesosSeleccionados[proceso].datos.datosExtendidos;
    
    // Restaurar en la estructura de trabajo
    globalThis.datosExtendidosTallasProceso = {
        dama: datosGuardados.dama ? { ...datosGuardados.dama } : {},
        caballero: datosGuardados.caballero ? { ...datosGuardados.caballero } : {},
        sobremedida: datosGuardados.sobremedida ? { ...datosGuardados.sobremedida } : {}
    };
    
    console.log(` [restaurarDatosExtendidosTallasProceso] Datos restaurados para "${proceso}":`, globalThis.datosExtendidosTallasProceso);
};
