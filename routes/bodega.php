<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Bodega\PedidosController;

/**
 * Rutas del módulo de Bodega
 * Requiere autenticación y rol de bodeguero
 */
// Ruta de prueba para diagnóstico (sin autenticación ni permisos)
Route::get('/pedidos-test', function() {
    $user = auth()->user();
    return response()->json([
        'mensaje' => 'Ruta de bodega funciona',
        'autenticado' => $user ? true : false,
        'usuario_actual' => $user ? $user->name : 'No autenticado',
        'usuario_id' => $user ? $user->id : null,
        'roles' => $user ? $user->getRoleNames()->toArray() : [],
        'role_ids' => $user ? $user->roles_ids : null,
        'role_id' => $user ? $user->role_id : null,
        'permisos' => $user ? $user->getAllPermissions()->pluck('name')->toArray() : [],
        'tiene_permiso_bodega' => $user ? $user->hasPermissionTo('view-bodega-pedidos') : false,
    ]);
});

// Ruta temporal para acceso sin restricciones (solo para diagnóstico)
Route::get('/pedidos-temp', [PedidosController::class, 'index']);

Route::middleware(['auth', 'role:bodeguero'])->group(function () {
    
    // Gestión de Pedidos
    Route::prefix('bodega')->name('bodega.')->group(function () {
        
        // Listar pedidos
        Route::get('/pedidos', [PedidosController::class, 'index'])
            ->name('pedidos')
            ->middleware('permission:view-bodega-pedidos');

        // Dashboard
        Route::get('/dashboard', [PedidosController::class, 'dashboard'])
            ->name('dashboard')
            ->middleware('permission:view-bodega-dashboard');

        // Acciones AJAX
        Route::post('/pedidos/{id}/entregar', [PedidosController::class, 'entregar'])
            ->name('entregar')
            ->middleware('permission:marcar-entregado');

        Route::post('/pedidos/observaciones', [PedidosController::class, 'actualizarObservaciones'])
            ->name('actualizar-observaciones')
            ->middleware('permission:editar-observaciones');

        Route::post('/pedidos/fecha', [PedidosController::class, 'actualizarFecha'])
            ->name('actualizar-fecha')
            ->middleware('permission:editar-fecha-entrega');

        // Exportar (opcional)
        Route::get('/pedidos/export', [PedidosController::class, 'export'])
            ->name('export')
            ->middleware('permission:export-bodega');
    });
});

// Ruta alternativa si prefieres acceso público (para testing)
// Descomenta solo en desarrollo
/*
Route::get('/bodega/pedidos', [PedidosController::class, 'index'])->name('bodega.pedidos');
Route::post('/bodega/pedidos/{id}/entregar', [PedidosController::class, 'entregar'])->name('bodega.entregar');
Route::post('/bodega/pedidos/observaciones', [PedidosController::class, 'actualizarObservaciones'])->name('bodega.actualizar-observaciones');
Route::post('/bodega/pedidos/fecha', [PedidosController::class, 'actualizarFecha'])->name('bodega.actualizar-fecha');
*/
