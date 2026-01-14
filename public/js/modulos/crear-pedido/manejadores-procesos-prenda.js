/**
 * manejadores-procesos-prenda.js
 * 
 * Maneja los checkboxes de procesos en el modal de agregar prenda
 * Coordina la apertura de modales genéricos y el resumen de procesos seleccionados
 */

let procesosSeleccionados = {};

// Iconos para cada tipo de proceso
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
    console.log(`🎯 manejarCheckboxProceso(${tipoProceso}, ${estaChecked})`);
    
    if (estaChecked) {
        // Registrar el proceso
        if (!procesosSeleccionados[tipoProceso]) {
            procesosSeleccionados[tipoProceso] = {
                tipo: tipoProceso,
                datos: null
            };
        }
        
        // Actualizar resumen visual
        actualizarResumenProcesos();
        
        // Abrir modal para capturar detalles del proceso
        window.abrirModalProcesoGenerico(tipoProceso);
        console.log(`✅ Modal abierto para ${tipoProceso}`);
    } else {
        // Usuario desmarcó el checkbox
        console.log(`❌ Removiendo proceso ${tipoProceso}`);
        delete procesosSeleccionados[tipoProceso];
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
 * Limpia los procesos seleccionados (se usa al cerrar el modal de prenda)
 */
window.limpiarProcesosSeleccionados = function() {
    procesosSeleccionados = {};
    // Desmarcar todos los checkboxes
    document.querySelectorAll('input[name="nueva-prenda-procesos"]').forEach(cb => cb.checked = false);
    
    // Ocultar resumen
    const seccionResumen = document.getElementById('seccion-procesos-resumen');
    if (seccionResumen) {
        seccionResumen.style.display = 'none';
    }
    
    console.log('🧹 Procesos limpiados');
};

/**
 * Limpia los procesos seleccionados
 */
window.limpiarProcesosSeleccionados = function() {
    procesosSeleccionados = {};
    document.getElementById('checkbox-reflectivo').checked = false;
    document.getElementById('checkbox-bordado').checked = false;
    document.getElementById('checkbox-estampado').checked = false;
    document.getElementById('checkbox-dtf').checked = false;
    document.getElementById('checkbox-sublimado').checked = false;
    document.getElementById('seccion-procesos-resumen').style.display = 'none';
};

console.log('✅ Módulo manejadores-procesos-prenda.js cargado correctamente');
