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
    Route::delete('/api/recibos/{idRecibo}/deshacer', [OperarioController::class, 'deshacerRecibo'])->name('api.recibos.deshacer');
    Route::get('/debug', [OperarioController::class, 'debug'])->name('debug');
    Route::get('/debug/prendas-recibos', [OperarioController::class, 'debugPrendasRecibos'])->name('debug.prendas-recibos');
});

// ========================================
// RUTAS PARA CONTROL DE CALIDAD
// ========================================
Route::middleware(['auth', 'control-calidad-access'])->prefix('control-calidad')->name('control-calidad.')->group(function () {
    Route::get('/dashboard', [ControlCalidadController::class, 'dashboard'])->name('dashboard');
    Route::get('/pedido/{numeroPedido}', [ControlCalidadController::class, 'verPedido'])->name('ver-pedido');
    Route::post('/api/recibos/{idRecibo}/completar', [ControlCalidadController::class, 'completarRecibo'])->name('api.recibos.completar');
    Route::delete('/api/recibos/{idRecibo}/deshacer', [ControlCalidadController::class, 'deshacerRecibo'])->name('api.recibos.deshacer');
});

// ========================================
// API ROUTES FOR OPERARIO (PUBLIC - Sin autenticación)
// ========================================
Route::prefix('operario')->name('operario.')->middleware([])->group(function () {
    Route::get('pedido/{numeroPedido}', [\App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'getPedidoData'])
        ->name('pedido-data');
});
