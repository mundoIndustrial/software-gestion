<?php

use App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController;
use App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'role:asesor,admin,supervisor_pedidos'])->group(function () {
    Route::prefix('pedidos-editable')->name('pedidos-editable.')->group(function () {
        // Gestión de ítems
        Route::post('/items/agregar', [CrearPedidoEditableController::class, 'agregarItem'])->name('agregar-item');
        Route::post('/items/eliminar', [CrearPedidoEditableController::class, 'eliminarItem'])->name('eliminar-item');
        Route::get('/items', [CrearPedidoEditableController::class, 'obtenerItems'])->name('obtener-items');
        
        // Validación y creación
        Route::post('/validar', [CrearPedidoEditableController::class, 'validarPedido'])->name('validar');
        Route::post('/crear', [CrearPedidoEditableController::class, 'crearPedido'])->name('crear');
        
        // Subir imágenes (FormData)
        Route::post('/subir-imagenes', [CrearPedidoEditableController::class, 'subirImagenesPrenda'])->name('subir-imagenes');
        
        // Renderizar componente item-card
        Route::post('/render-item-card', [PedidosProduccionController::class, 'renderItemCard'])->name('render-item-card');
    });
});
