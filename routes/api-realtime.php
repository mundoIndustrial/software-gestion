<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Asesores\AsesoresController;

/**
 * API Routes para Sistema de Tiempo Real
 * 
 * Estas rutas son específicas para el sistema de actualizaciones en tiempo real
 * y permiten acceso a cualquier usuario autenticado
 */

Route::middleware(['auth'])->group(function () {
    // API para listar pedidos en tiempo real (solo datos JSON)
    Route::get('/realtime/pedidos', [AsesoresController::class, 'apiListar'])->name('realtime.pedidos.listar');
    
    // API para cartera en tiempo real
    Route::get('/realtime/cartera-pedidos', function () {
        // Implementación temporal para cartera
        $pedidos = \App\Models\PedidoProduccion::where('estado', 'pendiente_cartera')
            ->select('id', 'numero_pedido', 'cliente', 'estado', 'area', 'novedades', 'forma_pago', 'fecha_creacion', 'fecha_estimada')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $pedidos->toArray()
        ]);
    })->name('realtime.cartera.pedidos');
});
