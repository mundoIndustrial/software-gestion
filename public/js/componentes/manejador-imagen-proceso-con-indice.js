/**
 * ================================================
 * MANEJADOR DE IMAGEN PROCESO CON ÍNDICE
 * ================================================
 * 
 * Wrapper para manejar carga de imágenes en el modal genérico de procesos
 * Coordina dos índices:
 * - Índice del cuadro HTML (1, 2, 3) para actualizar el preview visual
 * - Índice del proceso en storage (dinámico, basado en globalThis.procesoActualIndex)
 * 
 * @module ManejaadorImagenProcesoConIndice
 * @version 1.0.0
 */

/**
 * Wrapper para manejar imagen de proceso
 * Recibe el cuadro que se completó (1, 2, 3) y usa globalThis.procesoActualIndex para el storage
 * 
 * @param {HTMLInputElement} input - Input de tipo file
 * @param {number} cuadroIndex - Índice del cuadro en el modal (1, 2, 3)
 */
function obtenerManejadorImagenProceso() {
    return globalThis.ProcesoModalController?.imagenes?.manejar || globalThis.manejarImagenProceso;
}

globalThis.manejarImagenProcesoConIndice = function(input, cuadroIndex) {
    if (!input.files || input.files.length === 0) {
        globalThis._procesoQuadroIndex = undefined;
        return;
    }
    
    const file = input.files[0];
    const procesoIndex = globalThis.procesoActualIndex;
    
    if (!procesoIndex || procesoIndex <= 0) {
        globalThis._procesoQuadroIndex = undefined;
        return;
    }
    
    // Establecer el índice del cuadro para que manejarImagenProceso lo use
    globalThis._procesoQuadroIndex = cuadroIndex;
    
    // Delegar a la función original
    const manejarImagenProceso = obtenerManejadorImagenProceso();
    if (typeof manejarImagenProceso === 'function') {
        manejarImagenProceso(input, procesoIndex);
    }
    
    // Limpiar después
    setTimeout(() => {
        globalThis._procesoQuadroIndex = undefined;
    }, 100);
};

/**
 * Abrir selector de archivos para un cuadro de imagen específico
 * @param {number} cuadroIndex - Índice del cuadro (1, 2, 3)
 */
globalThis.abrirSelectorImagenProceso = function(cuadroIndex) {
    // Guard: evitar doble apertura del diálogo de archivos
    if (globalThis._abrirSelectorGuard) {
        return;
    }
    globalThis._abrirSelectorGuard = true;
    setTimeout(() => { globalThis._abrirSelectorGuard = false; }, 500);

    const input = document.getElementById(`proceso-foto-input-${cuadroIndex}`);
    if (input) {
        input.click();
    }
};
