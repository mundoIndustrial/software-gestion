<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS API PÚBLICAS - FESTIVOS
// ========================================
// Rutas públicas para festivos (sin autenticación requerida)

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/festivos', [App\Http\Controllers\API\FestivosController::class, 'index'])
        ->name('festivos.index');
    Route::get('/festivos/detailed', [App\Http\Controllers\API\FestivosController::class, 'detailed'])
        ->name('festivos.detailed');
    Route::get('/festivos/check', [App\Http\Controllers\API\FestivosController::class, 'check'])
        ->name('festivos.check');
    Route::get('/festivos/range', [App\Http\Controllers\API\FestivosController::class, 'range'])
        ->name('festivos.range');
});
