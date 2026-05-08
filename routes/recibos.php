<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\RecibosParcialesController;
use App\Infrastructure\Http\Controllers\RecibosNovedadesController;
use App\Infrastructure\Http\Controllers\ReciboCosturaController;
use App\Infrastructure\Http\Controllers\PedidoQueryController;
use App\Infrastructure\Http\Controllers\RegistroOrdenController;
// ========================================
// RUTAS DE RECIBOS - Web & API
// ========================================

Route::middleware('auth')->group(function () {
    // Ruta pública para recibos-datos (acceso para cualquier usuario autenticado)
    Route::get('/pedidos-public/{id}/recibos-datos', [PedidoQueryController::class, 'obtenerDetalleCompleto'])
        ->where('id', '[0-9]+')
        ->name('pedidos.public.recibos-datos');

    /**
     * API Routes for Recibos de Costura (Filtered, Paginated)
     * GET /api/recibos-costura?area=costura&estado=ejecutando&page=1
     */
    Route::get('/api/recibos-costura', [RegistroOrdenController::class, 'getRecibosCosutraJSON'])
        ->name('api.recibos-costura.list');

    /**
     * API Route: Get Filter Options for Recibos de Costura
     * GET /api/recibos-costura/filter-options
     * Retorna: { estados, areas, numeros_recibo, clientes, dias_entrega }
     */
    Route::get('/api/recibos-costura/filter-options', [RegistroOrdenController::class, 'getRecibosCosutraFilterOptions'])
        ->name('api.recibos-costura.filter-options');

    /**
     * API Route: Obtener distribucion de parciales de un recibo desde la vista de recibos-costura
     * GET /api/recibos-costura/{idRecibo}/distribucion
     */
    Route::get('/api/recibos-costura/{idRecibo}/distribucion', [RegistroOrdenController::class, 'obtenerDistribucionRecibo'])
        ->where('idRecibo', '[0-9]+')
        ->name('api.recibos-costura.distribucion');

    /**
     * API Route: Obtener seguimiento de un parcial desde el modal de distribucion
     * GET /api/recibos-costura/parciales/{parcialId}/seguimiento
     */
    Route::get('/api/recibos-costura/parciales/{parcialId}/seguimiento', [RegistroOrdenController::class, 'obtenerSeguimientoParcialRecibo'])
        ->where('parcialId', '[0-9]+')
        ->name('api.recibos-costura.parciales.seguimiento');
});

/**
 * API Routes for Novedades de Recibos
 * Gestiona las novedades específicas de prendas por recibo
 */
Route::middleware(['auth'])->prefix('recibos-novedades')->name('recibos-novedades.')->group(function () {
    
    // Obtener novedades de prendas para un recibo específico
    Route::get('{pedidoId}/{numeroRecibo}', [RecibosNovedadesController::class, 'index'])
        ->name('index');
    
    // Obtener texto consolidado de novedades para mostrar en tabla
    Route::get('{pedidoId}/{numeroRecibo}/consolidado', [RecibosNovedadesController::class, 'getConsolidado'])
        ->name('consolidado');
    
    // Guardar novedades para prendas de un recibo
    Route::post('{pedidoId}/{numeroRecibo}', [RecibosNovedadesController::class, 'store'])
        ->name('store');
    
    // Cambiar área de recibo a Control Calidad
    Route::post('{pedidoId}/{numeroRecibo}/cambiar-area-control-calidad', [ReciboCosturaController::class, 'cambiarAreaControlCalidad'])
        ->name('cambiar-area-control-calidad');
    
    // Deshacer Control Calidad
    Route::delete('{pedidoId}/{prendaId}/deshacer-control-calidad', [ReciboCosturaController::class, 'deshacerControlCalidad'])
        ->name('deshacer-control-calidad');
    
    // Pasar recibo a Costura (con encargado)
    Route::post('{pedidoId}/{numeroRecibo}/pasar-a-costura', [ReciboCosturaController::class, 'pasarACostura'])
        ->name('pasar-a-costura');
    
    // Pasar recibo a Taller (con distribución de tallas)
    Route::post('{pedidoId}/{numeroRecibo}/pasar-a-taller', [ReciboCosturaController::class, 'pasarATaller'])
        ->name('pasar-a-taller');
    
    // Limpiar encargado de Costura (sin cambiar área ni eliminar proceso)
    Route::delete('{pedidoId}/{prendaId}/limpiar-encargado-costura', [ReciboCosturaController::class, 'limpiarEncargadoCostura'])
        ->name('limpiar-encargado-costura');

    // Deshacer Costura
    Route::delete('{pedidoId}/{prendaId}/deshacer-costura', [ReciboCosturaController::class, 'deshacerCostura'])
        ->name('deshacer-costura');
    
    // Actualizar una novedad existente
    Route::put('{novedadId}', [RecibosNovedadesController::class, 'update'])
        ->name('update');
    
    // Eliminar una novedad
    Route::delete('{novedadId}', [RecibosNovedadesController::class, 'destroy'])
        ->name('destroy');

    // Distribuir recibos    
    Route::post('{pedidoId}/{numeroRecibo}/distribuir-por-modulos', [ReciboCosturaController::class, 'distribuirPorModulos'])
    ->name('distribuir-por-modulos');    
});

/**
 * Routes for Recibos Parciales (Supervisor de Recibos)
 * Gestiona la creación de recibos parciales por talla
 */
Route::middleware(['auth'])->prefix('api/recibos-parciales')->name('recibos-parciales.')->group(function () {
    
    // Crear recibo parcial sin consecutivo
    Route::post('', [RecibosParcialesController::class, 'store'])
        ->name('store');
    
    // Activar recibo parcial (asignar consecutivo)
    Route::post('{reciboId}/activar', [RecibosParcialesController::class, 'activar'])
        ->name('activar');

    // Anular recibo parcial
    Route::post('{reciboId}/anular', [RecibosParcialesController::class, 'anular'])
        ->name('anular');
    
    // Obtener detalles de recibo parcial
    Route::get('{reciboId}', [RecibosParcialesController::class, 'show'])
        ->name('show');
    
    // Eliminar recibo parcial
    Route::delete('{reciboId}', [RecibosParcialesController::class, 'destroy'])
        ->name('destroy');
});
