<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Operario\OperarioController;
use App\Infrastructure\Http\Controllers\Operario\OperarioDashboardController;
use App\Infrastructure\Http\Controllers\Operario\OperarioNovedadesController;
use App\Infrastructure\Http\Controllers\Operario\OperarioNotificacionesController;
use App\Infrastructure\Http\Controllers\Operario\OperarioPedidosController;
use App\Infrastructure\Http\Controllers\Operario\OperarioRecibosController;
use App\Infrastructure\Http\Controllers\Operario\OperarioRecibosPrestamoController;
use App\Infrastructure\Http\Controllers\ControlCalidad\ControlCalidadController;

// ========================================
// RUTAS PARA OPERARIOS (CORTADOR Y COSTURERO)
// ========================================
Route::middleware(['auth', 'operario-access'])->prefix('operario')->name('operario.')->group(function () {
    Route::get('/dashboard', [OperarioDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/recibos-prestamo', [OperarioRecibosPrestamoController::class, 'index'])->name('recibos-prestamo.index');
    Route::get('/recibos-prestamo/insumos/crear', [OperarioRecibosPrestamoController::class, 'createInsumos'])->name('recibos-prestamo.insumos.crear');
    Route::post('/recibos-prestamo/insumos', [OperarioRecibosPrestamoController::class, 'storeInsumos'])->name('recibos-prestamo.insumos.store');
    Route::get('/recibos-prestamo/insumos/{id}', [OperarioRecibosPrestamoController::class, 'showInsumos'])->name('recibos-prestamo.insumos.show');
    Route::post('/recibos-prestamo/insumos/{id}/firma/{firmante}', [OperarioRecibosPrestamoController::class, 'guardarFirmaInsumos'])->name('recibos-prestamo.insumos.firma');
    Route::post('/recibos-prestamo/insumos/{id}/anular', [OperarioRecibosPrestamoController::class, 'anularInsumos'])->name('recibos-prestamo.insumos.anular');
    Route::post('/recibos-prestamo/insumos/{id}/confirmar-entrada', [OperarioRecibosPrestamoController::class, 'confirmarEntradaInsumos'])->name('recibos-prestamo.insumos.confirmar-entrada');
    Route::get('/recibos-prestamo/contramuestra/crear', [OperarioRecibosPrestamoController::class, 'createContramuestra'])->name('recibos-prestamo.contramuestra.crear');
    Route::post('/recibos-prestamo/contramuestra', [OperarioRecibosPrestamoController::class, 'storeContramuestra'])->name('recibos-prestamo.contramuestra.store');
    Route::get('/recibos-prestamo/contramuestra/{id}', [OperarioRecibosPrestamoController::class, 'showContramuestra'])->name('recibos-prestamo.contramuestra.show');
    Route::post('/recibos-prestamo/contramuestra/{id}/firma/{firmante}', [OperarioRecibosPrestamoController::class, 'guardarFirmaContramuestra'])->name('recibos-prestamo.contramuestra.firma');
    Route::post('/recibos-prestamo/contramuestra/{id}/anular', [OperarioRecibosPrestamoController::class, 'anularContramuestra'])->name('recibos-prestamo.contramuestra.anular');
    Route::post('/recibos-prestamo/contramuestra/{id}/confirmar-entrada', [OperarioRecibosPrestamoController::class, 'confirmarEntradaContramuestra'])->name('recibos-prestamo.contramuestra.confirmar-entrada');
    Route::get('/mis-pedidos', [OperarioController::class, 'misPedidos'])->name('mis-pedidos');
    Route::get('/pedido/{numeroPedido}', [OperarioController::class, 'verPedido'])->name('ver-pedido');
    Route::get('/api/pedidos', [OperarioController::class, 'obtenerPedidosJson'])->name('api.pedidos');
    Route::get('/api/notificaciones/recibos', [OperarioNotificacionesController::class, 'listarNotificacionesRecibos'])->name('api.notificaciones.recibos');
    Route::post('/api/notificaciones/recibos/{id}/leer', [OperarioNotificacionesController::class, 'marcarNotificacionReciboLeida'])->name('api.notificaciones.recibos.leer');
    Route::post('/api/notificaciones/recibos/leer-todas', [OperarioNotificacionesController::class, 'marcarTodasNotificacionesRecibosLeidas'])->name('api.notificaciones.recibos.leer-todas');
    Route::get('/api/pedido/{numeroPedido}', [OperarioController::class, 'obtenerDatosRecibosOperario'])->name('api.pedido');
    Route::get('/api/prenda-bodega/{prendaBodegaId}', [OperarioController::class, 'obtenerDatosPrendaBodega'])->name('api.prenda-bodega');
    Route::post('/api/novedades/crear', [OperarioNovedadesController::class, 'crearNovedad'])->name('api.novedades.crear');
    Route::get('/api/novedades/bodega/{reciboId}/{prendaBodegaId}', [OperarioNovedadesController::class, 'obtenerNovedadesBodega'])->name('api.novedades.bodega');
    Route::post('/api/novedades/bodega/crear', [OperarioNovedadesController::class, 'crearNovedadBodega'])->name('api.novedades.bodega.crear');
    Route::delete('/api/novedades/{id}', [OperarioNovedadesController::class, 'eliminarNovedad'])->name('api.novedades.eliminar');
    Route::put('/api/novedades/{id}', [OperarioNovedadesController::class, 'actualizarNovedad'])->name('api.novedades.actualizar');
    Route::get('/api/novedades/{numeroPedido}/{prendaId}', [OperarioNovedadesController::class, 'obtenerNovedadesPrenda'])->name('api.novedades.prenda');
    Route::get('/api/novedades/{numeroPedido}', [OperarioNovedadesController::class, 'obtenerNovedades'])->name('api.novedades');
    Route::post('/buscar', [OperarioPedidosController::class, 'buscarPedido'])->name('buscar');
    Route::post('/reportar-pendiente', [OperarioPedidosController::class, 'reportarPendiente'])->name('reportar-pendiente');
    Route::post('/api/completar-proceso/{numeroPedido}', [OperarioController::class, 'completarProceso'])->name('api.completar-proceso');
    Route::post('/api/recibos/{idRecibo}/completar', [OperarioRecibosController::class, 'completarRecibo'])->name('api.recibos.completar');
    Route::post('/api/recibos/{idRecibo}/completar-corte-sobremedida', [OperarioRecibosController::class, 'completarReciboCorteSobremedida'])->name('api.recibos.completar-corte-sobremedida');
    Route::delete('/api/recibos/{idRecibo}/deshacer', [OperarioRecibosController::class, 'deshacerRecibo'])->name('api.recibos.deshacer');
    Route::delete('/api/parciales/{id}/deshacer', [OperarioRecibosController::class, 'deshacerParcial'])->name('api.parciales.deshacer');
    Route::patch('/api/parciales/{id}/anular', [OperarioRecibosController::class, 'anularParcial'])->name('api.parciales.anular');
    Route::get('/api/recibos/{idRecibo}/distribucion', [OperarioRecibosController::class, 'obtenerDistribucionRecibo'])->name('api.recibos.distribucion');
    Route::get('/api/recibos-procesos/observacion', [OperarioRecibosController::class, 'obtenerObservacionReciboProceso'])->name('api.recibos-procesos.observacion.obtener');
    Route::get('/api/recibos/control-calidad/{tipoRecibo}', [OperarioDashboardController::class, 'obtenerRecibosControlCalidad'])->name('api.recibos.control-calidad');
    Route::get('/api/recibos/{idRecibo}/distribucion-control-calidad', [OperarioDashboardController::class, 'obtenerDistribucionControlCalidad'])->name('api.recibos.distribucion-cc');
    Route::get('/api/recibos/{idRecibo}/tallas-control-calidad', [OperarioDashboardController::class, 'obtenerTallasControlCalidad'])->name('api.recibos.tallas-cc');
    Route::get('/debug', [OperarioController::class, 'debug'])->name('debug');
    Route::get('/debug/prendas-recibos', [OperarioController::class, 'debugPrendasRecibos'])->name('debug.prendas-recibos');
});

// ========================================
// RUTAS PARA CONTROL DE CALIDAD
// ========================================
Route::middleware(['auth', 'control-calidad-access'])->prefix('control-calidad')->name('control-calidad.')->group(function () {
    Route::get('/dashboard', [ControlCalidadController::class, 'dashboard'])->name('dashboard');
    Route::get('/reporte-seguimiento', [ControlCalidadController::class, 'reporteSeguimiento'])->name('reporte-seguimiento');
    Route::get('/pedido/{numeroPedido}', [ControlCalidadController::class, 'verPedido'])->name('ver-pedido');
    Route::get('/api/pedido/{numeroPedido}', [ControlCalidadController::class, 'getPedidoData'])->name('api.pedido');
    Route::post('/api/recibos/{idRecibo}/completar', [ControlCalidadController::class, 'completarRecibo'])->name('api.recibos.completar.cc');
    Route::delete('/api/recibos/{idRecibo}/deshacer', [ControlCalidadController::class, 'deshacerRecibo'])->name('api.recibos.deshacer.cc');
    Route::get('/api/recibos/{idRecibo}/distribucion-parciales', [ControlCalidadController::class, 'obtenerDistribucionParciales'])->name('api.recibos.distribucion-parciales');
});

// ========================================
// API ROUTES FOR OPERARIO (PUBLIC - Sin autenticación)
// ========================================
Route::prefix('operario/api')->name('operario.api.')->middleware([])->group(function () {
    Route::get('pedido/{numeroPedido}', [\App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'getPedidoData'])
        ->name('pedido-data');
});
