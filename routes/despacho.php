<?php

use App\Infrastructure\Http\Controllers\Despacho\DespachoControlController;
use App\Infrastructure\Http\Controllers\Despacho\DespachoNotasController;
use App\Infrastructure\Http\Controllers\Despacho\DespachoNotificacionesController;
use App\Infrastructure\Http\Controllers\Despacho\DespachoObservacionesController;
use App\Infrastructure\Http\Controllers\Despacho\DespachoPendientesController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas del modulo DESPACHO
 *
 * Prefijo: /despacho
 */

Route::prefix('despacho')
    ->middleware(['auth', 'check.despacho.role'])
    ->group(function () {
        Route::get('/', [DespachoControlController::class, 'index'])
            ->name('despacho.index');

        Route::get('/{pedido}', [DespachoControlController::class, 'show'])
            ->name('despacho.show')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/guardar', [DespachoControlController::class, 'guardarDespacho'])
            ->name('despacho.guardar')
            ->where('pedido', '[0-9]+');

        Route::get('/{pedido}/print', [DespachoControlController::class, 'printDespacho'])
            ->name('despacho.print')
            ->where('pedido', '[0-9]+');

        Route::get('/{pedido}/obtener-despachos', [DespachoControlController::class, 'obtenerDespachos'])
            ->name('despacho.obtener')
            ->where('pedido', '[0-9]+');

        Route::get('/{pedido}/factura-datos', [DespachoControlController::class, 'obtenerFacturaDatos'])
            ->name('despacho.factura-datos')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/marcar-entregado', [DespachoControlController::class, 'marcarEntregado'])
            ->name('despacho.marcar-entregado')
            ->where('pedido', '[0-9]+');

        Route::get('/{pedido}/estado-entregas', [DespachoControlController::class, 'obtenerEstadoEntregas'])
            ->name('despacho.estado-entregas')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/entregar-todo', [DespachoControlController::class, 'entregarTodo'])
            ->name('despacho.entregar-todo')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/deshacer-entregado', [DespachoControlController::class, 'deshacerEntregado'])
            ->name('despacho.deshacer-entregado')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/ajustes-cantidad', [DespachoControlController::class, 'guardarAjusteCantidad'])
            ->name('despacho.ajustes-cantidad.guardar')
            ->where('pedido', '[0-9]+');

        Route::post('/observaciones/resumen', [DespachoObservacionesController::class, 'resumenObservaciones'])
            ->name('despacho.observaciones.resumen');

        Route::post('/{pedido}/observaciones/marcar-leidas', [DespachoObservacionesController::class, 'marcarLeidas'])
            ->name('despacho.observaciones.marcar-leidas')
            ->where('pedido', '[0-9]+');

        Route::get('/{pedido}/observaciones', [DespachoObservacionesController::class, 'obtenerObservaciones'])
            ->name('despacho.observaciones.obtener')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/observaciones/guardar', [DespachoObservacionesController::class, 'guardarObservacion'])
            ->name('despacho.observaciones.guardar')
            ->where('pedido', '[0-9]+');

        Route::post('/{pedido}/observaciones/{observacionId}/actualizar', [DespachoObservacionesController::class, 'actualizarObservacion'])
            ->name('despacho.observaciones.actualizar')
            ->where('pedido', '[0-9]+')
            ->where('observacionId', '[A-Za-z0-9\-]+');

        Route::post('/{pedido}/observaciones/{observacionId}/eliminar', [DespachoObservacionesController::class, 'eliminarObservacion'])
            ->name('despacho.observaciones.eliminar')
            ->where('pedido', '[0-9]+')
            ->where('observacionId', '[A-Za-z0-9\-]+');

        Route::get('/pendientes', [DespachoPendientesController::class, 'pendientesUnificados'])
            ->name('despacho.pendientes');

        Route::get('/entregados', [DespachoPendientesController::class, 'entregados'])
            ->name('despacho.entregados');
        
        Route::get('/anulados', [DespachoPendientesController::class, 'anulados'])
            ->name('despacho.anulados');

        Route::get('/historial-pendientes', [DespachoPendientesController::class, 'historialPendientes'])
            ->name('despacho.historial-pendientes');

        Route::get('/api/pendientes-costura', [DespachoPendientesController::class, 'obtenerPendientesCostura'])
            ->name('despacho.api.pendientes-costura');

        Route::get('/api/pendientes-epp', [DespachoPendientesController::class, 'obtenerPendientesEpp'])
            ->name('despacho.api.pendientes-epp');

        Route::get('/api/pendientes-todos', [DespachoPendientesController::class, 'obtenerPendientesUnificados'])
            ->name('despacho.api.pendientes-todos');

        Route::get('/api/todos-pedidos', [DespachoPendientesController::class, 'obtenerTodosLosPedidos'])
            ->name('despacho.api.todos-pedidos');

        Route::get('/api/entregados', [DespachoPendientesController::class, 'obtenerEntregados'])
            ->name('despacho.api.entregados');
        
        Route::get('/api/anulados', [DespachoPendientesController::class, 'obtenerAnulados'])
            ->name('despacho.api.anulados');

        Route::get('/api/historial-pendientes', [DespachoPendientesController::class, 'obtenerHistorialPendientes'])
            ->name('despacho.api.historial-pendientes');

        Route::get('/api/test', function () {
            return response()->json([
                'success' => true,
                'message' => 'Test JSON working',
                'data' => ['test' => 'value'],
                'timestamp' => now()->toDateTimeString(),
            ]);
        });

        Route::post('/notas/obtener', [DespachoNotasController::class, 'obtenerNotasBodega'])
            ->name('despacho.notas.obtener');

        Route::post('/notas/guardar', [DespachoNotasController::class, 'guardarNotaBodega'])
            ->name('despacho.notas.guardar');

        Route::post('/notas/{notaId}/actualizar', [DespachoNotasController::class, 'actualizarNotaBodega'])
            ->name('despacho.notas.actualizar')
            ->where('notaId', '[0-9]+');

        Route::post('/notas/{notaId}/eliminar', [DespachoNotasController::class, 'eliminarNotaBodega'])
            ->name('despacho.notas.eliminar')
            ->where('notaId', '[0-9]+');

        Route::get('/pendientes/{id}', [DespachoPendientesController::class, 'showPendienteUnificado'])
            ->name('despacho.pendientes-show')
            ->where('id', '[0-9]+');

        Route::get('/historial-pendientes/{id}', [DespachoPendientesController::class, 'showHistorialPendiente'])
            ->name('despacho.historial-pendientes-show')
            ->where('id', '[0-9]+');

        Route::get('/notificaciones', [DespachoNotificacionesController::class, 'getNotifications'])
            ->name('despacho.notificaciones');

        Route::post('/notificaciones/marcar-todas-leidas', [DespachoNotificacionesController::class, 'markAllNotificationsAsRead'])
            ->name('despacho.notificaciones.marcar-todas');

        Route::post('/notificaciones/news/{newsId}/toggle-visto', [DespachoNotificacionesController::class, 'toggleNewsVisto'])
            ->name('despacho.notificaciones.toggle-news');

        Route::post('/notificaciones/pedido/{pedidoId}/toggle-visto', [DespachoNotificacionesController::class, 'togglePedidoVisto'])
            ->name('despacho.notificaciones.toggle-pedido');
    });
