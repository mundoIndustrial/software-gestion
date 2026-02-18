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
    });
