/**
 * CORE - Funciones base de Prenda Sin Cotización
 * 
 * Funciones fundamentales:
 * - Inicialización del gestor
 * - Creación de pedidos
 * - Agregar/Eliminar prendas
 */

/**
 * Inicializar el gestor de prenda sin cotización tipo PRENDA
 */
window.inicializarGestorPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion = new GestorPrendaSinCotizacion();
        console.log('✅ GestorPrendaSinCotizacion inicializado');
    }
};

/**
 * Crear pedido tipo PRENDA sin cotización
 */
window.crearPedidoTipoPrendaSinCotizacion = function() {
    console.log('🎯 Iniciando creación de pedido PRENDA sin cotización');

    // Inicializar gestor si no existe
    if (!window.gestorPrendaSinCotizacion) {
        window.inicializarGestorPrendaSinCotizacion();
    }

    // Agregar primera prenda
    window.gestorPrendaSinCotizacion.agregarPrenda();

    // Renderizar UI
    window.renderizarPrendasTipoPrendaSinCotizacion();

    // Mostrar secciones pertinentes
    document.getElementById('seccion-info-prenda')?.style.setProperty('display', 'block', 'important');
    document.getElementById('seccion-prendas')?.style.setProperty('display', 'block', 'important');

    // Scroll
    document.getElementById('seccion-info-prenda')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

/**
 * Agregar una nueva prenda tipo PRENDA
 */
window.agregarPrendaTipoPrendaSinCotizacion = function() {
    // Solo permitir una prenda en el tipo de pedido PRENDA sin cotización
    if (window.gestorPrendaSinCotizacion) {
        const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
        if (prendas.length >= 1) {
            console.warn('⚠️ Solo se permite una prenda en el tipo de pedido PRENDA sin cotización');
            return;
        }
    }
    
    if (!window.gestorPrendaSinCotizacion) {
        window.inicializarGestorPrendaSinCotizacion();
    }

    window.gestorPrendaSinCotizacion.agregarPrenda();
    window.renderizarPrendasTipoPrendaSinCotizacion();
};

/**
 * Eliminar una prenda tipo PRENDA
 * @param {number} index - Índice de la prenda
 */
window.eliminarPrendaTipoPrenda = function(index) {
    Swal.fire({
        title: '¿Eliminar Prenda?',
        text: `¿Está seguro que desea eliminar la prenda ${index + 1}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.gestorPrendaSinCotizacion.eliminar(index);
            window.renderizarPrendasTipoPrendaSinCotizacion();
            Swal.fire('Eliminada', 'La prenda ha sido eliminada', 'success');
        }
    });
};

console.log('✅ [CORE] Componente prenda-sin-cotizacion-core.js cargado');
