<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrdenController;
use App\Http\Controllers\Api\AsistenciaPersonalController;
use App\Http\Controllers\PrendaController;
use App\Infrastructure\Http\Controllers\CotizacionPrendaController;

/**
 * API Routes for DDD-based Orden management (FASE 3 - DDD)
 * 
 * Prefix: /api/v1
 * Auth: bearer token (JWT o similar)
 * Controller: App\Http\Controllers\Api\V1\OrdenController
 * 
 * Cumple: SOLID (SRP), DDD (Pure Domain Layer)
 */
Route::middleware('api')->prefix('api/v1')->name('api.v1.')->group(function () {
    
    // Rutas de lectura (GET)
    Route::get('ordenes', [OrdenController::class, 'index'])
        ->name('ordenes.index');
    
    Route::get('ordenes/{numero}', [OrdenController::class, 'show'])
        ->name('ordenes.show');
    
    Route::get('ordenes/cliente/{cliente}', [OrdenController::class, 'porCliente'])
        ->name('ordenes.por-cliente');
    
    Route::get('ordenes/estado/{estado}', [OrdenController::class, 'porEstado'])
        ->name('ordenes.por-estado');

    // Rutas de escritura (POST, PATCH, DELETE)
    Route::post('ordenes', [OrdenController::class, 'store'])
        ->name('ordenes.store');

    // Transiciones de estado
    Route::patch('ordenes/{numero}/aprobar', [OrdenController::class, 'aprobar'])
        ->name('ordenes.aprobar');

    Route::patch('ordenes/{numero}/iniciar-produccion', [OrdenController::class, 'iniciarProduccion'])
        ->name('ordenes.iniciar-produccion');

    Route::patch('ordenes/{numero}/completar', [OrdenController::class, 'completar'])
        ->name('ordenes.completar');

    Route::delete('ordenes/{numero}', [OrdenController::class, 'destroy'])
        ->name('ordenes.destroy');
});

/**
 * API Routes for Prendas (Nueva Arquitectura)
 * 
 * Prefix: /api
 * Auth: bearer token
 * Controller: App\Http\Controllers\PrendaController
 */
Route::middleware('api')->prefix('api')->name('api.')->group(function () {
    // Rutas de prendas
    Route::apiResource('prendas', PrendaController::class);
    Route::get('prendas/search', [PrendaController::class, 'search'])->name('prendas.search');
    
    // Rutas de cotizaciones
    Route::apiResource('cotizaciones', CotizacionPrendaController::class);
});

/**
 * API Routes for Pedidos Editables (DDD - Gestión de Ítems)
 * 
 * Prefix: /api/pedidos-editable
 * Auth: auth, role:asesor
 * Controller: App\Http\Controllers\Asesores\CrearPedidoEditableController
 */
require base_path('routes/api-pedidos-editable.php');

/**
 * API Routes for Operario (PUBLIC - Sin autenticación)
 */
Route::prefix('operario')->name('operario.')->middleware([])->group(function () {
    Route::get('pedido/{numeroPedido}', [\App\Infrastructure\Http\Controllers\Operario\OperarioController::class, 'getPedidoData'])
        ->name('pedido-data');
});

/**
 * API Routes for Asistencia Personal
 */
Route::prefix('asistencia-personal')->name('asistencia-personal.')->middleware(['web'])->group(function () {
    Route::post('/procesar-pdf', [AsistenciaPersonalController::class, 'procesarPDF'])
        ->name('procesar-pdf');
    Route::post('/validar-registros', [AsistenciaPersonalController::class, 'validarRegistros'])
        ->name('validar-registros');
    Route::post('/guardar-registros', [AsistenciaPersonalController::class, 'guardarRegistros'])
        ->name('guardar-registros');
    Route::get('/reportes/{id}/detalles', [AsistenciaPersonalController::class, 'getReportDetails'])
        ->name('reportes.detalles');
    Route::get('/reportes/{id}/ausencias', [AsistenciaPersonalController::class, 'getAbsenciasDelDia'])
        ->name('reportes.ausencias');
});
