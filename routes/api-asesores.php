<?php

use App\Infrastructure\Http\Controllers\Asesores\AsesoresDashboardController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresNotificacionesController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidoDocumentosController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosCommandController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresPedidosQueryController;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresRealtimePedidosController;
use App\Infrastructure\Http\Controllers\Asesores\CotizacionesFiltrosController;
use App\Infrastructure\Http\Controllers\Asesores\EppsPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\EntregasDespachoController;
use App\Infrastructure\Http\Controllers\Asesores\ObtenerClientesAutocompleteController;
use App\Infrastructure\Http\Controllers\Asesores\ObtenerPrendasAutocompleteController;
use App\Infrastructure\Http\Controllers\Asesores\ObservacionesDespachoController;
use App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoBorradorController;
use App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\Pedidos\ValidarPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\PedidosController;
use App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionViewController;
use App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\ReciboController;
use App\Infrastructure\Http\Controllers\Asesores\TelasColoresApiController;
use App\Infrastructure\Http\Controllers\Asesores\VariantesPrendaController;
use App\Infrastructure\Http\Controllers\CotizacionController;
use App\Infrastructure\Http\Controllers\CotizacionEppController;
use App\Infrastructure\Http\Controllers\Cotizaciones\ImagenBorradorController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use App\Infrastructure\Http\Controllers\PrendaEditorController;
use Illuminate\Support\Facades\Route;

const COTIZACIONES_ID_ROUTE = '/cotizaciones/{id}';

function registerAsesoresDashboardYPendientesRoutes(): void
{
    Route::get('/dashboard-data', [AsesoresDashboardController::class, 'getDashboardData'])
        ->name('dashboard-data');

    Route::get('/pendientes-asesor', [AsesoresPedidosQueryController::class, 'obtenerPendientesAsesor'])
        ->name('pendientes.index');
    Route::get('/buscar-pedidos', [AsesoresPedidosQueryController::class, 'buscarPedidosAsesor'])
        ->name('pedidos.buscar');
    Route::get('/conteo-pendientes-asesor', [AsesoresPedidosQueryController::class, 'contarPendientesAsesor'])
        ->name('pendientes.count');
    Route::get('/conteo-pedidos-devueltos', [AsesoresPedidosQueryController::class, 'contarPedidosDevueltos'])
        ->name('pedidos.devueltos.count');
    Route::get('/conteo-pedidos-produccion', [AsesoresPedidosQueryController::class, 'contarPedidosProduccion'])
        ->name('pedidos.produccion.count');
    Route::get('/conteo-pendientes-logo', [AsesoresPedidosQueryController::class, 'contarPendientesLogo'])
        ->name('pendientes-logo.count');
    Route::get('/conteo-revisar-prenda', [AsesoresPedidosQueryController::class, 'contarRevisarPrenda'])
        ->name('revisar-prenda.count');
    Route::get('/diseños-pendientes-logo', [AsesoresPedidosQueryController::class, 'obtenerDiseñosPendientesLogo'])
        ->name('diseños-pendientes-logo');
    Route::get('/obtener-diseños-proceso/{procesoId}', [AsesoresPedidosQueryController::class, 'obtenerDiseñosProceso'])
        ->name('obtener-diseños-proceso')
        ->whereNumber('procesoId');
    Route::get('/obtener-recibo-datos/{pedidoId}/{prendaId}', [AsesoresPedidosQueryController::class, 'obtenerDatosReciboDesdeAsesor'])
        ->name('obtener-recibo-datos')
        ->whereNumber('pedidoId')
        ->whereNumber('prendaId');
    Route::get('/recibos-procesos/observacion', [AsesoresPedidosQueryController::class, 'obtenerObservacionReciboProceso'])
        ->name('recibos-procesos.observacion');
    Route::post('/confirmar-diseño-logo', [AsesoresPedidosQueryController::class, 'confirmarDiseñoLogo'])
        ->name('confirmar-diseño-logo');
    Route::post('/devolver-diseño-logo', [AsesoresPedidosQueryController::class, 'devolverDiseñoLogo'])
        ->name('devolver-diseño-logo');
    Route::get('/pendientes/{id}/notas', [AsesoresPedidosQueryController::class, 'obtenerNotasPedido'])
        ->whereNumber('id')
        ->name('pendientes.notas');
    Route::get('/pedidos/next-pedido', [AsesoresPedidosQueryController::class, 'getNextPedido'])
        ->name('pedidos.next');
}

