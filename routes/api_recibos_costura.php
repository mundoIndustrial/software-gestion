<?php

/**
 * Rutas API para Recibos de Costura
 * 
 * Archivo: routes/api.php
 * 
 * Agregar esto en el archivo routes/api.php dentro de los middlewares de auth:
 * 
 * Route::prefix('recibos-costura')->middleware(['auth:sanctum', 'verified'])->group(function () {
 *     // Obtener lista de recibos con filtros
 *     Route::get('/', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'index']);
 *     
 *     // Obtener recibo individual
 *     Route::get('{reciboId}', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'show']);
 *     
 *     // Obtener opciones de filtro dinámicas
 *     Route::get('filtros/opciones', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'obtenerOpciones']);
 *     
 *     // Buscar recibos en tiempo real
 *     Route::get('buscar', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'buscar']);
 *     
 *     // Procesos
 *     Route::prefix('{reciboId}/procesos')->group(function () {
 *         // Obtener procesos del recibo
 *         Route::get('/', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'obtenerProcesos']);
 *         
 *         // Crear o actualizar proceso
 *         Route::post('/', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'agregarProceso']);
 *         
 *         // Marcar proceso como completado
 *         Route::post('{procesoId}/completar', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'marcarCompletado']);
 *     });
 *     
 *     // Obtener encargados y áreas
 *     Route::get('procesos/encargados', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'obtenerEncargados']);
 *     Route::get('procesos/areas', [\App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController::class, 'obtenerAreas']);
 * });
 */

// Importaciones necesarias
use App\Infrastructure\Recibos\Controllers\Api\RecibosCozturaApiController;

// Rutas API para Recibos de Costura
Route::prefix('recibos-costura')
    ->middleware(['auth:sanctum', 'verified'])
    ->name('api.recibos-costura.')
    ->group(function () {
        // Listado, búsqueda y opciones
        Route::get('/', [RecibosCozturaApiController::class, 'index'])->name('index');
        Route::get('{reciboId}', [RecibosCozturaApiController::class, 'show'])->name('show');
        Route::get('filtros/opciones', [RecibosCozturaApiController::class, 'obtenerOpciones'])->name('filter-options');
        Route::get('buscar', [RecibosCozturaApiController::class, 'buscar'])->name('search');

        // Procesos
        Route::prefix('{reciboId}/procesos')->name('procesos.')->group(function () {
            Route::get('/', [RecibosCozturaApiController::class, 'obtenerProcesos'])->name('index');
            Route::post('/', [RecibosCozturaApiController::class, 'agregarProceso'])->name('store');
            Route::post('{procesoId}/completar', [RecibosCozturaApiController::class, 'marcarCompletado'])->name('completar');
        });

        // Datos auxiliares
        Route::get('procesos/encargados', [RecibosCozturaApiController::class, 'obtenerEncargados'])->name('procesos.encargados');
        Route::get('procesos/areas', [RecibosCozturaApiController::class, 'obtenerAreas'])->name('procesos.areas');
    });
