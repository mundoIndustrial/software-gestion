/**
 * DIAGNOSTICS - Lavandería
 * Archivo para diagnosticar problemas de carga
 */

console.log('[Diagnostics] Iniciando diagnósticos de lavandería...');
console.log('[Diagnostics] window.apiSearchUrl:', window.apiSearchUrl);
console.log('[Diagnostics] document.readyState:', document.readyState);

// Verificar elementos del DOM
const elementosRequeridos = [
    'loadingScreen',
    'searchMovimientosInput',
    'btnAbrirModalSalida',
    'searchRecibo',
    'btnRegistrarSalida',
    'movementsContainer',
    'modalSalida',
    'modalFirmaSalida',
    'modalVerFirma'
];

console.log('[Diagnostics] Verificando elementos del DOM:');
elementosRequeridos.forEach(id => {
    const elemento = document.getElementById(id);
    console.log(`  - ${id}: ${elemento ? '✓ Encontrado' : '✗ NO ENCONTRADO'}`);
});

// Verificar clases
const clasesRequeridas = [
    'tab-button',
    'modal-close',
    'modal'
];

console.log('[Diagnostics] Verificando clases del DOM:');
clasesRequeridas.forEach(clase => {
    const elementos = document.querySelectorAll(`.${clase}`);
    console.log(`  - .${clase}: ${elementos.length} elemento(s) encontrado(s)`);
});

console.log('[Diagnostics] Diagnósticos completados');
