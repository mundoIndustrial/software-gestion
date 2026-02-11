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

    console.log('[generarTarjetaPrendaReadOnly]  Prenda a renderizar:');
    console.log('[generarTarjetaPrendaReadOnly]', prenda);
    
    // Verificar que servicios estén disponibles
    console.log('[generarTarjetaPrendaReadOnly]  ¿PrendaCardService existe?', !!window.PrendaCardService);
    console.log('[generarTarjetaPrendaReadOnly]  ¿PrendaDataTransformer existe?', !!window.PrendaDataTransformer);
    console.log('[generarTarjetaPrendaReadOnly]  ¿TallasBuilder existe?', !!window.TallasBuilder);
    
    if (!window.PrendaCardService) {
        console.log('[generarTarjetaPrendaReadOnly]  ERROR: PrendaCardService NO está disponible');
        return `<div class="error">Error: servicios no cargados</div>`;
    }

    // Usar PrendaCardService para generar HTML
    try {
        console.log('[generarTarjetaPrendaReadOnly] ⚡ Llamando PrendaCardService.generar()');
        const htmlTarjeta = window.PrendaCardService.generar(prenda, indice);
        console.log('[generarTarjetaPrendaReadOnly]  HTML generado exitosamente');
        return htmlTarjeta;
    } catch (error) {
        console.error('[generarTarjetaPrendaReadOnly]  ERROR en PrendaCardService:', error);
        return `<div class="error">Error generando tarjeta: ${error.message}</div>`;
    }
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
 * Después de generar e insertar una tarjeta en el DOM, inicializar event listeners
 */
function inicializarTarjetaReadOnly(tarjeta, prenda, indice, callbacks = {}) {

    
    if (!window.PrendaCardHandlers) {

        return;
    }
    
    // Delegar toda la gestión de eventos al servicio
    window.PrendaCardHandlers.inicializar(tarjeta, prenda, indice, callbacks);
    
    // Agregar listener para actualizar tarjeta cuando las asignaciones cambien
    agregarListenerActualizacionAsignaciones(tarjeta, prenda, indice);
}

/**
 * Agregar listener para actualizar tarjeta cuando cambian las asignaciones de colores
 */
function agregarListenerActualizacionAsignaciones(tarjeta, prenda, indice) {
    console.log('[agregarListenerActualizacionAsignaciones]  Agregando listener para prenda', indice);
    
    const handleActualizacion = (event) => {
        console.log('[agregarListenerActualizacionAsignaciones]  Evento disparado para prenda', event.detail?.prendaIndex);
        
        // Solo procesar si es para esta prenda
        if (event.detail?.prendaIndex !== indice) {
            return;
        }
        
        // Reconstruir la sección de tallas
        try {
            console.log('[agregarListenerActualizacionAsignaciones]  Reconstruyendo tallas...');
            
            // Buscar la sección de tallas
            const seccionTallas = tarjeta.querySelector('.tallas-y-cantidades-section');
            if (!seccionTallas) {
                console.log('[agregarListenerActualizacionAsignaciones]  Sección de tallas no encontrada');
                return;
            }
            
            // Reconstruir usando TallasBuilder
            if (window.TallasBuilder && prenda) {
                // El prenda object tiene asignacionesColores actualizado por PrendaDataTransformer
                // Recargar desde StateManager
                prenda.asignacionesColores = (window.StateManager && window.StateManager.getAsignaciones()) || {};
                
                const htmlTallas = window.TallasBuilder.construir(prenda, indice);
                
                // Reemplazar solo la sección de tallas
                seccionTallas.outerHTML = htmlTallas;
                console.log('[agregarListenerActualizacionAsignaciones]  ✓ Sección de tallas actualizada');
            }
        } catch (error) {
            console.error('[agregarListenerActualizacionAsignaciones]  Error:', error);
        }
    };
    
    // Agregar listener
    document.addEventListener('asignacionesActualizadas', handleActualizacion);
    
    // Guardar referencia para poder remover el listener si es necesario
    if (!tarjeta.__asignacionesListener) {
        tarjeta.__asignacionesListener = handleActualizacion;
    }
}


