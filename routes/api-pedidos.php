<?php

use App\Infrastructure\Http\Controllers\Asesores\PrendasPedidoController;
use App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'role:asesor,admin,supervisor_pedidos'])->group(function () {
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        // Validación y creación
        Route::post('/crear', [CrearPedidoController::class, 'crearPedido'])->name('crear');

        // Verificar si pedido ya fue creado (para evitar doble-creación en caso de error CSRF)
        Route::get('/verificar-ya-creado', [CrearPedidoController::class, 'verificarPedidoYaCreado'])->name('verificar-ya-creado');

        // Renderizar componente item-card
        Route::post('/render-item-card', [PrendasPedidoController::class, 'renderItemCard'])->name('render-item-card');
    });
});
