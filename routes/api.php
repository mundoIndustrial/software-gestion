<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrdenController;
use App\Http\Controllers\Api\V1\OrdenBodegaController;

/**
 * API Routes for DDD-based Orden management (FASE 3 - DDD)
 * 
 * Prefix: /api/v1
 * Auth: bearer token (JWT o similar)
 * Controller: App\Http\Controllers\Api\V1\OrdenController (Órdenes)
 * Controller: App\Http\Controllers\Api\V1\OrdenBodegaController (Bodega)
 * 
 * Cumple: SOLID (SRP), DDD (Pure Domain Layer)
 */
Route::middleware('api')->prefix('api/v1')->name('api.v1.')->group(function () {
    
    // ========================================
    // RUTAS DE ÓRDENES (Ordenes)
    // ========================================
    
    // Rutas de lectura (GET)
    Route::get('ordenes', [OrdenController::class, 'index'])
        ->name('ordenes.index');
    
    Route::get('ordenes/{numero}', [OrdenController::class, 'show'])
        ->name('ordenes.show');
    
    Route::get('ordenes/cliente/{cliente}', [OrdenController::class, 'porCliente'])
        ->name('ordenes.por-cliente');
    
    Route::get('ordenes/estado/{estado}', [OrdenController::class, 'porEstado'])
        ->name('ordenes.por-estado');

    // Rutas de escritura (POST, PATCH, DELETE)
    Route::post('ordenes', [OrdenController::class, 'store'])
        ->name('ordenes.store');

    // Transiciones de estado
    Route::patch('ordenes/{numero}/aprobar', [OrdenController::class, 'aprobar'])
        ->name('ordenes.aprobar');

    Route::patch('ordenes/{numero}/iniciar-produccion', [OrdenController::class, 'iniciarProduccion'])
        ->name('ordenes.iniciar-produccion');

    Route::patch('ordenes/{numero}/completar', [OrdenController::class, 'completar'])
        ->name('ordenes.completar');

    Route::delete('ordenes/{numero}', [OrdenController::class, 'destroy'])
        ->name('ordenes.destroy');

    // ========================================
    // RUTAS DE BODEGA (Bodega)
    // ========================================
    
    // Rutas de lectura (GET)
    Route::get('bodega', [OrdenBodegaController::class, 'index'])
        ->name('bodega.index');
    
    Route::get('bodega/{numero}', [OrdenBodegaController::class, 'show'])
        ->name('bodega.show');
    
    Route::get('bodega/cliente/{cliente}', [OrdenBodegaController::class, 'porCliente'])
        ->name('bodega.por-cliente');
    
    Route::get('bodega/estado/{estado}', [OrdenBodegaController::class, 'porEstado'])
        ->name('bodega.por-estado');

    // Rutas de escritura (POST, PATCH, DELETE)
    Route::post('bodega', [OrdenBodegaController::class, 'store'])
        ->name('bodega.store');

    // Transiciones de estado
    Route::patch('bodega/{numero}/iniciar-produccion', [OrdenBodegaController::class, 'iniciarProduccion'])
        ->name('bodega.iniciar-produccion');

    Route::patch('bodega/{numero}/completar', [OrdenBodegaController::class, 'completar'])
        ->name('bodega.completar');

    Route::delete('bodega/{numero}', [OrdenBodegaController::class, 'destroy'])
        ->name('bodega.destroy');
});
