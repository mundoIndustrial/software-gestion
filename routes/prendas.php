<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE PRENDAS - Catálogos y reconocimiento
// ========================================

// API Routes para Prendas (Reconocimiento)
Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/tipos-prenda', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'tiposPrenda'])
        ->name('tipos-prenda');
    Route::post('/prenda/reconocer', [App\Infrastructure\Http\Controllers\CatalogoController::class, 'reconocerPrenda'])
        ->name('prenda.reconocer');
});

// Rutas para variaciones de prendas
Route::middleware('auth')->get('/prenda-variaciones/{tipoPrendaId}', function($tipoPrendaId) {
    // Por ahora retornar vacío ya que el sistema maneja las variaciones automáticamente
    // El frontend espera null cuando no hay variaciones predefinidas
    return response()->json(null);
})->name('prenda-variaciones');
