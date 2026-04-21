<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecepcionDespachoController;

/**
 * Recepción Despacho Routes (Web)
 * Gestión de recepción de prendas en el área de despacho
 * Prefix: /recepcion-despacho
 * Middleware: auth, role:recepcion_despacho
 */

Route::middleware(['auth', 'role:recepcion_despacho'])
    ->prefix('recepcion-despacho')
    ->name('recepcion-despacho.')
    ->group(function () {
        Route::get('/', [RecepcionDespachoController::class, 'index'])->name('index');
    });
