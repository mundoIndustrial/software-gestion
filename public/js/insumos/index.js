/**
 * Bootstrap ligero para la vista de Insumos.
 * Registra utilidades compartidas en window.insumosHandlers.utilities.
 */
import {
    showToast,
    debounce,
    inicializarFestivos,
    calcularDemoraAsync,
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog
} from './utilities.js';

window.insumosHandlers = window.insumosHandlers || {};
window.insumosHandlers.utilities = {
    showToast,
    debounce,
    inicializarFestivos,
    calcularDemoraAsync,
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog,
};

export {
    showToast,
    debounce,
    inicializarFestivos,
    calcularDemoraAsync,
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog
};
