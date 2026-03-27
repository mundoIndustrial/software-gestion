/**
 * AGREGADOR DE PRENDAS DESDE COTIZACIÓN - Solución independiente
 * 
 * Este módulo se encarga de agregar prendas desde cotización al gestor
 * sin depender de GestionItemsUI ni otros sistemas existentes.
 */

(function() {
    'use strict';

    /**
     * Agregar prendas desde cotización al gestor
     * @param {Array} prendas - Array de prendas desde cotización
     */
    window.agregarPrendasDesdeCotizacion = function(prendas) {
        if (!window.gestorPedidoSinCotizacion) {
            console.error('[agregador-prendas-cotizacion]  gestorPedidoSinCotizacion no disponible');
            return false;
        }

        // Limpiar gestor existente
        window.gestorPedidoSinCotizacion.limpiar();
        
        // Agregar cada prenda al gestor
        prendas.forEach((prenda, index) => {
            console.log(`[agregador-prendas-cotizacion]  Agregando prenda ${index}:`, prenda.nombre_prenda || prenda.nombre);
            window.gestorPedidoSinCotizacion.setPrendaActual(prenda);
            window.gestorPedidoSinCotizacion.agregarPrenda();
        });

        console.log('[agregador-prendas-cotizacion]  Todas las prendas agregadas al gestor');
        
        // Renderizar usando el renderizador de cotizaciones
        if (window.renderizarPrendasDesdeCotizacion) {
            console.log('[agregador-prendas-cotizacion]  Renderizando con renderizador de cotizaciones');
            window.renderizarPrendasDesdeCotizacion(window.gestorPedidoSinCotizacion.obtenerTodas());
        } else {
            console.warn('[agregador-prendas-cotizacion]  renderizador no disponible');
        }

        return true;
    };

    /**
     * Obtener prendas desde el gestor (para compatibilidad)
     * @returns {Array} Array de prendas
     */
    window.obtenerPrendasDesdeCotizacion = function() {
        if (window.gestorPedidoSinCotizacion) {
            return window.gestorPedidoSinCotizacion.obtenerTodas();
        }
        return [];
    };

    /**
     * Eliminar prenda desde el gestor (para compatibilidad)
     * @param {number} index - Índice de la prenda a eliminar
     */
    window.eliminarPrendaDesdeCotizacion = function(index) {
        console.log(`[agregador-prendas-cotizacion]  Eliminando prenda ${index}`);
        
        if (window.gestorPedidoSinCotizacion) {
            window.gestorPedidoSinCotizacion.eliminarPrenda(index);
            
            // Re-renderizar
            if (window.renderizarPrendasDesdeCotizacion) {
                window.renderizarPrendasDesdeCotizacion(window.gestorPedidoSinCotizacion.obtenerTodas());
            }
        }
    };

    /**
     * Editar prenda desde el gestor (para compatibilidad)
     * @param {number} index - Índice de la prenda a editar
     */
    window.editarPrendaDesdeCotizacion = function(index) {
        console.log(`[agregador-prendas-cotizacion] ✏️ Editando prenda ${index}`);
        // Aquí se puede agregar la lógica para editar la prenda
    };

    console.log('[agregador-prendas-cotizacion]  Módulo de agregador de prendas desde cotización cargado');
})();
