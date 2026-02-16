/**
 * ================================================
 * MANEJADOR DE IMAGEN PROCESO CON ÍNDICE
 * ================================================
 * 
 * Wrapper para manejar carga de imágenes en el modal genérico de procesos
 * Coordina dos índices:
 * - Índice del cuadro HTML (1, 2, 3) para actualizar el preview visual
 * - Índice del proceso en storage (dinámico, basado en window.procesoActualIndex)
 * 
 * @module ManejaadorImagenProcesoConIndice
 * @version 1.0.0
 */

/**
 * Wrapper para manejar imagen de proceso
 * Recibe el cuadro que se completó (1, 2, 3) y usa window.procesoActualIndex para el storage
 * 
 * @param {HTMLInputElement} input - Input de tipo file
 * @param {number} cuadroIndex - Índice del cuadro en el modal (1, 2, 3)
 */
window.manejarImagenProcesoConIndice = function(input, cuadroIndex) {
    if (!input.files || input.files.length === 0) {
        window._procesoQuadroIndex = undefined;
        return;
    }
    
    const file = input.files[0];
    const procesoIndex = window.procesoActualIndex;
    
    if (!procesoIndex || procesoIndex <= 0) {
        window._procesoQuadroIndex = undefined;
        return;
    }
    
    // Establecer el índice del cuadro para que manejarImagenProceso lo use
    window._procesoQuadroIndex = cuadroIndex;
    
    // Delegar a la función original
    if (typeof window.manejarImagenProceso === 'function') {
        window.manejarImagenProceso(input, procesoIndex);
    }
    
    // Limpiar después
    setTimeout(() => {
        window._procesoQuadroIndex = undefined;
    }, 100);
};

/**
 * Abrir selector de archivos para un cuadro de imagen específico
 * @param {number} cuadroIndex - Índice del cuadro (1, 2, 3)
 */
window.abrirSelectorImagenProceso = function(cuadroIndex) {
    // Guard: evitar doble apertura del diálogo de archivos
    if (window._abrirSelectorGuard) {
        return;
    }
    window._abrirSelectorGuard = true;
    setTimeout(() => { window._abrirSelectorGuard = false; }, 500);

    const input = document.getElementById(`proceso-foto-input-${cuadroIndex}`);
    if (input) {
        input.click();
    }
};
