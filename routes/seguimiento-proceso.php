<?php

use Illuminate\Support\Facades\Route;

// ========================================
// Web Routes for Proceso Seguimiento (Seguimiento por Áreas)
// ========================================
// Gestiona el seguimiento de producción por áreas (Corte, Costura, Bordado, etc.)

Route::prefix('seguimiento-proceso')->name('seguimiento-proceso.')->group(function () {
    
    // Guardar un nuevo proceso de seguimiento
    Route::post('/guardar', [App\Infrastructure\Http\Controllers\ProcesoSeguimientoController::class, 'guardar'])
        ->name('guardar');
    
    // Obtener procesos de una prenda
    Route::get('/prenda/{prendaId}', [App\Infrastructure\Http\Controllers\ProcesoSeguimientoController::class, 'obtenerPorPrenda'])
        ->name('obtener-por-prenda');
    
    // Actualizar estado de un proceso
    Route::put('/{procesoId}/estado', [App\Infrastructure\Http\Controllers\ProcesoSeguimientoController::class, 'actualizarEstado'])
        ->name('actualizar-estado');
    
    // Actualizar un proceso completo
    Route::put('/{procesoId}', [App\Infrastructure\Http\Controllers\ProcesoSeguimientoController::class, 'actualizar'])
        ->name('actualizar');
    
    // Eliminar un proceso
    Route::delete('/{procesoId}', [App\Infrastructure\Http\Controllers\ProcesoSeguimientoController::class, 'eliminar'])
        ->name('eliminar');
});
