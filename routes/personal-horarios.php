<?php

use Illuminate\Support\Facades\Route;

/**
 * API Routes for Personal (Gestión de Roles)
 */
Route::prefix('personal')->name('personal.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Infrastructure\Http\Controllers\Personal\PersonalController::class, 'list'])
        ->name('list');
    
    Route::put('{id}/rol', [\App\Infrastructure\Http\Controllers\Personal\PersonalController::class, 'updateRol'])
        ->name('update-rol');
});

/**
 * API Routes for Horarios (Gestión de Horarios por Roles)
 */
Route::prefix('horarios')->name('horarios.')->middleware(['api'])->group(function () {
    Route::get('list', [\App\Infrastructure\Http\Controllers\Personal\HorarioController::class, 'list'])
        ->name('list');
    
    Route::get('roles-disponibles', [\App\Infrastructure\Http\Controllers\Personal\HorarioController::class, 'rolesDisponibles'])
        ->name('roles-disponibles');
    
    Route::put('{id}', [\App\Infrastructure\Http\Controllers\Personal\HorarioController::class, 'update'])
        ->name('update');
    
    Route::post('/', [\App\Infrastructure\Http\Controllers\Personal\HorarioController::class, 'store'])
        ->name('store');
});
