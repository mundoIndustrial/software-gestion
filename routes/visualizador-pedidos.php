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
    
    // API para obtener datos de pedidos (usa el mismo endpoint del visualizador-logo)
    Route::get('/data', [VisualizadorLogoController::class, 'pedidosVisualizacionData'])->name('data');

    // API para marcar un pedido como revisado
    Route::post('/marcar-revisado', [VisualizadorPedidosController::class, 'marcarRevisado'])->name('marcar-revisado');
});
