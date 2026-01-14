/**
 * FUNCIONES GLOBALES - Prenda Sin Cotizaci�n Tipo PRENDA [LEGADO - REFACTORIZADO]
 * 
 * Este archivo se mantiene solo como referencia hist�rica.
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
 * UBICACI�N DE SCRIPTS:
 * Ver: resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php (l�neas 164-170)
 * 
 *  MIGRACI�N COMPLETADA
 * Todas las funciones window.* han sido movidas a sus componentes respectivos.
 * Este archivo se mantiene por compatibilidad de versiones anteriores.
 */

console.warn('  [LEGADO] funciones-prenda-sin-cotizacion.js cargado. Ver componentes modulares para implementaci�n actual.');
