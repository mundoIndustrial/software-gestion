/**
 * manejadores-procesos-prenda.js
 * 
 * Maneja los checkboxes de procesos en el modal de agregar prenda
 * Coordina la apertura de modales genéricos y el resumen de procesos seleccionados
 */

let procesosSeleccionados = {};

// Exponer en window para acceso global
window.procesosSeleccionados = procesosSeleccionados;
const procesosIconos = {
    reflectivo: 'light_mode',
    bordado: 'auto_awesome',
    estampado: 'format_paint',
    dtf: 'print',
    sublimado: 'palette'
};

const procesosNombres = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Maneja el cambio de un checkbox de proceso
 * @param {string} tipoProceso - reflectivo, bordado, estampado, dtf, sublimado
 * @param {boolean} estaChecked - si el checkbox está marcado
 */
window.manejarCheckboxProceso = function(tipoProceso, estaChecked) {

    
    if (estaChecked) {
        // Registrar el proceso
        if (!procesosSeleccionados[tipoProceso]) {
            procesosSeleccionados[tipoProceso] = {
                tipo: tipoProceso,
                datos: null
            };
            //  CRÍTICO: Sincronizar con window inmediatamente
            window.procesosSeleccionados[tipoProceso] = procesosSeleccionados[tipoProceso];

        }
        
        // Actualizar resumen visual
        actualizarResumenProcesos();
        
        // Abrir modal para capturar detalles del proceso
        window.abrirModalProcesoGenerico(tipoProceso);

    } else {
        // Usuario desmarcó el checkbox

        delete procesosSeleccionados[tipoProceso];
        delete window.procesosSeleccionados[tipoProceso];
        actualizarResumenProcesos();
    }
};

/**
 * Actualiza el resumen visual de procesos seleccionados
 */
function actualizarResumenProcesos() {
    const procesosList = Object.keys(procesosSeleccionados);
    const seccionResumen = document.getElementById('seccion-procesos-resumen');
    const contenidoResumen = document.getElementById('procesos-resumen-contenido');
    
    if (procesosList.length === 0) {
        if (seccionResumen) seccionResumen.style.display = 'none';
        return;
    }
    
    if (seccionResumen) seccionResumen.style.display = 'block';
    
    let html = '<ul style="margin: 0; padding-left: 1.5rem; list-style: disc;">';
    procesosList.forEach(proceso => {
        const icon = procesosIconos[proceso];
        const nombre = procesosNombres[proceso];
        
        html += `<li><span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle; margin-right: 0.5rem;">${icon}</span>${nombre}</li>`;
    });
    html += '</ul>';
    
    if (contenidoResumen) {
        contenidoResumen.innerHTML = html;
    }
}

/**
 * Obtiene los procesos configurados para la prenda
 */
window.obtenerProcesosConfigurables = function() {
    return procesosSeleccionados;
};

/**
 * Limpia todos los procesos seleccionados
 */
window.limpiarProcesosSeleccionados = function() {

    procesosSeleccionados = {};
    window.procesosSeleccionados = procesosSeleccionados; // Mantener sincronizado con window
    
    // Desmarcar todos los checkboxes
    const checkboxes = [
        'checkbox-reflectivo',
        'checkbox-bordado',
        'checkbox-estampado',
        'checkbox-dtf',
        'checkbox-sublimado'
    ];
    
    checkboxes.forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) checkbox.checked = false;
    });
    
    // Ocultar resumen
    const seccionResumen = document.getElementById('seccion-procesos-resumen');
    if (seccionResumen) seccionResumen.style.display = 'none';
    

};


