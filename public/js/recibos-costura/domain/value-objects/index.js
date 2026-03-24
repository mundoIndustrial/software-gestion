/**
 * Barrel file: Exporta todos los Value Objects del dominio
 * Uso: import { EstadoRecibo, AreaRecibo, ... } from './index.js'
 * O: import * as ValueObjects from './index.js'
 */

// Imports
// (En el navegador, estos ya estarán cargados globalmente por sus scripts)

/**
 * Validar que todos los Value Objects estén disponibles
 */
function validarValueObjects() {
    const requeridos = ['EstadoRecibo', 'AreaRecibo', 'DiasTranscurridos', 'EncargadoProceso'];
    const faltantes = requeridos.filter(vo => typeof window[vo] === 'undefined');

    if (faltantes.length > 0) {
        throw new Error(
            `Value Objects faltantes: ${faltantes.join(', ')}. ` +
            `Asegúrate de incluir sus archivos antes de este.`
        );
    }
}

// Validar en carga
validarValueObjects();

/**
 * Objeto que agrupa todos los Value Objects para acceso conveniente
 */
const ValueObjects = {
    EstadoRecibo: window.EstadoRecibo,
    AreaRecibo: window.AreaRecibo,
    DiasTranscurridos: window.DiasTranscurridos,
    EncargadoProceso: window.EncargadoProceso
};

/**
 * Exportar tanto named exports como default export
 * Permite: 
 * - import { EstadoRecibo } from './index.js'
 * - import ValueObjects from './index.js'
 * - window.ValueObjects.EstadoRecibo
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ValueObjects;
}

// Hacer disponible globalmente
window.ValueObjects = ValueObjects;
