<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Despacho\DespachoController;

/**
 * Rutas del módulo DESPACHO
 * 
 * Controlador: DespachoController
 * Prefijo: /despacho
 * 
 * Responsabilidades:
 * - Visualizar pedidos listos para despacho
 * - Controlar entregas parciales (prendas + EPP)
 * - Imprimir control de entregas
 * 
 * NO crea pedidos, solo visualiza y controla entregas.
 */

Route::prefix('despacho')
    ->middleware(['auth', 'check.despacho.role'])
    ->group(function () {
        // Listar pedidos disponibles para despacho
        Route::get('/', [DespachoController::class, 'index'])
            ->name('despacho.index');

        // Mostrar detalle de despacho para un pedido
        Route::get('/{pedido}', [DespachoController::class, 'show'])
            ->name('despacho.show')
            ->where('pedido', '[0-9]+');

        // Guardar parciales de despacho (POST)
        Route::post('/{pedido}/guardar', [DespachoController::class, 'guardarDespacho'])
            ->name('despacho.guardar')
            ->where('pedido', '[0-9]+');

        // Vista de impresión del control de entregas
        Route::get('/{pedido}/print', [DespachoController::class, 'printDespacho'])
            ->name('despacho.print')
            ->where('pedido', '[0-9]+');

        // Obtener despachos guardados para un pedido
        Route::get('/{pedido}/obtener-despachos', [DespachoController::class, 'obtenerDespachos'])
            ->name('despacho.obtener')
            ->where('pedido', '[0-9]+');

        // Obtener datos de factura para un pedido
        Route::get('/{pedido}/factura-datos', [DespachoController::class, 'obtenerFacturaDatos'])
            ->name('despacho.factura-datos')
            ->where('pedido', '[0-9]+');

        // Marcar ítem como entregado
        Route::post('/{pedido}/marcar-entregado', [DespachoController::class, 'marcarEntregado'])
            ->name('despacho.marcar-entregado')
            ->where('pedido', '[0-9]+');

        // Obtener estado de entregas
        Route::get('/{pedido}/estado-entregas', [DespachoController::class, 'obtenerEstadoEntregas'])
            ->name('despacho.estado-entregas')
            ->where('pedido', '[0-9]+');

        // Marcar todos los ítems de un pedido como entregados
        Route::post('/{pedido}/entregar-todo', [DespachoController::class, 'entregarTodo'])
            ->name('despacho.entregar-todo')
            ->where('pedido', '[0-9]+');

        // Deshacer marcado como entregado
        Route::post('/{pedido}/deshacer-entregado', [DespachoController::class, 'deshacerEntregado'])
            ->name('despacho.deshacer-entregado')
            ->where('pedido', '[0-9]+');

        // ==================== OBSERVACIONES (JSON) ====================
        // Resumen de observaciones no leídas (batch)
        Route::post('/observaciones/resumen', [DespachoController::class, 'resumenObservaciones'])
            ->name('despacho.observaciones.resumen');

        // Marcar observaciones como leídas
        Route::post('/{pedido}/observaciones/marcar-leidas', [DespachoController::class, 'marcarLeidas'])
            ->name('despacho.observaciones.marcar-leidas')
            ->where('pedido', '[0-9]+');

        // Obtener observaciones del pedido
        Route::get('/{pedido}/observaciones', [DespachoController::class, 'obtenerObservaciones'])
            ->name('despacho.observaciones.obtener')
            ->where('pedido', '[0-9]+');

        // Guardar nueva observación
        Route::post('/{pedido}/observaciones/guardar', [DespachoController::class, 'guardarObservacion'])
            ->name('despacho.observaciones.guardar')
            ->where('pedido', '[0-9]+');

        // Actualizar una observación existente (por id interno en JSON)
        Route::post('/{pedido}/observaciones/{observacionId}/actualizar', [DespachoController::class, 'actualizarObservacion'])
            ->name('despacho.observaciones.actualizar')
            ->where('pedido', '[0-9]+')
            ->where('observacionId', '[A-Za-z0-9\-]+');

        // Eliminar una observación existente (por id interno en JSON)
        Route::post('/{pedido}/observaciones/{observacionId}/eliminar', [DespachoController::class, 'eliminarObservacion'])
            ->name('despacho.observaciones.eliminar')
            ->where('pedido', '[0-9]+')
            ->where('observacionId', '[A-Za-z0-9\-]+');

        // ===== RUTAS UNIFICADAS PARA DESPACHO =====
        
        // Vista unificada de pendientes (Costura + EPP)
        Route::get('/pendientes', [DespachoController::class, 'pendientesUnificados'])
            ->name('despacho.pendientes');

        // Vista de pedidos entregados
        Route::get('/entregados', [DespachoController::class, 'entregados'])
            ->name('despacho.entregados');

        // API para obtener pendientes de costura
        Route::get('/api/pendientes-costura', [DespachoController::class, 'obtenerPendientesCostura'])
            ->name('despacho.api.pendientes-costura');

        // API para obtener pendientes de EPP
        Route::get('/api/pendientes-epp', [DespachoController::class, 'obtenerPendientesEpp'])
            ->name('despacho.api.pendientes-epp');

        // API para obtener todos los pendientes unificados
        Route::get('/api/pendientes-todos', [DespachoController::class, 'obtenerPendientesUnificados'])
            ->name('despacho.api.pendientes-todos');

        // API para obtener todos los pedidos con estados solicitados
        Route::get('/api/todos-pedidos', [DespachoController::class, 'obtenerTodosLosPedidos'])
            ->name('despacho.api.todos-pedidos');

        // API para obtener pedidos entregados
        Route::get('/api/entregados', [DespachoController::class, 'obtenerEntregados'])
            ->name('despacho.api.entregados');

        // API de test para verificar JSON
        Route::get('/api/test', function() {
            return response()->json([
                'success' => true,
                'message' => 'Test JSON working',
                'data' => ['test' => 'value'],
                'timestamp' => now()->toDateTimeString()
            ]);
        });

        Route::post('/notas/obtener', [DespachoController::class, 'obtenerNotasBodega'])
            ->name('despacho.notas.obtener');

        Route::post('/notas/guardar', [DespachoController::class, 'guardarNotaBodega'])
            ->name('despacho.notas.guardar');

        Route::post('/notas/{notaId}/actualizar', [DespachoController::class, 'actualizarNotaBodega'])
            ->name('despacho.notas.actualizar')
            ->where('notaId', '[0-9]+');

        Route::post('/notas/{notaId}/eliminar', [DespachoController::class, 'eliminarNotaBodega'])
            ->name('despacho.notas.eliminar')
            ->where('notaId', '[0-9]+');

        // Mostrar detalles de pedido pendiente (unificado)
        Route::get('/pendientes/{id}', [DespachoController::class, 'showPendienteUnificado'])
            ->name('despacho.pendientes-show')
            ->where('id', '[0-9]+');

        // Notificaciones (campana)
        Route::get('/notificaciones', [DespachoController::class, 'getNotifications'])
            ->name('despacho.notificaciones');
        Route::post('/notificaciones/marcar-todas-leidas', [DespachoController::class, 'markAllNotificationsAsRead'])
            ->name('despacho.notificaciones.marcar-todas');
        Route::post('/notificaciones/news/{newsId}/toggle-visto', [DespachoController::class, 'toggleNewsVisto'])
            ->name('despacho.notificaciones.toggle-news');
        Route::post('/notificaciones/pedido/{pedidoId}/toggle-visto', [DespachoController::class, 'togglePedidoVisto'])
            ->name('despacho.notificaciones.toggle-pedido');
    });
