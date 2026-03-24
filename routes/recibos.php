<?php

use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE RECIBOS - Web & API
// ========================================

Route::middleware('auth')->group(function () {
    // Ruta pública para recibos-datos (acceso para cualquier usuario autenticado)
    Route::get('/pedidos-public/{id}/recibos-datos', [App\Infrastructure\Http\Controllers\PedidoQueryController::class, 'obtenerDetalleCompleto'])
        ->where('id', '[0-9]+')
        ->name('pedidos.public.recibos-datos');
});

/**
 * API Routes for Novedades de Recibos
 * Gestiona las novedades específicas de prendas por recibo
 */
Route::middleware(['auth'])->prefix('recibos-novedades')->name('recibos-novedades.')->group(function () {
    
    // Obtener novedades de prendas para un recibo específico
    Route::get('{pedidoId}/{numeroRecibo}', [App\Http\Controllers\Api_temp\RecibosNovedadesController::class, 'index'])
        ->name('index');
    
    // Obtener texto consolidado de novedades para mostrar en tabla
    Route::get('{pedidoId}/{numeroRecibo}/consolidado', [App\Http\Controllers\Api_temp\RecibosNovedadesController::class, 'getConsolidado'])
        ->name('consolidado');
    
    // Guardar novedades para prendas de un recibo
    Route::post('{pedidoId}/{numeroRecibo}', [App\Http\Controllers\Api_temp\RecibosNovedadesController::class, 'store'])
        ->name('store');
    
    // Cambiar área de recibo a Control Calidad
    Route::post('{pedidoId}/{numeroRecibo}/cambiar-area-control-calidad', [App\Infrastructure\Http\Controllers\ReciboCosturaController::class, 'cambiarAreaControlCalidad'])
        ->name('cambiar-area-control-calidad');
    
    // Deshacer Control Calidad
    Route::delete('{pedidoId}/{prendaId}/deshacer-control-calidad', [App\Infrastructure\Http\Controllers\ReciboCosturaController::class, 'deshacerControlCalidad'])
        ->name('deshacer-control-calidad');
    
    // Pasar recibo a Costura (con encargado)
    Route::post('{pedidoId}/{numeroRecibo}/pasar-a-costura', [App\Infrastructure\Http\Controllers\ReciboCosturaController::class, 'pasarACostura'])
        ->name('pasar-a-costura');
    
    // Limpiar encargado de Costura (sin cambiar área ni eliminar proceso)
    Route::delete('{pedidoId}/{prendaId}/limpiar-encargado-costura', [App\Infrastructure\Http\Controllers\ReciboCosturaController::class, 'limpiarEncargadoCostura'])
        ->name('limpiar-encargado-costura');

    // Deshacer Costura
    Route::delete('{pedidoId}/{prendaId}/deshacer-costura', [App\Infrastructure\Http\Controllers\ReciboCosturaController::class, 'deshacerCostura'])
        ->name('deshacer-costura');
    
    // Actualizar una novedad existente
    Route::put('{novedadId}', [App\Http\Controllers\Api_temp\RecibosNovedadesController::class, 'update'])
        ->name('update');
    
    // Eliminar una novedad
    Route::delete('{novedadId}', [App\Http\Controllers\Api_temp\RecibosNovedadesController::class, 'destroy'])
        ->name('destroy');
});

/**
 * Routes for Recibos Parciales (Supervisor de Recibos)
 * Gestiona la creación de recibos parciales por talla
 */
Route::middleware(['auth'])->prefix('api/recibos-parciales')->name('recibos-parciales.')->group(function () {
    
    // Crear recibo parcial sin consecutivo
    Route::post('', [App\Infrastructure\Http\Controllers\RecibosParcialesController::class, 'store'])
        ->name('store');
    
    // Activar recibo parcial (asignar consecutivo)
    Route::post('{reciboId}/activar', [App\Infrastructure\Http\Controllers\RecibosParcialesController::class, 'activar'])
        ->name('activar');

    // Anular recibo parcial
    Route::post('{reciboId}/anular', [App\Infrastructure\Http\Controllers\RecibosParcialesController::class, 'anular'])
        ->name('anular');
    
    // Obtener detalles de recibo parcial
    Route::get('{reciboId}', [App\Infrastructure\Http\Controllers\RecibosParcialesController::class, 'show'])
        ->name('show');
    
    // Eliminar recibo parcial
    Route::delete('{reciboId}', [App\Infrastructure\Http\Controllers\RecibosParcialesController::class, 'destroy'])
        ->name('destroy');
});
