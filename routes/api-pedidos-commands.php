<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\PedidoCommandController;

/**
 * API Routes for Pedidos - Write Operations (POST, PATCH, DELETE)
 * 
 * Protected routes for creating and updating production orders (pedidos)
 * 
 * Auth: web guard with authentication
 * Middleware: web, auth
 */
Route::withoutMiddleware(['api'])
    ->middleware(['web', 'auth'])
    ->prefix('pedidos')->name('pedidos.')
    ->group(function () {
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