function registerAsesoresPedidosCreacionRoutes(): void
{
    Route::post('/pedidos/crear', [CrearPedidoController::class, 'crearPedido'])
        ->name('pedidos.crear');
    Route::post('/pedidos/validar', [ValidarPedidoController::class, 'validarPedido'])
        ->name('pedidos.validar');
    Route::post('/pedidos/borrador', [CrearPedidoBorradorController::class, 'guardarBorrador'])
        ->name('pedidos.guardar-borrador');
    Route::match(['put', 'post'], '/pedidos/{pedidoId}/borrador', [CrearPedidoBorradorController::class, 'actualizarBorrador'])
        ->whereNumber('pedidoId')
        ->name('pedidos.actualizar-borrador');
    Route::match(['put', 'post'], '/pedidos/{pedidoId}/actualizar', [CrearPedidoBorradorController::class, 'actualizarBorrador'])
        ->whereNumber('pedidoId')
        ->name('pedidos.actualizar-borrador-legacy');
}

function registerAsesoresCotizacionesRoutes(): void
{
    Route::get('/cotizaciones/filtros/valores', [CotizacionesFiltrosController::class, 'valores'])
        ->name('cotizaciones.filtros.valores');
    Route::post('/cotizaciones', [CotizacionController::class, 'store'])
        ->name('cotizaciones.store');
    Route::post('/cotizaciones-epp', [CotizacionEppController::class, 'store'])
        ->name('cotizaciones-epp.store');
    Route::get(COTIZACIONES_ID_ROUTE, [CotizacionController::class, 'show'])
        ->whereNumber('id')
        ->name('cotizaciones.show');
    Route::put(COTIZACIONES_ID_ROUTE, [CotizacionController::class, 'update'])
        ->whereNumber('id')
        ->name('cotizaciones.update');
    Route::delete(COTIZACIONES_ID_ROUTE, [CotizacionController::class, 'destroy'])
        ->whereNumber('id')
        ->name('cotizaciones.destroy');
    Route::delete('/cotizaciones/{id}/borrador', [CotizacionController::class, 'destroyBorrador'])
        ->whereNumber('id')
        ->name('cotizaciones.destroy-borrador');
    Route::post('/cotizaciones/{id}/anular', [CotizacionController::class, 'anularCotizacion'])
        ->whereNumber('id')
        ->name('cotizaciones.anular');
    Route::post('/cotizaciones/{id}/imagenes', [CotizacionController::class, 'subirImagen'])
        ->whereNumber('id')
        ->name('cotizaciones.subir-imagen');
    Route::delete('/cotizaciones/{id}/imagenes', [CotizacionController::class, 'eliminarFotoInmediatamente'])
        ->whereNumber('id')
        ->name('cotizaciones.eliminar-imagen');
    Route::delete('/cotizaciones/imagenes/prenda/{id}', [ImagenBorradorController::class, 'borrarPrenda'])
        ->whereNumber('id')
        ->name('cotizaciones.imagen.borrar-prenda');
    Route::delete('/cotizaciones/imagenes/tela/{id}', [ImagenBorradorController::class, 'borrarTela'])
        ->whereNumber('id')
        ->name('cotizaciones.imagen.borrar-tela');
    Route::delete('/cotizaciones/imagenes/logo/{id}', [ImagenBorradorController::class, 'borrarLogo'])
        ->whereNumber('id')
        ->name('cotizaciones.imagen.borrar-logo');
    Route::post('/fotos/eliminar', [CotizacionController::class, 'eliminarFotoInmediatamente'])
        ->name('fotos.eliminar-inmediatamente');
}

