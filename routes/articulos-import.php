<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas para importación de artículos/EPP
 */
Route::prefix('articulos')->middleware(['api'])->group(function () {
    Route::post('guardar', [\App\Http\Controllers\Api_temp\ArticulosImportController::class, 'guardarArticulos'])
        ->name('articulos.guardar');
    Route::get('/', [\App\Http\Controllers\Api_temp\ArticulosImportController::class, 'listar'])
        ->name('articulos.index');
    Route::get('{id}', [\App\Http\Controllers\Api_temp\ArticulosImportController::class, 'obtener'])
        ->name('articulos.show');
});
