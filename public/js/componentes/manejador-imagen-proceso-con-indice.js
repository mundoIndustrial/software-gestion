/**
 * ================================================
 * MANEJADOR DE IMAGEN PROCESO CON ÍNDICE (v4 - NATIVE LABELS)
 * ================================================
 * 
 * Wrapper para manejar carga de imágenes en el modal genérico de procesos
 * Usa <label for="input"> nativo para evitar re-disparos de eventos
 * 
 * CAMBIOS v4:
 * - Eliminado input.click() mediante labels nativos
 * - Eliminado abrirSelectorImagenProceso() (ahora innecesario)
 * - El navegador maneja el file dialog correctamente
 * - Sin re-disparos, sin eventos sintéticos, sin hacks
 * 
 * ARQUITECTURA SIMPLE Y PURA:
 * - HTML: <label for="input-X"> envuelve el preview
 * - JavaScript: Solo manejar el change event
 * - No más estado complejo, no más llamadas a abrirDialogo
 * 
 * @module ManejaadorImagenProcesoConIndice
 * @version 4.0.0
 */

console.log(' Manejador de Imagen Proceso Con Índice (v4 - Native Labels) cargado...');

/**
 * Manejar imagen después de seleccionarla
 * 
 * FLUJO CON LABELS NATIVOS:
 * - Usuario hace click en <label for="input-X">
 * - Navegador abre automáticamente input-X
 * - Usuario selecciona imagen
 * - change event dispara automáticamente
 * - manejarImagenProcesoConIndice() procesa la imagen
 * - Ciclo completado, sin re-disparos
 * 
 * @param {HTMLInputElement} input - Input de tipo file
 * @param {number} cuadroIndex - Índice del cuadro en el modal (1, 2, 3)
 */
window.manejarImagenProcesoConIndice = function(input, cuadroIndex) {
    if (!input?.files || input.files.length === 0) {
        console.log(`[manejarImagenProcesoConIndice] Sin archivos seleccionados para cuadro ${cuadroIndex}`);
        window._procesoQuadroIndex = undefined;
        return;
    }
    
    const file = input.files[0];
    const procesoIndex = window.procesoActualIndex;
    
    console.log(`[manejarImagenProcesoConIndice] Archivo procesando:`, file.name);
    
    if (!procesoIndex || procesoIndex <= 0) {
        console.error('[manejarImagenProcesoConIndice] procesoActualIndex no definido');
        window._procesoQuadroIndex = undefined;
        return;
    }
    
    window._procesoQuadroIndex = cuadroIndex;
    
    if (typeof window.manejarImagenProceso === 'function') {
        window.manejarImagenProceso(input, procesoIndex);
    } else {
        console.error('[manejarImagenProcesoConIndice] window.manejarImagenProceso no disponible');
        return;
    }
};

/**
 *  DEPRECATED: abrirSelectorImagenProceso()
 * 
 * Ya no es necesario porque usamos <label for="input"> nativo
 * El navegador maneja el file dialog automáticamente
 * 
 * Se deja como fallback si hay algún código legacy que la llame
 * 
 * @deprecated Usar labels nativos en su lugar
 * @param {number} cuadroIndex - Índice del cuadro (no usado)
 */
window.abrirSelectorImagenProceso = function(cuadroIndex) {
    console.warn('[abrirSelectorImagenProceso]  DEPRECATED - Ya no es necesaria con labels nativos');
    console.warn('[abrirSelectorImagenProceso] El navegador abre el file dialog automáticamente');
    console.warn('[abrirSelectorImagenProceso] Verificar que <label for="proceso-foto-input-X"> está en el HTML');
};
