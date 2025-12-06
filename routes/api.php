<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistroOrdenDDDController;

/**
 * API Routes for DDD-based Orden management
 * 
 * Prefix: /api/v1
 * Auth: bearer token (JWT o similar)
 */
Route::middleware('api')->prefix('api/v1')->name('api.v1.')->group(function () {
    
    // Rutas de lectura (GET)
    Route::get('ordenes', [RegistroOrdenDDDController::class, 'index'])
        ->name('ordenes.index');
    
    Route::get('ordenes/{numero}', [RegistroOrdenDDDController::class, 'show'])
        ->name('ordenes.show');
    
    Route::get('ordenes/cliente/{cliente}', [RegistroOrdenDDDController::class, 'porCliente'])
        ->name('ordenes.por-cliente');
    
    Route::get('ordenes/estado/{estado}', [RegistroOrdenDDDController::class, 'porEstado'])
        ->name('ordenes.por-estado');

    // Rutas de escritura (POST, PATCH, DELETE)
    Route::post('ordenes', [RegistroOrdenDDDController::class, 'store'])
        ->name('ordenes.store');

    // Transiciones de estado
    Route::patch('ordenes/{numero}/aprobar', [RegistroOrdenDDDController::class, 'aprobar'])
        ->name('ordenes.aprobar');

    Route::patch('ordenes/{numero}/iniciar-produccion', [RegistroOrdenDDDController::class, 'iniciarProduccion'])
        ->name('ordenes.iniciar-produccion');

    Route::patch('ordenes/{numero}/completar', [RegistroOrdenDDDController::class, 'completar'])
        ->name('ordenes.completar');

    Route::delete('ordenes/{numero}', [RegistroOrdenDDDController::class, 'destroy'])
        ->name('ordenes.destroy');
});
