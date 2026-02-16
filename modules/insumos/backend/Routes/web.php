<?php

use App\Http\Controllers\Insumos\InsumosController;
use Illuminate\Support\Facades\Route;

/**
 * Rutas del Módulo Insumos
 */
Route::middleware(['auth', 'insumos-access'])
    ->prefix('insumos')
    ->name('insumos.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [InsumosController::class, 'dashboard'])
            ->name('dashboard');

        // Materiales
        Route::get('/materiales', [InsumosController::class, 'materiales'])
            ->name('materiales.index');

        Route::post('/materiales/{numeroPedido}', [InsumosController::class, 'guardarMateriales'])
            ->name('materiales.store');

        Route::post('/materiales/{numeroPedido}/guardar', [InsumosController::class, 'guardarMateriales'])
            ->name('materiales.guardar');

        Route::post('/materiales/{numeroPedido}/eliminar', [InsumosController::class, 'eliminarMaterial'])
            ->name('materiales.destroy');

        Route::post('/materiales/{numeroPedido}/cambiar-estado', [InsumosController::class, 'cambiarEstado'])
            ->name('materiales.cambiar-estado');

        Route::get('/api/materiales/{numeroPedido}', [InsumosController::class, 'obtenerMateriales'])
            ->name('api.materiales');

        Route::get('/api/filtros/{column}', [InsumosController::class, 'obtenerValoresFiltro'])
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
