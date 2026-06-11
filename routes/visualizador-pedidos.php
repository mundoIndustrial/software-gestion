<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\VisualizadorPedidos\VisualizadorPedidosController;
use App\Infrastructure\Http\Controllers\VisualizadorLogo\VisualizadorLogoController;

// ========================================
// RUTAS PARA VISUALIZADOR DE PEDIDOS
// ========================================
Route::middleware(['auth', 'role:visualizador-pedidos,admin'])->prefix('visualizador-pedidos')->name('visualizador-pedidos.')->group(function () {
    // Dashboard (ruta principal - tabla de pedidos)
    Route::get('/', [VisualizadorPedidosController::class, 'dashboard'])->name('index');
    
    Route::get('/data', [VisualizadorPedidosController::class, 'getVisualizadorPedidosData'])->name('data');

    // API para marcar un pedido como revisado
    Route::post('/marcar-revisado', [VisualizadorPedidosController::class, 'marcarRevisado'])->name('marcar-revisado');
});
