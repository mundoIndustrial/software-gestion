/**
 * PayloadNormalizer Initializer
 * 
 * Este archivo solo verifica que PayloadNormalizer esté disponible
 */

console.log('[PayloadNormalizer-Init] Verificando disponibilidad...');

if (window.PayloadNormalizer) {
    console.log('[PayloadNormalizer-Init] PayloadNormalizer existe');
    console.log('[PayloadNormalizer-Init] Métodos disponibles:', Object.keys(window.PayloadNormalizer));
    
    if (typeof window.PayloadNormalizer.normalizar === 'function') {
    } else {
    }
} else {
    console.error('[PayloadNormalizer-Init]  PayloadNormalizer NO existe en window');
}

console.log('[PayloadNormalizer-Init] Inicialización completada');
