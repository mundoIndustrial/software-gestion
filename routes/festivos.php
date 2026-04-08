<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Personal\FestivosController;

// ========================================
// RUTAS API PÚBLICAS - FESTIVOS
// ========================================
// Rutas públicas para festivos (sin autenticación requerida)

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/festivos', [FestivosController::class, 'index'])
        ->name('festivos.index');
    Route::get('/festivos/detailed', [FestivosController::class, 'detailed'])
        ->name('festivos.detailed');
    Route::get('/festivos/check', [FestivosController::class, 'check'])
        ->name('festivos.check');
    Route::get('/festivos/range', [FestivosController::class, 'range'])
        ->name('festivos.range');
});

// ========================================
// RUTAS API ADMIN - SINCRONIZACIÓN DE FESTIVOS
// ========================================
// Rutas para sincronizar festivos desde la API de Nager.Date

Route::prefix('api')->name('api.')->group(function () {
    // Sincronizar festivos de un año específico
    Route::post('/festivos/sincronizar/{year}', [FestivosController::class, 'sincronizarFestivos'])
        ->name('festivos.sincronizar')
        ->middleware('auth');
    
    // Sincronizar múltiples años a la vez
    Route::post('/festivos/sincronizar-rango', [FestivosController::class, 'sincronizarRango'])
        ->name('festivos.sincronizar-rango')
        ->middleware('auth');
});


