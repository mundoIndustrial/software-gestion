<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecepcionDespachoController;

/**
 * Recepción Despacho API Routes
 * Gestión de recepción de prendas en el área de despacho
 * Prefix: /api/recepcion-despacho
 * Auth: auth (sin validación de rol por ahora)
 */

Route::middleware(['web', 'auth:web'])
    ->prefix('recepcion-despacho')
    ->group(function () {
        Route::get('/items', [RecepcionDespachoController::class, 'getItems']);
        Route::get('/usuarios', [RecepcionDespachoController::class, 'getUsuarios']);
        Route::post('/{id}/confirmar', [RecepcionDespachoController::class, 'confirmarRecepcion']);
        Route::get('/{id}/novedades', [RecepcionDespachoController::class, 'getNovedades']);
        Route::post('/{id}/novedades', [RecepcionDespachoController::class, 'crearNovedad']);
        Route::put('/novedades/{novedadId}', [RecepcionDespachoController::class, 'actualizarNovedad']);
        Route::delete('/novedades/{novedadId}', [RecepcionDespachoController::class, 'eliminarNovedad']);
    });
