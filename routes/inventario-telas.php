<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE INVENTARIO DE TELAS - Gestión compartida
// ========================================

Route::middleware(['auth'])->prefix('inventario-telas')->name('inventario-telas.')->group(function () {
    Route::get('/', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'index'])
        ->name('index');
    Route::post('/store', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'store'])
        ->name('store');
    Route::post('/ajustar-stock', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'ajustarStock'])
        ->name('ajustar-stock');
    Route::delete('/{id}', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'destroy'])
        ->name('destroy');
    Route::get('/historial', [App\Http\Controllers\AsesoresInventarioTelasController::class, 'historial'])
        ->name('historial');
});
