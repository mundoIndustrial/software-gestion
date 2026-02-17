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
    });
