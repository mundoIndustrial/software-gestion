<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\PrendaController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use App\Infrastructure\Http\Controllers\Asesores\TelasColoresApiController;

/**
 * API Routes for Prendas - Lectura (Nueva Arquitectura)
 * 
 * Prefix: /api
 * Auth: Público (sin autenticación)
 * Controller: App\Infrastructure\Http\Controllers\PrendaController
 */
Route::middleware('api')->group(function () {
    Route::apiResource('prendas', PrendaController::class, ['only' => ['show', 'index']]);
    Route::get('prendas/search', [PrendaController::class, 'search'])->name('prendas.search');
    
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('{id}', [PedidoQueryController::class, 'show'])
            ->name('mostrar');
        
        Route::get('cliente/{clienteId}', [PedidoQueryController::class, 'listarPorCliente'])
            ->name('listar-por-cliente');
    });
    
    // Rutas de encargados por área
    Route::prefix('areas')->name('areas.')->group(function () {
        Route::get('{area}/encargados', [PedidoQueryController::class, 'obtenerEncargadosPorArea'])
            ->name('encargados');
    });

    // ========================================
    // TELAS Y COLORES - APIs PÚBLICAS
    // ========================================
    Route::prefix('public')->name('public.')->group(function () {
        Route::get('telas', [TelasColoresApiController::class, 'getTelas'])->name('telas');
        Route::get('colores', [TelasColoresApiController::class, 'getColores'])->name('colores');
    });
});
