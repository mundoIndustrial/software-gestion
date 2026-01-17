<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Pedidos\Infrastructure\Http\Controllers\PedidoEppController;

/**
 * Rutas del módulo Pedidos (DDD)
 * 
 * Gestión de EPP en pedidos - Rutas RESTful
 * Prefix: /api/v1/pedidos/{pedido}/epps
 */
Route::middleware('api')->group(function () {
    Route::prefix('pedidos/{pedido}/epps')->name('pedidos.epps.')->group(function () {
        Route::get('/', [PedidoEppController::class, 'index'])
            ->name('index');
        
        Route::post('/', [PedidoEppController::class, 'store'])
            ->name('store');
        
        Route::patch('{pedidoEpp}', [PedidoEppController::class, 'update'])
            ->name('update');
        
        Route::delete('{pedidoEpp}', [PedidoEppController::class, 'destroy'])
            ->name('destroy');
        
        Route::get('exportar/json', [PedidoEppController::class, 'exportarJson'])
            ->name('exportar-json');
    });
});
