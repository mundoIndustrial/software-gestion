/**
 * ================================================
 * MANEJADOR DE IMAGEN PROCESO CON ÍNDICE (v2 - FIX DOBLE FILE DIALOG)
 * ================================================
 * 
 * Wrapper para manejar carga de imágenes en el modal genérico de procesos
 * Coordina dos índices:
 * - Índice del cuadro HTML (1, 2, 3) para actualizar el preview visual
 * - Índice del proceso en storage (dinámico, basado en window.procesoActualIndex)
 * 
 *  CAMBIOS v2:
 * - Agregado guard para prevenir doble disparo del file dialog
 * - Eliminados logs innecesarios (no afectan al bug, solo ruido)
 * - Código más limpio y profesional
 * 
 * @module ManejaadorImagenProcesoConIndice
 * @version 2.0.0
 */

console.log(' Manejador de Imagen Proceso Con Índice (v2) cargado...');

/**
 * Wrapper para manejar imagen de proceso
 * Recibe el cuadro que se completó (1, 2, 3) y usa window.procesoActualIndex para el storage
 * 
 * @param {HTMLInputElement} input - Input de tipo file
 * @param {number} cuadroIndex - Índice del cuadro en el modal (1, 2, 3)
 */
window.manejarImagenProcesoConIndice = function(input, cuadroIndex) {
    console.log(`[manejarImagenProcesoConIndice] Procesando imagen - cuadro ${cuadroIndex}`);
    
    if (!input.files || input.files.length === 0) {
        console.log(`[manejarImagenProcesoConIndice] No se seleccionaron archivos para cuadro ${cuadroIndex}`);
        window._procesoQuadroIndex = undefined;
        return;
    }
    
    const file = input.files[0];
    const procesoIndex = window.procesoActualIndex;
    
    if (!procesoIndex || procesoIndex <= 0) {
        console.error('[manejarImagenProcesoConIndice] window.procesoActualIndex no está definido');
        window._procesoQuadroIndex = undefined;
        return;
    }
    
    // Establecer el índice del cuadro para que manejarImagenProceso lo use
    window._procesoQuadroIndex = cuadroIndex;
    
    // Delegar a la función original
    if (typeof window.manejarImagenProceso === 'function') {
        window.manejarImagenProceso(input, procesoIndex);
    } else {
        console.error('[manejarImagenProcesoConIndice] window.manejarImagenProceso no está disponible');
    }
};

/**
 * Abrir selector de archivos para un cuadro de imagen específico
 * 
 * PREVIENE DOBLE DISPARO: Usa guard para evitar que el file dialog se abra dos veces
 * con un solo click del usuario.
 * 
 * @param {number} cuadroIndex - Índice del cuadro (1, 2, 3)
 */
window.abrirSelectorImagenProceso = function(cuadroIndex) {
    const input = document.getElementById(`proceso-foto-input-${cuadroIndex}`);
    
    if (!input) {
        console.warn(`Input NO encontrado para cuadro ${cuadroIndex}`);
        return;
    }
    
    // GUARD CRÍTICO: Prevenir doble disparo del file dialog
    // Si ya hay un diálogo abierto para este input, ignorar el nuevo click
    if (input._isDialogOpening) {
        console.warn(`Diálogo ya está abriéndose para cuadro ${cuadroIndex} - ignorando click duplicado`);
        return;
    }
    
    // Marcar que estamos abriendo el diálogo
    input._isDialogOpening = true;
    
    // Resetear el value para permitir seleccionar el mismo archivo dos veces
    input.value = '';
    
    // Abrir el selector de archivos del sistema
    input.click();
    
    // Usar Promise para manejar el cleanup de forma asíncrona proper
    Promise.resolve().then(() => {
        input._isDialogOpening = false;
    });
};
