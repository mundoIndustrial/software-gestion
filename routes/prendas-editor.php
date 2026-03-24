<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\PrendaEditorController;

/**
 * API Routes for Prenda Editor (DDD Architecture)
 * 
 * Prefix: /api/prendas
 * Auth: middleware('auth')
 * Controller: App\Infrastructure\Http\Controllers\PrendaEditorController
 * 
 * Implementa DDD con separación de responsabilidades:
 * - Domain: ValueObjects, Entities, Services
 * - Application: DTOs, Services, Handlers
 * - Infrastructure: Controllers, Repositories
 */
Route::prefix('prendas')->name('prendas.')->middleware(['auth'])->group(function () {
    
    // Edición de prendas
    Route::get('{id}/editar', [PrendaEditorController::class, 'editar'])
        ->name('editar');
    
    // Preparar datos para guardar
    Route::post('preparar-guardar', [PrendaEditorController::class, 'prepararGuardar'])
        ->name('preparar-guardar');
    
    // Validación de prendas
    Route::post('validar', [PrendaEditorController::class, 'validar'])
        ->name('validar');
    
    // Tipos de manga disponibles
    Route::get('tipos-manga', [PrendaEditorController::class, 'tiposManga'])
        ->name('tipos-manga');
    
    // Procesamiento de tallas con DDD
    Route::post('{id}/procesar-tallas', [PrendaEditorController::class, 'procesarTallas'])
        ->name('procesar-tallas');
    
    // Procesamiento de variaciones con DDD
    Route::post('{id}/procesar-variaciones', [PrendaEditorController::class, 'procesarVariaciones'])
        ->name('procesar-variaciones');
    
    // Procesamiento de procesos con DDD
    Route::post('{id}/procesar-procesos', [PrendaEditorController::class, 'procesarProcesos'])
        ->name('procesar-procesos');
});