function registerAsesoresPedidosProduccionRoutes(): void
{
    Route::get('/pedidos/{id}/editar-datos', [PedidoQueryController::class, 'obtenerDatosEdicion'])
        ->whereNumber('id')
        ->name('pedidos.editar-datos');
    Route::get('/pedidos/{id}/recibos-datos', [ReciboController::class, 'datos'])
        ->whereNumber('id')
        ->name('pedidos.recibos-datos');
    Route::get('/pedidos/{id}/factura-datos', [AsesoresPedidoDocumentosController::class, 'obtenerDatosFactura'])
        ->whereNumber('id')
        ->name('pedidos.factura-datos');
    Route::get('/pedidos-produccion', [PedidosController::class, 'index'])
        ->name('pedidos-produccion.index');
    Route::get('/pedidos-produccion/obtener-datos-cotizacion/{cotizacionId}', [PedidosProduccionViewController::class, 'obtenerDatosCotizacion'])
        ->name('pedidos-produccion.obtener-datos-cotizacion');
    Route::get('/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}', [PedidosProduccionViewController::class, 'obtenerPrendaCompleta'])
        ->name('pedidos-produccion.obtener-prenda-completa');
    Route::get('/pedidos-produccion/{id}', [PedidosController::class, 'show'])
        ->whereNumber('id')
        ->name('pedidos-produccion.show');
    Route::get('/pedidos/listar', [AsesoresPedidosQueryController::class, 'apiListar'])
        ->name('pedidos.listar');
    Route::get('/pedidos-api-listar', [AsesoresPedidosQueryController::class, 'apiListar'])
        ->name('pedidos.api-listar');
    Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', [PrendasPedidoController::class, 'obtenerDatosPrendaEdicion'])
        ->whereNumber('pedidoId')
        ->whereNumber('prendaId')
        ->name('pedidos-produccion.prenda-datos');
    Route::get('/pedidos-produccion/{pedidoId}/datos-edicion', [PrendasPedidoController::class, 'obtenerDatosEdicion'])
        ->whereNumber('pedidoId')
        ->name('pedidos-produccion.datos-edicion');
    Route::put('/pedidos-produccion/{pedidoId}/prendas/{prendaId}', [PrendasPedidoController::class, 'actualizarPrendaDesdeProduccion'])
        ->whereNumber('pedidoId')
        ->whereNumber('prendaId')
        ->name('pedidos-produccion.actualizar-prenda');
}

function registerAsesoresPedidosCommandRoutes(): void
{
    Route::put('/pedidos/{id}', [AsesoresPedidosCommandController::class, 'update'])
        ->whereNumber('id')
        ->name('pedidos.update');
    Route::delete('/pedidos/borradores/{id}', [AsesoresPedidosCommandController::class, 'destroyBorrador'])
        ->whereNumber('id')
        ->name('pedidos.borradores.destroy');
    Route::delete('/pedidos/{id}', [AsesoresPedidosCommandController::class, 'destroy'])
        ->whereNumber('id')
        ->name('pedidos.destroy');
    Route::post('/pedidos/{id}/confirmar-correccion', [AsesoresPedidosCommandController::class, 'confirmarCorreccion'])
        ->whereNumber('id')
        ->name('pedidos.confirmar-correccion');
    Route::delete('/pedidos-produccion/{id}', [PedidosController::class, 'destroy'])
        ->whereNumber('id')
        ->name('pedidos-produccion.destroy');
    Route::post('/pedidos/{pedidoId}/agregar-prenda-simple', [AsesoresPedidosCommandController::class, 'agregarPrendaSimple'])
        ->whereNumber('pedidoId')
        ->name('pedidos.agregar-prenda-simple');
    Route::post('/pedidos/{id}/agregar-prenda', [PrendasPedidoController::class, 'agregarPrendaCompleta'])
        ->whereNumber('id')
        ->name('pedidos.agregar-prenda');
    Route::post('/pedidos/{id}/actualizar-prenda', [PrendasPedidoController::class, 'actualizarPrendaCompleta'])
        ->whereNumber('id')
        ->name('pedidos.actualizar-prenda');
    Route::post('/pedidos/{id}/eliminar-prenda', [PrendasPedidoController::class, 'eliminarPrenda'])
        ->whereNumber('id')
        ->name('pedidos.eliminar-prenda');
    Route::post('/pedidos/{id}/eliminar-epp', [EppsPedidoController::class, 'eliminarEpp'])
        ->whereNumber('id')
        ->name('pedidos.eliminar-epp');
    Route::post('/pedidos/{id}/homologar-epp', [EppsPedidoController::class, 'homologarEpp'])
        ->whereNumber('id')
        ->name('pedidos.homologar-epp');
    Route::put('/pedidos/{pedidoId}/prendas/{prendaId}/variante', [VariantesPrendaController::class, 'actualizarVariantePrend'])
        ->whereNumber('pedidoId')
        ->whereNumber('prendaId')
        ->name('pedidos.actualizar-variante-prenda');
}

