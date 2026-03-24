<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\ColoresPorTallaController;

/**
 * API Routes for ColoresPorTalla System
 * Gestiona asignaciones de colores a tallas de prendas
 */
Route::prefix('colores-por-talla')->name('colores-por-talla.')->middleware(['auth'])->group(function () {
    
    // Obtener asignaciones existentes
    Route::get('asignaciones', [ColoresPorTallaController::class, 'index'])
        ->name('asignaciones.index');
    
    // Guardar asignación de colores
    Route::post('asignaciones', [ColoresPorTallaController::class, 'store'])
        ->name('asignaciones.store');
    
    // Actualizar asignación específica
    Route::patch('asignaciones/{id}', [ColoresPorTallaController::class, 'update'])
        ->name('asignaciones.update');
    
    // Eliminar asignación
    Route::delete('asignaciones/{id}', [ColoresPorTallaController::class, 'destroy'])
        ->name('asignaciones.destroy');
    
    // Obtener colores disponibles para talla
    Route::get('colores-disponibles/{genero}/{talla}', [ColoresPorTallaController::class, 'coloresDisponibles'])
        ->name('colores-disponibles');
    
    // Obtener tallas disponibles para género
    Route::get('tallas-disponibles/{genero}', [ColoresPorTallaController::class, 'tallasDisponibles'])
        ->name('tallas-disponibles');
    
    // Procesar asignación del wizard (múltiples tallas)
    Route::post('procesar-asignacion-wizard', [ColoresPorTallaController::class, 'procesarAsignacionWizard'])
        ->name('procesar-asignacion-wizard');
});
