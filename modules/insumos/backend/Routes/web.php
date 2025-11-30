<?php

use Modules\Insumos\Backend\Controllers\MaterialesController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas del Módulo Insumos
 */
Route::middleware(['auth', 'insumos-access'])
    ->prefix('insumos')
    ->name('insumos.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [MaterialesController::class, 'dashboard'])
            ->name('dashboard');

        // Materiales
        Route::get('/materiales', [MaterialesController::class, 'index'])
            ->name('materiales.index');

        Route::post('/materiales/{numeroPedido}', [MaterialesController::class, 'store'])
            ->name('materiales.store');

        Route::post('/materiales/{numeroPedido}/eliminar', [MaterialesController::class, 'destroy'])
            ->name('materiales.destroy');

        Route::get('/api/materiales/{numeroPedido}', [MaterialesController::class, 'show'])
            ->name('api.materiales');

        Route::get('/api/filtros/{column}', [MaterialesController::class, 'obtenerFiltros'])
            ->name('api.filtros');

        // Testing
        Route::get('/test', function () {
            return view('insumos::test');
        })->name('test');

        // Metrajes
        Route::get('/metrajes', function () {
            return view('insumos::metrajes.index');
        })->name('metrajes.index');
    });
