<?php

use App\Http\Controllers\Asesores\CrearPedidoEditableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:asesor'])->group(function () {
    Route::prefix('pedidos-editable')->name('pedidos-editable.')->group(function () {
        // Gestión de ítems
        Route::post('/items/agregar', [CrearPedidoEditableController::class, 'agregarItem'])->name('agregar-item');
        Route::post('/items/eliminar', [CrearPedidoEditableController::class, 'eliminarItem'])->name('eliminar-item');
        Route::get('/items', [CrearPedidoEditableController::class, 'obtenerItems'])->name('obtener-items');
        
        // Validación y creación
        Route::post('/validar', [CrearPedidoEditableController::class, 'validarPedido'])->name('validar');
        Route::post('/crear', [CrearPedidoEditableController::class, 'crearPedido'])->name('crear');
    });
});
