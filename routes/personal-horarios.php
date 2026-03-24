<?php

use Illuminate\Support\Facades\Route;

/**
 * API Routes for Personal (Gestión de Roles)
 */
Route::prefix('personal')->name('personal.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Http\Controllers\Api_temp\PersonalController::class, 'list'])
        ->name('list');
    
    Route::put('{id}/rol', [\App\Http\Controllers\Api_temp\PersonalController::class, 'updateRol'])
        ->name('update-rol');
});

/**
 * API Routes for Horarios (Gestión de Horarios por Roles)
 */
Route::prefix('horarios')->name('horarios.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Http\Controllers\Api_temp\HorarioController::class, 'list'])
        ->name('list');
    
    Route::get('roles-disponibles', [\App\Http\Controllers\Api_temp\HorarioController::class, 'rolesDisponibles'])
        ->name('roles-disponibles');
    
    Route::put('{id}', [\App\Http\Controllers\Api_temp\HorarioController::class, 'update'])
        ->name('update');
    
    Route::post('/', [\App\Http\Controllers\Api_temp\HorarioController::class, 'store'])
        ->name('store');
});
