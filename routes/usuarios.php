<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioRolController;

/**
 * API Routes for Usuarios por Rol
 * Obtiene usuarios filtrados por rol específico
 */
Route::withoutMiddleware(['api'])
    ->middleware(['web', 'auth'])
    ->prefix('usuarios')
    ->name('usuarios.')
    ->group(function () {
    
    // Obtener usuarios con rol 'costura'
    Route::get('costura', [UsuarioRolController::class, 'getUsuariosCostura'])
        ->name('costura');
    
    // Obtener usuarios por área
    Route::get('por-area', [UsuarioRolController::class, 'getUsuariosPorArea'])
        ->name('por-area');
});