function registerAsesoresCatalogosRoutes(): void
{
    Route::get('/telas', [TelasColoresApiController::class, 'getTelas'])
        ->name('catalogos.telas');
    Route::get('/colores', [TelasColoresApiController::class, 'getColores'])
        ->name('catalogos.colores');
    Route::get('/prendas/autocomplete', [ObtenerPrendasAutocompleteController::class, 'obtenerPrendas'])
        ->name('prendas.autocomplete');
    Route::get('/clientes/autocomplete', [ObtenerClientesAutocompleteController::class, 'obtenerClientes'])
        ->name('clientes.autocomplete');
    Route::get('/tipos-manga', [PrendaEditorController::class, 'tiposManga'])
        ->name('tipos-manga');
    Route::post('/tipos-manga', [PrendaEditorController::class, 'crearTipoManga'])
        ->name('tipos-manga.store');
    Route::get('/tipos-broche-boton', [PrendaEditorController::class, 'tiposBrocheBoton'])
        ->name('tipos-broche-boton');
    Route::post('/tipos-broche-boton', [PrendaEditorController::class, 'crearTipoBrocheBoton'])
        ->name('tipos-broche-boton.store');
    Route::get('/tallas-disponibles', [VariantesPrendaController::class, 'obtenerTallasDisponibles'])
        ->name('tallas-disponibles');
}

function registerAsesoresNotificacionesRoutes(): void
{
    Route::get('/notificaciones', [AsesoresNotificacionesController::class, 'getNotifications'])
        ->name('notificaciones.index');
    Route::post('/notificaciones/marcar-todas-leidas', [AsesoresNotificacionesController::class, 'markAllAsRead'])
        ->name('notificaciones.mark-all-read');
    Route::post('/notificaciones/{notificationId}/marcar-leida', [AsesoresNotificacionesController::class, 'markNotificationAsRead'])
        ->whereNumber('notificationId')
        ->name('notificaciones.mark-read');
}

function registerAsesoresObservacionesDespachoRoutes(): void
{
    Route::post('/pedidos/observaciones-despacho/resumen', [ObservacionesDespachoController::class, 'resumen'])
        ->name('observaciones.resumen');
    Route::get('/pedidos/{id}/observaciones-despacho', [ObservacionesDespachoController::class, 'obtener'])
        ->whereNumber('id')
        ->name('observaciones.obtener');
    Route::post('/pedidos/{id}/observaciones-despacho', [ObservacionesDespachoController::class, 'guardar'])
        ->whereNumber('id')
        ->name('observaciones.guardar');
    Route::put('/pedidos/{id}/observaciones-despacho/{observacionId}', [ObservacionesDespachoController::class, 'actualizar'])
        ->whereNumber('id')
        ->name('observaciones.actualizar');
    Route::delete('/pedidos/{id}/observaciones-despacho/{observacionId}', [ObservacionesDespachoController::class, 'eliminar'])
        ->whereNumber('id')
        ->name('observaciones.eliminar');
    Route::post('/pedidos/{id}/observaciones-despacho/marcar-leidas', [ObservacionesDespachoController::class, 'marcarLeidas'])
        ->whereNumber('id')
        ->name('observaciones.marcar-leidas');
    Route::post('/pedidos/{id}/observaciones-despacho/marcar-bodega-vistas', [ObservacionesDespachoController::class, 'marcarBodegaVistas'])
        ->whereNumber('id')
        ->name('observaciones.marcar-bodega-vistas');
}

function registerAsesoresEntregasDespachoRoutes(): void
{
    Route::post('/pedidos/entregas-despacho/resumen', [EntregasDespachoController::class, 'resumen'])
        ->name('entregas-despacho.resumen');
    Route::get('/pedidos/{id}/entregas-despacho', [EntregasDespachoController::class, 'obtener'])
        ->whereNumber('id')
        ->name('entregas-despacho.obtener');
    Route::post('/pedidos/{id}/entregas-despacho/{detalleId}/marcar', [EntregasDespachoController::class, 'marcar'])
        ->whereNumber('id')
        ->whereNumber('detalleId')
        ->name('entregas-despacho.marcar');
}

function registerAsesoresRealtimeRoutes(): void
{
    Route::get('/realtime/pedidos', [AsesoresRealtimePedidosController::class, 'listar'])
        ->name('realtime.pedidos.listar');
}

Route::middleware(['web', 'auth:web', 'role:asesor,admin,supervisor_pedidos'])
    ->prefix('asesores')
    ->name('api.asesores.')
    ->group(function () {
        registerAsesoresDashboardYPendientesRoutes();
        registerAsesoresPedidosCreacionRoutes();
        registerAsesoresCotizacionesRoutes();
        registerAsesoresPedidosProduccionRoutes();
        registerAsesoresPedidosCommandRoutes();
        registerAsesoresCatalogosRoutes();
        registerAsesoresNotificacionesRoutes();
        registerAsesoresObservacionesDespachoRoutes();
        registerAsesoresEntregasDespachoRoutes();
        registerAsesoresRealtimeRoutes();
    });
