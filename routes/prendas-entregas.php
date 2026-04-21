<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\PrendasEntregas\PrendaEntregaController;

/**
 * API Routes for Prenda Entregas (Supervisor de Pedidos)
 * Gestiona el estado de entrega de prendas
 */
Route::withoutMiddleware(['api'])
    ->middleware(['web', 'auth'])
    ->prefix('prendas-entregas')
    ->name('prendas-entregas.')
    ->group(function () {
    
    // Toggle estado de entrega de una prenda
    Route::post('{prendaPedidoId}/toggle', [PrendaEntregaController::class, 'toggleEntrega'])
        ->name('toggle');
    
    // Obtener estado de entrega de una prenda
    Route::get('{prendaPedidoId}/estado', [PrendaEntregaController::class, 'obtenerEstado'])
        ->name('estado');

    // Obtener historial de entregas parciales de una prenda
    Route::get('{prendaPedidoId}/movimientos', [PrendaEntregaController::class, 'obtenerMovimientos'])
        ->name('movimientos');
});
