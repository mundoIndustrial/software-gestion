<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Lavanderia\LavanderiaController;

/**
 * Rutas del módulo de Lavandería
 * Requiere autenticación y rol de gestor-lavanderia o admin
 */

Route::middleware(['auth', 'lavanderia-access'])->prefix('gestion-lavanderia')->name('gestion-lavanderia.')->group(function () {
    
    // Ruta raíz - dashboard principal
    Route::get('/', [LavanderiaController::class, 'index'])
        ->name('index');
    
    // API: Buscar recibos
    Route::get('/api/search-recibos', [LavanderiaController::class, 'searchRecibos'])
        ->name('api.search-recibos');
    
    // API: Obtener movimientos
    Route::get('/api/movimientos', [LavanderiaController::class, 'getMovimientos'])
        ->name('api.movimientos');
    
    // API: Tallas disponibles para un recibo
    Route::get('/api/tallas-disponibles/{reciboId}', [LavanderiaController::class, 'apiTallasDisponibles'])
        ->name('api.tallas-disponibles');
    
    // API: Registrar salida
    Route::post('/api/registrar-salida', [LavanderiaController::class, 'registrarSalida'])
        ->name('api.registrar-salida');
    
    // API: Guardar firma de salida
    Route::post('/api/guardar-firma-salida', [LavanderiaController::class, 'guardarFirmaSalida'])
        ->name('api.guardar-firma-salida');
    
    // Ruta de diagnóstico
    Route::get('/diagnostico', function() {
        $user = auth()->user();
        return response()->json([
            'usuario_autenticado' => true,
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'usuario_nombre' => $user->name,
            'roles_ids_raw' => $user->roles_ids,
            'roles_ids' => $user->roles_ids ? ($user->roles_ids) : [],
            'roles_nombres' => $user->getRoleNames()->toArray(),
        ], 200);
    })->name('diagnostico');
});

Route::middleware(['auth', 'supervisor-access'])->prefix('seguimiento-lavanderia')->name('seguimiento-lavanderia.')->group(function () {
    
    Route::get('/', [LavanderiaController::class, 'seguimiento'])
        ->name('index');

    Route::get('/api/ordenes', [LavanderiaController::class, 'apiOrdenesSeguimiento'])
        ->name('api.ordenes');

    Route::get('/api/movimientos-recibo/{reciboId}', [LavanderiaController::class, 'apiMovimientosRecibo'])
        ->name('api.movimientos-recibo');

    Route::get('/api/tallas-pendientes/{reciboId}', [LavanderiaController::class, 'apiTallasPendientes'])
        ->name('api.tallas-pendientes');

    Route::get('/api/historial-movimientos', [LavanderiaController::class, 'apiHistorialMovimientos'])
        ->name('api.historial-movimientos');

    Route::get('/api/firma-movimiento/{movimientoId}', [LavanderiaController::class, 'apiFirmaMovimiento'])
        ->name('api.firma-movimiento');

    Route::get('/api/descargar-firma/{movimientoId}', [LavanderiaController::class, 'descargarFirmaMovimiento'])
        ->name('api.descargar-firma');
});
