/**
 * selector-modo-proceso.js
 * 
 * Maneja el modal de selección de modo para procesos.
 * Cuando el usuario marca un checkbox de proceso, se muestra un modal con dos opciones:
 * 1. "Para Todas" - Una configuración general (ubicación, observación, fotos) para todas las tallas
 * 2. "Por Tallas" - Configuración individual por talla (ubicación, observación, fotos por cada talla)
 */

// Variable para saber qué proceso se está configurando en el selector
let procesoEnSelector = null;

// Mapeo de iconos por tipo de proceso (Material Symbols)
const iconosSelectorProceso = {
    reflectivo: 'light_mode',
    bordado: 'auto_awesome',
    estampado: 'format_paint',
    dtf: 'print',
    sublimado: 'palette'
};

const nombresSelectorProceso = {
    reflectivo: 'Reflectivo',
    bordado: 'Bordado',
    estampado: 'Estampado',
    dtf: 'DTF',
    sublimado: 'Sublimado'
};

/**
 * Abre el modal selector de modo para un proceso.
 * Se llama desde manejarCheckboxProceso en lugar de abrirModalProcesoGenerico.
 */
window.abrirSelectorModoProceso = function(tipoProceso) {
    procesoEnSelector = tipoProceso;
    
    const modal = document.getElementById('modal-selector-modo-proceso');
    if (!modal) {
        console.warn('[selector-modo] Modal selector no encontrado, abriendo modal genérico como fallback');
        window.abrirModalProcesoGenerico(tipoProceso);
        return;
    }
    
    // Actualizar título e icono según el proceso
    const iconEl = document.getElementById('selector-modo-icon');
    const tituloEl = document.getElementById('selector-modo-titulo');
    
    if (iconEl) iconEl.textContent = iconosSelectorProceso[tipoProceso] || 'settings';
    if (tituloEl) tituloEl.textContent = `Configurar ${nombresSelectorProceso[tipoProceso] || tipoProceso}`;
    
    // Mostrar modal
    modal.style.display = 'flex';
};

/**
 * Cierra el modal selector.
 * @param {boolean} cancelado - Si es true, desmarca el checkbox del proceso
 */
window.cerrarSelectorModoProceso = function(cancelado) {
    const modal = document.getElementById('modal-selector-modo-proceso');
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (cancelado && procesoEnSelector) {
        // Desmarcar el checkbox ya que el usuario canceló
        const checkbox = document.getElementById(`checkbox-${procesoEnSelector}`);
        if (checkbox) {
            checkbox._ignorarOnclick = true;
            checkbox.checked = false;
            checkbox._ignorarOnclick = false;
        }
        
        // Eliminar de procesosSeleccionados
        delete window.procesosSeleccionados[procesoEnSelector];
        
        // Actualizar resumen si existe la función
        if (typeof actualizarResumenProcesos === 'function') {
            actualizarResumenProcesos();
        }
    }
    
    procesoEnSelector = null;
};

/**
 * Modo "Para Todas": Aplica proceso a todas las tallas y abre modal genérico.
 * El modal genérico ya tiene campos de ubicación, observaciones y fotos.
 */
window.seleccionarModoProcesoTodas = function() {
    if (!procesoEnSelector) return;
    
    const tipoProceso = procesoEnSelector;
    
    // Cerrar selector sin cancelar
    const modal = document.getElementById('modal-selector-modo-proceso');
    if (modal) modal.style.display = 'none';
    procesoEnSelector = null;
    
    // Guardar el modo seleccionado en el proceso
    if (window.procesosSeleccionados[tipoProceso]) {
        window.procesosSeleccionados[tipoProceso].modoTallas = 'para_todas';
    }
    
    // Abrir modal genérico (que tiene ubicaciones, observaciones y fotos)
    window.abrirModalProcesoGenerico(tipoProceso);
    
    // Auto-asignar todas las tallas
    setTimeout(function() {
        if (typeof window.aplicarProcesoParaTodasTallas === 'function') {
            window.aplicarProcesoParaTodasTallas();
        }
        
        // Ocultar la sección de botones de tallas ya que son "para todas"
        const btnTodas = document.getElementById('btn-aplicar-todas-tallas');
        const btnEditar = document.getElementById('btn-editar-tallas-especificas');
        if (btnTodas) btnTodas.style.display = 'none';
        if (btnEditar) btnEditar.style.display = 'none';
    }, 100);
};

/**
 * Modo "Por Tallas": Abre el modal dedicado de proceso por tallas.
 */
window.seleccionarModoProcesoTallas = function() {
    if (!procesoEnSelector) return;
    
    const tipoProceso = procesoEnSelector;
    
    // Cerrar selector sin cancelar  
    const modal = document.getElementById('modal-selector-modo-proceso');
    if (modal) modal.style.display = 'none';
    procesoEnSelector = null;
    
    // Guardar el modo seleccionado en el proceso
    if (window.procesosSeleccionados[tipoProceso]) {
        window.procesosSeleccionados[tipoProceso].modoTallas = 'por_tallas';
    }
    
    // Abrir modal dedicado de proceso por tallas
    if (typeof window.abrirModalProcesoPorTallas === 'function') {
        window.abrirModalProcesoPorTallas(tipoProceso);
    } else {
        console.error('[selector-modo] abrirModalProcesoPorTallas no encontrada');
    }
};
