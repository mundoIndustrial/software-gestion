<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE INVENTARIO DE TELAS - Gestión compartida
// ========================================

Route::middleware(['auth'])->prefix('inventario-telas')->name('inventario-telas.')->group(function () {
    Route::get('/', [App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController::class, 'index'])
        ->name('index');
    Route::post('/store', [App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController::class, 'store'])
        ->name('store');
    Route::post('/ajustar-stock', [App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController::class, 'ajustarStock'])
        ->name('ajustar-stock');
    Route::get('/historial', [App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController::class, 'historial'])
        ->name('historial');
    Route::get('/{id}', [App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController::class, 'show'])
        ->name('show');
    Route::delete('/{id}', [App\Infrastructure\Http\Controllers\Asesores\AsesoresInventarioTelasController::class, 'destroy'])
        ->name('destroy');
});
