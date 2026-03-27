<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS PARA ESTADOS DE PEDIDOS
// ========================================
// Gestión de estados y lógica de negocio de pedidos

Route::middleware(['auth', 'verified'])->name('pedidos.estado.')->group(function () {
    // Supervisor de Pedidos: Aprobar pedido
    Route::post('/pedidos/{pedido}/aprobar-supervisor', [App\Infrastructure\Http\Controllers\Legacy\PedidoEstadoController::class, 'aprobarSupervisor'])
        ->name('aprobar-supervisor');
    
    // Ver historial de cambios
    Route::get('/pedidos/{pedido}/historial', [App\Infrastructure\Http\Controllers\Legacy\PedidoEstadoController::class, 'historial'])
        ->name('historial');
    
    // Ver seguimiento de pedido
    Route::get('/pedidos/{pedido}/seguimiento', [App\Infrastructure\Http\Controllers\Legacy\PedidoEstadoController::class, 'seguimiento'])
        ->name('seguimiento');
});
