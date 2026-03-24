<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\PrendaController;
use App\Infrastructure\Http\Controllers\PedidoCommandController;

/**
 * API Routes for Prendas - Escritura (POST, PATCH, DELETE)
 * Rutas protegidas para crear, actualizar y eliminar prendas
 * 
 * Auth: web guard con autenticación
 * Middleware: web, auth
 */
Route::withoutMiddleware(['api'])
    ->middleware(['web', 'auth'])
    ->group(function () {
    Route::apiResource('prendas', PrendaController::class, ['only' => ['store', 'update', 'destroy']]);
});

/**
 * API Routes for Pedidos - Escritura (POST, PATCH, DELETE)
 * Rutas protegidas para crear y actualizar pedidos
 * 
 * Auth: web guard con autenticación
 * Middleware: web, auth
 */
Route::withoutMiddleware(['api'])
    ->middleware(['web', 'auth'])
    ->prefix('pedidos')->name('pedidos.')
    ->group(function () {
    Route::post('/', [PedidoCommandController::class, 'store'])
        ->name('crear');
    
    Route::patch('{id}/confirmar', [PedidoCommandController::class, 'confirmar'])
        ->name('confirmar');
    
    Route::patch('{id}/actualizar-descripcion', [PedidoCommandController::class, 'actualizarDescripcion'])
        ->name('actualizar-descripcion');
    
    Route::patch('{id}/actualizar-estado', [PedidoCommandController::class, 'actualizarEstado'])
        ->name('actualizar-estado');
    
    Route::delete('{id}/cancelar', [PedidoCommandController::class, 'cancelar'])
        ->name('cancelar');
    
    Route::post('{id}/calcular-fecha-entrega', [PedidoCommandController::class, 'calcularFechaEntrega'])
        ->name('calcular-fecha-entrega');
});
