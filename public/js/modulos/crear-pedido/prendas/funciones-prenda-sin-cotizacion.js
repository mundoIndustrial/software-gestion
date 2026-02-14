/**
 * FUNCIONES GLOBALES - Prenda Sin Cotizacin Tipo PRENDA [LEGADO - REFACTORIZADO]
 * 
 * Este archivo se mantiene solo como referencia histrica.
 * La funcionalidad se ha refactorizado en componentes especializados:
 * 
 * COMPONENTES NUEVOS (cargados en orden):
 * 1. prenda-sin-cotizacion-core.js
 *    - inicializarGestorPrendaSinCotizacion()
 *    - crearPedidoTipoPrendaSinCotizacion()
 *    - agregarPrendaTipoPrendaSinCotizacion()
 *    - eliminarPrendaTipoPrenda()
 * 
 * 2. prenda-sin-cotizacion-tallas.js
 *    - agregarTallaPrendaTipo()
 *    - eliminarTallaPrendaTipo()
 * 
 * 3. prenda-sin-cotizacion-telas.js
 *    - agregarTelaPrendaTipo()
 *    - eliminarTelaPrendaTipo()
 *    - eliminarImagenTelaTipo()
 * 
 * 4. prenda-sin-cotizacion-imagenes.js
 *    - mostrarGaleriaImagenesPrenda()
 *    - abrirGaleriaPrendaTipo()
 *    - abrirGaleriaTexturaTipo()
 *    - eliminarImagenPrendaTipo()
 * 
 * 5. prenda-sin-cotizacion-variaciones.js
 *    - eliminarVariacionPrendaTipo()
 *    - manejarCambioVariacionPrendaTipo()
 *    - sincronizarDatosTelas()
 *    - marcarPrendaDeBodega()
 *    - actualizarOrigenPrenda()
 * 
 * UBICACIN DE SCRIPTS:
 * Ver: resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php (lneas 164-170)
 * 
 *  MIGRACIN COMPLETADA
 * Todas las funciones window.* han sido movidas a sus componentes respectivos.
 * Este archivo se mantiene por compatibilidad de versiones anteriores.
 */


