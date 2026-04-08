/**
 * manejadores-procesos-prenda.js
 * 
 * Maneja los checkboxes de procesos en el modal de agregar prenda
 * Coordina la apertura de modales genéricos y el resumen de procesos seleccionados
 */

let procesosSeleccionados = globalThis.procesosSeleccionados && typeof globalThis.procesosSeleccionados === 'object'
    ? globalThis.procesosSeleccionados
    : {};

// Exponer en globalThis para acceso global
globalThis.procesosSeleccionados = procesosSeleccionados;

//  ALMACENAJE PERSISTENTE: Guardar procesos aunque se limpie procesosSeleccionados
// Estructura: { 'reflectivo': { tipo: 'reflectivo', datos: {...}, indiceResultado: 1 }, ... }
globalThis.procesosGuardados = globalThis.procesosGuardados || {};

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
function marcarProceso(tipoProceso) {
    if (!procesosSeleccionados[tipoProceso]) {
        procesosSeleccionados[tipoProceso] = {
            tipo: tipoProceso,
            datos: {
                tipo: tipoProceso,
                modo_tallas: 'generico'
            }
        };
        globalThis.procesosSeleccionados[tipoProceso] = procesosSeleccionados[tipoProceso];
    }

    actualizarResumenProcesos();

    if (typeof globalThis.abrirSelectorModoProceso === 'function') {
        globalThis.abrirSelectorModoProceso(tipoProceso);
    } else {
        const controller = globalThis.ProcesoModalController;
        if (controller?.abrir) {
            controller.abrir(tipoProceso);
        } else {
            globalThis.abrirModalProcesoGenerico(tipoProceso);
        }
    }
}

function desmarcarProceso(tipoProceso) {
    if (procesosSeleccionados[tipoProceso]) {
        delete procesosSeleccionados[tipoProceso];
        delete globalThis.procesosSeleccionados[tipoProceso];
    }

    actualizarResumenProcesos();
}

globalThis.manejarCheckboxProceso = function(tipoProceso, estaChecked) {
    if (estaChecked) {
        marcarProceso(tipoProceso);
    } else {
        desmarcarProceso(tipoProceso);
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
globalThis.obtenerProcesosConfigurables = function() {
    return procesosSeleccionados;
};

/**
 * Limpia todos los procesos seleccionados
 */
globalThis.limpiarProcesosSeleccionados = function() {

    procesosSeleccionados = {};
    globalThis.procesosSeleccionados = procesosSeleccionados; // Mantener sincronizado con globalThis
    
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
