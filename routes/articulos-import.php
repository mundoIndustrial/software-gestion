<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas para importación de artículos/EPP
 */
Route::prefix('articulos')->middleware(['api'])->group(function () {
    Route::post('guardar', [\App\Infrastructure\Http\Controllers\Epp\ArticulosImportController::class, 'guardarArticulos'])
        ->name('articulos.guardar');
    Route::get('/', [\App\Infrastructure\Http\Controllers\Epp\ArticulosImportController::class, 'listar'])
        ->name('articulos.index');
    Route::get('{id}', [\App\Infrastructure\Http\Controllers\Epp\ArticulosImportController::class, 'obtener'])
        ->name('articulos.show');
});
