/**
 * TARJETA DE PRENDA - SOLO LECTURA (Read-Only) - REFACTORIZADA
 * 
 * Componente visual para mostrar una prenda en el formulario de pedidos
 * Utiliza arquitectura modular con servicios:
 * - PrendaCardService: Generación de HTML
 * - PrendaCardHandlers: Gestión de eventos
 */

/**
 * Generar HTML de tarjeta de prenda (solo lectura)
 * Delega a servicios modulares para máxima reutilización
 * @param {Object} prenda - Objeto de prenda
 * @param {number} indice - Índice de la prenda en la lista
 * @returns {string} HTML de la tarjeta
 */
function generarTarjetaPrendaReadOnly(prenda, indice) {
    console.log(' [PRENDA-CARD-READONLY] Generando tarjeta para prenda índice:', indice);
    
    // Verificar que servicios estén disponibles
    if (!window.PrendaCardService) {
        console.error(' ✗ PrendaCardService no disponible');
        return `<div class="error">Error: servicios no cargados</div>`;
    }

    // Usar PrendaCardService para generar HTML
    const htmlTarjeta = window.PrendaCardService.generar(prenda, indice);
    
    console.log(' [PRENDA-CARD-READONLY] HTML generado correctamente');
    return htmlTarjeta;
}

// Inicializar handlers cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (window.PrendaCardHandlers) {
            window.PrendaCardHandlers.inicializar();
        }
    });
} else {
    if (window.PrendaCardHandlers) {
        window.PrendaCardHandlers.inicializar();
    }
}



/**
 * Manejador de tarjetas - Delega a servicio centralizado
 * 
 * Después de generar y insertar una tarjeta en el DOM, inicializar event listeners
 */
function inicializarTarjetaReadOnly(tarjeta, prenda, indice, callbacks = {}) {
    console.log(' [PRENDA-CARD-READONLY] Inicializando tarjeta índice:', indice);
    
    if (!window.PrendaCardHandlers) {
        console.error(' ✗ PrendaCardHandlers no disponible');
        return;
    }
    
    // Delegar toda la gestión de eventos al servicio
    window.PrendaCardHandlers.inicializar(tarjeta, prenda, indice, callbacks);
}
console.log(' [PRENDA-CARD-READONLY] Componente refactorizado - Versión modular con servicios');
console.log(' Usa: PrendaCardService (HTML), PrendaCardHandlers (eventos)');