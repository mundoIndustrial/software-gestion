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
    alert('🚀 [limpiarProcesosSeleccionados] INICIANDO LIMPIEZA');
    console.log('🧹🧹🧹 [limpiarProcesosSeleccionados] ==================== INICIANDO LIMPIEZA ====================');
    
    console.log('📝 Estado ANTES:');
    console.log('   window.procesosSeleccionados:', window.procesosSeleccionados);
    console.log('   Claves:', Object.keys(window.procesosSeleccionados || {}));

    procesosSeleccionados = {};
    window.procesosSeleccionados = procesosSeleccionados; // Mantener sincronizado con window
    alert('✅ window.procesosSeleccionados AHORA VACÍO: ' + JSON.stringify(window.procesosSeleccionados));
    console.log('✅ window.procesosSeleccionados reiniciado a objeto vacío');
    
    // Desmarcar todos los checkboxes
    console.log('📋 Desmarcando checkboxes...');
    const checkboxes = [
        'checkbox-reflectivo',
        'checkbox-bordado',
        'checkbox-estampado',
        'checkbox-dtf',
        'checkbox-sublimado'
    ];
    
    checkboxes.forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) {
            console.log(`   ✓ ${id}: ${checkbox.checked} → false`);
            checkbox.checked = false;
        } else {
            console.log(`   ⚠️  ${id}: NO ENCONTRADO`);
        }
    });
    
    // 🔴 NUEVO: Limpiar contenedores visuales de procesos
    console.log('🗑️  Limpiando contenedores visuales...');
    
    // Limpiar tarjetas de prendas reflectivo
    const prendasReflectivo = document.querySelectorAll('.prenda-card-reflectivo');
    if (prendasReflectivo.length > 0) {
        alert('🗑️ Encontradas ' + prendasReflectivo.length + ' tarjetas reflectivo - ELIMINANDO');
        console.log(`   🗑️  Encontradas ${prendasReflectivo.length} tarjetas reflectivo`);
        prendasReflectivo.forEach((card, idx) => {
            console.log(`      ✓ Eliminando tarjeta reflectivo ${idx + 1}`);
            card.remove();
        });
    } else {
        alert('ℹ️ No hay tarjetas reflectivo en el DOM');
        console.log('   ℹ️  No hay tarjetas reflectivo en el DOM');
    }
    
    // Limpiar contenedor de fotos del reflectivo
    const reflectivoFotosContainer = document.getElementById('reflectivo-fotos-container');
    if (reflectivoFotosContainer) {
        console.log('   ✓ reflectivo-fotos-container limpiado');
        reflectivoFotosContainer.innerHTML = '';
    } else {
        console.log('   ⚠️  reflectivo-fotos-container NO ENCONTRADO');
    }
    
    // Limpiar tarjetas de procesos renderizadas
    const contenedorTarjetas = document.getElementById('contenedor-tarjetas-procesos');
    if (contenedorTarjetas) {
        console.log('   ✓ contenedor-tarjetas-procesos limpiado');
        contenedorTarjetas.innerHTML = '';
    } else {
        console.log('   ⚠️  contenedor-tarjetas-procesos NO ENCONTRADO');
    }
    
    // Ocultar resumen
    const seccionResumen = document.getElementById('seccion-procesos-resumen');
    if (seccionResumen) {
        console.log('   ✓ seccion-procesos-resumen ocultado');
        seccionResumen.style.display = 'none';
    } else {
        console.log('   ⚠️  seccion-procesos-resumen NO ENCONTRADO');
    }
    
    console.log('📝 Estado DESPUÉS:');
    console.log('   window.procesosSeleccionados:', window.procesosSeleccionados);
    console.log('   Claves:', Object.keys(window.procesosSeleccionados || {}));
    
    alert('✅✅✅ [limpiarProcesosSeleccionados] LIMPIEZA COMPLETADA - window.procesosSeleccionados: ' + JSON.stringify(window.procesosSeleccionados));
    console.log('✅✅✅ [limpiarProcesosSeleccionados] ==================== LIMPIEZA COMPLETADA ====================');
};


