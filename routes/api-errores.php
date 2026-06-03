<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ErrorLogController;

/**
 * API Routes for Error Logging System
 * Prefix: /api/errores
 * Auth: auth:sanctum
 * Controller: App\Http\Controllers\Api\ErrorLogController
 */

Route::middleware('auth:web')->prefix('errores')->group(function () {
    // Registrar un error desde el cliente
    Route::post('/registrar', [ErrorLogController::class, 'registrar'])
        ->name('api.errores.registrar');

    // Obtener estadísticas de errores
    Route::get('/estadisticas', [ErrorLogController::class, 'estadisticas'])
        ->name('api.errores.estadisticas');
});
