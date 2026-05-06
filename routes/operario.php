<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Operario\OperarioController;
use App\Infrastructure\Http\Controllers\ControlCalidad\ControlCalidadController;

// ========================================
// RUTAS PARA OPERARIOS (CORTADOR Y COSTURERO)
// ========================================
Route::middleware(['auth', 'operario-access'])->prefix('operario')->name('operario.')->group(function () {
    Route::get('/dashboard', [OperarioController::class, 'dashboard'])->name('dashboard');
    Route::get('/mis-pedidos', [OperarioController::class, 'misPedidos'])->name('mis-pedidos');
    Route::get('/pedido/{numeroPedido}', [OperarioController::class, 'verPedido'])->name('ver-pedido');
    Route::get('/api/pedidos', [OperarioController::class, 'obtenerPedidosJson'])->name('api.pedidos');
    Route::get('/api/notificaciones/recibos', [OperarioController::class, 'listarNotificacionesRecibos'])->name('api.notificaciones.recibos');
    Route::post('/api/notificaciones/recibos/{id}/leer', [OperarioController::class, 'marcarNotificacionReciboLeida'])->name('api.notificaciones.recibos.leer');
    Route::post('/api/notificaciones/recibos/leer-todas', [OperarioController::class, 'marcarTodasNotificacionesRecibosLeidas'])->name('api.notificaciones.recibos.leer-todas');
    Route::get('/api/pedido/{numeroPedido}', [OperarioController::class, 'obtenerDatosRecibosOperario'])->name('api.pedido');
    Route::post('/api/novedades/crear', [OperarioController::class, 'crearNovedad'])->name('api.novedades.crear');
    Route::delete('/api/novedades/{id}', [OperarioController::class, 'eliminarNovedad'])->name('api.novedades.eliminar');
    Route::put('/api/novedades/{id}', [OperarioController::class, 'actualizarNovedad'])->name('api.novedades.actualizar');
    Route::get('/api/novedades/{numeroPedido}/{prendaId}', [OperarioController::class, 'obtenerNovedadesPrenda'])->name('api.novedades.prenda');
    Route::get('/api/novedades/{numeroPedido}', [OperarioController::class, 'obtenerNovedades'])->name('api.novedades');
    Route::post('/buscar', [OperarioController::class, 'buscarPedido'])->name('buscar');
    Route::post('/reportar-pendiente', [OperarioController::class, 'reportarPendiente'])->name('reportar-pendiente');
    Route::post('/api/completar-proceso/{numeroPedido}', [OperarioController::class, 'completarProceso'])->name('api.completar-proceso');
    Route::post('/api/recibos/{idRecibo}/completar', [OperarioController::class, 'completarRecibo'])->name('api.recibos.completar');
    Route::post('/api/recibos/{idRecibo}/completar-corte-sobremedida', [OperarioController::class, 'completarReciboCorteSobremedida'])->name('api.recibos.completar-corte-sobremedida');
    Route::delete('/api/recibos/{idRecibo}/deshacer', [OperarioController::class, 'deshacerRecibo'])->name('api.recibos.deshacer');
    Route::delete('/api/parciales/{id}/deshacer', [OperarioController::class, 'deshacerParcial'])->name('api.parciales.deshacer');
    Route::get('/api/recibos/{idRecibo}/distribucion', [OperarioController::class, 'obtenerDistribucionRecibo'])->name('api.recibos.distribucion');
    Route::get('/api/recibos-procesos/observacion', [OperarioController::class, 'obtenerObservacionReciboProceso'])->name('api.recibos-procesos.observacion.obtener');
    Route::get('/api/recibos/control-calidad/{tipoRecibo}', [OperarioController::class, 'obtenerRecibosControlCalidad'])->name('api.recibos.control-calidad');
    Route::get('/api/recibos/{idRecibo}/distribucion-control-calidad', [OperarioController::class, 'obtenerDistribucionControlCalidad'])->name('api.recibos.distribucion-cc');
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
