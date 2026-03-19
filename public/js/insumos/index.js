/**
 * INDEX.JS - Punto de entrada para todos los módulos de Insumos
 * Importa y expone todas las funciones necesarias
 */

// Importar utilidades
import {
    showToast,
    debounce,
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog
} from './utilities.js';

// Importar modal handlers
import {
    abrirModalAnchoMetraje,
    cerrarModalAnchoMetraje,
    guardarAnchoMetraje,
    abrirModalObservaciones,
    cerrarModalObservaciones,
    actualizarReciboConAnchoMetraje
} from './modal-handlers.js';

// Importar event listeners
import {
    initializeEventListeners,
    actualizarDiasDemora,
    toggleRowCheck,
    guardarEstadoMarcado
} from './event-listeners.js';

// Exponer funciones globalmente para compatibilidad con HTML inline
window.showToast = showToast;
window.debounce = debounce;
window.calcularDiasLaborales = calcularDiasLaborales;
window.getColorByDias = getColorByDias;
window.sanitizeForId = sanitizeForId;
window.formatDate = formatDate;
window.copyToClipboard = copyToClipboard;
window.wait = wait;
window.showConfirmDialog = showConfirmDialog;

// Exponer modal handlers
window.abrirModalAnchoMetraje = abrirModalAnchoMetraje;
window.cerrarModalAnchoMetraje = cerrarModalAnchoMetraje;
window.guardarAnchoMetraje = guardarAnchoMetraje;
window.abrirModalObservaciones = abrirModalObservaciones;
window.cerrarModalObservaciones = cerrarModalObservaciones;
window.actualizarReciboConAnchoMetraje = actualizarReciboConAnchoMetraje;

// Exponer event listeners
window.initializeEventListeners = initializeEventListeners;
window.actualizarDiasDemora = actualizarDiasDemora;
window.toggleRowCheck = toggleRowCheck;
window.guardarEstadoMarcado = guardarEstadoMarcado;

/**
 * Inicializa la vista cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Insumos] Inicializando módulos...');
    
    // Inicializar event listeners
    initializeEventListeners();
    
    // Log de confirmación
    console.log('[Insumos] ✓ Todos los módulos inicializados');
});

// Exportar para uso en otros módulos si es necesario
export {
    showToast,
    debounce,
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog,
    abrirModalAnchoMetraje,
    cerrarModalAnchoMetraje,
    guardarAnchoMetraje,
    abrirModalObservaciones,
    cerrarModalObservaciones,
    actualizarReciboConAnchoMetraje,
    initializeEventListeners,
    actualizarDiasDemora,
    toggleRowCheck,
    guardarEstadoMarcado
};
