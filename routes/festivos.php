<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS API PÚBLICAS - FESTIVOS
// ========================================
// Rutas públicas para festivos (sin autenticación requerida)

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/festivos', [App\Infrastructure\Http\Controllers\Personal\FestivosController::class, 'index'])
        ->name('festivos.index');
    Route::get('/festivos/detailed', [App\Infrastructure\Http\Controllers\Personal\FestivosController::class, 'detailed'])
        ->name('festivos.detailed');
    Route::get('/festivos/check', [App\Infrastructure\Http\Controllers\Personal\FestivosController::class, 'check'])
        ->name('festivos.check');
    Route::get('/festivos/range', [App\Infrastructure\Http\Controllers\Personal\FestivosController::class, 'range'])
        ->name('festivos.range');
});
